<?php

namespace App\DataProvider\Indicator;

use ApiPlatform\Core\Api\OperationType;
use App\Entity\Indicator;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * This trait contains data provider functions that are shared between
 * collection and item providers.
 */
trait IndicatorPresetCommons
{
    /**
     * Extract the `preset` value from context.
     *
     * @param string $operationType
     * @param array $context
     * @return string|null
     */
    public function getPresetFromContext(string $operationType, array $context = []): ?string
    {
        $preset = null;

        // Assign the `preset` value from context filters
        if (isset($context['filters'], $context['filters']['preset'])) {
            $preset = $context['filters']['preset'];
        }

        // Force `preset` as `latest`, if the `values` serialization group is set
        if (OperationType::COLLECTION === $operationType &&
            isset($context['groups']) &&
            false !== array_search('values', $context['groups'])) {
            $preset = Indicator::PRESET_LATEST;
        }

        return $preset;
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
        if ($preset !== Indicator::PRESET_LATEST) {
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
            ->addSelect('MAX(CONCAT(iv.date, iv.createdAt, iv.id)) AS maxDate')
            ->leftJoin("${originAlias}.values", 'iv')
            ->groupBy(implode(', ', $groupByFields))
            ->having("CONCAT(${mainValuesAlias}.date, ${mainValuesAlias}.createdAt, ${mainValuesAlias}.id) = maxDate");
    }
}
