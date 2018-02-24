<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

\defined('INC') or exit;

/**
 * Class TablePager
 */
class TablePager
{
    private $prefix_string = '';
    /**
     * @var int
     */
    private $page;
    /**
     * @var int
     */
    private $total_rows;
    /**
     * @var int
     */
    private $pages;
    /**
     * @var int
     */
    private $per_page;

    /**
     * @param int $page
     * @param int $total_rows
     * @param int $per_page
     */
    public function  __construct(int $page, int $total_rows, int $per_page = 20)
    {
        $this->page = $page ?: (isset($_GET['page']) ? abs((int)$_GET['page']) : 0);
        $this->per_page = $per_page ?: (isset($_GET['per_page']) ? abs((int)$_GET['per_page']) : 20);

        $this->setTotalRows($total_rows);
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = abs((int)$page);
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->per_page;
    }

    /**
     * @param int $per_page
     */
    public function setPerPage($per_page)
    {
        $this->per_page = abs((int)$per_page);
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->total_rows;
    }

    /**
     * @param int $total_rows
     */
    public function setTotalRows($total_rows)
    {
        $this->total_rows = abs((int)$total_rows);
        $this->pages = ceil($total_rows / $this->per_page);
    }

    /**
     * @return string
     */
    public function  __toString(): string
    {
        if ($this->total_rows === 0) {
            return '';
        }

        $page = &$this->page;
        $pages = &$this->pages;

        $get_data = $_GET;

        $start_position = $page * $this->per_page;
        $end_position = ($page + 1) * $this->per_page;

        ob_start();

        ?>
        <div class="datatable-bottom" style="margin-top: -9px; height: 42px;">
            <div class="pull-left">
                <div class="dataTables_paginate paging_bootstrap">
                    <ul class="pagination">
                        <li class="prev<?= $page > 0 ? '' : ' disabled' ?>"<?= $page > 0 ? '' : ' onclick="return false"' ?>>
                            <a href="<?php $get_data['page'] = $page - 1;
                            echo Linker::makeUrl($get_data) ?>">
                                <i class="fa fa-angle-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 0; $i < $pages; ++$i):
                            $get_data['page'] = $i;

                            $skip = false;
                            if (($i || $i === $pages - 1) && ($i < ($page - 5) || $i > ($page + 5))) {
                                $skip = true;
                            }

                            if ($i === 0) {
                                $get_data['page'] = 0;
                                echo '<li' . ($i === $page ? ' class="active"' : '') . '><a href="' . Linker::makeUrl($get_data) . '">1</a></li>';
                                if ($page > 6) {
                                    echo '<li><a href="" onclick="return false;">...</a></li>';
                                }
                            } elseif($i && !$skip && $i !== $pages - 1) {
                                echo '<li' . ($i === $page ? ' class="active"' : '') . '>' . ($page !== $i ? '<a href="' . Linker::makeUrl($get_data) . '">' . ($i + 1) . '</a>' : '<a onclick="return false;">' . ($i + 1) . '</a>') . '</li>';
                            }
                            if ($i && ($i === $pages - 1)) {
                                if ($page < $pages - 7) {
                                    echo '<li><a href="" onclick="return false;">...</a></li>';
                                }
                                echo '<li' . ($i === $page ? ' class="active"' : '') . '><a href="' . Linker::makeUrl($get_data) . '">' . $pages . '</a></li>';
                            }
                        endfor; ?>
                        <li class="next<?= $page !== $pages - 1 ? '' : ' disabled' ?>"<?= $page !== $pages - 1 ? '' : ' onclick="return false"' ?>>
                            <a href="<?php $get_data['page'] = $page + 1;
                            echo Linker::makeUrl($get_data) ?>">
                                <i class="ti-arrow-right mr5"></i>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="pull-right" style="margin: 16px">
                <div class="dataTables_info" id="DataTables_Table_0_info" role="status" aria-live="polite">
                    <?= $this->getPrefixString() ?>
                    &nbsp;<?= __('Showing ') ?><?= $start_position ?><?= __(' to ') ?><?= $end_position ?><?= __(' of ') ?><?= $this->total_rows ?><?= __(' entries') ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" value="<?= $this->per_page ?>"
                                                   style="width:25px;height:16px;padding:0 3px;margin-left:5px" maxlength="3"
                                                   onblur="pager.submitPerPage(this.value)" onclick="this.select()"
                                                   onkeypress="pager.submitOnEnter(this.value,event)"
                        >
                    <?= __('per page') ?>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>

        <?php

            $get_data = $_GET;
            unset($get_data['page'], $get_data['per_page']);

        $href = Linker::makeUrl($get_data);
            unset($get_data);

            if ($href) {
                $href .= '&';
            } else {
                $href = '?' . $href;
            }
        ?>
        <script>
            var pager = {
                per_page: '<?= $this->per_page ?>',
                href: '<?= $href ?>',
                submitPerPage: function (per_page) {
                    var tmp = parseInt(per_page, 10);
                    if (tmp == this.per_page || isNaN(tmp)) {
                        return;
                    }
                    location.href = this.href + 'per_page=' + tmp;
                },
                submitOnEnter: function (per_page, e) {
                    var keycode;
                    if (window.event) {
                        keycode = window.event.keyCode;
                    } else if (e) {
                        keycode = e.which;
                    }
                    if (keycode == 13) {
                        this.submitPerPage(per_page);
                    }
                }
            }
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getPrefixString(): string
    {
        return $this->prefix_string;
    }

    /**
     * @param string $string
     *
     * @return $this
     */
    public function setPrefixString($string)
    {
        $this->prefix_string = $string;

        return $this;
    }
}
