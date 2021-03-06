<?php
class MulticastSessions extends FOGController {
    protected $databaseTable = 'multicastSessions';
    protected $databaseFields = array(
        'id' => 'msID',
        'name' => 'msName',
        'port' => 'msBasePort',
        'logpath' => 'msLogPath',
        'image' => 'msImage',
        'clients' => 'msClients',
        'sessclients' => 'msSessClients',
        'interface' => 'msInterface',
        'starttime' => 'msStartDateTime',
        'percent' => 'msPercent',
        'stateID' => 'msState',
        'completetime' => 'msCompleteDateTime',
        'isDD' => 'msIsDD',
        'NFSGroupID' => 'msNFSGroupID',
        'anon3' => 'msAnon3',
        'anon4' => 'msAnon4',
        'anon5' => 'msAnon5',
    );
    public function getImage() {
        return $this->getClass('Image',$this->get('image'));
    }
    public function getTaskState() {
        return $this->getClass('TaskState',$this->get('stateID'));
    }
    public function cancel() {
        return $this->set('stateID',$this->getCancelledState());
    }
}
