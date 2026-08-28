<?php

namespace App\Layouts;

use Netgen\Layouts\Item\ValueConverterInterface;
use App\Entity\Recipe;

class RecipeValueConverter implements ValueConverterInterface
{

    public function supports(object $object): bool
    {
        return $object instanceof Recipe;
    }

    public function getValueType(object $object): string
    {
        return 'doctrine_recipe';
    }

    public function getId(object $object)
    {
        return $object->getId();
    }

    public function getRemoteId(object $object)
    {
        return $this->getId($object);
    }

    public function getName(object $object): string
    {
        assert($object instanceof Recipe);
        return $object->getName();
    }

    public function getIsVisible(object $object): bool
    {
        return true;
    }

    public function getObject(object $object): object
    {
        return $object;
    }
}