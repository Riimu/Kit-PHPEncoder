<?php

require_once __DIR__ . '/../vendor/autoload.php';

$precision = 17;
$normalEncoder = new \Riimu\Kit\PHPEncoder\PHPEncoder(['float.precision' => $precision]);
$exportEncoder = new \Riimu\Kit\PHPEncoder\PHPEncoder(['float.export' => true]);

ini_set('serialize_precision', $precision);

$count = 0;
$mindiff = 1;
$maxdiff = 0;
$total = 0;

for ($i = 0; $i <= 2 ** 53; $i++) {
    $normal = $normalEncoder->encode($i / 2 ** 53); // $i / 2 ** 53
    $export = $exportEncoder->encode($i / 2 ** 53); // $i / 2 ** 53

    $normalResult = eval("return $normal;");
    $exportResult = eval("return $export;");

    if ($normalResult !== $exportResult) {
        $diff = abs($normalResult - $exportResult);

        $count++;
        $mindiff = min($diff, $mindiff);
        $maxdiff = max($diff, $maxdiff);
        $total += $diff;
        $avgdiff = $total / $count;
        $pct = round($count / $i * 100, 2);

        echo "Different ($count / $i, $pct%): $normal, $export, Diff: $mindiff / $avgdiff / $maxdiff\n";
    }
}
