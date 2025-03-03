<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Slot;
use App\Repository\AppointmentRepository;
use App\Repository\PractitionerRepository;
use App\Repository\SlotRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AppointmentController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, private SerializerInterface $serializer2)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer2;
    }

    #[Route('/appointments', methods: ['GET'])]
    public function getAppointments(
        Request $request,
        UserRepository $userRepository,
        PractitionerRepository $practitionerRepository,
        AppointmentRepository $appointmentRepository
    ): JsonResponse {
        $userId = $request->query->get('userId');
        $practitionerId = $request->query->get('practitionerId');

        if (!$userId && !$practitionerId) {
            return new JsonResponse(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }

        if ($userId) {
            $user = $userRepository->find($userId);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $appointments = $appointmentRepository->findBy(['user' => $user]);
        } else {
            $practitioner = $practitionerRepository->find($practitionerId);

            if (!$practitioner) {
                return new JsonResponse(['error' => 'Practitioner not found'], Response::HTTP_NOT_FOUND);
            }

            $appointments = $appointmentRepository->findBy(['practitioner' => $practitioner]);
        }

        // serialize appointments, and also user and practitioner inside each appointment (manually, not with groups)
        $appointmentsArray = [];

        foreach ($appointments as $appointment) {
            $appointmentsArray[] = [
                'id' => $appointment->getId(),
                'time' => $appointment->getTime()->format('Y-m-d H:i:s'),
                'status' => $appointment->getStatus(),
                'user' => [
                    'id' => $appointment->getUser()->getId(),
                    'email' => $appointment->getUser()->getEmail(),
                    'firstName' => $appointment->getUser()->getFirstName(),
                    'lastName' => $appointment->getUser()->getLastName(),
                ],
                'practitioner' => [
                    'id' => $appointment->getPractitioner()->getId(),
                    'email' => $appointment->getUser()->getEmail(),
                    'firstName' => $appointment->getPractitioner()->getFirstName(),
                    'lastName' => $appointment->getPractitioner()->getLastName(),
                    'address' => $appointment->getPractitioner()->getAddress(),
                    'speciality' => $appointment->getPractitioner()->getSpeciality(),
                ],
            ];
        }

        $jsonAppointments = json_encode($appointmentsArray);

        return new JsonResponse($jsonAppointments, Response::HTTP_OK, [], true);

    }

    #[Route('/appointments', methods: ['POST'])]
    public function createAppointment(
        Request $request,
        UserRepository $userRepository,
        PractitionerRepository $practitionerRepository,
        AppointmentRepository $appointmentRepository,
        SlotRepository $slotRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $practitionerId = $data['practitionerId'] ?? null;
        $datetime = $data['datetime'] ?? null;

        if (!$userId || !$practitionerId || !$datetime) {
            return new JsonResponse(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($userId);
        $practitioner = $practitionerRepository->find($practitionerId);

        if (!$user || !$practitioner) {
            return new JsonResponse(['error' => 'User or practitioner not found'], Response::HTTP_NOT_FOUND);
        }

        $existingAppointmentUser = $appointmentRepository->findOneBy(['user' => $user, 'time' => new \DateTime($datetime)]);
        $existingAppointmentPractitioner = $appointmentRepository->findOneBy(['practitioner' => $practitioner, 'time' => new \DateTime($datetime)]);

        if ($existingAppointmentUser || $existingAppointmentPractitioner) {
            return new JsonResponse(['error' => 'Un rendez-vous existe déjà pour cette date'], Response::HTTP_CONFLICT);
        }

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setPractitioner($practitioner);
        $appointment->setTime(new \DateTime($datetime));
        $appointment->setStatus('booked');

        $this->entityManager->persist($appointment);

        // edit corresponding slot
        $slot = $slotRepository->findOneBy(['practitioner' => $practitioner, 'time' => new \DateTime($datetime)]);
        $slot->setStatus('booked');

        $this->entityManager->persist($slot);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Appointment created successfully'], Response::HTTP_CREATED);
    }

    #[\Symfony\Component\Routing\Annotation\Route('/slots/{id}', methods: ['GET'])]
    public function getSlots(
        int $id,
        PractitionerRepository $practitionerRepository,
        SlotRepository $slotRepository
    ): JsonResponse {
        $practitioner = $practitionerRepository->find($id);

        if (!$practitioner) {
            return new JsonResponse(['error' => 'Practitioner not found'], Response::HTTP_NOT_FOUND);
        }

        $slots = $slotRepository->findBy(['practitioner' => $practitioner]);

        $jsonSlots = $this->serializer->serialize($slots, 'json');

        return new JsonResponse($jsonSlots, Response::HTTP_OK, [], true);
    }


    #[\Symfony\Component\Routing\Annotation\Route('/freeSlots/{id}', methods: ['GET'])]
    public function getFreeSlots(
        int $id,
        PractitionerRepository $practitionerRepository,
        SlotRepository $slotRepository
    ): JsonResponse {
        $practitioner = $practitionerRepository->find($id);

        if (!$practitioner) {
            return new JsonResponse(['error' => 'Practitioner not found'], Response::HTTP_NOT_FOUND);
        }

        $slots = $slotRepository->findBy(['practitioner' => $practitioner, 'status' => 'free']);

        $jsonSlots = $this->serializer->serialize($slots, 'json');

        return new JsonResponse($jsonSlots, Response::HTTP_OK, [], true);
    }

    #[\Symfony\Component\Routing\Annotation\Route('/slots/{id}', methods: ['POST'])]
    public function addSlot(
        int $id,
        Request $request,
        PractitionerRepository $practitionerRepository,
        SlotRepository $slotRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $datetime = $data['datetime'] ?? null;

        if (!$datetime) {
            return new JsonResponse(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }

        $practitioner = $practitionerRepository->find($id);

        if (!$practitioner) {
            return new JsonResponse(['error' => 'Practitioner not found'], Response::HTTP_NOT_FOUND);
        }

        $existingSlot = $slotRepository->findOneBy(['practitioner' => $practitioner, 'time' => new \DateTime($datetime)]);

        if ($existingSlot) {
            return new JsonResponse(['error' => 'Un crénau existe déjà pour cette date'], Response::HTTP_CONFLICT);
        }

        $slot = new Slot();
        $slot->setPractitioner($practitioner);
        $slot->setTime(new \DateTime($datetime));
        $slot->setStatus('free');

        $this->entityManager->persist($slot);
        $this->entityManager->flush();

        $jsonSlot = $this->serializer->serialize($slot, 'json');

        return new JsonResponse($jsonSlot, Response::HTTP_CREATED, [], true);
    }
}
