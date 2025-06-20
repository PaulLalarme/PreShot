<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewerController extends AbstractController
{
    #[Route('/viewer', name: 'app_viewer')]
    public function viewer(): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'isVerified') || !$user->isVerified()) {
            throw $this->createAccessDeniedException('You must verify your email to access this page.');
        }

        return $this->render('viewer/index.html.twig');
    }
}
