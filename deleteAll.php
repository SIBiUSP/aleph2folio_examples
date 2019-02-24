#!/usr/bin/php
<?php

require 'inc/functions.php';

$folioCookies = FolioREST::loginREST();
//FolioREST::deleteItensREST($folioCookies);
FolioREST::deleteAllRecordsREST($folioCookies);
?>