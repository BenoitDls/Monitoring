<?php

namespace App\Controller;

use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/", name="home_page")
     */
    public function index()
    {
        $servers = $this->getDoctrine()
            ->getRepository(Server::class)
            ->findAll();

        return $this->render('homepage.html.twig', [
            'servers' => $servers
        ]);
    }
}
