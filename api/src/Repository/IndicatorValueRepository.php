<?php

namespace App\Repository;

use App\Entity\IndicatorValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class IndicatorValueRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Doctrine entity manager
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndicatorValue::class);
    }

    /**
     * Get the latest IndicatorValue per Indicator using the `$indicatorsIds`.
     *
     * @param array $indicatorsIds a list of Indicator IDs to filter values by
     * @return IndicatorValue[] An array of IndicatorValue(s) sorted in the same
     *                          order of items in `$indicatorsIds`
     */
    public function getLatestOfEachIndicator(array $indicatorsIds)
    {
        $dql = "
            SELECT iv
            FROM {$this->_entityName} iv
            WHERE CONCAT(iv.date, '|', iv.createdAt, '|', iv.id) IN (
                SELECT
                    MAX(CONCAT(iv2.date, '|', iv2.createdAt, '|', iv2.id)) AS maxDate
                FROM {$this->_entityName} iv2
                WHERE iv2.indicator IN (:indicatorsIds)
                GROUP BY iv2.indicator
            )
            ORDER BY FIELD(iv.indicator, :indicatorsIds)
        ";

        return $this
            ->getEntityManager()
            ->createQuery($dql)
            ->setParameter(':indicatorsIds', $indicatorsIds)
            ->execute();
    }

    /**
     * ** This is only suitable for use with operations of ITEM type **.
     *
     * Modify the query within `$queryBuilder` to aggregate indicator values
     * according to the provided `$preset`.
     *
     * @param QueryBuilder $queryBuilder QueryBuilder instance
     * @param string|null  $preset       Preset value
     */
    public function applyPresetMutations(QueryBuilder $queryBuilder, ?string $preset)
    {
        /* @var Join[] $joinsGroup */

        // Currently only a `latest` preset requires mutating the query since
        // the `history` preset fetches all IndicatorValues, which is the
        // default behavior.
        if (IndicatorValue::PRESET_LATEST !== $preset) {
            return;
        }

        $joins = $queryBuilder->getDQLPart('join');

        // Find a Join that targets the `values` table
        foreach ($joins as $joinSetAlias => $joinsGroup) {
            foreach ($joinsGroup as $jIndex => $join) {
                if ($join->getJoin() === "{$joinSetAlias}.values") {
                    // Find aliases of main entity and the current Join (IndicatorValues)
                    $valuesAlias = $join->getAlias();
                    $queryBuilder
                        ->addSelect("CONCAT({$valuesAlias}.date, '|', {$valuesAlias}.createdAt, '|', {$valuesAlias}.id) AS maxDate")
                        ->orderBy('maxDate', 'DESC')
                        ->setMaxResults(1);
                    break 2;
                }
            }
        }
    }
}
