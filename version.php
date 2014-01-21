<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2014011800;
$plugin->cron     = 0;
$plugin->requires = 2012062501; // Moodle 2.4
$plugin->release  = '2.4 (Build: 2014011800)';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array('mod_learningtimecheck' => 2014011800); // Must have learningtimecheck activity module installed
