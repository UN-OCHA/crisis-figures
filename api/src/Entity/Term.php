<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Traits\Accessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Indicator entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"term:output"}
 *     },
 *     denormalizationContext={
 *         "groups"={"term:input"}
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
     * @Groups({"term:output"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="A short name for the term to be used for filtering, etc...",
     * )
     * @Groups({"vocabulary:output", "vocabulary:input"})
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="A label that describes the term."
     * )
     * @Groups({"term:output", "term:input"})
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
     * @Groups({"term:output", "term:input"})
     * @ORM\ManyToMany(targetEntity="Indicator", mappedBy="terms")
     */
    private $indicators;

    /**
     * @var Term
     * @ApiProperty(
     *     description="Related terms."
     * )
     * @Groups({"term:output", "term:input"})
     * @ORM\ManyToOne(targetEntity="Term", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var array
     * @ApiProperty(
     *     description="Child terms."
     * )
     * @Groups({"term:output", "term:input"})
     * @ORM\OneToMany(targetEntity="Term", mappedBy="parent")
     */
    private $children;

    /**
     * @var Vocabulary
     * @ApiProperty(
     *     description="Related vocabulary."
     * )
     * @Groups({"term:output", "term:input"})
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
