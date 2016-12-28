<?php

namespace TMCms\Admin;

use TMCms\Admin\Users\Entity\AdminUser;
use TMCms\Admin\Users\Entity\UsersMessageEntity;
use TMCms\Admin\Users\Entity\UsersMessageEntityRepository;
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

    private $page_title = '';
    private $page_description = '';

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
        <script>
            function search_in_main_menu() {
                var $el = $('#menu_search_input');
                var text = $el.val();
                <?php // TODO hide element in menu that are not found by indexOf ?>
            }
        </script>
        <div class="page-sidebar-wrapper">
            <div class="page-sidebar navbar-collapse collapse">
                <ul class="page-sidebar-menu" data-auto-scroll="true" data-slide-speed="200">
                    <li class="sidebar-search-wrapper hidden-xs">
                        <div class="sidebar-search sidebar-search-bordered sidebar-search-solid">
<!--                            <div class="input-group">-->
<!--                                <input type="text" id="menu_search_input" autofocus class="form-control" placeholder="Search..." onkeyup="search_in_main_menu();">-->
<!--                                <span class="input-group-btn">-->
<!--                                    <a href="javascript:;" class="btn submit"><i class="icon-magnifier"></i></a>-->
<!--                                </span>-->
<!--                            </div>-->
                        </div>
                    </li>
                    <?php foreach ($this->_menu as $k => $v): ?>
                        <?php if (!is_array($v)): ?>
                            <li class="heading">
                                <h3 class="uppercase"><?= $v ?></h3>
                            </li>
                        <?php else:
                            if (!isset($v['title'])) {
                                $v['title'] = $k;
                            }
                            ?>
                            <li class="<?= P == $k ? 'active open' : '' ?>">
                                <a href="#">
                                    <i class="icon-<?= isset($v['icon']) ? $v['icon'] : 'home' ?>"></i>
                                    <span class="title"><?= __($v['title']) ?></span>
                                    <span class="arrow "></span>
                                </a>
                                <?php if (isset($this->_menu[$k]['items']) && is_array($this->_menu[$k]['items'])): ?>
                                    <ul class="sub-menu">
                                        <?php foreach ($this->_menu[$k]['items'] as $k_in => $v_in):
                                            if (!isset($v_in['title'])) {
                                                $v_in['title'] = $k_in;
                                            }
                                            ?>
                                        <li class="<?= (P == $k && P_DO == $k_in) ? 'active' : '' ?>">
                                            <a href="?p=<?= $k . '&do=' . $k_in ?>">
                                                <i class="icon-<?= isset($v_in['icon']) ? $v_in['icon'] : 'home' ?>"></i>
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

                    <?php // TODO multilevel menu items ?>
