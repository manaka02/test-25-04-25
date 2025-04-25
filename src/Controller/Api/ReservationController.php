<?php

namespace App\Controller\Api;

use App\Services\ReservationServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/reservation')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private ReservationServices $services,
        private SerializerInterface $serializer
    )
    {
    }

    #[Route('/create', name: 'app_api_reservation_create')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();

        try {
            $this->services->createReservation($data);

            return new JsonResponse([
                "success" => true,
                "message" => "Reservation créée avec succès",
            ]);

        }catch (\Exception $exception){
            return $this->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

    }
}
