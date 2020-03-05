<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
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

    /** Types of Indicator presets */
    const PRESET_LATEST = 'latest';

    /**
     * @var int The entity Id
     * @ORM\Id
     * @ORM\GeneratedValue
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
     * @ORM\Column(type="string", nullable=true)
     */
    private $organization;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of indicator values."
     * )
     * @Groups({"values"})
     * @ORM\OneToMany(targetEntity="IndicatorValue", mappedBy="indicator")
     */
    private $values;

    /**
     * @var Country
     * @ApiProperty(
     *     description="The target country."
     * )
     * @ApiFilter(SearchFilter::class, properties={"country": "exact", "country.code": "exact"})
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
     * @Groups({"terms"})
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
