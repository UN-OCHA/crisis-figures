<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
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
 * @ApiFilter(
 *     GroupFilter::class,
 *     arguments={
 *         "parameterName"="with",
 *         "overrideDefaultGroups"=false,
 *         "whitelist"={"terms", "values", "vocabulary", "country"}
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
     * @ApiFilter(OrderFilter::class, properties={"id"})
     * @Groups({"indicators", "indicator:output"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="The indicator's name."
     * )
     * @ApiFilter(SearchFilter::class, properties={"name": "start"})
     * @ApiFilter(OrderFilter::class, properties={"name"})
     * @Groups({"indicators", "indicator:output", "indicator:input"})
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="The source organization."
     * )
     * @Groups({"indicators", "indicator:output", "indicator:input"})
     * @ApiFilter(OrderFilter::class, properties={"organization"})
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank
     */
    private $organization;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of indicator values.",
     *     fetchEager=true
     * )
     * @Groups({"values"})
     * @ORM\OneToMany(targetEntity="IndicatorValue", mappedBy="indicator")
     */
    private $values;

    /**
     * @var Country
     * @ApiProperty(
     *     description="The target country.",
     *     fetchEager=true
     * )
     * @ApiFilter(SearchFilter::class, properties={"country": "exact", "country.code": "exact"})
     * @ApiFilter(OrderFilter::class, properties={"country.name"})
     * @Groups({"country", "indicators", "indicator:output", "indicator:input"})
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
     * @Groups({"terms", "indicator:input"})
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
    public function getOrganization(): ?string
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

    public function addValue(IndicatorValue $value): self
    {
        if (is_array($this->values)) {
            $this->values[] = $value;
        }

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
