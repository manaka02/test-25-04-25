<?php


namespace App\Services;


use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use ReflectionClass;

use Symfony\Component\Serializer\SerializerInterface;

class UtilityServices
{
    private $entity;

    private ManagerRegistry $managerRegistry;
    private ParameterBagInterface $params;
    private SerializerInterface $serializer;

    public function __construct(ManagerRegistry $managerRegistry, ParameterBagInterface $params, SerializerInterface $serializer)
    {
        $this->managerRegistry = $managerRegistry;
        $this->params = $params;
        $this->serializer = $serializer;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }


    /**
     * @param mixed $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    public function getMethods()
    {
        $reflection = new ReflectionClass($this->entity);
        return array_filter($reflection->getMethods(), function ($method) {
            return strpos($method->name, 'get') === 0;
        });
    }

    public function setMethods()
    {
        $reflection = new ReflectionClass($this->entity);
        return array_filter($reflection->getMethods(), function ($method) {
            return strpos($method->name, 'set') === 0;
        });
    }

    public function getAttributes()
    {
        $reflection = new ReflectionClass($this->entity);
        $attributes = $reflection->getProperties();
        return array_filter($attributes, function ($attribute) {
            return $attribute->getName() !== 'id';
        });
    }

    public function getSetterMethod($attribute)
    {
        $reflection = new ReflectionClass($this->entity);
        $setter = 'set' . ucfirst($attribute);
        if ($reflection->hasMethod($setter)) {
            return $setter;
        }
        return null;
    }

}