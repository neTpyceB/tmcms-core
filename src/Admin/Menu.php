<?php

namespace TMCms\Admin;

use TMCms\Admin\Users\Entity\AdminUser;
use TMCms\Admin\Users\Entity\UsersMessage;
use TMCms\Admin\Users\Entity\UsersMessageRepository;
use TMCms\Config\Configuration;
use TMCms\Strings\Converter;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class Menu
 */
class Menu
{
    use singletonOnlyInstanceTrait;

    /**
     * Maximum count of menu items in first row
     */
    const MAX_VISIBLE_ITEMS = 7;

    /**
     * Show or hide menu header
     * @var bool
     */
    private static $enabled = true;

    /**
     * Menu items and subitems
     * @var array
     */
    private $_menu = [];

    /**
     * Enable or disable adding new menu items during file parse process
     * @var bool
     */
    private $addingFlag = true;

    /**
     * Help tooltips for current page, shown under menu
     * @var array
     */
    private $help_texts = [];

    /**
     * Label hints near menu items
     * @var array
     */
    private $menu_labels = [];

    /**
     * Disables header and menu
     */
    public function disableMenu()
    {
        self::$enabled = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMenuEnabled()
    {
        return self::$enabled;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();
        ?>
        <aside class="sidebar offscreen-left">
            <nav class="main-navigation" data-height="auto" data-size="6px" data-distance="0" data-rail-visible="true" data-wheel-step="10">
                <p class="nav-title"></p>
                <ul class="nav">
                    <?php foreach ($this->_menu as $k => $v):
                        // Current module - rebder all submenu items
                        if (P == $k): ?>
                            <li class="open">
                                <a href="javascript:">
                                    <i class="toggle-accordion"></i>
                                    <i class="ti-home"></i>
                                    <span><?= __(Converter::symb2Ttl(is_array($v) ? $v[$k] : $v)) ?></span>
                                </a>
                                <ul class="sub-menu" style="display: block;">
                                    <li class="">
                                        <a href="/cms?p=<?= P ?>">
                                            <span><?= __('Main') ?></span>
                                            <?php if (isset($this->menu_labels[P]['_default'])): ?>
                                                <span class="pull-right small label label-info animated flash">
                                                    <?= __($this->menu_labels[P]['_default']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <?php if (isset($this->_menu[P]) && is_array($this->_menu[P])): // Have subitems ?>
                                        <?php foreach ($this->_menu[P] as $k_in => $v_in):
                                            if (!isset($first)) {
                                                $first = true; // Skip main menu item
                                                continue;
                                            }
                                            ?>
                                            <li class="<?= P_DO == $k_in ? ' active' : '' ?>">
                                                <a href="/cms?p=<?= P . '&do=' . $k_in ?>">
                                                    <span><?= __(Converter::symb2Ttl($v_in)) ?></span>
                                                    <?php if (isset($this->menu_labels[P][$k_in])): ?>
                                                        <span class="pull-right small label label-danger animated flash">
                                                            <?= __($this->menu_labels[P][$k_in]) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="/cms?p=<?= $k ?>">
                                    <span><?= __(Converter::symb2Ttl(is_array($v) ? $v[$k] : $v)) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <?php
        return ob_get_clean();
    }

    /**
     * Add menu item
     * @param string $k to use in links
     * @param string $v representation in menu
     * @return bool whether added
     */
    public function addMenuItem($k, $v = '')
    {
        // Can not be added if disabled
        if (!$this->isAddingItemsAllowed()) {
            return $this;
        }

        // Set value same as key if not provided
        if (!$v) {
            $v = $k;
        }

        // Check if menu item already exists
        if (isset($this->_menu[$k])) {
            error('Menu item "' . $k . ' - ' . $v . '" already exists');
        }

        // Add to menu
        $this->_menu[$k] = $v;

        return $this;
    }

    /**
     * Add submenu item
     * @param string $k to use in links
     * @param string $v representation in submenu
     * @param string $prefix to know to which main menu item goes current submenu item
     * @return $this
     */
    public function addSubMenuItem($k, $v = '', $prefix = P)
    {
        // Check if may be added if disables
        if (!$this->isAddingItemsAllowed()) {
            return $this;
        }

        // set value as key if not provided
        if (!$v) {
            $v = $k;
        }

        // Check that menu for this submenu item exists
        if (!isset($this->_menu[$prefix])) {
            return $this;
        }

        // Check if submenu item already exists
        if (isset($this->_menu[$prefix][$k])) {
            return $this;
        }

        // Check if have access to main menu item
        if (!Users::getInstance()->checkAccess($prefix, $k)) {
            return $this;
        }

        // Make main menu as array to add submenu items
        if (!is_array($this->_menu[$prefix])) {
            $this->_menu[$prefix] = [
                $prefix => $this->_menu[$prefix]
            ];
        }

        // Add submenu item
        $this->_menu[$prefix][$k] = $v;

        return $this;
    }

    /**
     * Set permissions - may or may not add new menu items
     * @param bool $flag
     * @return $this
     */
    public function setMayAddItemsFlag($flag = true)
    {
        $this->addingFlag = (bool)$flag;

        return $this;
    }

    public function isAddingItemsAllowed()
    {
        return $this->addingFlag;
    }

    /**
     * Add one line of help text for menu item
     * @param string $text
     * @return $this
     */
    public function addHelpText($text)
    {
        $this->help_texts[] = $text;

        return $this;
    }

    /**
     * Set text for label near menu item
     * @param string $label text on label
     * @param string $do menu item name
     * @param string $p submenu item name
     */
    public function addLabelForMenuItem($label, $do = P_DO, $p = P)
    {
        $this->menu_labels[$p][$do] = $label;
    }

    /**
     * Get top page header
     * @return string
     */
    public function getMenuHeaderView()
    {
        if (!defined('USER_ID') || !USER_ID) {
            return '';
        }

        ob_start();

        $messages_collection = new UsersMessageRepository;
        $messages_collection->setWhereToUserId(USER_ID);
        $messages_collection->addOrderByField('ts', true);

        $total_notifications = $messages_collection->getCountOfMaxPossibleFoundObjectsWithoutFilters();

        $messages_collection->setLimit(5);

        $notifications = $messages_collection->getAsArrayOfObjects();

        if (array_key_exists('logo_link', Configuration::getInstance()->get('cms'))) {
            $logo_link = Configuration::getInstance()->get('cms')['logo_link'];
        }

        if (array_key_exists('logo', Configuration::getInstance()->get('cms'))) {
            $logo = Configuration::getInstance()->get('cms')['logo'];
        }

        ?>
        <div id="menu_loading" class="bg-success"></div>
        <script>
            // Show page loading
            // Emulate loading
            page_loader.timer = setInterval(function () {
                // Add ten percents
                page_loader.loaded_percent += 1;
                if (page_loader.loaded_percent > 100) {
                    page_loader.loaded_percent = 100;
                }
                page_loader.show_progress();
            }, 100);
        </script>

        <header class="header header-fixed navbar">
            <div class="brand">
                <a class="navbar-brand" href="<?= isset($logo_link) ? $logo_link : CMS_SITE ?>" target="_blank">
                    <div id="petrik_logo" style="background-image: url('<?= isset($logo) ? $logo : '' ?>')"></div>
                </a>
            </div>
            <?php if ($this->help_texts): ?>
                <div class="text-white pull-left" id="cms_page_help_tips">
                    <div style="display: none">
                        <?= implode('<br><hr><br>', $this->help_texts) ?>
                    </div>
                </div>
            <?php endif; ?>
            <ul class="nav navbar-nav navbar-right">
                <li class="off-right">
                    <a href="/" title="Open site" target="_blank">
                        <span class="hidden-xs ml10">
                            <i class="fa fa-files-o"></i>
                        </span>
                    </a>
                </li>
                <?php if ($notifications): ?>
                    <li class="notifications dropdown">
                        <a href="javascript:" data-toggle="dropdown">
                            <i class="ti-bell"></i>
                            <div class="badge badge-top bg-danger animated flash">
                                <span id="messages_count"><?= $total_notifications; ?></span>
                            </div>
                        </a>
                        <div class="dropdown-menu animated fadeIn">
                            <div class="panel panel-default no-m">
                                <div class="panel-heading small">
                                    <b>Notifications</b>
                                </div>
                                <ul class="list-group">
                                    <?php foreach ($notifications as $k => $message): /** @var UsersMessage $message */
                                        $user = new AdminUser($message->getFromUserId()); ?>
                                        <li class="list-group-item" data-message-item="<?= $message->getId() ?>">
                                            <div class="m-body">
                                                <div class="">
                                                    <small>
                                                        <b><?= $message->getFromUserId() ? $user->getName() : 'CMS System' ?></b>
                                                    </small>
                                                </div>
                                                <span><?= $message->getMessage() ?></span>
                                                <span class="time small">
                                                    <?= Converter::getTimeFromEventAgo($message->getTs()) ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <script>
                        $('.message_close_button').click(function (event) {
                            event.preventDefault();

                            var $el = $(event.target);
                            var m_id = $el.data('message-id');
                            $('[data-message-item="' + m_id + '"]').remove();

                            $.get('?p=structure&do=_ajax_delete_message&ajax&id=' + m_id);

                            var $count = $('#messages_count');
                            $count.html($count.html() - 1);
                        });
                    </script>
                <?php endif; ?>

                <li class="off-right">
                    <a href="javascript:" data-toggle="dropdown">
                        <span class="hidden-xs ml10">
                            <?= Users::getInstance()->getUserLng() ?>
                        </span>
                        <i class="ti-angle-down ti-caret hidden-xs"></i>
                    </a>
                    <ul class="dropdown-menu animated fadeIn">
                        <?php foreach (AdminLanguages::getPairs() as $k => $v): ?>
                            <li>
                                <a href="?p=users&do=_change_lng&lng=<?= $k ?>"><?= $v ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="off-right">
                    <a href="javascript:" data-toggle="dropdown">
                        <span class="hidden-xs ml10">
                            <?= Users::getInstance()->getUserData('login') ?>
                        </span>
                        <i class="ti-angle-down ti-caret hidden-xs"></i>
                    </a>
                    <ul class="dropdown-menu animated fadeIn">
                        <?php if ($this->help_texts): ?>
                            <li>
                                <a href="" onclick="$('#cms_page_help_tips').find('> div').stop().toggle('fast'); return false;">Page help</a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="?p=users&do=users_edit&id=<?= USER_ID ?>">Settings</a>
                        </li>
                        <li>
                            <a href="<?= DIR_CMS_URL ?>?p=home&do=_exit" onclick="return confirm('<?= __('Are you sure?') ?>');">Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </header>
        <?php
        return ob_get_clean();
    }
}