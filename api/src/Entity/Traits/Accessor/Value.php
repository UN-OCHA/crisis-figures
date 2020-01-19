<?php

namespace App\Entity\Traits\Accessor;


trait Value {
    /**
     * @return float
     */
    public function getValue(): float {
        return $this->value;
    }

    /**
     * @param float $value
     * @return self
     */
    public function setValue(float $value): self {
        $this->value = $value;
        return $this;
    }
}
