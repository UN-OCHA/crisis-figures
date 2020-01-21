<?php

namespace App\Entity\Traits\Accessor;

use DateTimeInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableMethodsTrait;

trait Timestampable
{
    use TimestampableMethodsTrait;

    /**
     * @var DateTimeInterface
     */
    private $createdAt;

    /**
     * @var DateTimeInterface
     */
    private $updatedAt;
}
