<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use App\Entity\Indicator;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extend the default CollectionDataProvider functionality for Indicator
 * entities in order to customize the serialization process.
 *
 * This data provider implements the Indicators Presets requirement through
 * which API clients can request Indicator collections to be filtered and
 * formatted according to predefined criteria.
 *
 * @package App\DataProvider
 */
class IndicatorDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const PRESET_HISTORY = 'history';
    public const PRESET_LATEST = 'latest';

    private iterable $collectionExtensions;
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;
    private Request $currentRequest;

    /**
     * IndicatorDataProvider constructor.
     *
     * @param \Doctrine\Common\Persistence\ManagerRegistry $managerRegistry
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param iterable $collectionExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, iterable $collectionExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
        $this->requestStack = $requestStack;
        $this->currentRequest = $this->requestStack->getCurrentRequest();
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
     * @throws RuntimeException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        /** @var $queryBuilder QueryBuilder */

        // Create a QueryBuilder instance
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        // Delegate query building to API Platform's built-in extensions so this
        // data provider can support filtering, pagination, etc...
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName)) {
                // The QueryResultCollection extension wraps the entire DQL
                // query with another `SELECT DISTINCT` query that produces
                // scalar values in the result.
                // ``` $extension->getResult($queryBuilder, ...);```
                //
                // So, we skip that extension and apply our own query mutations
                // at this stage.

                // Retrieve the `preset` parameter from the request.
                $preset = $this->currentRequest->query->get('preset');
                $this->applyPresetMutations($queryBuilder, $preset);
            }
        }

        $result = $queryBuilder->getQuery()->getResult();
        // Adjust the result set by removing the extra fields used for
        // aggregation and keeping the hydrated objects only which are added in
        // index `"0"` of each item in the result set.
        if (is_array($result) && count($result) > 0 && is_array($result[0])) {
            return array_map(fn($entry) => ($entry['0'] ?? $entry), $result);
        }

        return $result;
    }

    /**
     * Modify the query to aggregate indicator values according to the
     * provided preset.
     *
     * @param QueryBuilder $queryBuilder
     * @param string|null $preset
     */
    protected function applyPresetMutations(QueryBuilder $queryBuilder, ?string $preset)
    {
        /* @var Join[] $joinsGroup */

        // Currently only a `latest` preset requires mutating the query since
        // the `history` preset fetches all IndicatorValues, which is the
        // default behavior.
        if ($preset !== self::PRESET_LATEST) {
            return;
        }

        $joins = $queryBuilder->getDQLPart('join');

        // Find a Join that targets the `values` table
        foreach ($joins as $joinSetAlias => $joinsGroup) {
            foreach ($joinsGroup as $jIndex => $join) {
                if ($join->getJoin() === "{$joinSetAlias}.values") {
                    $this->aggregateByLatestValue($queryBuilder, $join);
                    break 2;
                }
            }
        }
    }

    /**
     * Perform the actual injection and/or modification of DQL parts in order to
     * group the results based on the target aggregation field.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param Join $join The IndicatorValue's Join
     */
    protected function aggregateByLatestValue(QueryBuilder $queryBuilder, Join $join)
    {
        // Find aliases of main entity and the current Join (IndicatorValues)
        $originAlias = $queryBuilder->getRootAliases()[0];
        $mainValuesAlias = $join->getAlias();

        // The GROUP BY clause of the query must include the `id` fields of main
        // and joined entities to avoid running into the `only_full_group_by`
        // MySQL restriction. Then, append the aggregation target field (date).
        $groupByFields = array_map(fn($alias) => "{$alias}.id", $queryBuilder->getAllAliases());
        array_push($groupByFields, "${mainValuesAlias}.date");

        $queryBuilder
            ->addSelect('MAX(iv.date) AS maxDate')
            ->leftJoin("${originAlias}.values", 'iv')
            ->groupBy(implode(', ', $groupByFields))
            ->having("${mainValuesAlias}.date = maxDate");
    }
}
