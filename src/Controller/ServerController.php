<?php

namespace App\Controller;

use App\Entity\Server;
use App\Form\ServerType;
use App\Repository\ServerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use phpseclib\Net\SSH2;

/**
 * @Route("/server")
 */
class ServerController extends AbstractController
{
    /**
     * @Route("/", name="server_index", methods={"GET"})
     */
    public function index(ServerRepository $serverRepository): Response
    {
        $servers = $serverRepository->findAll();

        foreach ($servers as $key => $server) {
            $servers[$key] = $server->getDataAsArray();
            $servers[$key]['is_online'] = true;
            $ssh = new SSH2($server->getHost(), 22);
            if(!$ssh->login($server->getUsername(), $server->getPassword()))
                $servers[$key]['is_online'] = false;   
        }
        
        return $this->render('server/index.html.twig', [
            'servers' => $servers,
        ]);
    }

    /**
     * @Route("/new", name="server_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $server = new Server();
        $form = $this->createForm(ServerType::class, $server);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($server);
            $entityManager->flush();

            return $this->redirectToRoute('server_index');
        }

        return $this->render('server/new.html.twig', [
            'server' => $server,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="server_show", methods={"GET"})
     */
    public function show(Server $server): Response
    {
        return $this->render('server/show.html.twig', [
            'server' => $server,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="server_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Server $server): Response
    {
        $form = $this->createForm(ServerType::class, $server);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('server_index', [
                'id' => $server->getId(),
            ]);
        }

        return $this->render('server/edit.html.twig', [
            'server' => $server,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="server_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Server $server): Response
    {
        if ($this->isCsrfTokenValid('delete'.$server->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($server);
            $entityManager->flush();
        }

        return $this->redirectToRoute('server_index');
    }

    /**
     * @Route("/terminal/{id}", name="terminal", methods={"GET"})
     */
    public function terminal(Request $request, Server $server): Response
    {
        $ssh = new SSH2($server->getHost(), 22);
        $ssh->login($server->getUsername(), $server->getPassword());
        $result = $ssh->exec('pwd');
        
        return $this->render('terminal/terminal.html.twig', [
            'result' => $result,
        ]);
    }

    /**
     * @Route("/command/{id}", name="command", methods={"POST"})
     */
    public function command(Request $request, Server $server): Response
    {
        $command = $request->request->get('command');

        $ssh = new SSH2($server->getHost(), 22);
        $ssh->login($server->getUsername(), $server->getPassword());
        $result = $ssh->exec($command);
        
        return new JsonResponse(['result' => $result]);
    }
}
