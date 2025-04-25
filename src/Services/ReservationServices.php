<?php

namespace App\Services;

use App\Entity\Car;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservationServices
{
    public function __construct(
        private ReservationRepository $repository,
        private ValidatorInterface    $validator,
        private ManagerRegistry       $managerRegistry
    )
    {
    }

    private array $requiredParams = ["car", "userEmail", "startAt", "endAt"];

    // create new reservation
    public function createReservation(?string $dataJson): Reservation
    {

        try {
            $data = json_decode($dataJson, true);

            // verifier les requiredParams
            foreach ($this->requiredParams as $param) {
                if (!isset($data[$param])) {
                    throw new \Exception("Le paramètre '$param' est requis.");
                }
            }

            $car = $this->managerRegistry->getRepository(Car::class)->find($data['car']);

            if (!$car) {
                throw new \Exception("La voiture avec l'identifiant '{$data['car']}' n'existe pas.");
            }

            $reservation = new Reservation();
            $reservation->setCar($car);
            $reservation->setUserEmail($data['userEmail']);
            $reservation->setStartAt(new \DateTimeImmutable($data['startAt']));
            $reservation->setEndAt(new \DateTimeImmutable($data['endAt']));

            $this->validate($reservation);
            $this->checkOverlap($reservation);

            $this->managerRegistry->getManager()->persist($reservation);
            $this->managerRegistry->getManager()->flush();

            return $reservation;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        } catch (\Throwable $throwable) {
            throw new \Exception("Une erreur s'est produite lors de la création de la réservation.");
        }
    }

    private function validate(Reservation $reservation)
    {
        $errors = $this->validator->validate($reservation);

        if (count($errors) > 0) {
            $errorMessages = $errors->get(0)->getMessage();
            throw new \Exception( $errorMessages);
        }
    }

    private function checkOverlap(Reservation $reservation)
    {
        $existingReservations = $this->repository->findExistingReservation($reservation);

        if ($existingReservations) {
            throw new \Exception("La réservation entre {$reservation->getStartAt()->format('Y-m-d H:i:s')} et {$reservation->getEndAt()->format('Y-m-d H:i:s')} chevauche une réservation existante.");
        }
    }

}