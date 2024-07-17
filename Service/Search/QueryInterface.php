<?php

namespace TradusBundle\Service\Search;

/**
 * Interface QueryInterface.
 */
interface QueryInterface
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR = 'OR';
    public const OPERATOR_SPACE = ' ';
    public const OPERATOR_NOT = '-';

    /**
     * Solr sort mode descending.
     */
    public const SORT_DESC = 'desc';

    /**
     * Solr sort mode ascending.
     */
    public const SORT_ASC = 'asc';

    /**
     * Solr type query's
     * Used to build the url in the Request object.
     */
    public const TYPE_SELECT = 'select';
    public const TYPE_SUGGEST = 'suggest';

    /**
     * @param Request $request
     * @return Request
     */
    public function createRequest(Request $request): Request;

    /**
     * @return mixed
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type);
}
