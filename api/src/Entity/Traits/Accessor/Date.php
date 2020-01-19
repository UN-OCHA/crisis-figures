<?php

namespace App\Entity\Traits\Accessor;

use DateTimeInterface;

trait Date {
    /**
     * @return DateTimeInterface
     */
    public function getDate(): DateTimeInterface {
        return $this->date;
    }

    /**
     * @param DateTimeInterface $date
     * @return self
     */
    public function setDate(DateTimeInterface $date): self {
        $this->date = $date;
        return $this;
    }
}
