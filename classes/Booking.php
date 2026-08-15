<?php
/**
 * Booking.php
 * Core business logic for creating, modifying, cancelling and
 * listing studio booking sessions. Encapsulates the assignment's
 * business rules:
 *   - Locations operate 10:00 - 22:00 daily.
 *   - Duration between 1 and 12 hours.
 *   - A booking may only be modified/cancelled before its start time.
 *   - Total cost = duration_hours * location.cost_per_hour.
 */
class Booking
{
    private PDO $db;
    private Location $locationModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->locationModel = new Location();
    }

    /**
     * Validate + create a booking for a client.
     * Returns ['success'=>bool,'errors'=>array,'booking'=>array|null]
     */
    public function create(int $clientId, int $locationId, string $date, string $startTime, $durationHours): array
    {
        $errors = $this->validateInputs($locationId, $date, $startTime, $durationHours);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'booking' => null];
        }

        $durationHours = (int)$durationHours;

        // Business-rule time checks (done BEFORE formatting end time, using raw
        // seconds-from-midnight so a duration that would wrap past midnight is
        // correctly rejected rather than silently wrapping around to 00:xx).
        $timeErrors = $this->validateBusinessHours($startTime, $durationHours);
        if (!empty($timeErrors)) {
            return ['success' => false, 'errors' => $timeErrors, 'booking' => null];
        }
        $endTime = $this->computeEndTime($startTime, $durationHours);

        $location = $this->locationModel->getById($locationId);
        if (!$location) {
            return ['success' => false, 'errors' => ["Selected location does not exist."], 'booking' => null];
        }

        // Prevent the same client from having overlapping sessions.
        // This check is performed before the studio-capacity check so the
        // client receives the more useful reason when their own booking is
        // the problem.
        if ($this->clientHasOverlappingBooking($clientId, $date, $startTime, $endTime)) {
            return [
                'success' => false,
                'errors' => [
                    "Booking overlaps with one of your existing sessions on this date. Please choose a different start time or date."
                ],
                'booking' => null
            ];
        }

        // Availability check: is there a free studio at that location/time?
        $busy = $this->locationModel->studiosBookedDuring($locationId, $date, $startTime, $endTime);

        if ($busy >= (int)$location['num_studios']) {
            return [
                'success' => false,
                'errors' => [
                    "No studio is available at this location for the selected time. Please choose another available start time or location."
                ],
                'booking' => null
            ];
        }

        $totalCost = round($durationHours * (float)$location['cost_per_hour'], 2);

        $stmt = $this->db->prepare(
            "INSERT INTO bookings (client_id, location_id, booking_date, start_time, duration_hours, end_time, total_cost, status)
             VALUES (:client_id, :location_id, :date, :start_time, :duration, :end_time, :cost, 'active')"
        );
        $stmt->execute([
            ':client_id'  => $clientId,
            ':location_id'=> $locationId,
            ':date'       => $date,
            ':start_time' => $startTime,
            ':duration'   => $durationHours,
            ':end_time'   => $endTime,
            ':cost'       => $totalCost,
        ]);

        $bookingId = (int)$this->db->lastInsertId();
        return ['success' => true, 'errors' => [], 'booking' => $this->getById($bookingId)];
    }

    /**
     * Modify an existing booking. Only allowed while the session has
     * not yet started. $actingAsAdmin bypasses the client-ownership
     * check (admin may modify on behalf of any client) but the
     * "not yet started" rule still applies to the underlying session data.
     */
    public function update(int $bookingId, int $locationId, string $date, string $startTime, $durationHours, ?int $requestingClientId = null): array
    {
        $existing = $this->getById($bookingId);
        if (!$existing) {
            return ['success' => false, 'errors' => ["Booking not found."], 'booking' => null];
        }
        if ($requestingClientId !== null && (int)$existing['client_id'] !== $requestingClientId) {
            return ['success' => false, 'errors' => ["You are not authorised to modify this booking."], 'booking' => null];
        }
        if ($existing['status'] !== 'active') {
            return ['success' => false, 'errors' => ["This booking has already been cancelled."], 'booking' => null];
        }

        // Completed sessions are locked for everyone. An administrator may
        // modify a currently running session, but cannot change a session
        // that has already ended.
        if ($requestingClientId === null && $this->hasEnded($existing)) {
            return ['success' => false, 'errors' => ["Completed sessions cannot be modified."], 'booking' => null];
        }

        // Clients can only modify a session before it starts. Administrators
        // may modify an upcoming or currently running session on behalf of a client.
        if ($requestingClientId !== null && $this->hasStarted($existing)) {
            return ['success' => false, 'errors' => ["This session has already started, so it can no longer be modified."], 'booking' => null];
        }

        $errors = $this->validateInputs($locationId, $date, $startTime, $durationHours);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'booking' => null];
        }

        $durationHours = (int)$durationHours;

        $timeErrors = $this->validateBusinessHours($startTime, $durationHours);
        if (!empty($timeErrors)) {
            return ['success' => false, 'errors' => $timeErrors, 'booking' => null];
        }
        $endTime = $this->computeEndTime($startTime, $durationHours);

        $location = $this->locationModel->getById($locationId);
        if (!$location) {
            return ['success' => false, 'errors' => ["Selected location does not exist."], 'booking' => null];
        }

        // Prevent the client from creating an overlapping booking
        // when modifying an existing session.
        if ($this->clientHasOverlappingBooking(
            (int)$existing['client_id'],
            $date,
            $startTime,
            $endTime,
            $bookingId
        )) {
            return [
                'success' => false,
                'errors' => [
                    "Booking overlaps with one of this client's existing sessions on this date. Please choose a different start time or date."
                ],
                'booking' => null
            ];
        }

        $busy = $this->locationModel->studiosBookedDuring(
            $locationId,
            $date,
            $startTime,
            $endTime,
            $bookingId
        );

        if ($busy >= (int)$location['num_studios']) {
            return [
                'success' => false,
                'errors' => [
                    "No studio is available at this location for the selected time. Please choose another available start time or location."
                ],
                'booking' => null
            ];
        }


        $totalCost = round($durationHours * (float)$location['cost_per_hour'], 2);

        $stmt = $this->db->prepare(
            "UPDATE bookings SET location_id = :location_id, booking_date = :date, start_time = :start_time,
             duration_hours = :duration, end_time = :end_time, total_cost = :cost
             WHERE booking_id = :id"
        );
        $stmt->execute([
            ':location_id' => $locationId,
            ':date'        => $date,
            ':start_time'  => $startTime,
            ':duration'    => $durationHours,
            ':end_time'    => $endTime,
            ':cost'        => $totalCost,
            ':id'          => $bookingId,
        ]);

        return ['success' => true, 'errors' => [], 'booking' => $this->getById($bookingId)];
    }

    /**
     * Cancel a booking. $requestingClientId is checked when a client
     * cancels their own booking (null when an admin is cancelling).
     */
    public function cancel(int $bookingId, ?int $requestingClientId = null): array
    {
        $existing = $this->getById($bookingId);
        if (!$existing) {
            return ['success' => false, 'errors' => ["Booking not found."]];
        }
        if ($requestingClientId !== null && (int)$existing['client_id'] !== $requestingClientId) {
            return ['success' => false, 'errors' => ["You are not authorised to cancel this booking."]];
        }
        if ($existing['status'] !== 'active') {
            return ['success' => false, 'errors' => ["This booking is already cancelled."]];
        }
        // Clients may only cancel before the session starts.
        // An administrator can cancel an upcoming or currently running
        // session on behalf of a client, but completed sessions remain locked.
        if ($requestingClientId !== null && $this->hasStarted($existing)) {
            return [
                'success' => false,
                'errors' => [
                    "This session has already started, so it can no longer be cancelled."
                ]
            ];
        }

        if ($requestingClientId === null && $this->hasEnded($existing)) {
            return [
                'success' => false,
                'errors' => [
                    "Completed sessions cannot be cancelled."
                ]
            ];
        }

        $stmt = $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :id");
        $stmt->execute([':id' => $bookingId]);
        return ['success' => true, 'errors' => []];
    }

    public function hasStarted(array $booking): bool
    {
        $startDateTime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
        return $startDateTime <= time();
    }

    public function hasEnded(array $booking): bool
    {
        $endDateTime = strtotime($booking['booking_date'] . ' ' . $booking['end_time']);
        return $endDateTime <= time();
    }

    /** Dynamic status label for display: upcoming / in_progress / completed / cancelled */
    public function computedStatus(array $booking): string
    {
        if ($booking['status'] === 'cancelled') return 'cancelled';
        if ($this->hasEnded($booking)) return 'completed';
        if ($this->hasStarted($booking)) return 'in_progress';
        return 'upcoming';
    }

    public function getById(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, l.description AS location_description, l.cost_per_hour,
                    u.name AS client_name, u.email AS client_email
             FROM bookings b
             JOIN locations l ON l.location_id = b.location_id
             JOIN users u ON u.user_id = b.client_id
             WHERE b.booking_id = :id"
        );
        $stmt->execute([':id' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** All bookings for a given client (any status/time). */
    public function getByClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, l.description AS location_description, l.cost_per_hour
             FROM bookings b JOIN locations l ON l.location_id = b.location_id
             WHERE b.client_id = :cid
             ORDER BY b.booking_date DESC, b.start_time DESC"
        );
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll();
    }

    /** Client's completed sessions (ended, not cancelled). */
    public function getCompletedByClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, l.description AS location_description, l.cost_per_hour
             FROM bookings b JOIN locations l ON l.location_id = b.location_id
             WHERE b.client_id = :cid AND b.status = 'active'
               AND CONCAT(b.booking_date, ' ', b.end_time) <= NOW()
             ORDER BY b.booking_date DESC, b.start_time DESC"
        );
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll();
    }

    /** Client's current + future sessions (not started to end, not cancelled). */
    public function getUpcomingByClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, l.description AS location_description, l.cost_per_hour
             FROM bookings b JOIN locations l ON l.location_id = b.location_id
             WHERE b.client_id = :cid AND b.status = 'active'
               AND CONCAT(b.booking_date, ' ', b.end_time) > NOW()
             ORDER BY b.booking_date ASC, b.start_time ASC"
        );
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll();
    }

    /** All bookings (admin view) */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT b.*, l.description AS location_description, u.name AS client_name, u.email AS client_email
             FROM bookings b
             JOIN locations l ON l.location_id = b.location_id
             JOIN users u ON u.user_id = b.client_id
             ORDER BY b.booking_date DESC, b.start_time DESC"
        );
        return $stmt->fetchAll();
    }

    /** Clients who currently have an active (in-progress right now) booking. */
    public function getClientsCurrentlyUsingStudio(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT u.user_id, u.name, u.phone, u.email,
                    b.booking_id, b.location_id, l.description AS location_description,
                    b.booking_date, b.start_time, b.end_time
             FROM bookings b
             JOIN users u ON u.user_id = b.client_id
             JOIN locations l ON l.location_id = b.location_id
             WHERE b.status = 'active'
               AND CONCAT(b.booking_date, ' ', b.start_time) <= NOW()
               AND CONCAT(b.booking_date, ' ', b.end_time)   > NOW()
             ORDER BY u.name ASC"
        );
        return $stmt->fetchAll();
    }


    /**
     * Return available start times for a location/date/duration.
     * Each slot is checked against the number of studios at that location.
     */
    public function getAvailableStartTimes(
        int $locationId,
        string $date,
        int $durationHours,
        ?int $excludeBookingId = null,
        ?int $clientId = null,
        bool $ignoreClientOverlap = false
    ): array {
        $location = $this->locationModel->getById($locationId);

        if (!$location || $durationHours < MIN_DURATION || $durationHours > MAX_DURATION) {
            return [];
        }

        if (!Validator::isDate($date) || $date < date('Y-m-d')) {
            return [];
        }

        $slots = [];

        $openMinutes = OPEN_HOUR * 60;
        $closeMinutes = CLOSE_HOUR * 60;
        $lastStartMinutes = $closeMinutes - ($durationHours * 60);

        // If the selected date is today, do not show past start times.
        $currentMinutes = ((int)date('H') * 60) + (int)date('i');
        $isToday = $date === date('Y-m-d');

        // Show times every 30 minutes.
        for ($minutes = $openMinutes; $minutes <= $lastStartMinutes; $minutes += 30) {

            if ($isToday && $minutes <= $currentMinutes) {
                continue;
            }

            $startHour = intdiv($minutes, 60);
            $startMinute = $minutes % 60;

            $endMinutes = $minutes + ($durationHours * 60);
            $endHour = intdiv($endMinutes, 60);
            $endMinute = $endMinutes % 60;

            $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
            $endTime   = sprintf('%02d:%02d:00', $endHour, $endMinute);

            $busy = $this->locationModel->studiosBookedDuring(
                $locationId,
                $date,
                $startTime,
                $endTime,
                $excludeBookingId
            );

            // A client should not be offered a slot that overlaps
            // another booking already owned by that same client.
            if (!$ignoreClientOverlap && $clientId !== null && $this->clientHasOverlappingBooking(
                $clientId,
                $date,
                $startTime,
                $endTime,
                $excludeBookingId
            )) {
                continue;
            }

            $free = (int)$location['num_studios'] - $busy;

            if ($free > 0) {
                $slots[] = [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'studios_free' => $free,
                    'studios_total' => (int)$location['num_studios']
                ];
            }
        }

        return $slots;
    }

    // ---------------------------------------------------------
    // Validation helpers
    // ---------------------------------------------------------

    private function validateInputs($locationId, string $date, string $startTime, $durationHours): array
    {
        $errors = [];

        if (!Validator::isPositiveInt($locationId)) {
            $errors[] = "Please select a valid location.";
        }

        if (!Validator::isDate($date)) {
            $errors[] = "Please enter a valid booking date (YYYY-MM-DD).";
        } elseif ($date < date('Y-m-d')) {
            $errors[] = "Booking date cannot be in the past.";
        }

        if (!Validator::isTime(substr($startTime, 0, 5))) {
            $errors[] = "Please enter a valid start time.";
        } elseif ($date === date('Y-m-d')) {
            $bookingDateTime = strtotime($date . ' ' . $startTime);

            if ($bookingDateTime <= time()) {
                $errors[] = "The booking start time must be later than the current time.";
            }
        }

        if (!Validator::isPositiveInt($durationHours) ||
            (int)$durationHours < MIN_DURATION ||
            (int)$durationHours > MAX_DURATION) {

            $errors[] = "Duration must be between " . MIN_DURATION .
                        " and " . MAX_DURATION . " hours.";
        }

        return $errors;
    }

    /**
     * Validates opening-hours rules using raw seconds-from-midnight
     * arithmetic (start + duration), WITHOUT wrapping past 24h, so a
     * duration that would run past midnight is correctly rejected
     * instead of silently wrapping around to an early-morning time.
     */
    private function validateBusinessHours(string $startTime, int $durationHours): array
    {
        $errors = [];
        $openSeconds  = OPEN_HOUR * 3600;
        $closeSeconds = CLOSE_HOUR * 3600;
        $startSeconds = $this->secondsFromMidnight($startTime);
        $endSecondsRaw = $startSeconds + ($durationHours * 3600);

        if ($startSeconds < $openSeconds) {
            $errors[] = "Sessions cannot start before 10:00 AM.";
        }
        if ($startSeconds >= $closeSeconds) {
            $errors[] = "Start time must be before closing time (10:00 PM).";
        }
        if ($endSecondsRaw > $closeSeconds) {
            $errors[] = "Sessions cannot extend beyond 10:00 PM. Please choose an earlier start time or shorter duration.";
        }
        return $errors;
    }


    private function clientHasOverlappingBooking(
        int $clientId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $sql = "SELECT COUNT(*) AS cnt
                FROM bookings
                WHERE client_id = :client_id
                AND booking_date = :date
                AND status = 'active'
                AND start_time < :end_time
                AND end_time > :start_time";

        $params = [
            ':client_id' => $clientId,
            ':date' => $date,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
        ];

        if ($excludeBookingId !== null) {
            $sql .= " AND booking_id != :exclude";
            $params[':exclude'] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetch()['cnt'] > 0;
    }

    private function secondsFromMidnight(string $time): int
    {
        // Accepts "HH:MM" or "HH:MM:SS"
        $parts = explode(':', $time);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        $s = (int)($parts[2] ?? 0);
        return $h * 3600 + $m * 60 + $s;
    }

    private function computeEndTime(string $startTime, int $durationHours): string
    {
        $endSeconds = $this->secondsFromMidnight($startTime) + ($durationHours * 3600);
        $h = intdiv($endSeconds, 3600);
        $m = intdiv($endSeconds % 3600, 60);
        $s = $endSeconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
