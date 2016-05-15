<?php

require_once('searchEngine.php');

$se = new SearchEngine();

$f = fopen('nurse-practitioner.txt','r');



$successCondition = 'nurse practitioner';
$length = strlen($successCondition);
$pad = $length + (int) ($length * 0.5);

echo '<pre>';

$count = 0;
$passCount = 0;

$startTime = microtime(true);

ob_start();

while($line = fgets($f)){
    $count++;
    $suggested = @$se->didYouMean($line);
    $testStatus = ($suggested == $successCondition) ? 'pass' : 'fail';
    echo str_pad($testStatus,6) . ' :: ' . str_pad(trim($line),$pad) . ' :: ' . str_pad($suggested,$pad) . "\n";

    if($testStatus == 'pass'){
        $passCount++;
    }

}

$details = ob_get_contents();
ob_end_clean();

$endTime = microtime(true);
$totalTime = $endTime - $startTime;

$passRate = $passCount / $count;


echo "Count: $count Pass: $passCount Pass Rate: $passRate Processing Time: $totalTime \n";
echo '=================================================================================================='."\n\n\n";

echo $details;

fclose($f);




