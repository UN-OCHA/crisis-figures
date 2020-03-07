<?php

namespace App\DataProvider\Indicator;

use ApiPlatform\Core\Api\OperationType;
use App\Entity\Indicator;
use App\Entity\IndicatorValue;

/**
 * This trait contains data provider functions that are shared between
 * collection and item providers.
 */
trait IndicatorPresetCommons
{
    /**
     * Extract the `preset` value from context.
     *
     * @param string $operationType Type of operation
     * @param array  $context       Context related
     *
     * @return string|null Context value or NULL
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
            $preset = IndicatorValue::PRESET_LATEST;
        }

        return $preset;
    }

    /**
     * Assign IndicatorValue instances to corresponding Indicator instances.
     *
     * The function modifies the `$indicators` argument with `$values` assigned
     * to their corresponding indicators.
     *
     * Both lists are assumed to be sorted to improve the performance of this
     * function. Note that the number of IndicatorValue(s) in `$values` does not
     * necessarily need match the number of `$indicators`; though, they are
     * still assumed to follow the same order of:
     * ``` indicatorValue.indicatorId => indicator.id ```
     *
     * @param IndicatorValue[] $values     IndicatorValue instances
     * @param Indicator[]      $indicators Indicator instances (in-out parameter)
     */
    protected function assignValuesToIndicators(array $values, array &$indicators): void
    {
        foreach ($indicators as $indicator) {
            foreach ($values as $valIndex => $value) {
                if ($value->getIndicator()->getId() === $indicator->getId()) {
                    $indicator->setValues([$value]);
                    unset($values[$valIndex]);
                }
                break;
            }

            if (empty($values)) {
                break;
            }
        }
    }
}
