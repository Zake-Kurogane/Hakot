<?php
// fetch_collection_stats.php
//
// PURPOSE
//   • “Monthly”  → returns one point **per day of the selected month**
//   • “Yearly”   → returns one point **per month of the selected year**
//
//   Every point is the *sum* of disposedTrashWeight for that day / month.
//   • Negative weights are ignored (they were correction entries).
//   • We rely only on string‐slices (substr) so there is **no timezone drift**.
//
require 'dbcon.php';
header('Content-Type: application/json');

/* ── 1.  Detect timeframe and the string we are matching ─────────────── */
$timeframe = isset($_GET['year']) ? 'yearly' : 'monthly';
$selected  = $timeframe === 'monthly'
           ? ($_GET['month'] ?? date('Y-m'))   // “YYYY-MM”
           : ($_GET['year']  ?? date('Y'));    // “YYYY”

/* ── 2.  Pull the raw logs once ──────────────────────────────────────── */
$logs = $database->getReference('reports/truckusagedata')->getValue();

/* ── 3.  Buckets ─────────────────────────────────────────────────────── */
$daily  = [];          // 1-31  (monthly view)
$monthly= [];          // 1-12  (yearly view)

foreach (($logs ?? []) as $r) {
    if (!isset($r['date'], $r['disposedTrashWeight'])) continue;

    $isoDate = $r['date'];                    // “YYYY-MM-DDThh:mm:ss”
    $weight  = floatval($r['disposedTrashWeight']);
    if ($weight <= 0) continue;               // skip negatives / zeros

    /* quick string slices – no DateTime, no timezone surprises */
    $yearPart  = substr($isoDate, 0, 4);      // YYYY
    $monthPart = substr($isoDate, 0, 7);      // YYYY-MM
    $dayPart   = intval(substr($isoDate, 8, 2)); // 01-31 → int

    if ($timeframe === 'monthly') {
        if ($monthPart !== $selected) continue;
        $daily[$dayPart] = ($daily[$dayPart] ?? 0) + $weight;

    } else {  // yearly
        if ($yearPart !== $selected) continue;
        $monthNum = intval(substr($isoDate, 5, 2)); // 1-12
        $monthly[$monthNum] = ($monthly[$monthNum] ?? 0) + $weight;
    }
}

/* ── 4.  Build ordered list for the graph ────────────────────────────── */
$out = [];

if ($timeframe === 'monthly') {
    ksort($daily);                // 1 … 31
    foreach ($daily as $d => $kg) {
        $out[] = ['day' => $d, 'totalDisposedTrashWeight' => $kg];
    }

} else {                          // yearly
    ksort($monthly);              // 1 … 12
    $names = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    foreach ($monthly as $m => $kg) {
        $out[] = ['monthName' => $names[$m], 'totalDisposedTrashWeight' => $kg];
    }
}

echo json_encode([
    'status' => 'success',
    'data'   => $out
]);
