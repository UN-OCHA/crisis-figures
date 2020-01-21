<?php

namespace App\Entity\Traits\Accessor;

use DateTimeInterface;

trait Date
{
    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
