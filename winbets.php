<?php

function getWeights($odds, $profit = 0, $precision = 10){
    $weights = [];
    $totalWeights = 0;
    foreach($odds as $key => $value){
        $weights[$key] = 1;
        $totalWeights += $weights[$key];
    }
    $criterion = true;
    foreach($odds as $key => $value){
        $criterion = $criterion && ($weights[$key] * $odds[$key] >= $totalWeights + $profit);
    }
    $iterations = 0;
    while($criterion === false){
        $criterion = true;
        foreach($odds as $key => $value){
            if($weights[$key] * $odds[$key] < $totalWeights + $profit){
                $weights[$key] +=1;
                $totalWeights += 1;
            }
            $criterion = $criterion && ($weights[$key] * $odds[$key] >= $totalWeights + $profit);
        }
        $iterations ++;
        if($iterations == $precision) {
            $failed = [];
            foreach($odds as $key => $value) $failed[$key] = 0;
            return $failed;
        }
    }
    return $weights;
}

if(!isset($argv[1])) die("Race Date Not Entered!!\n");

$step = "winbets";
$raceDate = trim($argv[1]);
$currentDir = __DIR__ . DIRECTORY_SEPARATOR . $raceDate;

$allRacesRunners = include($currentDir . DIRECTORY_SEPARATOR . "1.php");
$allRacesOdds = include($currentDir . DIRECTORY_SEPARATOR . "getodds.php");
$history = include(__DIR__ . DIRECTORY_SEPARATOR . "triohistory.php");
$outFile = $currentDir . DIRECTORY_SEPARATOR . "$step.php";

$totalRaces = count($allRacesRunners);

$outtext = "<?php\n\n";
$outtext .= "return [\n";

for ($raceNumber = 1; $raceNumber <= $totalRaces; $raceNumber++) {
    if(!isset($allRacesRunners[$raceNumber])) continue;
    $runners = explode(", ", $allRacesRunners[$raceNumber]['Runners']);
    $favorite = $runners[0];
    $secondFavorite = $runners[1];
    $raceData = $history[$raceNumber][$favorite];
    $raceData2 = $history[$raceNumber][$secondFavorite];
    $racetext = "";
   
    $racetext .= "\t'$raceNumber' => [\n";
    $racetext .= "\t\t/**\n";
    $racetext .= "\t\tRace $raceNumber\n";
    $racetext .= "\t\t*/\n";
    $racetext .= "\t\t'Favorite       '  =>  '$favorite',\n";
    $racetext .= "\t\t'Second Favorite'  =>  '$secondFavorite',\n";
    $toWin = $raceData['win'];
    $toWin2 = array_values(array_unique(array_merge($toWin, $raceData2['win'])));
    if(!empty($toWin)){
        //Sort  toWin by odds
        $qplsOdds = [];
        foreach($toWin as $iIndex){
            if(isset($allRacesOdds[$raceNumber][$iIndex])) $qplsOdds[$iIndex] = $allRacesOdds[$raceNumber][$iIndex];
        }
        asort($qplsOdds);
        $toWin = array_keys($qplsOdds);
        $weights = [];
        foreach($toWin as $winner){
            $weights[$winner] = $allRacesOdds[$raceNumber][$winner];
        }
        $bets = getWeights($weights, 1);
        $racetext .= "\t\t'Win Set Win Bets'  =>  [\n";
        $total = 0;
        foreach($bets as $horse => $bet){
            $racetext .= "\t\t\t'$horse' => '" . 10 * $bet . " HKD',\n"  ;
            $total += 10 * $bet;
        }
        $racetext .= "\t\t],\n";
        $racetext .= "\t\t'Total Bets set 1'  =>  '$total HKD',\n";
    }
    $qin = $raceData['qin'];
    if(!empty($qin)){
        $qinValues = [];
        foreach($qin as $qinArray){
            $qinValues = array_values(array_unique(array_merge($qinValues, $qinArray)));
        }
        //Sort  qinValues by odds
        $qplsOdds = [];
        foreach($qinValues as $iIndex){
            if(isset($allRacesOdds[$raceNumber][$iIndex])) $qplsOdds[$iIndex] = $allRacesOdds[$raceNumber][$iIndex];
        }
        asort($qplsOdds);
        $qinValues = array_keys($qplsOdds);
        $weights = [];
        foreach($qinValues as $winner){
            $weights[$winner] = $allRacesOdds[$raceNumber][$winner];
        }
        $bets = getWeights($weights, 1);
        $racetext .= "\t\t'Qin Set Win Bets'  =>  [\n";
        $total = 0;
        foreach($bets as $horse => $bet){
            $racetext .= "\t\t\t'$horse' => '" . 10 * $bet . " HKD',\n"  ;
            $total += 10 * $bet;
        }
        $racetext .= "\t\t],\n";
        $racetext .= "\t\t'Total Bets set 2'  =>  '$total HKD',\n";
    }
    if(!empty($toWin2)){
        //Sort  toWin2 by odds
        $qplsOdds = [];
        foreach($toWin2 as $iIndex){
            if(isset($allRacesOdds[$raceNumber][$iIndex])) $qplsOdds[$iIndex] = $allRacesOdds[$raceNumber][$iIndex];
        }
        asort($qplsOdds);
        $toWin2 = array_keys($qplsOdds);
        $weights = [];
        foreach($toWin2 as $winner){
            $weights[$winner] = $allRacesOdds[$raceNumber][$winner];
        }
        $bets = getWeights($weights, 1);
        $racetext .= "\t\t'Win Bets Based on 1st and 2nd favorites'  =>  [\n";
        $total = 0;
        foreach($bets as $horse => $bet){
            $racetext .= "\t\t\t'$horse' => '" . 10 * $bet . " HKD',\n"  ;
            $total += 10 * $bet;
        }
        $racetext .= "\t\t],\n";
        $racetext .= "\t\t'Total Bets set 3'  =>  '$total HKD',\n";
    }
    $racetext .= "\t],\n";
    $outtext .= $racetext;
}

$outtext .= "];\n";

file_put_contents($outFile, $outtext);