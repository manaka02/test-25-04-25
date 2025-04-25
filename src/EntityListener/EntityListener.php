<?php

namespace App\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use JetBrains\PhpStorm\Pure;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
class EntityListener
{

    #[Pure] public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'getCreatedAt')) {
            if (empty($entity->getCreatedAt())) {
                $entity->setCreatedAt(new \DateTimeImmutable());
            }
        }
    }

    #[Pure] public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'getUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }

    }


}