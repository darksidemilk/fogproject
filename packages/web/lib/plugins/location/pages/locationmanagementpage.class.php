<?php
class LocationManagementPage extends FOGPage {
    public $node = 'location';
    public function __construct($name = '') {
        $this->name = 'Location Management';
        parent::__construct($this->name);
        if ($_REQUEST['id']) {
            $this->subMenu = array(
                "$this->linkformat" => $this->foglang['General'],
                $this->membership => $this->foglang['Membership'],
                "$this->delformat" => $this->foglang['Delete'],
            );
            $this->notes = array(
                $this->foglang['Location'] => $this->obj->get('name'),
                "{$this->foglang['Storage']} {$this->foglang['Group']}" => $this->obj->getStorageGroup(),
            );
            if ($this->obj->getStorageNode()->isValid()) $this->notes["{$this->foglang['Storage']} {$this->foglang['Node']}"] = $this->obj->getStorageNode();
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" checked/>',
            _('Location Name'),
            _('Storage Group'),
            _('Storage Node'),
            _('TFTP Server'),
        );
        $this->templates = array(
            '<input type="checkbox" name="location[]" value="${id}" class="toggle-action" checked/>',
            '<a href="?node=location&sub=edit&id=${id}" title="Edit">${name}</a>',
            '${storageGroup}',
            '${storageNode}',
            '${tftp}',
        );
        $this->attributes = array(
            array('class' => 'l filter-false','width'=>16),
            array('class' => 'l'),
            array('class' => 'l'),
            array('class' => 'c'),
            array('class' => 'r'),
        );
    }
    public function index() {
        $this->title = _('Search');
        if ($this->getSetting('FOG_DATA_RETURNED')>0 && $this->getClass('LocationManager')->count() > $this->getSetting('FOG_DATA_RETURNED') && $_REQUEST['sub'] != 'list') $this->redirect(sprintf('?node=%s&sub=search',$this->node));
        foreach ((array)$this->getClass('LocationManager')->find() AS $i => &$Location) {
            if (!$Location->isValid()) continue;
            $StorageGroup = $this->getClass('StorageGroup',$Location->get('storageGroupID'));
            if (!$StorageGroup->isValid()) continue;
            $this->data[] = array(
                'id'=>$Location->get('id'),
                'name'=>$Location->get('name'),
                'storageNode'=>($Location->get('storageNodeID')?$this->getClass('StorageNode',$Location->get('storageNodeID'))->get('name'):'Not Set'),
                'storageGroup'=>$StorageGroup->get('name'),
                'tftp'=>$Location->get('tftp')?_('Yes'):_('No'),
            );
            unset($Location);
        }
        $this->HookManager->processEvent('LOCATION_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function search_post() {
        foreach ($this->getClass('LocationManager')->search('',true) AS $i => &$Location) {
            if (!$Location->isValid()) continue;
            $this->data[] = array(
                'id'=>$Location->get('id'),
                'name'=>$Location->get('name'),
                'storageGroup'=>$this->getClass('StorageGroup',$Location->get('storageGroupID'))->get('name'),
                'storageNode'=>$Location->get('storageNodeID')?$this->getClass('StorageNode',$Location->get('storageNodeID'))->get('name') : _('Not Set'),
                'tftp'=>$Location->get('tftp') ? _('Yes') : _('No'),
            );
            unset($Location);
        }
        $this->HookManager->processEvent('LOCATION_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function add() {
        $this->title = _('New Location');
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            _('Location Name') => '<input class="smaller" type="text" name="name" />',
            _('Storage Group') => $this->getClass('StorageGroupManager')->buildSelectBox(),
            _('Storage Node') => $this->getClass('StorageNodeManager')->buildSelectBox(),
            _('TFTP From Node') => '<input type="checkbox" name="tftp" value="on" />',
            '' => sprintf('<input name="add" class="smaller" type="submit" value="%s"/>',_('Add')),
        );
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
            unset($input);
        }
        unset($fields);
        $this->HookManager->processEvent('LOCATION_ADD',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        echo '</form>';
    }
    public function add_post() {
        try {
            $name = trim($_REQUEST['name']);
            if ($this->getClass('LocationManager')->exists(trim($_REQUEST['name']))) throw new Exception(_('Location already Exists, please try again.'));
            if (!$name) throw new Exception(_('Please enter a name for this location.'));
            if (empty($_REQUEST['storagegroup'])) throw new Exception(_('Please select the storage group this location relates to.'));
            $Location = $this->getClass('Location')
                ->set('name',$name)
                ->set('storageGroupID',$_REQUEST['storagegroup'])
                ->set('storageNodeID',$_REQUEST['storagenode'])
                ->set('tftp',$_REQUEST['tftp']);
            if ($_REQUEST['storagenode'] && $Location->get('storageGroupID') != $this->getClass('StorageNode',$_REQUEST['storagenode'])->get('storageGroupID')) $Location->set('storageGroupID',$this->getClass('StorageNode',$_REQUEST['storagenode'])->get('storageGroupID'));
            if (!$Location->save()) throw new Exception(_('Failed to create'));
            $this->setMessage(_('Location Added, editing!'));
            $this->redirect(sprintf('?node=location&sub=edit&id=%s',$Location->get('id')));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function edit() {
        $this->title = sprintf('%s: %s',_('Edit'),$this->obj->get('name'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            _('Location Name') => sprintf('<input class="smaller" type="text" name="name" value="%s"/>',$this->obj->get('name')),
            _('Storage Group') => $this->getClass('StorageGroupManager')->buildSelectBox($this->obj->get('storageGroupID')),
            _('Storage Node') => $this->getClass('StorageNodeManager')->buildSelectBox($this->obj->get('storageNodeID')),
            _('TFTP From Node') => sprintf('<input type="checkbox" name="tftp" value="on"%s/>',$this->obj->get('tftp') ? ' checked' : ''),
            '&nbsp;' => sprintf('<input type="submit" class="smaller" name="update" value="%s"/>',_('Update')),
        );
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
            unset($input);
        }
        unset($fields);
        $this->HookManager->processEvent('LOCATION_EDIT',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s&id=%d">',$this->formAction,$this->obj->get('id'));
        $this->render();
        echo '</form>';
    }
    public function edit_post() {
        $this->HookManager->processEvent('LOCATION_EDIT_POST',array('Location'=> &$this->obj));
        try {
            if ($_REQUEST['name'] != $this->obj->get('name') && $this->obj->getManager()->exists($_REQUEST['name'])) throw new Exception(_('A location with that name already exists.'));
            if (isset($_REQUEST['update'])) {
                if ($_REQUEST['storagegroup']) {
                    $this->obj
                        ->set('name',$_REQUEST['name'])
                        ->set('storageGroupID',$_REQUEST['storagegroup']);
                }
                $this->obj
                    ->set('storageNodeID',$_REQUEST['storagenode'])
                    ->set('tftp',$_REQUEST['tftp']);
                if (!$this->obj->save()) throw new Exception(_('Failed to update'));
                $this->setMessage(_('Location Updated'));
                $this->redirect(sprintf('?node=location&sub=edit&id=%d',$this->obj->get('id')));
            }
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
}
