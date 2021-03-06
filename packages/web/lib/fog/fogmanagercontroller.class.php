<?php
abstract class FOGManagerController extends FOGBase {
    protected $childClass;
    protected $loadQueryTemplate = 'SELECT * FROM `%s` %s %s %s %s %s';
    protected $loadQueryGroupTemplate = 'SELECT * FROM (%s) `%s` %s %s %s %s %s';
    protected $countQueryTemplate = 'SELECT COUNT(`%s`.`%s`) AS `total` FROM `%s`%s LIMIT 1';
    protected $updateQueryTemplate = 'UPDATE `%s` SET %s %s';
    protected $destroyQueryTemplate = "DELETE FROM `%s` WHERE `%s`.`%s` IN ('%s')";
    protected $existsQueryTemplate = "SELECT COUNT(`%s`.`%s`) AS `total` FROM `%s` WHERE `%s`.`%s`='%s' AND `%s`.`%s` <> '%s'";
    public function __construct() {
        parent::__construct();
        $this->childClass = preg_replace('#_?Manager$#', '', get_class($this));
        $classVars = $this->getClass($this->childClass,'',true);
        $this->databaseTable = $classVars['databaseTable'];
        $this->databaseFields = $classVars['databaseFields'];
        $this->databaseFieldsRequired = $classVars['databaseFieldsRequired'];
        $this->databaseFieldClassRelationships = $classVars['databaseFieldClassRelationships'];
        $this->additionalFields = $classVars['additionalFields'];
        unset($classVars);
    }
    public function find($findWhere = array(), $whereOperator = 'AND', $orderBy = 'name', $sort = 'ASC', $compare = '=', $groupBy = false, $not = false, $idField = false,$onecompare = true,$filter = 'array_unique') {
        // Fail safe defaults
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        if (empty($sort)) $sort = 'ASC';
        $this->orderBy($orderBy);
        if (empty($compare)) $compare = '=';
        $not = ($not ? ' NOT ' : ' ');
        if (count($findWhere)) {
            $count = 0;
            foreach ((array)$findWhere AS $field => &$value) {
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s`%sIN ('%s')",$this->databaseTable,$this->databaseFields[$field],$not,implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s%s",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? $not.'LIKE ' : (trim($not) ? '!' : '').($onecompare ? (!$count ? $compare : '=') : $compare)), ($value === 0 || $value ? "'".(string)$value."'" : null));
                $count++;
            }
            unset($value);
        }
        if (!is_array($orderBy)) {
            $orderBy = sprintf('ORDER BY `%s`.`%s`',$this->databaseTable,$this->databaseFields[$orderBy]);
            if ($groupBy) $groupBy = sprintf('GROUP BY `%s`.`%s`',$this->databaseTable,$this->databaseFields[$groupBy]);
            else $groupBy = '';
        } else $orderBy = '';
        list($join, $whereArrayAnd) = $this->getClass($this->childClass)->buildQuery($not, $compare);
        $isEnabled = false;
        if (!in_array($this->childClass,array('Image','Snapin','StorageNode')) && array_key_exists('isEnabled',$this->databaseFields)) $isEnabled = sprintf('`%s`=1',$this->databaseFields['isEnabled']);
        $query = sprintf(
            $this->loadQueryTemplate,
            $this->databaseTable,
            $join,
            (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
            (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
            $orderBy,
            $sort
        );
        if ($groupBy) {
            $query = sprintf(
                $this->loadQueryGroupTemplate,
                sprintf(
                    $this->loadQueryTemplate,
                    $this->databaseTable,
                    $join,
                    (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
                    (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
                    $orderBy,
                    $sort
                ),
                $this->databaseTable,
                $join,
                (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
                (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
                $groupBy,
                $orderBy,
                $sort
            );
        }
        $data = array();
        if ($idField) {
            if (is_array($idField)) {
                foreach ($idField AS $i => &$idstore) {
                    $idstore = trim($idstore);
                    $ids[$idstore] = array_map('html_entity_decode',array_values((array)array_filter(@$filter($this->DB->query($query)->fetch('','fetch_all')->get($this->databaseFields[$idstore])))));
                }
                unset($idstore);
            } else {
                $idField = trim($idField);
                $ids = array_map('html_entity_decode',array_values((array)array_filter((array)@$filter($this->DB->query($query)->fetch('','fetch_all')->get($this->databaseFields[$idField])))));
            }
            $data = $ids;
        } else {
            $queryData = $this->DB->query($query)->fetch('','fetch_all')->get();
            foreach ((array)$queryData AS $i => &$row) $data[] = $this->getClass($this->childClass)->setQuery($row);
            unset($row);
        }
        return (array)$data;
    }
    public function count($findWhere = array(), $whereOperator = 'AND', $compare = '=') {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        if (count($findWhere)) {
            foreach ((array)$findWhere AS $field => &$value) {
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s` IN ('%s')",$this->databaseTable,$this->databaseFields[$field],implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s'%s'",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? 'LIKE' : $compare), (string)$value);
            }
            unset($value);
        }
        $isEnabled = false;
        if (!in_array($this->childClass,array('Image','Snapin')) && array_key_exists('isEnabled',$this->databaseFields)) $isEnabled = sprintf('`%s`=1',$this->databaseFields['isEnabled']);
        $query = sprintf(
            $this->countQueryTemplate,
            $this->databaseTable,
            $this->databaseFields['id'],
            $this->databaseTable,
            (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : ''))
        );
        return (int)$this->DB->query($query)->fetch()->get('total');
    }
    public function update($findWhere = array(), $whereOperator = 'AND', $insertData) {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        $insertArray = array();
        foreach ((array)$insertData AS $field => &$value) {
            $field = trim($field);
            $insertKey = sprintf('`%s`.`%s`',$this->databaseTable,$this->databaseFields[$field]);
            $insertVal = $this->DB->sanitize($value);
            $insertArray[] = sprintf("%s='%s'",$insertKey,$insertVal);
        }
        unset($value);
        if (count($findWhere)) {
            foreach ((array)$findWhere AS $field => &$value) {
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s` IN ('%s')",$this->databaseTable,$this->databaseFields[$field],implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s'%s'",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? 'LIKE' : '='), (string)$value);
            }
            unset($value);
        }
        $query = sprintf(
            $this->updateQueryTemplate,
            $this->databaseTable,
            implode(',',(array)$insertArray),
            (count($whereArray) ? ' WHERE '.implode(' '.$whereOperator.' ',(array)$whereArray) : '')
        );
        return (bool)$this->DB->query($query)->fetch()->get();
    }
    public function destroy($findWhere = array(), $whereOperator = 'AND', $orderBy = 'name', $sort = 'ASC', $compare = '=', $groupBy = false, $not = false) {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        $this->orderBy($orderBy);
        if (empty($sort)) $sort = 'ASC';
        if (empty($compare)) $compare = '=';
        if (array_key_exists('id',$findWhere)) $ids = $findWhere['id'];
        else $ids = $this->find($findWhere, $whereOperator, $orderBy, $sort, $compare, $groupBy, $not, 'id');
        $query = sprintf(
            $this->destroyQueryTemplate,
            $this->databaseTable,
            $this->databaseTable,
            $this->databaseFields['id'],
            implode("','",(array)$ids)
        );
        return $this->DB->query($query)->fetch()->get();
    }
    public function buildSelectBox($matchID = '', $elementName = '', $orderBy = 'name', $filter = '', $template = false) {
        $matchID = ($_REQUEST['node'] == 'image' ? ($matchID === 0 ? 1 : $matchID) : $matchID);
        if (empty($elementName)) $elementName = strtolower($this->childClass);
        $this->orderBy($orderBy);
        $Objects = $this->find($filter ? array('id'=>$filter) : '', '', $orderBy, '', '', '',($filter ? true : false));
        foreach ($Objects AS $i => &$Object) {
            if (array_key_exists('isEnabled',$this->databaseFields) && !$Object->get('isEnabled')) continue;
            $listArray[] = sprintf('<option value="%s"%s>%s</option>',$Object->get('id'),($matchID == $Object->get('id') ? ' selected' : ($template ? ' ${selected_item'.$Object->get('id').'}' : '')),$Object->get('name').' - ('.$Object->get('id').')');
            unset($Object);
        }
        return (isset($listArray) ? sprintf('<select name="%s" autocomplete="off"><option value="">%s</option>%s</select>',($template ? '${selector_name}' : $elementName),'- '.$this->foglang[PleaseSelect].' -',implode($listArray)) : false);
    }
    public function exists($name, $id = 0, $idField = 'name') {
        if (empty($id)) $id = 0;
        if (empty($idField)) $idField = 'name';
        $query = sprintf(
            $this->existsQueryTemplate,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $this->databaseTable,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $name,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $id
        );
        return (bool)$this->DB->query($query)->fetch()->get('total');
    }
    public function search($keyword = '',$returnObjects = false) {
        if (empty($keyword)) $keyword = trim($this->isMobile ? $_REQUEST['host-search'] : $_REQUEST['crit']);
        $mac_keyword = join(':',str_split(str_replace(array('-',':'),'',$keyword),2));
        $mac_keyword = preg_replace('#[%\+\s\+]#','%',sprintf('%%%s%%',$mac_keyword));
        if (empty($keyword)) $keyword = '%';
        if ($keyword === '%') return $this->getClass($this->childClass)->getManager()->find();
        $keyword = preg_replace('#[%\+\s\+]#','%',sprintf('%%%s%%',$keyword));
        $_SESSION['caller'] = __FUNCTION__;
        $this->array_remove($this->aliasedFields,$this->databaseFields);
        $findWhere = array_fill_keys(array_keys($this->databaseFields),$keyword);
        $itemIDs = $this->getSubObjectIDs($this->childClass,$findWhere,'id','','OR');
        $HostIDs = $this->getSubObjectIDs('MACAddressAssociation',array('mac'=>$mac_keyword,'description'=>$keyword),'hostID','','OR');
        $HostIDs = array_merge($HostIDs,$this->getSubObjectIDs('Inventory',array('sysserial'=>$keyword,'caseserial'=>$keyword,'mbserial'=>$keyword,'primaryUser'=>$keyword,'other1'=>$keyword,'other2'=>$keyword,'sysman'=>$keyword,'sysproduct'=>$keyword),'hostID','','OR'));
        $HostIDs = array_merge($HostIDs,$this->getSubObjectIDs('Host',array('name'=>$keyword,'description'=>$keyword,'ip'=>$keyword),'','','OR'));
        switch (strtolower($this->childClass)) {
        case 'user':
            break;
        case 'host':
            $ImageIDs = $this->getSubObjectIDs('Image',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $GroupIDs = $this->getSubObjectIDs('Group',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $SnapinIDs = $this->getSubObjectIDs('Snapin',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $PrinterIDs = $this->getSubObjectIDs('Printer',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            if (count($ImageIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Host',array('imageID'=>$ImageIDs)));
            if (count($GroupIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('GroupAssociation',array('groupID'=>$GroupIDs),'hostID'));
            if (count($SnapinIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('SnapinAssociation',array('snapinID'=>$SnapinIDs),'hostID'));
            if (count($PrinterIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('PrinterAssociation',array('printerID'=>$PrinterIDs),'hostID'));
            $itemIDs = array_merge($itemIDs,$HostIDs);
            break;
        case 'image':
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Host',array('id'=>$HostIDs),'imageID'));
            break;
        case 'task':
            $TaskStateIDs = $this->getSubObjectIDs('TaskState',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $TaskTypeIDs = $this->getSubObjectIDs('TaskType',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $ImageIDs = $this->getSubObjectIDs('Image',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $GroupIDs = $this->getSubObjectIDs('Group',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $SnapinIDs = $this->getSubObjectIDs('Snapin',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $PrinterIDs = $this->getSubObjectIDs('Printer',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            if (count($ImageIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Host',array('imageID'=>$ImageIDs)));
            if (count($GroupIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('GroupAssociation',array('groupID'=>$GroupIDs),'hostID'));
            if (count($SnapinIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('SnapinAssociation',array('snapinID'=>$SnapinIDs),'hostID'));
            if (count($PrinterIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('PrinterAssociation',array('printerID'=>$PrinterIDs),'hostID'));
            if (count($TaskStateIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Task',array('stateID'=>$TaskStateIDs)));
            if (count($TaskTypeIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Task',array('typeID'=>$TaskTypeIDs)));
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs('Task',array('hostID'=>$HostIDs)));
            break;
        default:
            $assoc = sprintf('%sAssociation',$this->childClass);
            $objID = sprintf('%sID',strtolower($this->childClass));
            if (!class_exists($assoc)) break;
            if (count($itemIDs) && !count($HostIDs)) break;
            $HostIDs = array_merge($HostIDs,$this->getSubObjectIDs($assoc,array($objID=>$itemIDs),'hostID'));
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,$this->getSubObjectIDs($assoc,array('hostID'=>$HostIDs),$objID));
            break;
        }
        $itemIDs = array_values(array_filter(array_unique($itemIDs)));
        if ($returnObjects) return $this->getClass($this->childClass)->getManager()->find(array('id'=>$itemIDs));
        return $itemIDs;
    }
}
