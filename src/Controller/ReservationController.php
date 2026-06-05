<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $reservations = $entityManager->getRepository(Reservation::class)
            ->findBy(['user' => $user], ['startDate' => 'DESC']);
        
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
    
    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $room = $reservation->getRoom();
            $startDate = $reservation->getStartDate();
            $endDate = $reservation->getEndDate();
            
            // Vérifier si la salle est déjà réservée sur ce créneau
            $conflict = $entityManager->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->where('r.room = :room')
                ->andWhere('r.id != :reservationId')
                ->andWhere('(r.startDate BETWEEN :start AND :end OR r.endDate BETWEEN :start AND :end OR :start BETWEEN r.startDate AND r.endDate)')
                ->setParameter('room', $room)
                ->setParameter('reservationId', $reservation->getId() ?: 0)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($conflict) {
                $this->addFlash('error', 'Cette salle est déjà réservée sur ce créneau !');
                return $this->redirectToRoute('app_reservation_new');
            }
            
            $reservation->setUser($this->getUser());
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réservation effectuée avec succès !');
            return $this->redirectToRoute('app_reservation_index');
        }
        
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($reservation->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres réservations.');
            return $this->redirectToRoute('app_reservation_index');
        }
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $room = $reservation->getRoom();
            $startDate = $reservation->getStartDate();
            $endDate = $reservation->getEndDate();
            
            // Vérifier les conflits
            $conflict = $entityManager->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->where('r.room = :room')
                ->andWhere('r.id != :reservationId')
                ->andWhere('(r.startDate BETWEEN :start AND :end OR r.endDate BETWEEN :start AND :end OR :start BETWEEN r.startDate AND r.endDate)')
                ->setParameter('room', $room)
                ->setParameter('reservationId', $reservation->getId())
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($conflict) {
                $this->addFlash('error', 'Cette salle est déjà réservée sur ce créneau !');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès !');
            return $this->redirectToRoute('app_reservation_index');
        }
        
        return $this->render('reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }
    
    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($reservation->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez annuler que vos propres réservations.');
            return $this->redirectToRoute('app_reservation_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée avec succès !');
        }
        
        return $this->redirectToRoute('app_reservation_index');
    }
}
