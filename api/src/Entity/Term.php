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
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Indicator entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"term:output"},
 *         "enable_max_depth"=true,
 *         "force_eager"=false
 *     },
 *     denormalizationContext={
 *         "groups"={"term:input"}
 *     }
 * )
 * @ApiFilter(
 *     GroupFilter::class,
 *     arguments={
 *         "parameterName"="with",
 *         "overrideDefaultGroups"=false,
 *         "whitelist"={"vocabulary", "parent"}
 *     }
 * )
 * @ORM\Entity
 */
class Term
{
    use Accessor\Id;
    use Accessor\Name;
    use Accessor\Label;

    /**
     * @var int The entity Id
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ApiFilter(OrderFilter::class, properties={"id"})
     * @Groups({"terms", "term:output"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="A short name for the term to be used for filtering, etc...",
     * )
     * @ApiFilter(OrderFilter::class, properties={"name"})
     * @Groups({"terms", "term:output", "term:input"})
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="A label that describes the term."
     * )
     * @ApiFilter(OrderFilter::class, properties={"label"})
     * @Groups({"terms", "term:output", "term:input"})
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     */
    private $label;

    /**
     * @var string
     * @ApiProperty(
     *     description="An arbitrary value used for filtering."
     * )
     * @Groups({"term:output", "term:input"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     * @var array
     * @ApiProperty(
     *     description="One or more values of the indicator."
     * )
     * @ApiFilter(SearchFilter::class, properties={"indicators": "exact"})
     * @ORM\ManyToMany(targetEntity="Indicator", mappedBy="terms")
     */
    private $indicators;

    /**
     * @var Term
     * @ApiProperty(
     *     description="Related terms."
     * )
     * @ApiFilter(SearchFilter::class, properties={"parent.name": "exact"})
     * @Groups({"parent"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="Term", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var array
     * @ApiProperty(
     *     description="Child terms."
     * )
     * @Groups({"term:children"})
     * @ORM\OneToMany(targetEntity="Term", mappedBy="parent")
     */
    private $children;

    /**
     * @var Vocabulary
     * @ApiProperty(
     *     description="Related vocabulary."
     * )
     * @ApiFilter(SearchFilter::class, properties={"vocabulary.name": "exact"})
     * @Groups({"vocabulary"})
     * @MaxDepth(1)
     * @ORM\ManyToOne(targetEntity="Vocabulary", inversedBy="terms")
     */
    private $vocabulary;

    /**
     * Term constructor.
     */
    public function __construct()
    {
        $this->indicators = [];
        $this->children = [];
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
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

    public function getParent(): ?Term
    {
        return $this->parent;
    }

    public function setParent(Term $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getVocabulary(): ?Vocabulary
    {
        return $this->vocabulary;
    }

    public function setVocabulary(Vocabulary $vocabulary): void
    {
        $this->vocabulary = $vocabulary;
    }
}
