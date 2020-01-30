<?php

namespace App\Entity\Traits\Accessor;

trait Terms
{
    public function getTerms()
    {
        return $this->terms;
    }

    public function setTerms(array $terms): self
    {
        $this->terms = $terms;

        return $this;
    }
}
