<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;


/**
 * Interface IFilter
 */
interface IFilter
{
    /**
     * @return Filter
     */
    public function getFilter(): Filter;

    public function getFilterValue();

    public function getActAs();

    public function getDisplayValue();

    public function loadData();

    public function isEmpty();

    public function isIgnoreFilterInWhereSqlEnabled();
}