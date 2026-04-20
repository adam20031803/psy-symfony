<?php

// src/EventListener/CensorListener.php

namespace App\EventListener;

use App\Entity\Post;
use App\Entity\Commentaire;
use App\Service\BadWordChecker;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Listener Doctrine pour intercepter la sauvegarde des Posts et Commentaires
 * et censurer automatiquement les mots interdits avant l'insertion en base.
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Post::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Post::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Commentaire::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Commentaire::class)]
class CensorListener
{
    private BadWordChecker $badWordChecker;

    public function __construct(BadWordChecker $badWordChecker)
    {
        $this->badWordChecker = $badWordChecker;
    }

    public function prePersist(Post|Commentaire $entity, PrePersistEventArgs $event): void
    {
        $this->censorEntity($entity);
    }

    public function preUpdate(Post|Commentaire $entity, PreUpdateEventArgs $event): void
    {
        $this->censorEntity($entity);
    }

    private function censorEntity(Post|Commentaire $entity): void
    {
        if ($entity instanceof Post) {
            if ($entity->getTitre() !== null) {
                $entity->setTitre($this->badWordChecker->censorText($entity->getTitre()));
            }
            if ($entity->getContenu() !== null) {
                $entity->setContenu($this->badWordChecker->censorText($entity->getContenu()));
            }
        } elseif ($entity instanceof Commentaire) {
            if ($entity->getContenu() !== null) {
                $entity->setContenu($this->badWordChecker->censorText($entity->getContenu()));
            }
        }
    }
}
