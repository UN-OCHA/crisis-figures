<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Traits\Accessor;

/**
 * The Country entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"read"}
 *     },
 *     denormalizationContext={
 *         "groups"={"write"}
 *     }
 * )
 * @ORM\Entity
 */
class Country {

    use Accessor\Id;
    use Accessor\Name;

    /**
     * @var int The entity Id
     * @ApiProperty
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty
     * @Groups({"read", "write"})
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="The officially assigned ISO 3166-1 alpha-3 country code.",
     * )
     * @Groups({"read", "write"})
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $iso3;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of Indicator entities associated with the country."
     * )
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="Indicator", mappedBy="country")
     */
    private $indicators;

    /**
     * Country constructor.
     */
    public function __construct() {
        $this->indicators = [];
    }

    /**
     * @return string
     */
    public function getIso3(): string {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     * @return self
     */
    public function setIso3(string $iso3): self {
        $this->iso3 = $iso3;
        return $this;
    }

    /**
     * @return array
     */
    public function getIndicators() {
        return $this->indicators;
    }

    /**
     * @param array $indicators
     * @return self
     */
    public function setIndicators(array $indicators): self {
        $this->indicators = $indicators;
        return $this;
    }
}
