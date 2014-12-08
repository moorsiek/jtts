<?php
/**
 * Created by PhpStorm.
 * User: moorsiek
 * Date: 08.12.14
 * Time: 10:33
 */

class Jtts {
    private $version = '0.1.0';
    
    public function __construct() {
        
    }
    
    public function getVersion() {
        return $this->version;
    }

    public function run() {
        $this->getOpts();
        
        if (!isset($this->options['i'])) {
            $this->logstr("No input file given.\n");
            $this->printHelp();
            $this->quit();
        }
        
        if (is_array($this->options['i'])) {
            $this->options['i'] = $this->options['i'][0]; 
        }

        $this->verbose = isset($this->options['v']);

        $this->source = @file_get_contents($this->options['i']);
        if (false === $this->source) {
            $this->logf('Unable to read input file "%s", quitting', $this->options['i']);
            $this->quit();
        }
        
        $this->logf('Fetching templates');
        $this->fetchTemplates();
        if (!count($this->templates)) {
            $this->logstr('No templates found. Quitting');
            $this->quit();
        }
        
        $this->listTemplates();
        $choice = $this->getTemplateChoice();
        
        $converted = $this->convertTemplate($choice);
        echo $converted;
    }
    
    protected function printHelp() {
        $this->logstr('JTTS - Javascript Template To Javascript (string) v' . $this->getVersion());
        $this->logstr('Usage:');
        $this->logstr('/path/to/php jtts.php -i templates_file');
        $this->logstr("This prints converted template to the standard output\n");
        $this->logstr('You can write conversion result to a file like this:');
        $this->logstr('/path/to/php jtts.php -i templates_file > output_file.js');
    }
    
    protected function convertTemplate($templateName) {
        $template = $this->templates[$templateName]['source'];
        $lines = preg_split('#\r\n|\n|\r#u', $template);
        for ($i = 0, $imax = count($lines) - 1; $i <= $imax; ++$i) {
            $line = &$lines[$i];
            //$line = preg_replace('#^\s+#u', '', $line);
            $line = preg_replace('#([\'\\\\])#u', '\\\\$1', $line);
            $line = "'" . $line . "'";
            $line .= $i === $imax ? ';' : (" + \n");
        }
        return implode('', $lines);
    }
    
    protected function getTemplateChoice() {
        while (true) {
            $templateName = $this->readline('Please entry template name to convert: ');
            if (array_key_exists($templateName, $this->templates)) {
                return $templateName;
            } else {
                $action = $this->readline('There is no such template. (R)etry, (C)ancel: ');
            }
            if ('c' === mb_strtolower($action)) {
                $this->logstr('Quitting');
                $this->quit();
            }
        }
    }
    
    protected function listTemplates() {
        $this->logstr("Templates found: \n");
        foreach ($this->templates as &$template) {
            $this->logf("%s", $template['name']);
        }
    }
    
    protected function fetchTemplates() {
        $this->templates = array();
        
        $result = preg_match_all('#<!--\s*jtts\s+([a-zа-я0-9_-]+)\s*-->\s*(?:\r\n|\n|\r)([\S\s]*?)(?:\r\n|\n|\r)<!--\s*jtts\s*-->#iu', $this->source, $m);
        if (false === $result) {
            $this->logstr('An unknown error occurred! Quitting.');
            $this->quit();
        } else if (0 === $result) {
            return;
        }
        
        for ($i = 0, $ilim = count($m[0]); $i < $ilim; ++$i) {
            $this->templates[$m[1][$i]] = array(
                'name' => $m[1][$i],
                'source' => $m[2][$i]
            );
        }
    }

    public function logf($msg) {
        $msg = call_user_func_array('sprintf', func_get_args());
        return $this->logstr($msg);
    }

    public function logstr($msg, $noEol = false) {
        static $STDERR;
        $STDERR = fopen('php://stderr', 'w+');
        fwrite($STDERR, $msg . ($noEol ? '' : "\n"));
    }

    protected function getOpts() {
        $options = getopt('vhi:');
        if ($options === false) {
            throw new Exception('Failed to parse options!');
        }

        $this->options = $options;
    }

    protected function quit() {
        die();
    }
    
    protected function readline($prompt) {
        if ($prompt){
            $this->logstr($prompt, true);
        }
        $fp = fopen('php://stdin','r');
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }
}

$jtts = new Jtts();
$jtts->run();