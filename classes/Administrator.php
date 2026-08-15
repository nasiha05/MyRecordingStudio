<?php
/**
 * Administrator.php
 * Represents a logged-in Administrator user. Wraps Location/Booking/
 * User model calls that are administrator-only actions.
 */
class Administrator extends User
{
    private Location $locationModel;
    private Booking $bookingModel;

    public function __construct(int $userId)
    {
        parent::__construct();
        $data = $this->getById($userId);
        if ($data) {
            $this->userId   = (int)$data['user_id'];
            $this->name     = $data['name'];
            $this->phone    = $data['phone'];
            $this->email    = $data['email'];
            $this->userType = $data['user_type'];
        }
        $this->locationModel = new Location();
        $this->bookingModel  = new Booking();
    }

    // ---- Location management ----
    public function createLocation(string $description, $numStudios, $costPerHour): array
    {
        return $this->locationModel->create($description, $numStudios, $costPerHour);
    }

    public function updateLocation(int $locationId, string $description, $numStudios, $costPerHour): array
    {
        return $this->locationModel->update($locationId, $description, $numStudios, $costPerHour);
    }

    public function getAllLocations(): array
    {
        return $this->locationModel->getAll();
    }

    public function getAvailableLocations(): array
    {
        return $this->locationModel->getAvailableNow();
    }

    public function getFullyBookedLocations(): array
    {
        return $this->locationModel->getFullyBookedNow();
    }

    public function searchLocations(?string $locationId, ?string $description): array
    {
        return $this->locationModel->search($locationId, $description);
    }

    // ---- Booking management (on behalf of a client) ----
    public function createBookingForClient(int $clientId, int $locationId, string $date, string $startTime, $duration): array
    {
        return $this->bookingModel->create($clientId, $locationId, $date, $startTime, $duration);
    }

    public function modifyBooking(int $bookingId, int $locationId, string $date, string $startTime, $duration): array
    {
        // requestingClientId = null -> admin override, ownership check skipped
        return $this->bookingModel->update($bookingId, $locationId, $date, $startTime, $duration, null);
    }

    public function cancelBooking(int $bookingId): array
    {
        return $this->bookingModel->cancel($bookingId, null);
    }

    public function getAllBookings(): array
    {
        return $this->bookingModel->getAll();
    }

    // ---- Client management ----
    public function getAllClients(): array
    {
        $stmt = $this->db->query("SELECT * FROM users WHERE user_type = 'client' ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getClientsCurrentlyUsingStudio(): array
    {
        return $this->bookingModel->getClientsCurrentlyUsingStudio();
    }
}
