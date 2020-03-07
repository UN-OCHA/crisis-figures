<?php

namespace App\DataProvider\Indicator;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use App\Entity\Indicator;
use App\Entity\IndicatorValue;
use App\Repository\IndicatorValueRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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
     * @throws \Exception
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        /** @var $queryBuilder QueryBuilder */

        // Create a QueryBuilder instance
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();
        $result = null;

        // Delegate query building to API Platform's built-in extensions so this
        // data provider can support filtering, pagination, etc...
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            if ($extension instanceof ContextAwareQueryResultCollectionExtensionInterface
                && $extension->supportsResult($resourceClass, $operationName)) {
                $result = $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }

        // If the `values` serialization group is set, execute a second query to
        // fetch the most recent IndicatorValue per Indicator.
        //
        // By default, `values` are not serialized with indicators in collection
        // operations.
        if (array_search('values', $context['groups']) !== false && $result instanceof Paginator) {
            // Detach Indicator instances form the persistence manager to avoid
            // saving data coincidentally.
            $manager->clear(Indicator::class);

            // Create a plain array of Indicators and another for their IDs
            /** @var Indicator $indicator */
            $indicators = [];
            $indicatorsIds = [];
            foreach ($result->getIterator() as $indicator) {
                $indicatorsIds[] = $indicator->getId();
                $indicators[] = $indicator;
            }

            // Query database for latest IndicatorValue for each indicator
            // represented by its ID in `$indicatorsIds`
            /** @var $valuesRepo IndicatorValueRepository */
            $valuesRepo = $this->managerRegistry->getRepository(IndicatorValue::class);
            $values = $valuesRepo->getLatestOfEachIndicator($indicatorsIds);

            // Finally, manually assign each IndicatorValue in `$values` to
            // its corresponding Indicator in `$indicators`. Note that both
            // lists are queried and fetched in the same order of the IDs in
            // `$indicatorIds`, in order to improve the performance of this
            // function.
            $this->assignValuesToIndicators($values, $indicators);
        }

        return $result;
    }
}
