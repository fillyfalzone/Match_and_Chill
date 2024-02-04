<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use App\HttpClient\OpenLigaDBClient;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EventsController extends AbstractController
{

    // Liste des événements
    #[Route('/events', name: 'app_events')]
    public function index(OpenLigaDBClient $httpClient, EventRepository $eventRepository): Response
    {   
        // Récupérer le tableau de la Bundesliga
        $table = $httpClient->getTable();

        // Recuère tous les événements classé par date de commencement
        $events = $eventRepository->findBy([], ['startDate' => 'ASC']);

        

        return $this->render('events/events.html.twig', [
            'table' => $table,
            'events' => $events,
        ]);
    }

    // list events sorted by date or status
    #[Route('/events/sorted', name: 'app_events_sorted', methods: ['POST'])]
    public function sorted(EventRepository $eventRepository, OpenLigaDBClient $httpClient, Request $request): JsonResponse
    {   
        // Décodage des données JSON envoyées dans la requête
        $data = json_decode($request->getContent(), true);

        $teamId = $data['teamId'] ?? null;

        // On recupère les matchs d'une équipe
        $matchs = $httpClient->getMatchByTeamId($data['teamId']);

        if($teamId){
            // On recupère les événements associés à ces matchs
            foreach ($matchs as $match) {
                $event = $eventRepository->findOneBy(['matchId' => $teamId]);
                $events[] = $event;
            }
        }

        return new JsonResponse('events', $events);
    }

    // Create new or edit event
    #[Route('/events/new', name: 'app_events_new')]
    #[Route('/events/edit/{id}', name: 'app_events_edit')]
    public function newedit(Event $event = null, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, Request $request): Response
    {   
        // Création d'un nouvel objet Event si aucun n'est fourni
        if (!$event){
            $event = new Event();
        }
        // Récupérer l'id de l'événement s'il existe
        $id = $event->getId();
        
        // Création du formulaire
        $form = $this->createForm(EventFormType::class, $event);

        // Traitement de la requête
        $form->handleRequest($request); 
       
        if ($form->isSubmitted() && $form->isValid()) {

            // Ajouter les informations manquantes
            $event->setUser($tokenStorage->getToken()->getUser());
            $event->setCreationDate(new \DateTime());
            $event->setIsLocked("0");

            // Enregistrer l'événement
            $entityManager->persist($event);
            $entityManager->flush();

            // Rediriger vers la page de l'événement
            return $this->redirectToRoute('app_events_show', ['id' => $event->getId()]);
        }

        // Affichage du formulaire
        return $this->render('events/newedit.html.twig', [
            'form' => $form->createView(),
            'id' => $id,
        ]);
    }


    // Delete event
    #[Route('/events/delete/{id}', name: 'app_events_delete')]
    public function delete(EntityManagerInterface $entityManager, EventRepository $eventRepository, int $id): Response
    {
        // Récupérer l'événement
        $event = $eventRepository->find($id);

        // Supprimer l'événement
        $entityManager->remove($event);
        $entityManager->flush();

        return $this->redirectToRoute('app_events');
    }

    // Show event
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour afficher les détails d\'un événement')]
    #[Route('/events/show/{id}', name: 'app_events_show')]
    public function show(OpenLigaDBClient $httpClient, EventRepository $eventRepository,UserRepository $userRepository, TokenStorageInterface $tokenStorage, int $id ): Response
    {   
        // Récupérer l'événement
        $event = $eventRepository->find($id);
        // Récupérer l'utilisateur connecté
        $user = $tokenStorage->getToken()->getUser();
        $userId = $user->getId();

        // Vérifier si l'utilisateur participe à l'événement
        $participate = $userRepository->participate($id, $userId);

        // On recupère le nombre de places disponibles
        $availablePlaces = $event->getNumberOfPlaces() - count($event->getUsersParticipate());

        // On recupère l'id du match
        $matchId = $event->getMatchId(); 
        // On recupère les informations du match
        $match = $httpClient->getMatchById($matchId);

        // Affichage de la page de l'événement
        return $this->render('events/show.html.twig', [
            'event' => $event,
            'match' => $match,
            'participate' => $participate,
            'availablePlaces' => $availablePlaces,
        ]);
    }

    // Participer à un événement
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour participer à un événement')]
    #[Route('/events/participate/{id}', name: 'app_events_participate')]
    public function participate(Event $event, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, int $id): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $tokenStorage->getToken()->getUser();

        // Ajouter l'utilisateur à la liste des participants
        $event->addUserParticipate($user);

        // Enregistrer l'événement
        $entityManager->persist($event);
        $entityManager->flush();

        // Rediriger vers la page de l'événement
        return $this->redirectToRoute('app_events_show', ['id' => $id]);
    }

    // Annuler la participation à un événement
    #[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour annuler votre participation à un événement')]
    #[Route('/events/unparticipate/{id}', name: 'app_events_unparticipate')]
    public function unparticipate(Event $event, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, int $id): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $tokenStorage->getToken()->getUser();

        // Supprimer l'utilisateur de la liste des participants
        $event->removeUserParticipate($user);

        // Enregistrer l'événement
        $entityManager->persist($event);
        $entityManager->flush();

        // Rediriger vers la page de l'événement
        return $this->redirectToRoute('app_events_show', ['id' => $id]);
    }


}
