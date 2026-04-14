<?php

// src/Service/ChallengeCalendarService.php

namespace App\Service;

use App\Entity\Challenge;
use App\Repository\ChallengeRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class ChallengeCalendarService implements EventSubscriberInterface
{
    public function __construct(
        private readonly ChallengeRepository $challengeRepository,
        private readonly RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvent::class => 'loadEvents',
        ];
    }

    public function loadEvents(CalendarEvent $calendarEvent): void
    {
        $start = $calendarEvent->getStart();
        $end   = $calendarEvent->getEnd();

        $challenges = $this->challengeRepository->createQueryBuilder('c')
            ->where('c.dateDebut <= :end AND c.dateFin >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        /** @var Challenge $challenge */
        foreach ($challenges as $challenge) {
            $color = match ($challenge->getStatut()) {
                'actif'   => '#6c63ff',
                'termine' => '#22d3a5',
                'annule'  => '#ef4444',
                default   => '#94a3b8',
            };

            $icon = match ($challenge->getStatut()) {
                'actif'   => '🔥',
                'termine' => '✅',
                'annule'  => '❌',
                default   => '📋',
            };

            $event = new Event(
                $icon . ' ' . $challenge->getTitre(),
                $challenge->getDateDebut(),
                $challenge->getDateFin()
            );

            $event->setOptions([
                'backgroundColor'   => $color,
                'borderColor'       => $color,
                'textColor'         => '#ffffff',
                'url'               => $this->router->generate('app_challenge_tasks', ['id' => $challenge->getId()]),
                'extendedProps'     => [
                    'statut'      => $challenge->getStatut(),
                    'categorie'   => $challenge->getCategorie()?->getNom() ?? '—',
                    'description' => mb_substr($challenge->getDescription(), 0, 100) . '...',
                    'coaches'     => count($challenge->getCoaches()),
                    'recompenses' => count($challenge->getRecompenses()),
                ],
            ]);

            $calendarEvent->addEvent($event);
        }
    }
}
