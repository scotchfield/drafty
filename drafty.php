<?php
/*
Plugin Name: Drafty
Plugin URI: http://scootah.com/
Description: Share post drafts with the click of a button
Author: Scott Grant
Version: 0.1
Author URI: http://scootah.com
*/

require_once 'class.drafty.php';
require_once 'class.drafty-data.php';

$wp_drafty = new Drafty();
