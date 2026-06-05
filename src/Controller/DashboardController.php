<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(RoomRepository $roomRepository, ReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $totalRooms = $roomRepository->count([]);
        $totalReservations = $reservationRepository->count([]);
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        
        $todayReservations = $reservationRepository->createQueryBuilder('r')
            ->where('r.startDate BETWEEN :today AND :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getResult();
        
        return $this->render('dashboard/index.html.twig', [
            'total_rooms' => $totalRooms,
            'total_reservations' => $totalReservations,
            'today_reservations' => $todayReservations,
        ]);
    }
}
