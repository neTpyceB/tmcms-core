<?php

namespace TMCms\App;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TMCms\Admin\Menu;
use TMCms\Admin\Updater;
use TMCms\Admin\Users;
use TMCms\Admin\Users\Entity\UserLog;
use TMCms\Config\Configuration;
use TMCms\Config\Settings;
use TMCms\Files\Finder;
use TMCms\HTML\BreadCrumbs;use TMCms\Services\ServiceManager;
use TMCms\Log\App;
use TMCms\Log\Usage;
use TMCms\Network\Mailer;
use TMCms\Strings\Converter;
use TMCms\Templates\Page;
use TMCms\Templates\PageBody;
use TMCms\Templates\PageHead;
use TMCms\Templates\PageTail;

defined('INC') or exit;

/**
 * Front Application - site itself
 * Class Backend
 */
class Backend
{
    private $p;
    private $p_do;
    private $no_menu;
    private $content = '';

    public function __construct()
    {
        // Check background services
        if (isset($_GET['cms_is_running_background']) && $_GET['cms_is_running_background'] == 1) {
            ServiceManager::checkNeeded();
            exit;
        }

        // Try updating CMS
        if (isset($_GET['key']) && $_GET['key'] == Configuration::getInstance()->get('cms')['unique_key'] && count($_GET) === 2) {

            $updater = Updater::getInstance();

            // Update files from repo
            $updater->updateSourceCode(isset($_GET['branch']) ? $_GET['branch'] : NULL);

            // Update composer only if required
            if (isset($_GET['composer'])) {
                $updater->updateComposerVendors();
            }

            // Update database
            $updater->runMigrations();

            // Output
            $out = $updater->getResult();
            $text = json_encode($out, JSON_FORCE_OBJECT);

            echo $text;

            exit;
        }

        // Else - running usual Admin panel

        // Proceed with request
        $this->parseUrl();

        // Save log
        if (Users::getInstance()->isLogged() && Settings::get('save_back_access_log') && !IS_AJAX_REQUEST) {
            $users_log = new UserLog();
            $users_log->save();
        }

        // Init page data
        if (Users::getInstance()->isLogged()) {
            define('LNG', Users::getInstance()->getUserLng());
        }

        $this->sendHeaders();

        $this->prepareHead();

        $this->parseMenu();

        // Post-scripts
        Page::setTail(PageTail::getInstance());

        // Flush application log
        App::flushLog();

        $this->generateContent();
    }

