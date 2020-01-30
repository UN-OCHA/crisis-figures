<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Traits\Accessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Vocabulary entity.
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"vocabulary:output"}
 *     },
 *     denormalizationContext={
 *         "groups"={"vocabulary:input"}
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
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ApiProperty(
     *     description="A short name for the vocabulary to be used for filtering, etc...",
     * )
     * @Groups({"vocabulary:output", "vocabulary:input"})
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     * @ApiProperty(
     *     description="A label that describes the vocabulary."
     * )
     * @Groups({"term:output", "term:input"})
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     */
    private $label;

    /**
     * @var array
     * @ApiProperty(
     *     description="A list of terms associated with the vocabulary."
     * )
     * @Groups({"vocabulary:output"})
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
