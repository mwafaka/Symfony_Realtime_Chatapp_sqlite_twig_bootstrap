<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('chat');
        }

        $messages = $em->getRepository(Message::class)->findAll();

        return $this->render('chat/index.html.twig', [
            'form' => $form->createView(),
            'messages' => $messages,
        ]);
    }

    
    #[Route('/chat/messages', name: 'chat_messages')]
public function messages(EntityManagerInterface $em): Response
{
    $messages = $em->getRepository(Message::class)->findAll();

    return $this->render('chat/messages.html.twig', [
        'messages' => $messages,
    ]);
}
}