    /**
     * Render page itself
     * @return string
     */
    public function __toString()
    {
        ob_start();

        $user_logged_in = Users::getInstance()->isLogged();

        // Page top part and menu part
        if ($user_logged_in):
            /**
             * @var Menu $menu
             */
            $menu = (!Menu::getInstance()->isMenuEnabled() ? '' : Menu::getInstance());

            ?>
            <div class="page-header navbar navbar-fixed-top">
                <?= $menu ? $menu->getMenuHeaderView() : '' ?>
            </div>
            <div class="clearfix"></div>
            <div class="page-container">
                <?= $menu ?>
                <div class="page-content-wrapper">
                    <div class="page-content">
                        <div class="row">
                            <div class="col-md-12">
                                <h3 class="page-title">
                                    <?= $menu->getPageTitle() ?> <small><?= $menu->getPageDescription() ?></small>
                                </h3>
                                <?= BreadCrumbs::getInstance(); ?>
                            </div>
                        </div>
            <?php endif;
            // Main page
            echo $this->content;
            if ($user_logged_in): ?>

                    </div>
                </div>
            </div>

                    <?php // TODO right quick sidebar ?>
                        <!--            <a href="javascript:;" class="page-quick-sidebar-toggler"><i class="icon-close"></i></a>-->
                        <!--            <div class="page-quick-sidebar-wrapper">-->
                        <!--                <div class="page-quick-sidebar">-->
                        <!--                    <div class="nav-justified">-->
                        <!--                        <ul class="nav nav-tabs nav-justified">-->
                        <!--                            <li class="active">-->
                        <!--                                <a href="#quick_sidebar_tab_1" data-toggle="tab">-->
                        <!--                                    Users <span class="badge badge-danger">2</span>-->
                        <!--                                </a>-->
                        <!--                            </li>-->
                        <!--                            <li>-->
                        <!--                                <a href="#quick_sidebar_tab_2" data-toggle="tab">-->
                        <!--                                    Alerts <span class="badge badge-success">7</span>-->
                        <!--                                </a>-->
                        <!--                            </li>-->
                        <!--                            <li class="dropdown">-->
                        <!--                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">-->
                        <!--                                    More<i class="fa fa-angle-down"></i>-->
                        <!--                                </a>-->
                        <!--                                <ul class="dropdown-menu pull-right" role="menu">-->
                        <!--                                    <li>-->
                        <!--                                        <a href="#quick_sidebar_tab_3" data-toggle="tab">-->
                        <!--                                            <i class="icon-bell"></i> Alerts </a>-->
                        <!--                                    </li>-->
                        <!--                                    <li>-->
                        <!--                                        <a href="#quick_sidebar_tab_3" data-toggle="tab">-->
                        <!--                                            <i class="icon-info"></i> Notifications </a>-->
                        <!--                                    </li>-->
                        <!--                                    <li>-->
                        <!--                                        <a href="#quick_sidebar_tab_3" data-toggle="tab">-->
                        <!--                                            <i class="icon-speech"></i> Activities </a>-->
                        <!--                                    </li>-->
                        <!--                                    <li class="divider">-->
                        <!--                                    </li>-->
                        <!--                                    <li>-->
                        <!--                                        <a href="#quick_sidebar_tab_3" data-toggle="tab">-->
                        <!--                                            <i class="icon-settings"></i> Settings </a>-->
                        <!--                                    </li>-->
                        <!--                                </ul>-->
                        <!--                            </li>-->
                        <!--                        </ul>-->
                        <!--                        <div class="tab-content">-->
                        <!--                            <div class="tab-pane active page-quick-sidebar-chat" id="quick_sidebar_tab_1">-->
                        <!--                                <div class="page-quick-sidebar-chat-users" data-rail-color="#ddd" data-wrapper-class="page-quick-sidebar-list">-->
                        <!--                                    <h3 class="list-heading">Staff</h3>-->
                        <!--                                    <ul class="media-list list-items">-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="badge badge-success">8</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar3.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Bob Nilson</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    Project Manager-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar1.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Nick Larson</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    Art Director-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="badge badge-danger">3</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar4.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Deon Hubert</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CTO-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar2.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Ella Wong</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CEO-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                    <h3 class="list-heading">Customers</h3>-->
                        <!--                                    <ul class="media-list list-items">-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="badge badge-warning">2</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar6.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Lara Kunis</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CEO, Loop Inc-->
                        <!--                                                </div>-->
                        <!--                                                <div class="media-heading-small">-->
                        <!--                                                    Last seen 03:10 AM-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="label label-sm label-success">new</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar7.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Ernie Kyllonen</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    Project Manager,<br>-->
                        <!--                                                    SmartBizz PTL-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar8.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Lisa Stone</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CTO, Keort Inc-->
                        <!--                                                </div>-->
                        <!--                                                <div class="media-heading-small">-->
                        <!--                                                    Last seen 13:10 PM-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="badge badge-success">7</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar9.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Deon Portalatin</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CFO, H&D LTD-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar10.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Irina Savikova</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    CEO, Tizda Motors Inc-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li class="media">-->
                        <!--                                            <div class="media-status">-->
                        <!--                                                <span class="badge badge-danger">4</span>-->
                        <!--                                            </div>-->
                        <!--                                            <img class="media-object" src="../../assets/admin/layout/img/avatar11.jpg" alt="...">-->
                        <!--                                            <div class="media-body">-->
                        <!--                                                <h4 class="media-heading">Maria Gomez</h4>-->
                        <!--                                                <div class="media-heading-sub">-->
                        <!--                                                    Manager, Infomatic Inc-->
                        <!--                                                </div>-->
                        <!--                                                <div class="media-heading-small">-->
                        <!--                                                    Last seen 03:10 AM-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                </div>-->
                        <!--                                <div class="page-quick-sidebar-item">-->
                        <!--                                    <div class="page-quick-sidebar-chat-user">-->
                        <!--                                        <div class="page-quick-sidebar-nav">-->
                        <!--                                            <a href="javascript:;" class="page-quick-sidebar-back-to-list"><i class="icon-arrow-left"></i>Back</a>-->
                        <!--                                        </div>-->
                        <!--                                        <div class="page-quick-sidebar-chat-user-messages">-->
                        <!--                                            <div class="post out">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar3.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Bob Nilson</a>-->
                        <!--                                                    <span class="datetime">20:15</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                When could you send me the report ? </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post in">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar2.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Ella Wong</a>-->
                        <!--                                                    <span class="datetime">20:15</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Its almost done. I will be sending it shortly </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post out">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar3.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Bob Nilson</a>-->
                        <!--                                                    <span class="datetime">20:15</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Alright. Thanks! :) </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post in">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar2.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Ella Wong</a>-->
                        <!--                                                    <span class="datetime">20:16</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                You are most welcome. Sorry for the delay. </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post out">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar3.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Bob Nilson</a>-->
                        <!--                                                    <span class="datetime">20:17</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                No probs. Just take your time :) </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post in">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar2.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Ella Wong</a>-->
                        <!--                                                    <span class="datetime">20:40</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Alright. I just emailed it to you. </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post out">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar3.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Bob Nilson</a>-->
                        <!--                                                    <span class="datetime">20:17</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Great! Thanks. Will check it right away. </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post in">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar2.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Ella Wong</a>-->
                        <!--                                                    <span class="datetime">20:40</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Please let me know if you have any comment. </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="post out">-->
                        <!--                                                <img class="avatar" alt="" src="../../assets/admin/layout/img/avatar3.jpg"/>-->
                        <!--                                                <div class="message">-->
                        <!--                                                    <span class="arrow"></span>-->
                        <!--                                                    <a href="#" class="name">Bob Nilson</a>-->
                        <!--                                                    <span class="datetime">20:17</span>-->
                        <!--                                                    <span class="body">-->
                        <!--                                                Sure. I will check and buzz you if anything needs to be corrected. </span>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </div>-->
                        <!--                                        <div class="page-quick-sidebar-chat-user-form">-->
                        <!--                                            <div class="input-group">-->
                        <!--                                                <input type="text" class="form-control" placeholder="Type a message here...">-->
                        <!--                                                <div class="input-group-btn">-->
                        <!--                                                    <button type="button" class="btn blue"><i class="icon-paper-clip"></i></button>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </div>-->
                        <!--                                    </div>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                            <div class="tab-pane page-quick-sidebar-alerts" id="quick_sidebar_tab_2">-->
                        <!--                                <div class="page-quick-sidebar-alerts-list">-->
                        <!--                                    <h3 class="list-heading">General</h3>-->
                        <!--                                    <ul class="feeds list-items">-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-info">-->
                        <!--                                                            <i class="fa fa-check"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 4 pending tasks. <span class="label label-sm label-warning ">-->
                        <!--                                                        Take action <i class="fa fa-share"></i>-->
                        <!--                                                        </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    Just now-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <a href="#">-->
                        <!--                                                <div class="col1">-->
                        <!--                                                    <div class="cont">-->
                        <!--                                                        <div class="cont-col1">-->
                        <!--                                                            <div class="label label-sm label-success">-->
                        <!--                                                                <i class="fa fa-bar-chart-o"></i>-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                        <div class="cont-col2">-->
                        <!--                                                            <div class="desc">-->
                        <!--                                                                Finance Report for year 2013 has been released.-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                                <div class="col2">-->
                        <!--                                                    <div class="date">-->
                        <!--                                                        20 mins-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </a>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-danger">-->
                        <!--                                                            <i class="fa fa-user"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 5 pending membership that requires a quick review.-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    24 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-info">-->
                        <!--                                                            <i class="fa fa-shopping-cart"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            New order received with <span class="label label-sm label-success">-->
                        <!--                                                        Reference Number: DR23923 </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    30 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-success">-->
                        <!--                                                            <i class="fa fa-user"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 5 pending membership that requires a quick review.-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    24 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-info">-->
                        <!--                                                            <i class="fa fa-bell-o"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            Web server hardware needs to be upgraded. <span class="label label-sm label-warning">-->
                        <!--                                                        Overdue </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    2 hours-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <a href="#">-->
                        <!--                                                <div class="col1">-->
                        <!--                                                    <div class="cont">-->
                        <!--                                                        <div class="cont-col1">-->
                        <!--                                                            <div class="label label-sm label-default">-->
                        <!--                                                                <i class="fa fa-briefcase"></i>-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                        <div class="cont-col2">-->
                        <!--                                                            <div class="desc">-->
                        <!--                                                                IPO Report for year 2013 has been released.-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                                <div class="col2">-->
                        <!--                                                    <div class="date">-->
                        <!--                                                        20 mins-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </a>-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                    <h3 class="list-heading">System</h3>-->
                        <!--                                    <ul class="feeds list-items">-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-info">-->
                        <!--                                                            <i class="fa fa-check"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 4 pending tasks. <span class="label label-sm label-warning ">-->
                        <!--                                                        Take action <i class="fa fa-share"></i>-->
                        <!--                                                        </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    Just now-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <a href="#">-->
                        <!--                                                <div class="col1">-->
                        <!--                                                    <div class="cont">-->
                        <!--                                                        <div class="cont-col1">-->
                        <!--                                                            <div class="label label-sm label-danger">-->
                        <!--                                                                <i class="fa fa-bar-chart-o"></i>-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                        <div class="cont-col2">-->
                        <!--                                                            <div class="desc">-->
                        <!--                                                                Finance Report for year 2013 has been released.-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                                <div class="col2">-->
                        <!--                                                    <div class="date">-->
                        <!--                                                        20 mins-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </a>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-default">-->
                        <!--                                                            <i class="fa fa-user"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 5 pending membership that requires a quick review.-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    24 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-info">-->
                        <!--                                                            <i class="fa fa-shopping-cart"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            New order received with <span class="label label-sm label-success">-->
                        <!--                                                        Reference Number: DR23923 </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    30 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-success">-->
                        <!--                                                            <i class="fa fa-user"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            You have 5 pending membership that requires a quick review.-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    24 mins-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <div class="col1">-->
                        <!--                                                <div class="cont">-->
                        <!--                                                    <div class="cont-col1">-->
                        <!--                                                        <div class="label label-sm label-warning">-->
                        <!--                                                            <i class="fa fa-bell-o"></i>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                    <div class="cont-col2">-->
                        <!--                                                        <div class="desc">-->
                        <!--                                                            Web server hardware needs to be upgraded. <span class="label label-sm label-default ">-->
                        <!--                                                        Overdue </span>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="col2">-->
                        <!--                                                <div class="date">-->
                        <!--                                                    2 hours-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            <a href="#">-->
                        <!--                                                <div class="col1">-->
                        <!--                                                    <div class="cont">-->
                        <!--                                                        <div class="cont-col1">-->
                        <!--                                                            <div class="label label-sm label-info">-->
                        <!--                                                                <i class="fa fa-briefcase"></i>-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                        <div class="cont-col2">-->
                        <!--                                                            <div class="desc">-->
                        <!--                                                                IPO Report for year 2013 has been released.-->
                        <!--                                                            </div>-->
                        <!--                                                        </div>-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                                <div class="col2">-->
                        <!--                                                    <div class="date">-->
                        <!--                                                        20 mins-->
                        <!--                                                    </div>-->
                        <!--                                                </div>-->
                        <!--                                            </a>-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                            <div class="tab-pane page-quick-sidebar-settings" id="quick_sidebar_tab_3">-->
                        <!--                                <div class="page-quick-sidebar-settings-list">-->
                        <!--                                    <h3 class="list-heading">General Settings</h3>-->
                        <!--                                    <ul class="list-items borderless">-->
                        <!--                                        <li>-->
                        <!--                                            Enable Notifications <input type="checkbox" class="make-switch" checked data-size="small" data-on-color="success" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Allow Tracking <input type="checkbox" class="make-switch" data-size="small" data-on-color="info" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Log Errors <input type="checkbox" class="make-switch" checked data-size="small" data-on-color="danger" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Auto Sumbit Issues <input type="checkbox" class="make-switch" data-size="small" data-on-color="warning" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Enable SMS Alerts <input type="checkbox" class="make-switch" checked data-size="small" data-on-color="success" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                    <h3 class="list-heading">System Settings</h3>-->
                        <!--                                    <ul class="list-items borderless">-->
                        <!--                                        <li>-->
                        <!--                                            Security Level-->
                        <!--                                            <select class="form-control input-inline input-sm input-small">-->
                        <!--                                                <option value="1">Normal</option>-->
                        <!--                                                <option value="2" selected>Medium</option>-->
                        <!--                                                <option value="e">High</option>-->
                        <!--                                            </select>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Failed Email Attempts <input class="form-control input-inline input-sm input-small" value="5"/>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Secondary SMTP Port <input class="form-control input-inline input-sm input-small" value="3560"/>-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Notify On System Error <input type="checkbox" class="make-switch" checked data-size="small" data-on-color="danger" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                        <li>-->
                        <!--                                            Notify On SMTP Error <input type="checkbox" class="make-switch" checked data-size="small" data-on-color="warning" data-on-text="ON" data-off-color="default" data-off-text="OFF">-->
                        <!--                                        </li>-->
                        <!--                                    </ul>-->
                        <!--                                    <div class="inner-content">-->
                        <!--                                        <button class="btn btn-success"><i class="icon-settings"></i> Save Changes</button>-->
                        <!--                                    </div>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                        </div>-->
                        <!--                    </div>-->
                        <!--                </div>-->
                        <!--            </div>-->
            <div class="page-footer">
                <div class="page-footer-inner">
                    2007 - <?= Y ?> &copy; <?= CMS_NAME ?> | <a href="<?= CMS_SITE ?>" target="_blank"><?= CMS_SITE ?></a>
                </div>
                <div class="page-footer-tools">
                    <span class="go-top">
                        <i class="fa fa-angle-up"></i>
                    </span>
                </div>
            </div>
        <?php endif;

        $html = ob_get_contents();

        Page::setBody(new PageBody($html));

        ob_clean();

        return Page::getHTML();
    }

    private function parseUrl()
    {
        // Some pages do not have menu header nor items
        $this->no_menu = isset($_GET['nomenu']);

        /* Get P and P_DO - these are module and action shortcuts */
        if (!isset($_GET['p'])) {
            $_GET['p'] = 'home';
        }
        if (!isset($_GET['do'])) {
            $_GET['do'] = '_default';
        }

        // Render log=in form if if user is not auth-ed
        if (!Users::getInstance()->isLogged()) {
            $_GET['p'] = 'guest';
            $this->no_menu = true;
        }

        if (!defined('P')) {
            define('P', $_GET['p']);
        }
        if (!defined('P_DO')) {
            define('P_DO', $_GET['do']);
        }

        // Parse URL
        $path = [];
        if ((!$url = parse_url(SELF)) || !isset($url['path'])) {
            die('URL can not be parsed');
        }

        // Generate real path
        foreach (explode('/', $url['path']) as $pa) {
            if ($pa) {
                $path[] = $pa;
            }
        }

        // For non-rewrite hostings remove last file name
        if (end($path) === 'index.php') {
            array_pop($path);
        }

        if ($this->no_menu) {
            Menu::getInstance()->disableMenu();
        }

        // Log CMS usage
        Usage::getInstance()->add(P, P_DO);

        // Rewite $_GET in case constans defined not from params
        $_GET['p'] = P;
        $_GET['do'] = P_DO;

        $this->p = P;
        $this->p_do = P_DO;
    }

    private function sendHeaders()
    {
        // Do not send twice
        if (headers_sent()) {
            return;
        }

        // Set headers to disable cache
        header('Expires: Wed, 01 Jul 2005 08:50:08 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        if (!isset($_SERVER['HTTP_X_FLASH_VERSION'])) {
            header('Pragma: no-cache');
        } // HTTP/1.0
        header('Content-Type: text/html; charset=utf-8');
        if (strpos(USER_AGENT, 'MSIE') !== false) {
            header('Imagetoolbar: no');
        }
    }

    /**
     * Data for HTML <head> generation
     */
    private function prepareHead()
    {
        $config = Configuration::getInstance();
        // Favicon url
        $favicon = !empty($config->get('cms')['favicon']) ? $config->get('cms')['favicon'] : DIR_CMS_IMAGES_URL . 'logo_square.png';

        // Prepare page HTML for head
        PageHead::getInstance()
            // Cms attributes
            ->addHtmlTagAttributes('lang="en" class="no-js"')
            ->setTitle((P_DO !== '_default' ? Converter::symb2Ttl(P_DO) : 'Main') . ' / ' . Converter::symb2Ttl(P) . ' / ' . $config->get('site')['name'] . ' / ' . CMS_NAME . ' v. ' . CMS_VERSION)
            ->setFavicon($favicon)
            ->addMeta('name=' . CMS_NAME . ' - ' . $config->get('site')['name'] . '; action-uri=http://' . CFG_DOMAIN . '/cms/; icon-uri=http://' . DIR_CMS_IMAGES_URL . 'logo_square.png', 'msapplication-task')
            ->addMeta('width=device-width, initial-scale=1', 'viewport')
            ->addMeta('IE=edge', '', 'X-UA-Compatible')
            ->addClassToBody('page-header-fixed')
            ->addClassToBody('page-sidebar-fixed')
            ->addClassToBody('page-quick-sidebar-over-content')
            // Global styles
            ->addCssUrl('cms/fonts/open-sans.css')
            ->addCssUrl('cms/plugins/font-awesome/font-awesome.css')
            ->addCssUrl('cms/plugins/simple-line-icons/simple-line-icons.css')
            ->addCssUrl('cms/plugins/bootstrap/css/bootstrap.css')
            ->addCssUrl('cms/plugins/uniform/css/uniform.default.css')
            ->addCssUrl('cms/plugins/bootstrap-switch/css/bootstrap-switch.css')
            ->addCssUrl('cms/plugins/pace/pace-theme-minimal.css')
            ->addCssUrl('cms/plugins/select2/select2.css')
            // Theme styles
            ->addCssUrl('cms/css/components.css')
            ->addCssUrl('cms/css/plugins.css')
            ->addCssUrl('cms/layout/css/layout.css')
//            ->addCssUrl('cms/layout/css/themes/default.css') // TODO can switch in Settings
            ->addCssUrl('cms/layout/css/themes/darkblue.css') // TODO can switch in Settings
            ->addCssUrl('cms/layout/css/custom.css')
            ->addCssUrl('plugins/toastr/toastr.min.css')
            ->addJsUrl('cms/jquery-1.11.0.min.js')
            // Cms overwrites
            ->addCssUrl('cms/cms_css.css')

//            ->addCssUrl('css/font-awesome.css') // TODO remove all old files that are not in assets/cms/ folder
//            ->addCssUrl('css/metronic/simple-line-icons.css')
//            ->addCssUrl('bootstrap/css/bootstrap.min.css')
//            ->addCssUrl('css/metronic/components.css')
//            ->addCssUrl('css/metronic/layout.css')
//            ->addCssUrl('css/metronic/darkblue.css')
//            ->addCssUrl('css/themify-icons.css')
//            ->addCssUrl('css/animate.min.css')
//            ->addCssUrl('css/skins/palette.css')
//            ->addCssUrl('css/fonts/font.css')
//            ->addCssUrl('css/main.css')
//            ->addJsUrl('plugins/modernizr.js')
//            ->addCssUrl('css.css')
//                ->addCssUrl('print_css.css', 'print')
//                ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'jquery-2.1.0.min.js')
            ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'jquery.form.min.js')// Ajaxify forms
