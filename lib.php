<?php
/**
 * PHPSCR PHP Runner
 * 
 * @author Avid [@Av_id]
 */
require "./safemode.php"; // require phpscr simulator

class PHPSCR {
    /**
     * Files Structure
     *  $path
     *  ├── root
     *  │   ├── --Files created by script--
     *  │   └── cwd (current directory)
     *  │       ├── --Files created by script--
     *  │       └── file (source file)
     *  ├── datas of phpscr
     *  └── databases (soon)
     */

    /**
     * Path of script for run
     * @var string $path
     */
    private $path;

    /**
     * Save settings for run
     * @var array $settings
     */
    private $settings;
    
    /**
     * Constructor
     * 
     * @param string $path                    Path of script for run
     * @param array $settings = []
     *   @param string $settings[root] = "/"           $path/source/root           Root directory
     *   @param string $settings[cwd] = "/"            $path/source/root/cwd       Curren directory
     *   @param string $settings[file] = "index.php"   $path/source/root/cwd/file  Script file
     *   @param array $settings[limits] = []           Limits of Script (0 for unlimited)
     *     @param int $settings[limits][tick] = 5M     Ticks limit
     *     @param int $settings[limits][rw] = 8M       Read/Write (Disk/Net) Limit (must smaller than disk limit)
     *     @param int $settings[limits][time] = 10s    Time limit
     *     @param int $settings[limits][mem] = 64M     Memory Limit
     *     @param int $settings[limits][disk] = 10M    Files size Limit (size of directory $path)
     *   @param int $settings[perms] = Defual          Permission of Path directory
     *   @param int $settings[errorlog] = Default      Error reporting
     *   @param array $settings[disabled_classes]      Disabled classes
     *   @param array $settings[disabled_funcs]        Disabled functions
     *   @param array $settings[disabled_extensions]   Disabled Extensions
     *   @param array $settings[convert_classes]       Convert Class names
     *     $settings[convert_classes] = ["class name" => "substitute class name", ...]
     *   @param array $settings[convert_funcs]         Convert Function names
     *     $settings[convert_funcs] = ["function name" => "substitute function name", ...]
     */
    public function __construct($path, $settings = array()){
        if(!file_exists($path))
            if(!mkdir($path))
                mkdir($path = "./phpscr".rand().rand());
        $path = realpath($path);
        if(!file_exists($path.'/source'))
            mkdir($path.'/source');
        if(!isset($settings['root']))
            $settings['root'] = '/';
        if(!file_exists($path.'/source/'.$settings['root']))
            mkdir($path.'/source/'.$settings['root']);
        if(!isset($settings['cwd']))
            $settings['cwd'] = '/';
        if(!file_exists($path.'/source/'.$settings['root'].'/'.$settings['cwd']))
            mkdir($path.'/source/'.$settings['root'].'/'.$settings['cwd']);
        if(!isset($settings['file']))
            $settings['file'] = 'index.php';
        if(!isset($settings['limits'])){
            $settings['limits'] = array(
                'tick' => 4*1024*1024,
                'rw' => 8*1024*1024,
                'time' => 10,
                'mem' => 64*1024*1024,
                'disk' => 10*1024*1024
            );
        }else{
            if(!isset($settings['limits']['tick']))
                $settings['limits']['tick'] = 5*1000*1000;
            elseif((int)$settings['limits']['tick'] == 0)
                $settings['limits']['tick'] = 99999999999999;
            else{
                if(strtolower(subste($settings['limits']['tick'], -1)) == 'm')
                    $settings['limits']['tick'] = (int)substr($settings['limits']['tick'], 0, -1)*1000*1000;
                if(strtolower(subste($settings['limits']['tick'], -1)) == 'k')
                    $settings['limits']['tick'] = (int)substr($settings['limits']['tick'], 0, -1)*1000;
                else
                    $esttings['limits']['tick'] = (int)$settings['limits']['tick'];
            }
            if(!isset($settings['limits']['rw']))
                $settings['limits']['rw'] = 8*1024*1024;
            elseif((int)$settings['limits']['rw'] == 0)
                $settings['limits']['rw'] = 99999999999999;
            else{
                if(strtolower(subste($settings['limits']['rw'], -1)) == 'm')
                    $settings['limits']['rw'] = (int)substr($settings['limits']['rw'], 0, -1)*1024*1024;
                if(strtolower(subste($settings['limits']['rw'], -1)) == 'k')
                    $settings['limits']['rw'] = (int)substr($settings['limits']['rw'], 0, -1)*1024;
                else
                    $esttings['limits']['rw'] = (int)$settings['limits']['rw'];
            }
            if(!isset($settings['limits']['time']))
                $settings['limits']['time'] = 10;
            elseif((int)$settings['limits']['time'] == 0)
                $settings['limits']['time'] = 99999999999999;
            else{
                if(strtolower(subste($settings['limits']['time'], -1)) == 'h')
                    $settings['limits']['time'] = (int)substr($settings['limits']['time'], 0, -1)*3600;
                if(strtolower(subste($settings['limits']['time'], -1)) == 'm')
                    $settings['limits']['time'] = (int)substr($settings['limits']['time'], 0, -1)*60;
                else
                    $esttings['limits']['time'] = (int)$settings['limits']['time'];
            }
            if(!isset($settings['limits']['mem']))
                $settings['limits']['mem'] = 64*1024*1024;
            elseif((int)$settings['limits']['mem'] == 0)
                $settings['limits']['mem'] = 99999999999999;
            else{
                if(strtolower(subste($settings['limits']['mem'], -1)) == 'm')
                    $settings['limits']['mem'] = (int)substr($settings['limits']['mem'], 0, -1)*1024*1024;
                if(strtolower(subste($settings['limits']['mem'], -1)) == 'k')
                    $settings['limits']['mem'] = (int)substr($settings['limits']['mem'], 0, -1)*1024;
                else
                    $esttings['limits']['mem'] = (int)$settings['limits']['mem'];
            }
            if(!isset($settings['limits']['disk']))
                $settings['limits']['disk'] = 10*1024*1024;
            elseif((int)$settings['limits']['disk'] == 0)
                $settings['limits']['disk'] = 99999999999999;
            else{
                if(strtolower(subste($settings['limits']['disk'], -1)) == 'm')
                    $settings['limits']['disk'] = (int)substr($settings['limits']['disk'], 0, -1)*1024*1024;
                if(strtolower(subste($settings['limits']['disk'], -1)) == 'k')
                    $settings['limits']['disk'] = (int)substr($settings['limits']['disk'], 0, -1)*1024;
                else
                    $settings['limits']['disk'] = (int)$settings['limits']['disk'];
            }
        }
        if(isset($settings['perms'])){
            $settings['perms'] = (int)$settings['perms'];
            chmod($path, $esttings['perms']);
        }
        if(isset($settings['errorlog']))
            $settings['errorlog'] = (int)$settings['errorlog'];
        $convert_classes = $convert_funcs = array();
        if(isset($settings['disabled_classes']) && is_array($settings['disabled_classes'])){
            foreach($settings['disabled_classes'] as $name)
                $convert_classes[strtolower($name)] = '_PHPSCR_empty';
        }
        if(isset($settings['disabled_funcs']) && is_array($settings['disabled_funcs'])){
            foreach($settings['disabled_funcs'] as $name)
                $convert_funcs[strtolower($name)] = '_PHPSCR_empty';
        }
        if(isset($settings['disabled_extensions']) && is_array($settings['disabled_extensions'])){
            foreach($settings['disabled_extensions'] as $extension){
                if(!extension_loaded($extension))
                    continue;
                $extension = new ReflectionExtension($extension);
                foreach($extension->getClasses() as $name)
                    $convert_classes[strtolower($name->name)] = '_PHPSCR_empty';
                foreach($extension->getFunctions() as $name)
                    $convert_funcs[strtolower($name->name)] = '_PHPSCR_empty';
            }
        }
        if(isset($settings['convert_classes']) && is_array($settings['convert_classes'])){
            foreach($settings['convert_classes'] as $name => $convert)
                $convert_classes[strtolower($name)] = $convert;
        }
        if(isset($settings['convert_funcs']) && is_array($settings['convert_funcs'])){
            foreach($settings['convert_funcs'] as $name => $convert)
                $convert_funcs[strtolower($name)] = $convert;
        }
        $settings['convert_classes'] = $convert_classes;
        $settings['convert_funcs'] = $convert_funcs;
        $this->path = $path;
        $this->settings = $settings;
    }

