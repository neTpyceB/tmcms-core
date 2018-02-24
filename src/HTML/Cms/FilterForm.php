<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use TMCms\HTML\Cms\Filter\Hidden;

\defined('INC') or exit;

/**
 * Class FilterForm
 * @package TMCms\HTML\Cms
 */
class FilterForm extends CmsForm
{
    /**
     * @var array
     */
    protected $fields;
    private $applied = false;

    /**
     * @var array
     */
    private $provider;
    private $filter_caption = '';
    private $open = false;

    /**
     * FilterForm constructor.
     */
    public function __construct()
    {
        $this->setMethod('GET');

        $get = $_GET;

        unset($get['page']);

        $this->provider = $get;
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * @return $this
     */
    public function enableExpandedByDefault()
    {
        $this->open = true;

        return $this;
    }

    /**
     * @param array $provider
     *
     * @return $this
     */
    public function setProvider(array &$provider)
    {
        $this->provider = &$provider;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setCaption(string $data)
    {
        $this->filter_caption = trim($data);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $default_action = '?p=' . P . (P_DO ? '&do=' . $_GET['do'] : '');

        $this->setAction($default_action);
        $this->setButtonSubmit(__('Filter'));
        $this->disableFullView();

        if ($this->fields) {
            $temp_provider_data = $this->provider;

            // Unset temp data
            /* @var $field CmsFormElement */
            foreach ($this->fields as $field) {
                if (isset($temp_provider_data[$field->getElement()->getName()])) {
                    unset($temp_provider_data[$field->getElement()->getName()]);
                }
                if (isset($temp_provider_data[$field->getElement()->getName() . '_ids'])) {
                    unset($temp_provider_data[$field->getElement()->getName() . '_ids']);
                }
            }

            foreach ($temp_provider_data as $k => $v) {
                $this->addFilter($k, Hidden::getInstance($k)->setValue($v));
            }
        }

        ob_start();

        ?>
        <div class="panel-group accordion" id="filter_form">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="panel-title">
                        <span class="accordion-toggle accordion-toggle-styled<?= $this->isExpandedByDefault() ? '' : ' collapsed' ?>" data-toggle="collapse"
                              data-parent="#filter_form" data-target="#collapse_filter_form">
                            <i class="fa fa-filter"></i>
                            <?= $this->filter_caption ?: __('Filter') ?>
                            <?php if ($this->applied) : ?>
                                &nbsp;(<?= $this->getAppliedFilterString() ?>)
                                <a href="<?= $default_action ?>" onclick="arguments[0].stopPropagation()">[Reset]</a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div id="collapse_filter_form" class="panel-collapse collapse<?= $this->isExpandedByDefault() ? ' in' : '' ?>">
                    <div class="panel-body">
                        <?= parent::__toString() ?>
                        <script>
                            $(function() {
                                $('#filter_form input[type="text"], #filter_form textarea, #filter_form select').first().focus();
                            })
                        </script>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * @param string $title
     * @param IFilter $filter
     *
     * @return $this
     */
    public function addFilter(string $title, IFilter $filter)
    {
        $this->addField($title, $filter);

        $provider = $filter->getFilter()->getProvider();
        if (!$provider) {
            $filter->setProvider($this->provider);
        }

        $filter->loadData();

        if (!$filter->isEmpty() && !($filter instanceof Hidden)) {
            $this->applied = true;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpandedByDefault(): bool
    {
        return $this->open;
    }

    /**
     * @return string
     */
    public function getAppliedFilterString(): string
    {
        $res = [];

        /* @var $field CmsFormElement */
        foreach ($this->fields as $field) {
            /** @var IFilter $filter */
            $filter = $field->getElement();

            if (!$filter->isEmpty() && !($filter instanceof Hidden)) {
                $provider = $filter->getFilter()->getProvider();

                unset($provider[$filter->getName()]);

                $res[] = '<span class="grey">' . $field->getLabel() . ':</span> ' . $filter->getDisplayValue() . ' <a class="nounderline grey" href="' . Linker::makeUrl($provider) . '"  onclick="arguments[0].stopPropagation()">[x]</a>';
            }
        }

        return implode('; ', $res);
    }
}
