<?php

namespace TradusBundle\Service\Utils;

class PagerService
{
    /**
     * We should rewrite this shit together with the pager twig and have a proper 'calculus'.
     *
     * @param $currentPage
     * @param $pageUrl
     * @param $totalResults
     * @return mixed
     */
    public function generatePager($currentPage, $pageUrl, $totalResults)
    {
        $maxResultsPerPage = 30;
        $maxPagers = 5;
        $totalPages = intval(ceil($totalResults / $maxResultsPerPage));
        $requestUrl = str_replace(['?page=%s&', '?page=%s', '&page=%s'], ['?', '', ''], $pageUrl);

        //defaults
        $result['total'] = $totalPages;
        $result['current'] = $currentPage;
        $result['ellipses']['next'] = false;
        $result['ellipses']['previous'] = false;

        if ($totalPages > $maxPagers + 1) {
            if ($currentPage < $totalPages - ceil($maxPagers / 2)) {
                $result['items']['last']['href'] = sprintf($pageUrl, $totalPages);
                $result['items']['last']['rel'] = 'last';

                $midpointlast = ceil(($currentPage + $totalPages) / 2) + 1;
                $result['items']['midpointlast']['href'] = sprintf($pageUrl, $midpointlast);
                $result['items']['midpointlast']['rel'] = 'midpointlast';
            }
            if ($currentPage > ceil($maxPagers / 2)) {
                $result['items']['first']['href'] = sprintf($requestUrl);
                $result['items']['first']['rel'] = 'first';

                if ($currentPage > ceil(($maxPagers + 2) / 2)) {
                    $midpointfirst = ceil($currentPage / 2) - 1;
                    $result['items']['midpointfirst']['href'] = sprintf($pageUrl, $midpointfirst);
                    $result['items']['midpointfirst']['rel'] = 'midpointfirst';
                }
            }
        }

        // previous url
        if ($currentPage > 1 && $currentPage <= $totalPages) {
            $result['ellipses']['previous'] = true;
            $result['items']['previous']['href'] = sprintf($pageUrl, ($currentPage - 1));
            $result['items']['previous']['rel'] = '';
            if (($currentPage - 1) == 1) {
                $result['items']['previous']['href'] = sprintf($requestUrl);
                $result['items']['previous']['rel'] = 'first';
            }
        }

        // next url
        if ($currentPage >= 1 && $currentPage < $totalPages) {
            $result['ellipses']['next'] = true;
            $result['items']['next']['href'] = sprintf($pageUrl, ($currentPage + 1));
            $result['items']['next']['rel'] = '';
            if ($currentPage + 1 == $totalPages) {
                $result['items']['next']['rel'] = 'last';
            }
        }

        // Create pages
        $pages = [];
        $pageCounter = 0;
        $pageNumber = intval($currentPage - floor($maxPagers / 2));

        // this logic makes little sense but...it works
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        if ($pageNumber + $maxPagers >= $totalPages) {
            $pageNumber = $totalPages - $maxPagers + 1;
        }

        if ($totalPages <= $maxPagers + 1) {
            $pageNumber = 1;
        }

        while ($pageCounter < $maxPagers + 1) {
            if ($pageNumber >= 1 && $pageNumber <= $totalPages) {
                $pages[$pageNumber]['href'] = sprintf($pageUrl, $pageNumber);
                if ($pageNumber == 1) {
                    $pages[$pageNumber]['href'] = sprintf($requestUrl);
                    $pages[$pageNumber]['rel'] = 'first';
                }
                if ($pageNumber == $totalPages) {
                    $pages[$pageNumber]['rel'] = 'last';
                }
            }
            $pageCounter++;
            $pageNumber++;
        }

        $result['items']['pages'] = $pages;

        return $result;
    }
}