    /**
     * Initialized
     * @var bool $initialized
     */
    private $initialized = false;

    /**
     * Initialize settings
     * 
     * @method _init
     * @internal
     */
    private function _init(){
        if(!is_array($this->settings))
            return false;
        if(!$this->initialized){
            $info = array(
                'admin' => 0,
                'token' => '',
                'lastpay' => 0,
                'paycoin' => 0,
                'autopay' => 0,
                'paylog' => 0,
                'lastlog' => 0
            );
            $info['indexnm'] = $this->settings['file'];
            $info['limits'] = $this->settings['limits'];
            $info['convert_classes'] = $this->settings['convert_classes'];
            $info['convert_funcs'] = $this->settings['convert_funcs'];
            file_put_contents($this->path.'/info', json_encode($info));
        }
        _PHPSCR_safe::$dir = $this->path;
        _PHPSCR_safe::$root = $this->settings['root'];
        _PHPSCR_safe::$cwd = $this->settings['cwd'];
        $bu = array(
            'errorlog' => error_reporting(),
            'cwd' => getcwd(),
            'server' => _PHPSCR_start()
        );
        if(isset($this->settings['errorlog']))
            error_reporting($this->settings['errorlog']);
        chdir($this->path.'/source/'.$this->settings['root'].'/'.$this->settings['cwd']);
        return $bu;
    }

