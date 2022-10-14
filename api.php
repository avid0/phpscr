<?php
require "../lib/phpscr/safemode.php";

function deldir($dir){
    $scan = scandir($dir);
    foreach($scan as $file)
        if($file == '.' || $file == '..')continue;
        elseif(is_dir("$dir/$file"))
            deldir("$dir/$file");
        else
            unlink("$dir/$file");
    return rmdir($dir);
}

$ipc = $_SERVER["REMOTE_ADDR"];
$dir = realpath("script")."/$ipc";
@mkdir($dir);
chmod($dir, 0700);
$code = (string)(@$_REQUEST['code']);
$code = _PHPSCR_settag($code, true);
$code = _PHPSCR_ebs($code);
$code = "<?php unset(\$file);{$code[0]}_PHPSCR_start(); ?>{$code[1]}";
$file = "$dir/run/source.php";
@mkdir("$dir/run");
file_put_contents($file, $code);
$info = [
    'limits' => [
        'tick' => 1024*1024*4,
        'rw' => 1024*1024*16,
        'time' => 10,
        'mem' => 1024*1024*64,
        'disk' => 1024*1024*10
    ],
    'admin' => 0,
    'token' => '',
    'lastpay' => 0,
    'paycoin' => 0,
    'autopay' => 0,
    'paylog' => 0,
    'lastlog' => 0,
    'indexnm' => 'source.php'
];
file_put_contents("$dir/info", json_encode($info));
_PHPSCR_safe::$dir = $dir;
_PHPSCR_safe::$fsd = "run";

_PHPSCR_error_handle();
ob_clean();
ob_flush();
flush();
ob_start();
_PHPSCR_shutdown(function(){
    global $ret, $err, $dir, $code;
    restore_error_handler();
    chdir(__DIR__);
    $out = ltrim(ob_get_contents());
    $hdr = implode("\r\n", headers_list());
    ob_clean();
    $stat = _PHPSCR_tickinfo();
    $lims = _PHPSCR_ticklimit();
    if($ret === 1 && stripos($code, "return") === false)
        $ret = null;
    if(is_string($ret))
        $ret = _PHPSCR_string_files($ret);
    $out = _PHPSCR_string_files($out);
    $hdr = _PHPSCR_string_files($hdr);
    $err = trim(_PHPSCR_safe::$logs."\n$err", "\n");
    $err = _PHPSCR_string_files($err);
    header("Content-Type: application/json");
    $res = [
        "output" => $out
    ];
    if($ret !== null)
        $res['return'] = $ret;
    if($err !== '')
        $res['errors'] = $err;
    $res['header'] = $hdr;
    $res['usages'] = $stat;
    $res['limits'] = $lims;
    $res['exited'] = _PHPSCR_safe::$exited;
    print json_encode($res);
    ob_flush();
    flush();
    @deldir($dir);
});
try{
    $ret = (function()use($file){
        return require $file;
    })();
}catch(\Error|\Exception $error){
    $err = $error->__toString();
}
_PHPSCR_safe::$exited = false;

?>