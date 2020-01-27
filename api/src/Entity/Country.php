<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Traits\Accessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Country entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"country:output"}
 *     },
 *     denormalizationContext={
 *         "groups"={"country:input"}
 *     }
 * )
 * @ORM\Entity
 */
class Country
{
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
     * @Groups({"country:output", "country:input"})
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="The officially assigned ISO 3166-1 alpha-3 country code.",
     * )
     * @Groups({"country:output", "country:input"})
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $code;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of Indicator entities associated with the country."
     * )
     * @Groups({"country:output"})
     * @ORM\OneToMany(targetEntity="Indicator", mappedBy="country")
     */
    private $indicators;

    /**
     * Country constructor.
     */
    public function __construct()
    {
        $this->indicators = [];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return array
     */
    public function getIndicators()
    {
        return $this->indicators;
    }

    public function setIndicators(array $indicators): self
    {
        $this->indicators = $indicators;

        return $this;
    }
}
