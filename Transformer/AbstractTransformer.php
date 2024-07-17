<?php

namespace TradusBundle\Transformer;

/**
 * Class AbstractTransformer.
 */
class AbstractTransformer
{
    public const FORCE_UPGRADE_FALSE = false;
    public const FORCE_UPGRADE_TRUE = true;
    public const FORCE_UPGRADE_VERSION = '76';

    /**
     * clean text for html.
     *
     * @param string $html
     *
     * @return string
     */
    public function cleanText(string $html): string
    {
        /* Because of the mixed content scrapping we have the following patterns to replace the tags */
        $replacePattern = [
            'emptySpaces' => [
                'tag' => ['</li>', '<ul>', '\n'],
                'pattern' => '',
            ],
            'newLines' => [
                'tag' => ['<br />', '<br>', '<br/>'],
                'pattern' => "\r\n",
            ],
            'twoNewLines' => [
                'tag' => ['</ul>'],
                'pattern' => "\r\n",
            ],
            'newLineWithHyphen' => [
                'tag' => ['<li>'],
                'pattern' => "\r\n - ",
            ],
        ];

        foreach ($replacePattern as $paternType => $patternValues) {
            $html = str_ireplace($patternValues['tag'], $patternValues['pattern'], $html);
        }

        return strip_tags($html);
    }

    /**
     * Regex to convert snake to camelcase.
     *
     * @param string $snakeCaseString
     *
     * @return string
     */
    public function underToCamel(string $snakeCaseString): string
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $snakeCaseString))));
    }

    /**
     * Converts Snakecase to Camelcase.
     *
     * @param array $snakeCaseList
     *
     * @return array
     */
    public function snakeCaseToCamelCase(array $snakeCaseList): array
    {
        $result = [];
        foreach ($snakeCaseList as $key => $value) {
            $result[$this->underToCamel($key)] = is_array($value) ? $this->snakeCaseToCamelCase($value) : $value;
        }

        return $result;
    }

    public function getForceUpgradeFlag($userAgent)
    {
        if (preg_match_all("/\((.*?)\)/", $userAgent, $buildNumber)) {
            return (isset($buildNumber) && $buildNumber[1][0] <= self::FORCE_UPGRADE_VERSION) ? self::FORCE_UPGRADE_TRUE : self::FORCE_UPGRADE_FALSE;
        }

        return self::FORCE_UPGRADE_FALSE;
    }
}
