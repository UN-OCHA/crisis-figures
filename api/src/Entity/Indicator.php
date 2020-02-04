<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\Traits\Accessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Indicator entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"indicator:output"}
 *     },
 *     denormalizationContext={
 *         "groups"={"indicator:input"}
 *     }
 * )
 * @ORM\Entity
 */
class Indicator
{
    use Accessor\Id;
    use Accessor\Name;
    use Accessor\Terms;

    /**
     * @var int The entity Id
     * @ORM\Id
     * @ORM\GeneratedValue
     * @Groups({"indicator:output"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="The indicator's name."
     * )
     * @Groups({"indicator:output", "indicator:input"})
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="The source organization."
     * )
     * @Groups({"indicator:output", "indicator:input"})
     * @ORM\Column
     */
    private $organization;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of indicator values."
     * )
     * @Groups({"indicator:output", "indicator:input"})
     * @ORM\OneToMany(targetEntity="IndicatorValue", mappedBy="indicator")
     */
    private $values;

    /**
     * @var Country
     * @ApiProperty(
     *     description="The target country."
     * )
     * @Groups({"indicator:output", "indicator:input"})
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="indicators")
     * @Assert\NotBlank
     */
    private $country;

    /**
     * @var array
     * @ApiProperty(
     *     description="Related terms."
     * )
     * @ApiFilter(SearchFilter::class, properties={"terms.name": "exact"})
     * @Groups({"indicator:output", "indicator:input"})
     * @ORM\ManyToMany(targetEntity="Term", inversedBy="indicators")
     * @ORM\JoinTable(name="indicators_terms")
     */
    private $terms;

    /**
     * Indicator constructor.
     */
    public function __construct()
    {
        $this->values = [];
        $this->terms = [];
    }

    /**
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry(Country $country): self
    {
        $this->country = $country;

        return $this;
    }
}
