<?php
declare(strict_types=1);

namespace TMCms\Admin;

use TMCms\Admin\Users\Entity\AdminUser;
use TMCms\Admin\Users\Entity\UsersMessageEntity;
use TMCms\Admin\Users\Entity\UsersMessageEntityRepository;
use TMCms\Config\Configuration;
use TMCms\Config\Settings;
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
    private $add_of_items_allowed = true;

    /**
     * Help tooltips for current page, shown under menu
     * @var array
     */
    private $help_texts = [];
    private $critical_help_texts = false;

    private $page_title = '';
    private $page_description = '';

    /**
     * Label hints near menu items
     * @var array
     */
    private $menu_labels = [];

    /**
     * Disables header and menu
     *
     * @return $this
     */
    public function disableMenu()
    {
        self::$enabled = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMenuEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        ob_start();
        ?>
        <script>
            function search_in_main_menu() {
                var $all_elements = $('.page-sidebar-menu li ul.sub-menu li a');
                var searched_value = $('#menu_search_input').val();

                $all_elements.css({'color': '#b4bcc8'}).closest('li').closest('ul').hide().closest('li').removeClass('open');

                if (searched_value !== '') {
                    $all_elements.each(function (k, v) {
                        var $el = $(v);
                        var value_of_item = $el.text();

                        if (value_of_item.indexOf(searched_value) !== -1) {
                            $el.css({'color': 'yellow'}).closest('li').closest('ul').show().closest('li').addClass('open');
                        }
                    });
                }
            }
        </script>
        <div class="page-sidebar-wrapper">
            <div class="page-sidebar navbar-collapse collapse">
                <ul class="page-sidebar-menu" data-auto-scroll="true" data-slide-speed="200">
                    <li class="sidebar-search-wrapper hidden-xs">
                        <div class="sidebar-search sidebar-search-bordered sidebar-search-solid">
                            <div class="input-group">
                                <input id="menu_search_input" autofocus class="form-control" placeholder="Search..."
                                       onkeyup="search_in_main_menu();">
                                <span class="input-group-btn">
                                    <a href="javascript:" class="btn submit"><i class="cms-icon-magnifier"></i></a>
                                </span>
                            </div>
                        </div>
                    </li>
                    <?php
                    // Draw menu items
                    foreach ($this->_menu as $k => $v): ?>
                        <?php
                        // If not array with elements - than it's block heading
                        if (!is_array($v)): ?>
                            <li class="heading">
                                <h3 class="uppercase"><?= __($v) ?></h3>
                            </li>
                        <?php else:
                            // If no title provided - it will be the same as menu key
                            if (!isset($v['title'])) {
                                $v['title'] = $k;
                            }
                            ?>
                            <li class="<?= P == $k ? 'active open' : '' ?>">
                                <a href="#">
                                    <?php if (isset($v['icon'])): ?>
                                        <i class="cms-icon-<?= $v['icon'] ?>"></i>
                                    <?php endif; ?>
                                    <span class="title"><?= __($v['title']) ?></span>
                                    <?php if (P == $k): ?>
                                        <span class="selected"></span>
                                    <?php endif; ?>
                                    <span class="arrow "></span>
                                </a>
                                <?php if (isset($this->_menu[$k]['items']) && is_array($this->_menu[$k]['items'])): ?>
                                    <ul class="sub-menu">
                                        <?php
                                        // Draw submenu items
                                        foreach ($this->_menu[$k]['items'] as $k_in => $v_in):
                                            if (!isset($v_in['title'])) {
                                                $v_in['title'] = $k_in;
                                            }
                                            ?>
                                            <li class="<?= (P == $k && P_M == $k_in) ? 'active' : '' ?>">
                                                <a href="?p=<?= $k . '&do=' . $k_in ?>">
                                                    <?php if (isset($v_in['icon'])): ?>
                                                        <i class="cms-icon-<?= $v_in['icon'] ?>"></i>
                                                    <?php endif; ?>
                                                    <?= __($v_in['title']) ?>
                                                    <?php if (isset($this->menu_labels[$k][$k_in])): ?>
                                                        <span class="badge badge-roundless badge-warning">
                                                        <?= __($this->menu_labels[$k][$k_in]) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Add menu item
     *
     * @param string $k    to use in links
     * @param array  $data representation in menu
     *
     * @return $this
     */
    public function addMenuItem(string $k, array $data)
    {
        // Can not be added if disabled
        if (!$this->isAddingItemsAllowed()) {
            return $this;
        }

        // Add to menu
        $this->_menu[$k] = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAddingItemsAllowed(): bool
    {
        return $this->add_of_items_allowed;
    }

    /**
     * Add menu separator
     *
     * @param  string $data representation in menu
     *
     * @return $this whether added
     */
    public function addMenuSeparator($data)
    {
        // Can not be added if disabled
        if (!$this->isAddingItemsAllowed()) {
            return $this;
        }

        // Add to menu
        $this->_menu[] = $data;

        return $this;
    }

    /**
     * Add submenu item
     *
     * @param string $k      to use in links
     * @param string $v      representation in submenu
     * @param string $prefix to know to which main menu item goes current submenu item
     *
     * @return $this
     */
    public function addSubMenuItem(string $k, string $v = '', string $prefix = P)
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
                $prefix => $this->_menu[$prefix],
            ];
        }

        // Add submenu item
        $this->_menu[$prefix][$k] = $v;

        return $this;
    }

    /**
     * Set permissions - may or may not add new menu items
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setMayAddItemsFlag(bool $flag = true)
    {
        $this->add_of_items_allowed = $flag;

        return $this;
    }

    /**
     * Add one line of help text for menu item
     *
     * @param string $text
     *
     * @return $this
     */
    public function addHelpText(string $text, string $link = null, $critical = false)
    {
        if(!$link) {
            $this->help_texts[] = $text;
        }else{
            $this->help_texts[] = [$text, $link];
        }
        if($critical)
            $this->critical_help_texts = true;

        return $this;
    }

    /**
     * Set text for label near menu item
     *
     * @param string $label text on label
     * @param string $do    menu item name
     * @param string $p     submenu item name
     *
     * @return $this
     */
    public function addLabelForMenuItem(string $label, string $do = P_DO, string $p = P)
    {
        $this->menu_labels[$p][$do] = $label;

        return $this;
    }

    /**
     * Get top page header
     *
     * @return string
     */
    public function getMenuHeaderView(): string
    {
        // Outside of authorized area
        if (!defined('USER_ID') || !USER_ID || !defined('LNG')) {
            return '';
        }

        // Show non-default CMS headers
        $cfg = Configuration::getInstance()->get('cms');
        if (isset($cfg['custom_header']) && $cfg['custom_header']) {
            // Need to create this file prior to use
            return include DIR_MODULES . 'cms/Admin/Menu/getMenuHeaderView.php';
        }

        // Notifications from system
        $notification_repository = new UsersMessageEntityRepository;
        $notification_repository
            ->setWhereToUserId(USER_ID)
            ->setWhereFromUserId(0)
            ->addOrderByField('ts', true)
            ->setWhereSeen(0);

        // All count for number
        $total_notifications = $notification_repository->getCountOfObjectsInCollection();

        // Show a few latest
        $notifications = $notification_repository
            ->setLimit(10)
            ->getAsArrayOfObjects();

        // Messages from users
        $messages_repository = new UsersMessageEntityRepository;
        $messages_repository
            ->setWhereToUserId(USER_ID)
            ->addWhereFieldIsNot('from_user_id', 0)
            ->addOrderByField('ts', true)
            ->setWhereSeen(0);

        // All count for number
        $total_messages = $messages_repository->getCountOfObjectsInCollection();

        $messages = $messages_repository
            ->setLimit(10)
            ->getAsArrayOfObjects();

        // Custom notifiers
        $custom_notifiers = [];

        $custom_notifiers[] = $this->getHelpTextsNotifier();

        // Logo image and link
        $logo = '';
        if (array_key_exists('logo', Configuration::getInstance()->get('cms'))) {
            $logo = Configuration::getInstance()->get('cms')['logo'];
        }

        $logo_link = DIR_CMS_URL;
        if (array_key_exists('logo_link', Configuration::getInstance()->get('cms'))) {
            $logo_link = Configuration::getInstance()->get('cms')['logo_link'];
        }

        // User avatar image
        $user_avatar = Users::getInstance()->getUserData('avatar');
        if (!$user_avatar) {
            $user_avatar = '/vendor/devp-eu/tmcms-core/src/assets/cms/layout/img/avatar.png';
        }

        $languages = AdminLanguages::getPairs();

        ob_start();

        ?>
        <div class="page-header-inner">
            <?php if ($logo): ?>
                <div class="page-logo">
                    <a href="<?= $logo_link ?>">
                        <img src="<?= $logo ?>" alt="logo" class="logo-default">
                    </a>

                    <div class="menu-toggler sidebar-toggler"></div>
                </div>
            <?php endif; ?>
            <a href="javascript:" class="menu-toggler responsive-toggler" data-toggle="collapse"
               data-target=".navbar-collapse"></a>

            <div class="top-menu">
                <ul class="nav navbar-nav pull-right">
                    <li class="dropdown dropdown-extended dropdown-home" id="header_home_bar">
                        <a href="/" target="_blank" class="dropdown-toggle" data-hover="dropdown"
                           data-close-others="true">
                            <i class="cms-icon-globe"></i>
                        </a>
                    </li>
                    <?php if (count($languages) > 1): ?>
                        <li class="dropdown dropdown-language">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                               data-close-others="true">
                                <?php if (Settings::get('show_language_selector_flags')): ?>
                                    <img alt=""
                                         src="/vendor/devp-eu/tmcms-core/src/assets/cms/img/flags/<?= LNG ?>.png">
                                <?php endif; ?>
                                <span class="langname"><?= strtoupper(LNG) ?> </span>
                                <i class="fa fa-angle-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($languages as $k => $v):
                                    if ($k == LNG) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <a href="?p=users&do=_change_lng&lng=<?= $k ?>">
                                            <?php if (Settings::get('show_language_selector_flags')): ?>
                                                <img alt=""
                                                     src="/vendor/devp-eu/tmcms-core/src/assets/cms/img/flags/<?= $k ?>.png">
                                            <?php endif; ?>
                                            <?= $v ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($notifications): ?>
                        <li class="dropdown dropdown-extended dropdown-notification" id="header_notification_bar">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                               data-close-others="true">
                                <i class="cms-icon-bell"></i>
                                <span class="badge badge-default"><?= count($notifications); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <p>
                                        You have <?= $total_notifications; ?> new notifications
                                    </p>
                                </li>
                                <li>
                                    <ul class="dropdown-menu-list scroller" style="height: 250px;">
                                        <?php foreach ($notifications as $k => $message): /** @var UsersMessageEntity $message */ ?>
                                            <li>
                                                <a href="#">
                                                    <span class="label label-sm label-icon label-warning">
                                                        <i class="fa fa-bell-o"></i>
                                                    </span>
                                                    <?= $message->getMessage() ?>
                                                    <span class="time">
                                                        <?= Converter::getTimeFromEventAgo((int)$message->getTs()) ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                                <li class="external">
                                    <a href="?p=home&do=notifications">
                                        See all notifications <i class="m-icon-swapright"></i>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($messages): ?>
                        <li class="dropdown dropdown-extended dropdown-inbox" id="header_inbox_bar">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                               data-close-others="true">
                                <i class="cms-icon-envelope-open"></i>
                                <span class="badge badge-default"><?= count($messages); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <p>
                                        You have <?= $total_messages; ?> new messages
                                    </p>
                                </li>
                                <li>
                                    <ul class="dropdown-menu-list scroller" style="height: 250px;">
                                        <?php foreach ($notifications as $k => $message): /** @var UsersMessageEntity $message */
                                            $user = new AdminUser($message->getFromUserId());
                                            $avatar = $user->getAvatar(); ?>
                                            <li>
                                                <a href="?p=users&do=chat&user_id=2">
                                                    <?php if ($avatar): ?>
                                                        <span class="photo">
                                                           <img src="<?= $avatar ?>" alt="" style="height=40px">
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="subject">
                                                        <span class="from"><?= $user->getName() ?></span>
                                                        <span class="time"><?= Converter::getTimeFromEventAgo((int)$message->getTs()) ?></span>
                                                    </span>
                                                    <span class="message"><?= Converter::cutLongStrings($message->getMessage()) ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                                <li class="external">
                                    <a href="?p=users&do=chat">
                                        See all messages <i class="m-icon-swapright"></i>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($custom_notifiers): ?>
                        <?= implode('', $custom_notifiers) ?>
                    <?php endif; ?>
                    <li class="dropdown dropdown-user">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                           data-close-others="true">
                            <img alt="" class="img-circle" src="<?= $user_avatar ?>" style="height: 29px;">
                            <span class="username"><?= Users::getInstance()->getUserData('name') ?></span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="?p=users&do=users_edit&id=<?= USER_ID ?>">
                                    <i class="cms-icon-user"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a href="?p=home&do=notifications">
                                    <i class="cms-icon-envelope-open"></i>My notifications
                                    <span class="badge badge-danger"> <?= count($notifications) ?></span>
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="#" onclick="clipboard_forms.copy_page_forms(); return false;">
                                    <i class="cms-icon-cloud-download"></i>Copy form data
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="clipboard_forms.paste_page_forms(); return false;">
                                    <i class="cms-icon-cloud-upload"></i>Paste form data
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="?p=home&do=_exit" onclick="return confirm('<?= __('Are you sure?') ?>');">
                                    <i class="cms-icon-key"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * @return string
     */
    private function getHelpTextsNotifier(): string
    {
        if (!$this->help_texts) {
            return '';
        }

        ob_start();

        ?>
        <li class="dropdown dropdown-extended dropdown-notification">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                <i class="cms-icon-question"></i>
                <span class="badge <?= $this->critical_help_texts ? "badge-danger" : "badge-default" ?>"><?= count($this->help_texts); ?></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <p>
                        <?= count($this->help_texts); ?> help texts for this page
                    </p>
                </li>
                <li>
                    <ul class="dropdown-menu-list scroller" style="height: 250px;">
                        <?php foreach ($this->help_texts as $k => $message): ?>
                            <li>
                                <a href="<?= is_array($message) ? $message[1] : '#' ?>">
                                    <?= is_array($message) ? $message[0] : $message ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </li>
        <?php

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->page_title;
    }

    /**
     * @param string $page_title
     *
     * @return $this
     */
    public function setPageTitle(string $page_title)
    {
        $this->page_title = $page_title;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageDescription(): string
    {
        return $this->page_description;
    }

    /**
     * @param string $page_description
     *
     * @return $this
     */
    public function setPageDescription(string $page_description)
    {
        $this->page_description = $page_description;

        return $this;
    }
}