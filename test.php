<?php
require "lib.php";

$phpscr = new PHPSCR("test");
$phpscr->execute('
file_put_contents("myvar", "PHP");
$avid = file_get_contents("myvar");
print "hello $avid :)";
');
$phpscr->clear();

// Avid [@Av_id]
?>