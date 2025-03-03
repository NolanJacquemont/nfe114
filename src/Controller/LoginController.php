<?php

namespace App\Controller;

use App\Repository\PractitionerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class LoginController extends AbstractController
{
    // POST /login (email, password) returns id if user exists and password is correct
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User does not exist (email: ' . $email . ')'], Response::HTTP_NOT_FOUND);
        }
        if (!$user->getPassword() === $password) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        else {
            return new JsonResponse(['token' => $user->getId()]);
        }
    }


    // POST /login (email, password) returns id if user exists and password is correct
    #[Route('/loginPractitioner', methods: ['POST'])]
    public function loginPractitioner(Request $request, PractitionerRepository $practitionerRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $practitioner = $practitionerRepository->findOneBy(['email' => $email]);

        if (!$practitioner) {
            return new JsonResponse(['error' => 'User does not exist (email: ' . $email . ')'], Response::HTTP_NOT_FOUND);
        }
        if (!$practitioner->getPassword() === $password) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        else {
            return new JsonResponse(['token' => $practitioner->getId()]);
        }
    }
}
