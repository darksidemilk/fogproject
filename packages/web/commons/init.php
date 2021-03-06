<?php
class Initiator {
    /** $HookPaths the paths were hooks are stored */
    public $HookPaths;
    /** $EventPaths the paths where events are stored */
    public $EventPaths;
    /** $FOGPaths the paths for the main fog stuff */
    public $FOGPaths;
    /** $PagePaths the paths where pages are stored */
    public $PagePaths;
    /** $plugPaths the plugin paths integrated with the other paths */
    public $plugPaths;
    /** __construct() Initiates to load the rest of FOG
     * @return void
     */
    public function __construct() {
        if (!isset($_SESSION)) {
            session_start();
            session_cache_limiter('no-cache');
        }
        define('BASEPATH', self::DetermineBasePath());
        $plugs = sprintf('%s%s%slib%splugins%s*',DIRECTORY_SEPARATOR,trim(str_replace(array('\\','/'),DIRECTORY_SEPARATOR,BASEPATH),DIRECTORY_SEPARATOR),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
        $path = sprintf('%s%s%slib%s%s%s',DIRECTORY_SEPARATOR,trim(str_replace(array('\\','/'),DIRECTORY_SEPARATOR,BASEPATH),DIRECTORY_SEPARATOR),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,'%s',DIRECTORY_SEPARATOR);
        $this->plugPaths = array_filter(glob($plugs),'is_dir');
        foreach($this->plugPaths AS $plugPath) {
            $plug_class[] = sprintf('%s%s%sclass%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_class[] = sprintf('%s%s%sclient%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_class[] = sprintf('%s%s%sreg-task%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_class[] = sprintf('%s%s%sservice%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_hook[] = sprintf('%s%s%shooks%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_event[] = sprintf('%s%s%sevents%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
            $plug_page[] = sprintf('%s%s%spages%s',DIRECTORY_SEPARATOR,trim($plugPath,'/'),DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR);
        }
        $FOGPaths = array();
        $FOGPaths = array(sprintf($path,'fog'),sprintf($path,'db'),sprintf($path,'client'),sprintf($path,'reg-task'),sprintf($path,'service'));
        $HookPaths = array(sprintf($path,'hooks'));
        $EventPaths = array(sprintf($path,'events'));
        $PagePaths = array(sprintf($path,'pages'));
        $this->FOGPaths = array_merge((array)$FOGPaths,(array)$plug_class);
        $this->HookPaths = array_merge((array)$HookPaths,(array)$plug_hook);
        $this->EventPaths = array_merge((array)$EventPaths,(array)$plug_event);
        $this->PagePaths = array_merge((array)$PagePaths,(array)$plug_page);
        set_include_path(sprintf('%s%s%s',implode(PATH_SEPARATOR,array_merge($this->FOGPaths,$this->PagePaths,$this->HookPaths,$this->EventPaths)),PATH_SEPARATOR,get_include_path()));
        spl_autoload_extensions('.class.php,.event.php,.hook.php');
        spl_autoload_register(array($this,'FOGLoader'));
    }
    /** DetermineBasePath() Gets the base path and sets WEB_ROOT constant
     * @return null
     */
    private static function DetermineBasePath() {
        $script_name = htmlentities($_SERVER['SCRIPT_NAME'],ENT_QUOTES,'utf-8');
        define('WEB_ROOT',sprintf('/%s',(preg_match('#/fog/#',$script_name)?'fog/':'')));
        return (file_exists('/srv/http/fog') ? '/srv/http/fog' : (file_exists('/var/www/html/fog') ? '/var/www/html/fog' : (file_exists('/var/www/fog') ? '/var/www/fog' : '/'.trim($_SERVER['DOCUMENT_ROOT'],'/').'/'.WEB_ROOT)));
    }
    /** __destruct() Cleanup after no longer needed
     * @return void
     */
    public function __destruct() {
        spl_autoload_unregister(array($this,'FOGLaoder'));
    }
    /** startInit() initiates the environment
     * @return void
     */
    public static function startInit() {
        @set_time_limit(0);
        @error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        self::verCheck();
        self::extCheck();
        foreach(array('node','sub','printertype','id','sub','crit','sort','confirm','tab') AS $x) {
            global $$x;
            if (isset($_REQUEST[$x])) $_REQUEST[$x] = $$x = trim(htmlentities(mb_convert_encoding($_REQUEST[$x],'UTF-8'),ENT_QUOTES,'UTF-8'));
            unset($x);
        }
        new System();
        new Config();
    }
    public function sanitize_items($value = '') {
        if (!$value) {
            foreach ($_REQUEST AS $key => &$val) {
                if (is_string($val)) $_REQUEST[$key] = htmlentities($val,ENT_QUOTES,'utf-8');
                else if (is_array($val)) $_REQUEST[$key] = $this->sanitize_items($val);
                unset($val);
            }
            foreach ($_GET AS $key => &$val) {
                if (is_string($val)) $_GET[$key] = htmlentities($val,ENT_QUOTES,'utf-8');
                else if (is_array($val)) $_GET[$key] = $this->sanitize_items($val);
                unset($val);
            }
            foreach ($_POST AS $key => &$val) {
                if (is_string($val)) $_POST[$key] = htmlentities($val,ENT_QUOTES,'utf-8');
                else if (is_array($val)) $_POST[$key] = $this->sanitize_items($val);
                unset($val);
            }
        } else {
            foreach ($value AS $key => &$val) {
                if (is_string($val)) $value[$key] = htmlentities($val,ENT_QUOTES,'utf-8');
                else if (is_array($val)) $value[$key] = $this->sanitize_items($val);
                unset($val);
            }
            return $value;
        }
    }
    /** verCheck() Checks the php version is good with current system
     * @return void
     */
    private static function verCheck() {
        try {
            if (!version_compare(phpversion(),'5.3.0','>=')) throw new Exception('FOG Requires PHP v5.3.0 or higher. You have PHP v'.phpversion());
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
    /** extCheck() Checks required extentions are installed
     * @return void
     */
    private static function extCheck() {
        $requiredExtensions = array('gettext');
        foreach($requiredExtensions AS $extension) {
            if (!in_array($extension, get_loaded_extensions())) $missingExtensions[] = $extension;
        }
        try {
            if (count($missingExtensions)) throw new Exception(sprintf('%s: %s',_('Missing Extensions'),implode(', ',(array)$missingExtensions)));
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
    /** endInit() Calls the params at the end of the init
     * @return void
     */
    public static function endInit() {
        if ($_SESSION['locale']) {
            putenv("LC_ALL={$_SESSION['locale']}");
            setlocale(LC_ALL, $_SESSION['locale']);
        }
        bindtextdomain('messages', 'languages');
        textdomain('messages');
    }
    /** FOGLoader() Loads the class files as they're needed
     * @param $className the class to include as called.
     * @return void
     */
    private function FOGLoader($className) {
        if (in_array($className,get_declared_classes())) return;
        global $EventManager;
        global $HookManager;
        spl_autoload($className);
    }
    /** sanitize_output() Clean the buffer
     * @param $buffer the buffer to clean
     * @return the cleaned up buffer
     */
    public static function sanitize_output($buffer) {
        $search = array(
            '/\>[^\S ]+/s', //strip whitespaces after tags, except space
            '/[^\S ]+\</s', //strip whitespaces before tags, except space
            '/(\s)+/s',  // shorten multiple whitespace sequences
        );
        $replace = array(
            '>',
            '<',
            '\\1',
        );
        $buffer = preg_replace($search,$replace,$buffer);
        return $buffer;
    }
}
/** $Init the initiator class */
$Init = new Initiator();
/** Sanitize user input */
$Init->sanitize_items();
/** Starts the init itself */
$Init::startInit();
/** $FOGFTP the FOGFTP class */
$FOGFTP = new FOGFTP();
/** $FOGCore the FOGCore class */
$FOGCore = new FOGCore();
/** $DB set's the DB class from the DatabaseManager */
$DB = $FOGCore->getClass('DatabaseManager')->establish()->DB;
/** $EventManager initiates the EventManager class */
$EventManager = $FOGCore->getClass('EventManager');
/** $HookManager initiates the HookManager class */
$HookManager = $FOGCore->getClass('HookManager');
$FOGCore->setSessionEnv();
/** $TimeZone the timezone setter */
$TimeZone = $_SESSION['TimeZone'];
$HookManager->load();
/** $HookManager initiates the FOGURLRequest class */
$FOGURLRequests = $FOGCore->getClass('FOGURLRequests');
