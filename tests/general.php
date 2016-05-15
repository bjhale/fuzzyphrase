<?php 

require_once('../src/FuzzyPhrase.php');

$ff = new bjhale\FuzzyPhrase\FuzzyPhrase();

$ff->addWord('gidget gadget go');
$ff->addWord('gidget');