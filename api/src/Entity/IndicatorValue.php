<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity as BehaviorEntity;
use Knp\DoctrineBehaviors\Model as BehaviorModel;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Traits\Accessor;

/**
 * The IndicatorValue entity.
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
 * @package App\Entity
 */
class IndicatorValue implements BehaviorEntity\TimestampableInterface {

    use Accessor\Id;
    use Accessor\Date;
    use Accessor\Value;
    use BehaviorModel\Timestampable\TimestampableTrait;

    /**
     * @var int The entity Id
     *
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var DateTimeInterface
     * @ApiProperty(
     *     description="The date on which the value is recorder."
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     */
    private $date;

    /**
     * @var float
     * @ApiProperty(
     *     description="A numeric value of an indicator."
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="decimal")
     */
    private $value;

    /**
     * @var string
     * @ApiProperty(
     *     description="The source URL of the value."
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string")
     */
    private $sourceUrl;

    /**
     * @return string
     */
    public function getSourceUrl(): string {
        return $this->sourceUrl;
    }

    /**
     * @param string $sourceUrl
     * @return self
     */
    public function setSourceUrl(string $sourceUrl): self {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }
}
