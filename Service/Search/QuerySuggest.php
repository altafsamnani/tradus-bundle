<?php

namespace TradusBundle\Service\Search;

/**
 * Class QuerySuggest.
 */
class QuerySuggest extends BaseQuery implements QueryInterface
{
    const DEFAULT_BUILD = false;
    const DEFAULT_DICTIONARY = 'mySuggester';
    const DEFAULT_QUERY = '';

    /**
     * Query Type, like SELECT or UPDATE, DELETE.
     * @var string
     */
    protected $type = self::TYPE_SUGGEST;

    /**
     * Default options.
     * @var array
     */
    protected $options = [
        'build'            => self::DEFAULT_BUILD,
        'dictionary'       => self::DEFAULT_DICTIONARY,
        'q'                => self::DEFAULT_QUERY,
    ];

    /**
     * @param bool $value
     * @return $this
     */
    public function setBuild(bool $value)
    {
        $this->setOption('build', $value);

        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getBuild()
    {
        return $this->getOption('build');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDictionary(string $value)
    {
        $this->setOption('dictionary', $value);

        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getDictionary()
    {
        return $this->getOption('dictionary');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setQuery(string $value, array $bindValues = null)
    {
        if ($bindValues) {
            $value = vsprintf($value, $bindValues);
        }

        $this->setOption('q', $value);

        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getQuery()
    {
        return $this->getOption('q');
    }

    public function addQuery(string $field, $value, string $operator = self::OPERATOR_AND)
    {
        if (! empty($value) && ! empty($field)) {
            if (is_string($value)) {
                $value = $this->escapePhrase($value);
            }

            $this->addRawQuery($field, $value, $operator);
        }

        return $this;
    }

    /**
     * Escapes the input.
     *
     * @param $input
     * @return string | array
     */
    public static function escapePhrase($input)
    {
        if (is_array($input)) {
            $value = [];
            foreach ($input as $item) {
                array_push($value, '"'.preg_replace('/("|\\\)/', '\\\$1', $item).'"');
            }

            return $value;
        } else {
            return '"'.preg_replace('/("|\\\)/', '\\\$1', $input).'"';
        }
    }

    /**
     * Add a field to the query with operator (AND OR).
     *
     * @param string $field
     * @param array|string $value
     * @param string $operator
     * @return $this
     */
    public function addRawQuery(
        string $field,
        $value,
        string $operator = self::OPERATOR_AND,
        string $operatorArray = self::OPERATOR_OR
    ) {
        $field = trim($field).':%s';
        $query = $this->getQuery();

        if (is_string($value)) {
            $value = trim($value);
        }
        if (is_array($value)) {
            $value = $this->escapePhrase($value);
            $value = '('.implode(' '.$operatorArray.' ', $value).')';
        }

        if ($query == self::DEFAULT_QUERY) {
            $this->setQuery($field, [$value]);
        } else {
            $this->setQuery($query.' '.$operator.' '.$field, [$value]);
        }

        return $this;
    }

    /**
     * Creates request parameters for SOLR.
     *
     * @param Request $request
     * @return Request
     */
    public function createRequest(Request $request): Request
    {
        $request->setType($this->getType());

        $request->addParam('suggest', true);
        $request->addParam('suggest.build', $this->getBuild());
        $request->addParam('suggest.dictionary', $this->getDictionary());
        $request->addParam('suggest.q', $this->getQuery());
        $request->addParam('wt', 'json');

        return $request;
    }
}
