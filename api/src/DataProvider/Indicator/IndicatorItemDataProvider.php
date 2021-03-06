<?php

// api/src/DataProvider/BlogPostItemDataProvider.php

namespace App\DataProvider\Indicator;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use App\Entity\Indicator;
use App\Entity\IndicatorValue;
use App\Repository\IndicatorValueRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Extend the default ItemDataProvider functionality for Indicator
 * entities in order to customize the serialization process.
 *
 * This data provider implements the Indicators Presets requirement through
 * which API clients can request an Indicator item to be filtered and
 * formatted according to predefined criteria.
 */
final class IndicatorItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    use IdentifierManagerTrait;
    use IndicatorPresetCommons;

    private ManagerRegistry $managerRegistry;
    private iterable $itemExtensions;

    /**
     * IndicatorItemDataProvider constructor.
     *
     * @param \Doctrine\Common\Persistence\ManagerRegistry $managerRegistry
     * @param iterable $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Indicator::class === $resourceClass;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        /** @var QueryBuilder $queryBuilder */
        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $identifiers = ['id' => $id];

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        // Create QueryBuilder
        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);

        /** @var $valuesRepo IndicatorValueRepository */
        $valuesRepo = $manager->getRepository(IndicatorValue::class);
        $preset = $this->getPresetFromContext(OperationType::ITEM, $context);
        $this->addWhereForIdentifiers($identifiers, $queryBuilder, $doctrineClassMetadata);

        // Execute Doctrine extensions associated with "item" operation type
        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);

            if ($extension instanceof ContextAwareQueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            } else if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName)) {
                return $extension->getResult($queryBuilder);
            }
        }

        // Update the query to either fetch the most recent value per Indicator,
        // using the `date` field; or fetch all indicator values if the `latest`
        // preset is not set.
        $valuesRepo->applyPresetMutations($queryBuilder, $preset);
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        // Adjust the result set by removing the extra fields used for
        // aggregation and keeping the hydrated objects only which are added in
        // index `"0"` of each item in the result set.
        if (is_array($result) && isset($result[0]) && ($result[0] instanceof Indicator)) {
            $result = $result[0];
        }

        return $result;
    }

    /**
     * Add WHERE conditions to the query for one or more identifiers (simple or composite).
     *
     * @param array $identifiers
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     */
    private function addWhereForIdentifiers(array $identifiers, QueryBuilder $queryBuilder, ClassMetadata $classMetadata)
    {
        $alias = $queryBuilder->getRootAliases()[0];
        foreach ($identifiers as $identifier => $value) {
            $placeholder = ':id_'.$identifier;
            $expression = $queryBuilder->expr()->eq(
                "{$alias}.{$identifier}",
                $placeholder
            );

            $queryBuilder
                ->andWhere($expression)
                ->setParameter($placeholder, $value, $classMetadata->getTypeOfField($identifier));
        }
    }
}