<!--                    <li>-->
<!--                        <a href="javascript:;">-->
<!--                            <i class="icon-folder"></i>-->
<!--                            <span class="title">Multi Level Menu</span>-->
<!--                            <span class="arrow "></span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu">-->
<!--                            <li>-->
<!--                                <a href="javascript:;">-->
<!--                                    <i class="icon-settings"></i> Item 1 <span class="arrow"></span>-->
<!--                                </a>-->
<!--                                <ul class="sub-menu">-->
<!--                                    <li>-->
<!--                                        <a href="javascript:;">-->
<!--                                            <i class="icon-user"></i>-->
<!--                                            Sample Link 1 <span class="arrow"></span>-->
<!--                                        </a>-->
<!--                                        <ul class="sub-menu">-->
<!--                                            <li>-->
<!--                                                <a href="#"><i class="icon-power"></i> Sample Link 1</a>-->
<!--                                            </li>-->
<!--                                            <li>-->
<!--                                                <a href="#"><i class="icon-paper-plane"></i> Sample Link 1</a>-->
<!--                                            </li>-->
<!--                                            <li>-->
<!--                                                <a href="#"><i class="icon-star"></i> Sample Link 1</a>-->
<!--                                            </li>-->
<!--                                        </ul>-->
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-camera"></i> Sample Link 1</a>-->
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-link"></i> Sample Link 2</a>-->
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-pointer"></i> Sample Link 3</a>-->
<!--                                    </li>-->
<!--                                </ul>-->
<!--                            </li>-->
<!--                            <li>-->
<!--                                <a href="javascript:;">-->
<!--                                    <i class="icon-globe"></i> Item 2 <span class="arrow"></span>-->
<!--                                </a>-->
<!--                                <ul class="sub-menu">-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-tag"></i> Sample Link 1</a>-->
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-pencil"></i> Sample Link 1</a>-->
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                        <a href="#"><i class="icon-graph"></i> Sample Link 1</a>-->
<!--                                    </li>-->
<!--                                </ul>-->
<!--                            </li>-->
<!--                            <li>-->
<!--                                <a href="#">-->
<!--                                    <i class="icon-bar-chart"></i>-->
<!--                                    Item 3 </a>-->
<!--                            </li>-->
<!--                        </ul>-->
<!--                    </li>-->

                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean(); ?>



        <aside class="sidebar offscreen-left">
            <p class="nav-top-overlay"></p>
            <nav class="main-navigation custom_scrollbar" data-height="auto" data-size="6px" data-distance="0" data-rail-visible="true" data-wheel-step="10">
                <ul class="nav">
                    <?php foreach ($this->_menu as $k => $v):
                        // Current module - rebder all submenu items
                        if (P == $k): ?>
                            <li class="open">
                                <a href="">
                                    <i class="ti-home"></i>
                                    <span><?= __(Converter::symb2Ttl(is_array($v) ? $v[$k] : $v)) ?></span>
                                </a>
                                <ul class="sub-menu" style="display: block;">
                                    <li class="<?= P_DO == '_default' ? ' active' : '' ?>">
                                        <a href="/cms?p=<?= P ?>">
                                            <i class="ti-arrow-right"></i>
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
                                                    <i class="ti-arrow-right"></i>
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
     * @param array $data representation in menu
     * @return $this whether added
     */
    public function addMenuItem($k, array $data)
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
     * Add menu separator
     * @param  string $data representation in menu
     * @return $this whether added
     */
    public function addMenuSeparator(string $data)
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

        // Notifications from system
        $notification_repository = new UsersMessageEntityRepository;
        $notification_repository->setWhereToUserId(USER_ID);
        $notification_repository->setWhereFromUserId(0);
        $notification_repository->addOrderByField('ts', true);
        $notification_repository->setWhereSeen(0);

        $total_notifications = $notification_repository->getCountOfObjectsInCollection();

        $notification_repository->setLimit(10);

        $notifications = $notification_repository->getAsArrayOfObjects();

        // Messages from users
        $messages_repository = new UsersMessageEntityRepository;
        $messages_repository->setWhereToUserId(USER_ID);
        $messages_repository->addWhereFieldIsNot('from_user_id', 0);
        $messages_repository->addOrderByField('ts', true);
        $messages_repository->setWhereSeen(0);

        $total_messages = $messages_repository->getCountOfObjectsInCollection();

        $messages_repository->setLimit(10);

        $messages = $messages_repository->getAsArrayOfObjects();

        // Custom notifiers
        // TODO
        $custom_notifiers = [];

        $custom_notifiers[] = $this->getHelpTextsNotifier();

        // Logo image and link
        $logo= '';
        if (array_key_exists('logo', Configuration::getInstance()->get('cms'))) {
            $logo = Configuration::getInstance()->get('cms')['logo'];
        }
        $logo_link = DIR_CMS_URL;
        if (array_key_exists('logo_link', Configuration::getInstance()->get('cms'))) {
            $logo_link = Configuration::getInstance()->get('cms')['logo_link'];
        }

        $user_avatar = Users::getInstance()->getUserData('avatar');
        if (!$user_avatar) {
            $user_avatar = '/vendor/devp-eu/tmcms-core/src/assets/cms/layout/img/avatar.png';
        }

        $languages = AdminLanguages::getPairs();
        $current_language = Users::getInstance()->getUserLng();

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
            <a href="javascript:;" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse"></a>
            <div class="top-menu">
                <ul class="nav navbar-nav pull-right">
                    <li class="dropdown dropdown-extended dropdown-home" id="header_home_bar">
                        <a href="/" target="_blank" class="dropdown-toggle" data-hover="dropdown" data-close-others="true">
                            <i class="icon-home"></i>
                        </a>
                    </li>
                    <?php if (count($languages) > 1): ?>
                        <li class="dropdown dropdown-language">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                                <img alt="" src="/vendor/devp-eu/tmcms-core/src/assets/cms/img/flags/<?= LNG ?>.png">
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
                                        <img alt="" src="/vendor/devp-eu/tmcms-core/src/assets/cms/img/flags/<?= $k ?>.png"> <?= $v?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($notifications): ?>
                        <li class="dropdown dropdown-extended dropdown-notification" id="header_notification_bar">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                                <i class="icon-bell"></i>
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
                                                        <?= Converter::getTimeFromEventAgo($message->getTs()) ?>
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
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                                <i class="icon-envelope-open"></i>
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
                                                        <span class="time"><?= Converter::getTimeFromEventAgo($message->getTs()) ?></span>
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
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                            <img alt="" class="img-circle" src="<?= $user_avatar ?>" style="height: 29px;">
                            <span class="username"><?= Users::getInstance()->getUserData('name') ?></span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="?p=users&do=users_edit&id=<?= USER_ID ?>">
                                    <i class="icon-user"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a href="?p=home&do=notifications">
                                    <i class="icon-envelope-open"></i>My notifications
                                    <span class="badge badge-danger"> <?= count($notifications) ?></span>
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="#" onclick="clipboard_forms.copy_page_forms(); return false;">
                                    <i class="icon-cloud-download"></i>Copy form data
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="clipboard_forms.paste_page_forms(); return false;">
                                    <i class="icon-cloud-upload"></i>Paste form data
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="?p=home&do=_exit" onclick="return confirm('<?= __('Are you sure?') ?>');">
                                    <i class="icon-key"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php // TODO right panel ?>
<!--                    <li class="dropdown dropdown-quick-sidebar-toggler">-->
<!--                        <a href="javascript:;" class="dropdown-toggle">-->
<!--                            <i class="icon-logout"></i>-->
<!--                        </a>-->
<!--                    </li>-->
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function getHelpTextsNotifier()
    {
        if (!$this->help_texts) {
            return '';
        }

        ob_start();
        ?>
        <li class="dropdown dropdown-extended dropdown-notification">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                <i class="icon-question"></i>
                <span class="badge badge-default"><?= count($this->help_texts); ?></span>
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
                                <a href="#">
                                    <?= $message ?>
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
    public function getPageTitle()
    {
        return $this->page_title;
    }

    /**
     * @param string $page_title
     * @return $this
     */
    public function setPageTitle($page_title)
    {
        $this->page_title = $page_title;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageDescription()
    {
        return $this->page_description;
    }

    /**
     * @param string $page_description
     * @return $this
     */
    public function setPageDescription($page_description)
    {
        $this->page_description = $page_description;

        return $this;
    }
}