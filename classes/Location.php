<?php
/**
 * Location.php
 * Handles all operations on studio locations: create, edit, list,
 * search (partial match on multiple fields), and availability
 * queries ("has a free studio right now" / "fully booked right now").
 */
class Location
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Validate + create a new location. Returns ['success'=>bool,'errors'=>array] */
    public function create(string $description, $numStudios, $costPerHour): array
    {
        $errors = $this->validate($description, $numStudios, $costPerHour);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO locations (description, num_studios, cost_per_hour)
             VALUES (:description, :num_studios, :cost)"
        );
        $stmt->execute([
            ':description' => $description,
            ':num_studios' => (int)$numStudios,
            ':cost'        => (float)$costPerHour,
        ]);
        return ['success' => true, 'errors' => [], 'location_id' => (int)$this->db->lastInsertId()];
    }

    /** Validate + update an existing location. */
    public function update(int $locationId, string $description, $numStudios, $costPerHour): array
    {
        $errors = $this->validate($description, $numStudios, $costPerHour);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare(
            "UPDATE locations SET description = :description, num_studios = :num_studios,
             cost_per_hour = :cost WHERE location_id = :id"
        );
        $stmt->execute([
            ':description' => $description,
            ':num_studios' => (int)$numStudios,
            ':cost'        => (float)$costPerHour,
            ':id'          => $locationId,
        ]);
        return ['success' => true, 'errors' => []];
    }

    private function validate(string $description, $numStudios, $costPerHour): array
    {
        $errors = [];
        if (!Validator::required($description) || !Validator::minLength($description, 3)) {
            $errors[] = "Please enter a location description (at least 3 characters).";
        }
        if (!Validator::isPositiveInt($numStudios)) {
            $errors[] = "Number of studios must be a positive whole number.";
        }
        if (!Validator::isPositiveNumber($costPerHour)) {
            $errors[] = "Cost per hour must be a positive number.";
        }
        return $errors;
    }

    public function getById(int $locationId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM locations WHERE location_id = :id");
        $stmt->execute([':id' => $locationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** List all locations */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY description ASC");
        return $stmt->fetchAll();
    }

    /**
     * Search locations by a combination of fields, partial match.
     * Any parameter left blank/null is ignored.
     */
    public function search(?string $locationId = null, ?string $description = null): array
    {
        $sql = "SELECT * FROM locations WHERE 1=1";
        $params = [];

        if (Validator::required($locationId)) {
            $sql .= " AND location_id LIKE :location_id";
            $params[':location_id'] = '%' . $locationId . '%';
        }
        if (Validator::required($description)) {
            $sql .= " AND description LIKE :description";
            $params[':description'] = '%' . $description . '%';
        }
        $sql .= " ORDER BY description ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Locations that currently have at least one free studio
     * (i.e. active bookings overlapping right now < num_studios).
     */
    public function getAvailableNow(): array
    {
        $sql = "SELECT l.*,
                       l.num_studios - COALESCE(b.busy, 0) AS studios_free
                FROM locations l
                LEFT JOIN (
                    SELECT location_id, COUNT(*) AS busy
                    FROM bookings
                    WHERE status = 'active'
                      AND CONCAT(booking_date, ' ', start_time) <= NOW()
                      AND CONCAT(booking_date, ' ', end_time)   >  NOW()
                    GROUP BY location_id
                ) b ON b.location_id = l.location_id
                HAVING studios_free > 0
                ORDER BY l.description ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Locations where ALL studios are currently booked (fully booked right now). */
    public function getFullyBookedNow(): array
    {
        $sql = "SELECT l.*,
                       COALESCE(b.busy, 0) AS studios_busy
                FROM locations l
                LEFT JOIN (
                    SELECT location_id, COUNT(*) AS busy
                    FROM bookings
                    WHERE status = 'active'
                      AND CONCAT(booking_date, ' ', start_time) <= NOW()
                      AND CONCAT(booking_date, ' ', end_time)   >  NOW()
                    GROUP BY location_id
                ) b ON b.location_id = l.location_id
                HAVING studios_busy >= l.num_studios AND studios_busy > 0
                ORDER BY l.description ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Number of studios already booked for a location during a
     * specific requested date/time range (used to check if a
     * new booking can be accepted). Optionally excludes one
     * booking_id (used when modifying an existing booking).
     */
    public function studiosBookedDuring(int $locationId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM bookings
                WHERE location_id = :loc
                  AND booking_date = :date
                  AND status = 'active'
                  AND start_time < :end_time
                  AND end_time   > :start_time";
        $params = [
            ':loc'        => $locationId,
            ':date'       => $date,
            ':start_time' => $startTime,
            ':end_time'   => $endTime,
        ];
        if ($excludeBookingId !== null) {
            $sql .= " AND booking_id != :exclude";
            $params[':exclude'] = $excludeBookingId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['cnt'];
    }

    public function delete(int $locationId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM locations WHERE location_id = :id");
        return $stmt->execute([':id' => $locationId]);
    }
}