    /**
     * Delete all files in directory
     * 
     * @method _deldir
     * @internal
     */
    private function _deldir($dir){
        $scan = scandir($dir);
        foreach($scan as $file)
            if($file == '.' || $file == '..')continue;
            elseif(is_dir("$dir/$file"))
                $this->_deldir("$dir/$file");
            else
                unlink("$dir/$file");
        return rmdir($dir);
    }

    /**
     * Delete script path (every scripts and result datas in $path)
     * 
     * @method clear
     * @return bool
     */
    public function clear(){
        $this->settings = null;
        return $this->_deldir($this->path);
    }

    /**
     * If the last executed script is forced out by exit() or die()
     * @var bool $exited
     */
    public $exited = false;

    /**
     * Execute script
     * 
     * @method execute
     * @param string $script PHP Script
     * @return mixed returned value
     */
    public function execute($script){
        $bu = self::_init();
        $bu = array(
            'errorlog' => error_reporting(),
            'cwd' => getcwd(),
            'server' => _PHPSCR_start()
        );
        $script = _PHPSCR_settag($script);
        $script = _PHPSCR_ebs($script);
        $script = "<?php unset(\$file);{$script[0]} ?>".$script[1];
        $file = $this->path.'/source/'.$this->settings['root'].'/'.$this->settings['cwd'].'/'.$this->settings['file'];
        file_put_contents($file, $script);
        $ret = (function()use($file){
            return require $file;
        })();
        $this->exited = _PHPSCR_safe::$exited;
        error_reporting($bu['errorlog']);
        chdir($bu['cwd']);
        $_SERVER = $bu['server'];
        foreach($_SERVER as $env=>$val)
            if(is_string($val))
                putenv("$env=$val");
        return $ret;
    }

    /**
     * Execute script file
     * 
     * @method executeFile
     * @param string $file PHP Script file
     * @return mixed returned value
     */
    public function executeFile($file){
        return self::execute(file_get_contents($file));
    }
}

?>