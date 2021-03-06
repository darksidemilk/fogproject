<?php
abstract class FOGClient extends FOGBase {
    protected $newService;
    protected $Host;
    public function __construct($service = true,$encoded = false,$hostnotrequired = false,$returnmacs = false,$override = false) {
        try {
            parent::__construct();
            header('Content-Type: text/plain');
            $this->newService = isset($_REQUEST['newService']);
            $this->Host = $this->getHostItem($service,$encoded,$hostnotrequired,$returnmacs,$override);
            $this->send();
            if (in_array(strtolower(get_class($this)),array('autologout','displaymanager','printerclient','servicemodule'))) throw new Exception($this->send);
            $this->sendData(trim($this->send));
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
}
