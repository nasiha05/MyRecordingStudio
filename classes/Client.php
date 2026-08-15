<?php
/**
 * Client.php
 * Represents a logged-in Client user. Wraps Booking/Location model
 * calls that are scoped to "this" client.
 */
class Client extends User
{
    private Booking $bookingModel;
    private Location $locationModel;

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
        $this->bookingModel  = new Booking();
        $this->locationModel = new Location();
    }

    public function bookStudio(int $locationId, string $date, string $startTime, $duration): array
    {
        return $this->bookingModel->create($this->userId, $locationId, $date, $startTime, $duration);
    }

    public function modifyBooking(int $bookingId, int $locationId, string $date, string $startTime, $duration): array
    {
        return $this->bookingModel->update($bookingId, $locationId, $date, $startTime, $duration, $this->userId);
    }

    public function cancelBooking(int $bookingId): array
    {
        return $this->bookingModel->cancel($bookingId, $this->userId);
    }

    public function getAllBookings(): array
    {
        return $this->bookingModel->getByClient($this->userId);
    }

    public function getCompletedSessions(): array
    {
        return $this->bookingModel->getCompletedByClient($this->userId);
    }

    public function getUpcomingSessions(): array
    {
        return $this->bookingModel->getUpcomingByClient($this->userId);
    }

    public function getAvailableLocations(): array
    {
        return $this->locationModel->getAvailableNow();
    }

    public function searchLocations(?string $locationId, ?string $description): array
    {
        return $this->locationModel->search($locationId, $description);
    }
}
