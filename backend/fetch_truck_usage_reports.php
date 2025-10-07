<?php
// fetch_truck_usage_reports.php
require 'dbcon.php';
header('Content-Type: application/json');

/* ── timeframe & key dates ─────────────────────────────── */
$timeframe = 'daily';
if (isset($_GET['month']))      $timeframe = 'monthly';
elseif (isset($_GET['year']))   $timeframe = 'yearly';

$selected  = $timeframe==='daily'   ? ($_GET['date']  ?? date('Y-m-d')) :
             ($timeframe==='monthly'? ($_GET['month'] ?? date('Y-m'))     :
                                       ($_GET['year']  ?? date('Y')));

$yesterday = date('Y-m-d', strtotime($selected.' -1 day'));

/* ── truck → kmPerLiter map (still handy elsewhere) ───── */
$kmplMap=[];
foreach (($database->getReference('trucks')->getValue() ?? []) as $t){
    if(!empty($t['vehicleName'])) $kmplMap[$t['vehicleName']] = (float)($t['kmPerLiter']??0);
}

/* ── pull usage logs ───────────────────────────────────── */
$usage = $database->getReference('reports/truckusagedata')->getValue();
$byTruck=[];

foreach (($usage??[]) as $r){
    if (empty($r['date'])||empty($r['truckName'])) continue;
    $d  = substr($r['date'],0,10);                 // yyyy-mm-dd
    $ok = ($timeframe==='daily'   && ($d===$selected||$d===$yesterday))
       ||($timeframe==='monthly' && substr($d,0,7)===$selected)
       ||($timeframe==='yearly'  && substr($d,0,4)===$selected);
    if(!$ok) continue;

    $t=$r['truckName'];
    $byTruck[$t] ??= [
        'truckName'                => $t,
        'fuelLoadedToday'          => 0,
        'fuelLoadedYesterday'      => 0,
        'odoToday'                 => null,
        'odoYesterday'             => null,
        'totalKilometersTraveled'  => 0,
        'totalTimeTravel'          => 0,
        'totalDisposedTrashWeight' => 0,
        'kmPerLiter'               => 0
    ];

    /* ---- bucket by date when in daily mode ---- */
    if ($timeframe==='daily'){
        if ($d===$selected){
            $byTruck[$t]['fuelLoadedToday'] += $r['fuelLoaded'] ?? 0;
            if(isset($r['odometerReading']))
                $byTruck[$t]['odoToday'] = max($byTruck[$t]['odoToday']??0,$r['odometerReading']);
        }elseif($d===$yesterday){
            $byTruck[$t]['fuelLoadedYesterday'] += $r['fuelLoaded'] ?? 0;
            if(isset($r['odometerReading']))
                $byTruck[$t]['odoYesterday'] = max($byTruck[$t]['odoYesterday']??0,$r['odometerReading']);
        }
    }

    /* ---- always accumulate monthly / yearly sums ---- */
    $byTruck[$t]['totalKilometersTraveled']  += $r['kilometersTraveled']  ?? 0;
    $byTruck[$t]['totalTimeTravel']          += $r['timeTravel']          ?? 0;
    $byTruck[$t]['totalDisposedTrashWeight'] += $r['disposedTrashWeight'] ?? 0;
}

/* ── final calc per truck ─────────────────────────────── */
foreach($byTruck as $t=>&$d){
    /* DAILY distance & efficiency */
    if($timeframe==='daily'
       && $d['odoToday']!==null
       && $d['odoYesterday']!==null){
        $d['distanceTraveled'] = $d['odoToday'] - $d['odoYesterday']; // km
        $fuel = $d['fuelLoadedToday'] + $d['fuelLoadedYesterday'];    // L
        $d['fuelEfficiency'] = $fuel>0 ? $d['distanceTraveled'] / $fuel : 0;
    }else{
        /* monthly / yearly: keep summed distance from logs */
        $d['distanceTraveled'] = $d['totalKilometersTraveled'];
    }
    $d['kmPerLiter'] = $kmplMap[$t] ?? 0;
}
unset($d);

echo json_encode([
    'status'=>'success',
    'data'=>['truckusagedata'=>$byTruck]
]);