//                ->addJsUrl('js/jquery.bpopup.min.js')// Popup modals
            ->addJs('var cms_data = {context_menu_items: {}};') // Required for global data
            ->addJs('cms_data.cfg_domain="' . CFG_DOMAIN . '"') // Required for notifications
            ->addJs('cms_data.site_name="' . $config->get('site')['name'] . '"') // Required for notifications
            ->addJsUrl('cms_js.js')
//                ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'scripts.js')
            ->addJsUrl('plupload/plupload.full.min.js')
        ;

        // Script for sending JS errors
        if (CFG_MAIL_ERRORS && Settings::isProductionState() && !Settings::get('do_not_send_js_errors')) {
            PageHead::getInstance()
                    ->addJsUrl('send_error.js')
                    ->addJs('register_js_error.ini(\'' . DIR_CMS_URL . '\');')
            ;
        }

        PageTail::getInstance()
            // Global scripts
            ->addJsUrl('cms/jquery-migrate-1.2.1.min.js')
            ->addJsUrl('cms/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js')
            ->addJsUrl('cms/plugins/bootstrap/js/bootstrap.min.js') // This must be after jquery-ui.custom.js
            ->addJsUrl('cms/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js')
            ->addJsUrl('cms/plugins/jquery-slimscroll/jquery.slimscroll.min.js')
            ->addJsUrl('cms/jquery.blockui.min.js')
            ->addJsUrl('cms/jquery.cokie.min.js')
            ->addJsUrl('cms/plugins/uniform/jquery.uniform.min.js')
            ->addJsUrl('cms/plugins/bootstrap-switch/js/bootstrap-switch.min.js')
            ->addCssUrl('cms/plugins/jquery-contextmenu/jquery.contextMenu.css')
            ->addJsUrl('cms/plugins/jquery-contextmenu/jquery.contextMenu.js')
            // Pages
            ->addJsUrl('cms/plugins/jquery-validation/js/jquery.validate.min.js')
            ->addJsUrl('cms/plugins/backstretch/jquery.backstretch.min.js')
            ->addJsUrl('cms/plugins/select2/select2.min.js')
            // Final scripts
            ->addJsUrl('cms/metronic.js')
            ->addJsUrl('cms/layout/scripts/layout.js')
            ->addJsUrl('cms/layout/scripts/quick-sidebar.js')
            ->addJsUrl('cms/plugins/pace/pace.js')
