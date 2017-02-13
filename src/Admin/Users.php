<?php

namespace TMCms\Admin;

use Composer\Config;
use TMCms\Admin\Entity\UsersSessionEntity;
use TMCms\Admin\Entity\UsersSessionEntityRepository;
use TMCms\Admin\Structure\Entity\StructurePagePermissionRepository;
use TMCms\Admin\Users\Entity\AdminUserGroup;
use TMCms\Admin\Users\Entity\GroupAccess;
use TMCms\Admin\Users\Entity\GroupAccessRepository;
use TMCms\Admin\Users\Entity\AdminUserGroupRepository;
use TMCms\Admin\Users\Entity\AdminUser;
use TMCms\Admin\Users\Entity\AdminUserRepository;
use TMCms\Config\Configuration;
use TMCms\Log\App;
use TMCms\Routing\Languages;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class Users represents admin panel users and their sessions
 */
class Users
{
    use singletonOnlyInstanceTrait;
    /**
     * NEVER change salt on sites in use!!! Only for new project with empty install
     * @var string
     */
    private static $salt = 'D%GG*dsac3425';
    /**
     * @var bool
     */
    private static $is_logged;
    private static $cached_group_pairs = [];
    private static $cached_users_pairs = [];
    private static $cached_users_pairs_with_groups;
    private static $cached_group_data = [];
    private static $cached_user_data = [];
    private static $access = [];
    private static $_structure_permissions = [];
    private $_online_status_cached = [];

    /**
     * Generate unique hash for password or any other string
     * @param string $string e.g. real password
     * @param bool $salt supply random string
     * @param string $algorithm name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
     * @return string
     */
    public function generateHash($string, $salt = false, $algorithm = 'whirlpool')
    {
        $string = trim($string);
        return hash($algorithm, ($salt ? $salt : self::$salt) . $string);
    }

    /**
     * Check that current user is logged-in
     * @return bool
     */
    public function isLogged()
    {
        return self::$is_logged || self::$is_logged = (bool)(
            isset($_SESSION['admin_logged'], $_SESSION['admin_id'], $_SESSION['admin_sid'])
            && $_SESSION['admin_logged'] && $_SESSION['admin_id']
            && $this->checkSession($_SESSION['admin_id'], $_SESSION['admin_sid'], true)
        );
    }

    public function isOnline($user_id) {
        if (isset($this->_online_status_cached[$user_id])) {
            return $this->_online_status_cached[$user_id];
        }

        // Check session for current user exists
        $sessions = new UsersSessionEntityRepository();
        $sessions->setWhereUserId($user_id);
        $sessions->setLimit(1);
        $sessions->addWhereFieldIsHigher('ts', (NOW - 600)); // 10 minutes
        return $this->_online_status_cached[$user_id] = $sessions->hasAnyObjectInCollection();
    }

    /**
     * Generate used session id using visitor data
     * @param int $user_id
     * @return string session id
     */
    private function generateUserSid($user_id)
    {
        return md5($user_id . VISITOR_HASH . NOW);
    }

    /**
     * Starts session for admin user
     * @param int $user_id
     * @return string session id
     */
    public function startSession($user_id)
    {
        $this->deleteOldSessions();

        $user_id = (int)$user_id;
        $sid = $this->generateUserSid($user_id);

        $session = UsersSessionEntityRepository::findOneEntityByCriteria([
            'sid' => $sid,
            'user_id' => $user_id,
        ]);
        if (!$session) {
            $session = new UsersSessionEntity();
        }
        $session->setSid($sid);
        $session->setUserId($user_id);
        $session->save();

        return $sid;
    }

    /**
     * After that user is logged-in
     * @param AdminUser $user
     * @return string session id
     */
    public function setUserLoggedIn($user)
    {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_id'] = $user->getId();
        $_SESSION['admin_login'] = $user->getLogin();
        $_SESSION['admin_sid'] = Users::getInstance()->startSession($user->getId());
        if (!defined('USER_ID')) {
            define('USER_ID', $user->getId());
        }

        App::add('User "' . $user->getLogin() . '" logged in.');
    }

    /**
     * Check whether user is logged-in
     * @param int $user_id
     * @param string $user_sid session
     * @param bool $touch prolong session
     * @return bool
     */
    private function checkSession($user_id, $user_sid, $touch = false)
    {
        $user_id = (int)$user_id;
        if (!defined('USER_ID')) {
            define('USER_ID', $user_id);
        }

        // Prolong session
        if ($touch) {
            $sessions = new UsersSessionEntityRepository();
            $sessions->setWhereSid($user_sid);
            /**
             * @var UsersSessionEntity $session
             */
            $session = $sessions->getFirstObjectFromCollection();
            if ($session) {
                $session->setTs(NOW);
                $session->save();
            }
        }

        // Check session for current user exists
        $sessions = new UsersSessionEntityRepository();
        $sessions->setWhereUserId($user_id);
        $sessions->setWhereSid($user_sid);
        return $sessions->hasAnyObjectInCollection();
    }

