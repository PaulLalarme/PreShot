<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $entityManager->persist($user);
            $entityManager->flush();

            $verificationUrl = $this->generateUrl('app_verify_email', ['token' => $user->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Please verify your email')
                ->htmlTemplate('emails/verify_email.html.twig')
                ->context(['verificationUrl' => $verificationUrl]);
            $mailer->send($email);

            $this->addFlash('success', 'Account created. Please verify your email.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, UserRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $token = $request->query->get('token');
        if (!$token) {
            throw $this->createNotFoundException();
        }
        $user = $repository->findOneBy(['verificationToken' => $token]);
        if (!$user) {
            throw $this->createNotFoundException();
        }
        $user->setVerificationToken(null);
        $user->setIsVerified(true);
        $entityManager->flush();
        $this->addFlash('success', 'Your email has been verified.');
        return $this->redirectToRoute('app_login');
    }
}
