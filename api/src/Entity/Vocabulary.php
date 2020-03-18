<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use App\Entity\Traits\Accessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Vocabulary entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"vocabulary:output"},
 *         "enable_max_depth"=true,
 *         "force_eager"=false
 *     },
 *     denormalizationContext={
 *         "groups"={"vocabulary:input"}
 *     }
 * )
 * @ApiFilter(
 *     GroupFilter::class,
 *     arguments={
 *         "parameterName"="with",
 *         "overrideDefaultGroups"=false,
 *         "whitelist"={"terms"}
 *     }
 * )
 * @ORM\Entity
 */
class Vocabulary
{
    use Accessor\Id;
    use Accessor\Name;
    use Accessor\Label;
    use Accessor\Terms;

    /**
     * @var int The entity Id
     * @ApiProperty
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ApiFilter(OrderFilter::class, properties={"id"})
     * @Groups({"vocabulary", "vocabulary:output"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="A short name for the vocabulary to be used for filtering, etc...",
     * )
     * @ApiFilter(OrderFilter::class, properties={"name"})
     * @Groups({"vocabulary", "vocabulary:output", "vocabulary:input"})
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="A label that describes the vocabulary."
     * )
     * @ApiFilter(OrderFilter::class, properties={"label"})
     * @Groups({"vocabulary", "vocabulary:output", "vocabulary:input"})
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     */
    private $label;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of terms associated with the vocabulary."
     * )
     * @MaxDepth(1)
     * @ORM\OneToMany(targetEntity="Term", mappedBy="vocabulary")
     */
    private $terms;

    /**
     * Vocabulary constructor.
     */
    public function __construct()
    {
        $this->terms = [];
    }
}
