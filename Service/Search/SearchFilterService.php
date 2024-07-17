<?php

namespace TradusBundle\Service\Search;

/**
 * Class SearchFilterService.
 */
class SearchFilterService
{
    /**
     * Function getYearFilters.
     * @return array
     */
    public function getYearFilters(): array
    {
        $stepsArray = [
            1920 => 10,
            1990 => 5,
            2000 => 1,
        ];

        return $this->getRangeList($stepsArray, date('Y'));
    }

    /**
     * @return array
     */
    public function getPriceFilters(): array
    {
        return [2000, 3000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 50000, 65000,
            80000, 100000, 200000, 500000, 1000000, 7500000, ];
    }

    /**
     * @return array
     */
    public function getMileageFilters(): array
    {
        return [20000, 35000, 50000, 75000, 100000, 125000, 150000, 200000, 250000, 300000, 600000, 900000];
    }

    /**
     * @return array
     */
    public function getWeightFilters(): array
    {
        return [3500, 7500, 12000, 18000, 26000, 32000, 35000, 40000, 44000];
    }

    /* @param array $stepsArray
     * @param int $last
     *
     * @return array
     */
    public function getRangeList(array $stepsArray, int $last)
    {
        $finalArray = [];
        $start = $step = '';
        foreach ($stepsArray as $key => $value) {
            if ($start) {
                $end = $key;
            } else {
                $start = $key;
            }
            if (empty($step)) {
                $step = $value;
            }
            if (! empty($start) && ! empty($end) && ! empty($step)) {
                $finalArray = array_merge($finalArray, range($start, $end, $step));
                $start = $end;
                $end = 0;
                $step = $value;
            }
        }
        $finalArray = array_merge($finalArray, range($start, $last, $step));

        return array_reverse(array_unique($finalArray));
    }
}
