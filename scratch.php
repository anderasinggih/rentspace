<?php
$prev = 0;
$period = 100;
$gain1 = $prev > 0 ? round((($period - $prev) / $prev) * 100, 1) : ($period > 0 ? 100 : 0);
echo "Gain 1: $gain1\n";

$prev2 = 50;
$period2 = 150;
$gain2 = $prev2 > 0 ? round((($period2 - $prev2) / $prev2) * 100, 1) : ($period2 > 0 ? 100 : 0);
echo "Gain 2: $gain2\n";
