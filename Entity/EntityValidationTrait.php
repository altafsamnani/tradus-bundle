<?php

namespace TradusBundle\Entity;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

/**
 * Trait EntityValidationTrait.
 */
trait EntityValidationTrait
{
    /** @var array */
    protected $messages;

    /**
     * Function for validating a given entity.
     *
     * @param $entity
     *
     * @throws UnprocessableEntityHttpException
     */
    public function validateEntity($entity)
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        $violationList = $validator->validate($entity);
        if ($violationList->count() > 0) {
            $this->messages = [];

            /** @var ConstraintViolation $violationListItem */
            foreach ($violationList as $violationListItem) {
                $this->messages[] = $violationListItem->getPropertyPath().': '.$violationListItem->getMessage();
            }

            throw new UnprocessableEntityHttpException(implode(', ', $this->messages));
        }
    }
}
