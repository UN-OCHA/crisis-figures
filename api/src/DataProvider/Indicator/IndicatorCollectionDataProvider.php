<?php

namespace App\DataProvider\Indicator;

use ApiPlatform\Core\Api\OperationType;
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
 */
class IndicatorCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    use IndicatorPresetCommons;

    private iterable $collectionExtensions;
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;
    private Request $currentRequest;

    /**
     * IndicatorCollectionDataProvider constructor.
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
        $preset = $this->getPresetFromContext(OperationType::COLLECTION, $context);

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
}
