<?php
/**
 * Access Control plugin
 *
 * PHP version 7
 *
 * @category AccessControlManagementPage
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Access Control plugin
 *
 * @category AccessControlManagementPage
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AccessControlManagementPage extends FOGPage
{
    /**
     * The node of this page.
     *
     * @var string
     */
    public $node = 'accesscontrol';
    /**
     * Constructor
     *
     * @param string $name The name for the page.
     *
     * @return void
     */
    public function __construct($name = '')
    {
        /**
         * The name to give.
         */
        $this->name = 'Role Management';
        parent::__construct($this->name);
        $this->headerData = [
            _('Role Name'),
            _('Role Description')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            []
        ];
    }
    /**
     * Create new role.
     *
     * @return void
     */
    public function add()
    {
        $this->title = _('Create New Role');

        $role = filter_input(INPUT_POST, 'accesscontrol');
        $description = filter_input(INPUT_POST, 'description');

        $labelClass = 'col-sm-2 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'role',
                _('Role Name')
            ) => self::makeInput(
                'form-control rolename-input',
                'role',
                _('Access Control Name'),
                'text',
                'role',
                $role,
                true
            ),
            self::makelabel(
                $labelClass,
                'description',
                _('Role Description')
            ) => self::makeTextarea(
                'form-control roledescription-input',
                'description',
                _('Role Description'),
                'description',
                $description
            )
        ];

        self::$HookManager->processEvent(
            'ACCESSCONTROL_ADD_FIELDS',
            [
                'fields' => &$fields,
                'AccessControl' => self::getClass('AccessControl')
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'role-create-form',
            $this->formAction,
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid" id="role-create">';
        echo '<div class="box-body">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Create New Role');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo self::makeButton(
            'send',
            _('Create'),
            'btn btn-primary'
        );
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Add post.
     *
     * @return void
     */
    public function addPost()
    {
        header('Content-type: application/json');
        self::$HookManger->processEvent('ACCESSCONTROL_ADD_POST');
        $role = trim(
            filter_input(INPUT_POST, 'role')
        );
        $description = trim(
            filter_input(INPUT_POST, 'description')
        );
        $serverFault = false;
        try {
            if (!$group) {
                throw new Exception(
                    _('A role name is required!')
                );
            }
            if (self::getClass('AccessControlManager')->exists($role)) {
                throw new Exception(
                    _('A role already exists with this name!')
                );
            }
            $AccessControl = self::getClass('AccessControl')
                ->set('name', $role)
                ->set('description', $description);
            if (!$AccessControl->save()) {
                $serverFault = true;
                throw new Exception(_('Add access control failed!'));
            }
            $code = 201;
            $hook = 'ACCESSCONTROL_ADD_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Access Control added!'),
                    'title' => _('Access Control Create Success')
                ]
            );
        } catch (Exception $e) {
            $code = ($serverFault ? 500 : 400);
            $hook = 'ACCESSCONTROL_ADD_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Access Control Create Fail')
                ]
            );
        }
        //header('Location: ../management/index.php?node=accesscontrol&sub=edit&id=' . $AccessControl->get('id'));
        self::$HookManager->processEvent(
            $hook,
            [
                'AccessControl' => &$AccessControl,
                'hook' => &$hook,
                'code' => &$code,
                'msg' => &$msg,
                'serverFault' => &$serverFault
            ]
        );
        http_response_Code($code);
        unset($AccessControl);
        echo $msg;
        exit;
    }
    /**
     * Displays the access control general tab.
     *
     * @return void
     */
    public function accesscontrolGeneral()
    {
    }
    /**
     * Updates the access control general element.
     *
     * @return void
     */
    public function accesscontrolGeneralPost()
    {
    }
    /**
     * The edit element.
     *
     * @return void
     */
    public function edit()
    {
    }
    /**
     * Update the edit elements.
     *
     * @return void
     */
    public function editPost()
    {
    }
}