    /**
     * Removes current session, logs out admin user
     * @param int $user_id
     * @return bool
     */
    public function deleteSession($user_id)
    {
        if (!isset($_SESSION['admin_sid'])) {
            return true;
        }
        $user_id = (int)$user_id;

        $sessions = new UsersSessionEntityRepository();
        $sessions->setWhereUserId($user_id);
        $sessions->setWhereSid($_SESSION['admin_sid']);
        $sessions->deleteObjectCollection();

        return true;
    }

    /**
     * Delete outdated sessions for users that are no longer logged-in
     * @return bool sessions are deleted
     */
    private function deleteOldSessions()
    {
        // 2% chance of kicking idle sessions
        if (mt_rand(0, 49)) {
            return false;
        }

        $sessions = new UsersSessionEntityRepository();
        $sessions->setOnlyOutdated();
        $sessions->deleteObjectCollection();

        return true;
    }

    /**
     * Get admin user currently selected language
     * @param int $user_id
     * @return string
     */
    public function getUserLng($user_id = 0)
    {
        $user_id = (int)$user_id;

        $preferred_language = $this->getUserData('lng', $user_id);

        // Check if exists selected
        $languages = Languages::getPairs();
        if (!isset($languages[$preferred_language])) {
            // Get first language
            $preferred_language = key($languages);
        }

        return $preferred_language;
    }

    /**
     * Get User's data by key
     * @param string $key key to get value
     * @param int $id user id to get value from
     * @return string
     */
    public function getUserData($key, $id = 0)
    {
        // Current User id
        if (!$id) {
            if (defined('USER_ID')) {
                $id = USER_ID;
            } else {
                return NULL;
            }
        } else {
            $id = abs((int)$id);
        }

        // Check or init cache
        if (!isset(self::$cached_user_data[$id])) {
            $user = AdminUserRepository::findOneEntityById($id);
            if (!$user) {
                return NULL;
            }
            self::$cached_user_data[$id] = $user->getAsArray();

            // Unset critical data
            unset(self::$cached_user_data[$id]['password']);
        }

        return isset(self::$cached_user_data[$id][$key]) ? self::$cached_user_data[$id][$key] : NULL;
    }

    /**
     * Get Group's data by key
     * @param string $key
     * @param int $id
     * @return string
     */
    public function getGroupData($key, $id = 0)
    {
        // Get Group id
        if (!$id) {
            $id = $this->getUserData('group_id');
        } else {
            $id = abs((int)$id);
        }

        // Check or init cache
        if (!isset(self::$cached_group_data[$id])) {
            $group = new AdminUserGroup($id);
            self::$cached_group_data[$id] = $group->getAsArray();
        }

        return isset(self::$cached_group_data[$id][$key]) ? self::$cached_group_data[$id][$key] : NULL;
    }

    /**
     * Get key-value array of groups
     * @return array
     */
    public function getGroupsPairs()
    {
        // Check or init cache
        if (!self::$cached_group_pairs) {
            $user_collection = new AdminUserGroupRepository;
            $user_collection->addOrderByField('title');
            $pairs = $user_collection->getPairs('title');

            self::$cached_group_pairs = $pairs;
        }

        return self::$cached_group_pairs;
    }

    /**
     * Check whether user have access to open selected page
     * @param string $p
     * @param string $do
     * @param int $user_id
     * @return bool
     */
    public function checkAccess($p = P, $do = P_DO, $user_id = 0)
    {
        // Guest module can be opened by anyone
        if ($p === 'guest') {
            return true;
        }

        // Only logged-in Users can proceed further
        if (!$this->isLogged()) {
            return false;
        }

        // Get User id
        if (!$user_id) {
            $user_id = USER_ID;
        }

        // Check maybe User have group with full access
        $group_id = $this->getUserData('group_id', $user_id);
        if (self::getInstance()->getGroupData('full_access', $group_id)) {
            return true;
        }

        // Check or init cache
        if (!self::$access) {
            $group_access_collection = new GroupAccessRepository();
            $group_access_collection->setWhereGroupId((int)$group_id);
            foreach ($group_access_collection->getAsArrayOfObjectData() as $group_access) {
                /** @var GroupAccess $group_access */
                self::$access[$group_access['p']][$group_access['do']] = true;
            }
        }

        // Check access for exact admin panel page and action
        return isset(self::$access[$p][$do]);
    }

    /**
     * Permissions for site structure
     * @param int $page_id
     * @param string $action
     * @return bool
     */
    public function checkSitePagePermissions($page_id, $action)
    {
        $group_id = self::getInstance()->getUserData('group_id');

        // Check if current group have access to any page
        if (self::getInstance()->getGroupData('structure_permissions', $group_id)) {
            return true;
        }

        // Check and init cache
        if (!self::$_structure_permissions) {
            // Build access tree for every page
            $structure_pages = new StructurePagePermissionRepository();
            $structure_pages->setWhereGroupId((int)$group_id);
            foreach ($structure_pages->getAsArrayOfObjectData() as $v) {
                self::$_structure_permissions[$v['id']] = $v;
            }
        }

        // Check that user have access to desired action on selected stucture page
        return isset(self::$_structure_permissions[$page_id], self::$_structure_permissions[$page_id][$action]) && self::$_structure_permissions[$page_id][$action];
    }

