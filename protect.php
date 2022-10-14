<?php
function _PHPSCR_getwebhook(){
    return json_decode(file_get_contents("php://input"));
}
function _PHPSCR_verify(){
    $ip = $_SERVER['REMOTE_ADDR'];
    if(($ip < "149.154.160.0" || $ip > "149.154.176.0")
        && ($ip < "91.108.4.0" || $ip > "91.108.8.0")){
        header("Content-Type: text/plain");
        print "Protected by SourceExchange!";
        exit;
    }
}
_PHPSCR_verify();
?>