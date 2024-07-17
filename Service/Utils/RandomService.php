<?php

namespace TradusBundle\Service\Utils;

class RandomService
{
    /**
     * @param int $maxIndex
     * @param int $totalIndexes
     * @return array
     */
    public static function getRandomIndexes(int $maxIndex, int $totalIndexes): array
    {
        if ($maxIndex < $totalIndexes || $maxIndex == 0 || $totalIndexes == 0) {
            return [];
        }
        $numbers = range(0, $maxIndex);
        for ($i = 0; $i < $totalIndexes; $i++) {
            $randomIndex = mt_rand($i, $maxIndex);
            $temp = $numbers[$i];
            $numbers[$i] = $numbers[$randomIndex];
            $numbers[$randomIndex] = $temp;
        }

        return array_slice($numbers, 0, $totalIndexes);
    }
}