    /**
     * Get key-value array of Users
     * @return array
     */
    public function getUsersPairs()
    {
        // Check and init cache
        if (!self::$cached_users_pairs) {
            $user_collection = new AdminUserRepository;
            $user_collection->addOrderByField('name');
            foreach ($user_collection->getAsArrayOfObjects() as $v) {
                /** @var AdminUser $v */
                self::$cached_users_pairs[$v->getId()] = $v->getName() . ' ' . $v->getSurname() . ' [' . $v->getLogin() . ']';
            }
        }

        return self::$cached_users_pairs;
    }

    /**
     * Get key-value array of Users
     * @return array
     */
    public function getUsersPairsWithGroupNamesForSelects()
    {
        // Check and init cache
        if (!self::$cached_users_pairs_with_groups) {
            $user_collection = new AdminUserRepository;
            $user_collection->addOrderByField('name');
            foreach ($user_collection->getAsArrayOfObjects() as $v) {
                /** @var AdminUser $v */

                $group = new AdminUserGroup($v->getGroupId());
                self::$cached_users_pairs_with_groups[$group->getTitle()][$v->getId()] = $v->getName() . ' ' . $v->getSurname() . ' [' . $v->getLogin() . ']';
            }
        }

        return self::$cached_users_pairs_with_groups;
    }

    /**
     * Installing new database. Use only when auto-creating new site
     */
    public function recreateDefaults()
    {
        // Administrator group
        /** @var AdminUserGroup $group */
        $group = AdminUserGroupRepository::findOneEntityById(1);

        // If no any Admin group - create new empty group
        if (!$group || !$group->getUndeletable() || !$group->getCanSetPermissions() || !$group->getFullAccess()) {
            if ($group) {
                $group->is_superadmin = true;
                $group
                    ->setField('undeletable', 1)
                    ->setField('can_set_permissions', 1)
                    ->setField('structure_permissions', 1)
                    ->setFullAccess(1)
                    ->save();
            } else {

                // Delete all groups
                $group_collection = new AdminUserGroupRepository();
                $group_collection->deleteObjectCollection();

                // Drop auto-increment value
                $group_collection->alterTableResetAutoIncrement();

                // Create new Group for Admins
                $group = new AdminUserGroup();
                $group->is_superadmin = true;
                $group->loadDataFromArray(
                    [
                        'undeletable' => 1,
                        'can_set_permissions' => 1,
                        'structure_permissions' => 1,
                        'full_access' => 1,
                        'title' => 'Developers',
                    ]
                );
                $group->save();

                // Create new Group for Managers
                $group = new AdminUserGroup();
                $group->is_superadmin = true;
                $group->loadDataFromArray(
                    [
                        'undeletable' => 0,
                        'can_set_permissions' => 1,
                        'structure_permissions' => 1,
                        'full_access' => 1,
                        'title' => 'Managers',
                        'default' => 1
                    ]
                );
                $group->save();

                echo '<br>Default User Group is created.<br>';
            }
        }

        unset($data);

        // Check we have any active Admin
        $users_collection = new AdminUserRepository();
        $users_collection->setWhereActive(1);
        $have_any_user = $users_collection->hasAnyObjectInCollection();

        // Check we have admin as first User
        $users_collection = new AdminUserRepository();
        $users_collection->setWhereActive(1);
        $users_collection->setWhereId(1);
        $users_collection->setWhereGroupId(1);
        $users_collection->setWhereLogin('neTpyceB'); // Name of vendor repo owner
        $have_default_user = $users_collection->hasAnyObjectInCollection();

        // Recreate default User
        if (!$have_any_user || !$have_default_user) {
            //Remove all Users
            $users_collection = new AdminUserRepository;
            $users_collection->deleteObjectCollection();

            // Reset auto-increment
            $users_collection->alterTableResetAutoIncrement();

            // Create new default Developer
            $user = new AdminUser;
            $user->loadDataFromArray(
                [
                    'group_id' => 1, // Developer
                    'login' => 'neTpyceB', // Name of vendor repo owner
                    'password' => $this->generateHash(Configuration::getInstance()->get('cms')['unique_key']), // Password is the same as unique key
                    'active' => 1
                ]
            );
            $user->save();

            // Create new default Manager
            $user = new AdminUser;
            $user->loadDataFromArray(
                [
                    'group_id' => 2, // Manager
                    'login' => 'manager',
                    'password' => $this->generateHash(''), // Empty password
                    'active' => 1
                ]
            );
            $user->save();

            echo '<br>Default User "manager" and empty password is created.
			<br>
			Please log in and change password.
			<br>';
        }
    }
}