<?php
abstract class Hook extends Event {
    public function log($txt, $level = 1) {
        $log = trim(preg_replace(array("#\r#", "#\n#", "#\s+#", "# ,#"), array("", " ", " ", ","), $txt));
        if ($this->logToBrowser && $this->logLevel >= $level && !$this->post)
            printf('%s<div class="debug-hook">%s</div>%s', "\n", $log, "\n");
        if ($this->logToFile)
            file_put_contents(BASEPATH . '/lib/hooks/' . get_class($this) . '.log', sprintf("[%s] %s\r\n", $this->nice_date()->format("d-m-Y H:i:s"), $log), FILE_APPEND | LOCK_EX);
    }
}
