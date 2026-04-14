<?php

namespace App\Service;

use App\Entity\Challenge;
use App\Entity\SmartMeeting;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SmartMeetingTriggerService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Analyse un challenge pour voir si l'orchestrateur (IA) doit déclencher une réunion.
     */
    public function checkAndTrigger(Challenge $challenge): ?SmartMeeting
    {
        // 1. Détecteur de Stagnation (S'il n'y a pas eu de tâche validée depuis 48h par le groupe)
        if ($this->isGroupStagnating($challenge)) {
            return $this->triggerMeeting($challenge, 'FLASH_SYNC', "Inactivité détectée : Le groupe semble avoir perdu le rythme.");
        }

        // 2. Détecteur de Blocage Critique (Plusieurs échecs sur la même tâche difficile)
        if ($this->isCriticalBlockage($challenge)) {
            return $this->triggerMeeting($challenge, 'DEBUG', "Blocage critique détecté sur une tâche complexe.");
        }

        return null;
    }

    private function isGroupStagnating(Challenge $challenge): bool
    {
        // On pourrait vérifier ici la date de dernière mise à jour des tâches du challenge
        // Pour cet exemple, on simule une logique simplifiée
        return true; 
    }

    private function isCriticalBlockage(Challenge $challenge): bool
    {
        // Logique avancée : Analyser les tentatives échouées (si stockées en DB)
        return false;
    }

    private function triggerMeeting(Challenge $challenge, string $type, string $reason): SmartMeeting
    {
        // Vérifier si un meeting identique n'est pas déjà programmé
        $existing = $this->em->getRepository(SmartMeeting::class)->findOneBy([
            'challenge' => $challenge,
            'status' => 'SCHEDULED',
            'type' => $type
        ]);

        if ($existing) {
            return $existing;
        }

        $meeting = new SmartMeeting();
        $meeting->setChallenge($challenge);
        $meeting->setType($type);
        $meeting->setTriggerReason($reason);
        // Ici, on pourrait appeler une API (Google/Zoom) pour obtenir un vrai lien
        $meeting->setMeetingUrl($this->generateFakeMeetLink());
        
        $this->em->persist($meeting);
        $this->em->flush();

        $this->logger->info("AI Orchestrator a déclenché un meeting {type} pour le challenge {id}", [
            'type' => $type,
            'id' => $challenge->getId()
        ]);

        return $meeting;
    }



    private function generateFakeMeetLink(): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    
    return sprintf(
        "https://meet.google.com/%s%s%s-%s%s%s%s-%s%s%s",
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)],
        $chars[rand(0,25)]
    );
}
}