//                ->addCssURL('plugins/chosen/chosen.min.css') // Beautify selects
//                ->addJsURL('context_menu/menu.js') // Context menu
//                ->addJsUrl('bootstrap/js/bootstrap.js')
//                ->addJsUrl('plugins/jquery.slimscroll.min.js')
//                ->addJsUrl('plugins/jquery.easing.min.js') // Animations
//                ->addJsUrl('plugins/appear/jquery.appear.js')
//                ->addJsUrl('plugins/jquery.placeholder.js')
//                ->addJsUrl('plugins/fastclick.js')
//                ->addJsUrl('js/offscreen.js')
//                ->addJsUrl('js/main.js')
//                ->addJsUrl('js/buttons.js')
            ->addJsUrl('plugins/toastr/toastr.min.js') // Notifications
//                ->addJsUrl('js/notifications.js')
//                ->addJsUrl('plugins/chosen/chosen.jquery.min.js')
//                ->addJsUrl('plugins/chosen/chosen.order.jquery.js')
//                ->addJsURL('ckeditor/ckeditor.js') // Wysiwyg
            ->addJsUrl('plugins/parsley.js') // Input validation
            ->addJsUrl('cms/respond.min.js')
            ->addJsUrl('cms/excanvas.min.js')
            ->addJs('$(function() {
               $(".chosen").select2();
               Metronic.init();
               Layout.init();
               QuickSidebar.init();
            });')
        ;

        // Search for custom css
        $custom_css_url = DIR_ASSETS_URL . 'cms.css';
        if (file_exists(DIR_BASE . $custom_css_url)) {
            PageHead::getInstance()->addCssUrl($custom_css_url);
        } else {
            PageHead::getInstance()->addCustomString('<!--Create file "'. $custom_css_url .'" if you wish to use custom css file-->');
        }

        // Set head for page
        Page::setHead(PageHead::getInstance());
    }

    private function parseMenu()
    {
        $menu = Menu::getInstance();

        $all_menu_items = [
            'system',
            'home' => [
                'icon' => 'home',
            ],
            'structure' => [
                'icon' => 'layers',
            ],
            'users' => [
                'icon' => 'user',
            ],
            'tools' => [
                'icon' => 'wrench',
            ],
//            'modules',
        ];

        // Combine items
        $custom_items = [];
        if (file_exists(DIR_FRONT . 'menu.php')) {
            $custom_items = include_once DIR_FRONT . 'menu.php';
        }
        $all_menu_items = array_merge($custom_items, $all_menu_items);

        // For every main menu item search for module and submenu
        foreach ($all_menu_items as $main_menu_key => $main_menu_data) {
            $menu_class = 'TMCms\Admin\\'. Converter::to_camel_case($main_menu_key) .'\\Cms' . Converter::to_camel_case($main_menu_key);
            if (!class_exists($menu_class)) {
                $menu_class = 'TMCms\Modules\\'. Converter::to_camel_case($main_menu_key) .'\\Cms' . Converter::to_camel_case($main_menu_key);
            }
            if (class_exists($menu_class)) {
                $reflection = new \ReflectionClass($menu_class);
                $filename = $reflection->getFileName();
                $folder = dirname($filename);
                $module_menu_file = $folder . '/menu.php';
                if (file_exists($module_menu_file)) {
                    $module_menu_data = include_once $module_menu_file;
                    $all_menu_items[$main_menu_key] = array_merge($all_menu_items[$main_menu_key], $module_menu_data);
                }
            }
        }

        // Add menu items
        foreach ($all_menu_items as $key => $item) {
            // Separator
            if (!is_array($item)) {
                $menu->addMenuSeparator($item);
            } else {
                // Add menu item if have access to page
                if ($key && Users::getInstance()->checkAccess($key, '_default')) {
                    $menu->addMenuItem($key, $item);
                }
            }
        }

        // Add translations if have project-related files
        Finder::getInstance()->addTranslationsSearchPath(DIR_FRONT . 'translations/');
    }

    private function generateContent()
    {
        ob_start();

        // Requesting P page
        $method = false;

        $call_object = false;
        // Find in classes under Vendor - Modules
        $real_class = Converter::to_camel_case(P);
        $class = '\TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
        if (!class_exists($class)) {
            // Not vendor module - check main CMS admin object
            $class = '\TMCms\Admin\\' . $real_class . '\Cms' . $real_class;
            $call_object = true;
            if (!class_exists($class)) {
                // Search for exclusive module CMS pages created in Project folder for this individual site
                $file_path = DIR_MODULES . strtolower($real_class) . '/' . 'Cms' . $real_class . '.php';
                if (file_exists($file_path)) {
                    require_once $file_path;

                    // Check for module itself
                    $file_path = DIR_MODULES . strtolower($real_class) . '/' . 'Module' . $real_class . '.php';
                    if (file_exists($file_path)) {
                        require_once $file_path;
                    }
                    // Require all objects class files
                    $objects_path = DIR_MODULES . strtolower($real_class) . '/Entity/';
                    if (file_exists($objects_path)) {
                        foreach (array_diff(scandir($objects_path), ['.', '..']) as $object_file) {
                            require_once $objects_path . $object_file;
                        }
                    }

                    // CmsClass
                    $real_class = Converter::to_camel_case(P);
                    $class = '\TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
                }
            }
        }

        // Try autoload PSR-0 or PSR-4
        if (!class_exists($class)) {
            $class = 'TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
        }

        // Try to find the right directory of requested class
        if (!class_exists($class)) {
            $class_name = 'Cms' . $real_class;

            $directory_iterator = new RecursiveDirectoryIterator(DIR_MODULES);
            $iterator = new RecursiveIteratorIterator($directory_iterator);

            foreach ($iterator as $file) {
                if ($file->getFilename() == $class_name . '.php') {
                    $module_path = $file->getPathInfo()->getPathName();
                    $module_name = $file->getPathInfo()->getFilename();

                    $module_directory_iterator = new RecursiveDirectoryIterator($module_path);
                    $module_iterator = new RecursiveIteratorIterator($module_directory_iterator);

                    foreach ($module_iterator as $module_file) {
                        $module_file_directory = $module_file->getPathInfo()->getFilename();
                        $module_file_name = $module_file->getFileName();

                        if (!in_array($module_file_name, ['.', '..']) and in_array($module_file_directory, [$module_name, 'Entity'])) {
                            require_once $module_file->getPathName();
                        }
                    }

                    $class = implode('\\', ['\TMCms', 'Modules', $module_name, $class_name]);

                    break;
                }
            }
        }

        // Still no class
        if (!class_exists($class)) {
            dump('Requested class "' . $class . '" not found');
        }

        // Check existence of requested method
        if (class_exists($class)) {
            $call_object = true;

            // Check requested method exists or set default
            if (method_exists($class, P_DO)) {
                $method = P_DO;
            } else {
                $method = '_default';
            }

            // Final check we have anything to run
            if (!method_exists($class, $method)) {
                dump('Method "' . $method . '" not found in class "' . $class . '"');
            }
        }

        // Check user's permission
        if (!Users::getInstance()->checkAccess(P, $method)) {
            error('You do not have permissions to access this page ("' . P . ' - ' . $method . '")');
            die;
        }

        // Call required method
        if ($call_object) {
            $obj = new $class;
            $obj->{$method}();
        } else {
            call_user_func([$class, $method]);
        }

        $this->content = ob_get_clean();
    }
}