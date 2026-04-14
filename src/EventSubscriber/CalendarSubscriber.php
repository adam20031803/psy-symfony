<?php

namespace App\EventSubscriber;

use App\Repository\ChallengeRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $challengeRepository;

    public function __construct(ChallengeRepository $challengeRepository)
    {
        $this->challengeRepository = $challengeRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            CalendarEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();

        $challenges = $this->challengeRepository->createQueryBuilder('c')
            ->where('c.dateDebut BETWEEN :start and :end OR c.dateFin BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();

        foreach ($challenges as $challenge) {
            $event = new Event(
                $challenge->getTitre(),
                $challenge->getDateDebut() ?? new \DateTime(),
                $challenge->getDateFin() ?? (new \DateTime())->modify('+1 day')
            );
            
            $event->setOptions([
                'backgroundColor' => $challenge->getStatut() === 'actif' ? 'rgba(34,197,94,0.15)' : 'rgba(108,99,255,0.15)',
                'borderColor' => $challenge->getStatut() === 'actif' ? '#22c55e' : '#6c63ff',
                'textColor' => '#ffffff',
                'url' => '/challenge/' . $challenge->getId() . '/tasks'
            ]);

            $calendar->addEvent($event);
        }
    }
}
