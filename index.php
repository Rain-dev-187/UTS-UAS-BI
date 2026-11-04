<?php
// Dasbor Bencana Indonesia — PHP + Tailwind (CDN) + Chart.js (CDN)
// Data: data.csv (dataset bencana BNPB/DIBI yang sudah dibersihkan, 17.878 baris)

$file = __DIR__ . '/data.csv';
$rows = [];
$fh = fopen($file, 'r');
$header = fgetcsv($fh);
$idx = array_flip($header);
while (($r = fgetcsv($fh)) !== false) {
    if (count($r) < count($header)) continue;
    $rows[] = $r;
}
fclose($fh);

function col($row, $idx, $name) { return $row[$idx[$name]] ?? ''; }
function num($row, $idx, $name) { $v = col($row, $idx, $name); return is_numeric($v) ? (float)$v : 0; }

// Daftar unik untuk filter
$events = [];
$provinces = [];
foreach ($rows as $r) {
    $events[col($r, $idx, 'Event')] = true;
    $provinces[col($r, $idx, 'Province')] = true;
}
ksort($events); ksort($provinces);

// Filter dari form (GET)
$fEvent = $_GET['event'] ?? '';
$fProv  = $_GET['provinsi'] ?? '';
$fFrom  = $_GET['dari'] ?? '';
$fTo    = $_GET['sampai'] ?? '';

$filtered = [];
foreach ($rows as $r) {
    $th = (int)num($r, $idx, 'Tahun');
    if ($fEvent !== '' && col($r, $idx, 'Event') !== $fEvent) continue;
    if ($fProv !== '' && col($r, $idx, 'Province') !== $fProv) continue;
    if ($fFrom !== '' && $th < (int)$fFrom) continue;
    if ($fTo !== '' && $th > (int)$fTo) continue;
    $filtered[] = $r;
}

// Agregasi KPI
$kpi = ['events' => count($filtered), 'deaths' => 0, 'injured' => 0, 'affected' => 0, 'houses' => 0];
$perYear = []; $perEvent = []; $perProv = [];
foreach ($filtered as $r) {
    $kpi['deaths']   += num($r, $idx, 'Deaths');
    $kpi['injured']  += num($r, $idx, 'Injured');
    $kpi['affected'] += num($r, $idx, 'Affected');
    $kpi['houses']   += num($r, $idx, 'Houses Destroyed') + num($r, $idx, 'Houses Damaged');
    $y = (int)num($r, $idx, 'Tahun');
    $perYear[$y] = ($perYear[$y] ?? 0) + 1;
    $e = col($r, $idx, 'Event');
    $perEvent[$e] = ($perEvent[$e] ?? 0) + 1;
    $p = col($r, $idx, 'Province');
    $perProv[$p] = ($perProv[$p] ?? 0) + 1;
}
ksort($perYear);
arsort($perEvent);
arsort($perProv);
$topProv = array_slice($perProv, 0, 10, true);

// 10 kejadian dengan korban jiwa terbesar (sesuai filter)
$top = $filtered;
usort($top, function ($a, $b) use ($idx) {
    return num($b, $idx, 'Deaths') <=> num($a, $idx, 'Deaths');
});
$top = array_slice($top, 0, 10);

function fmt($n) { return number_format($n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dasbor Bencana Indonesia</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: "class" };</script>
<script>if (localStorage.getItem("tema") === "gelap") document.documentElement.classList.add("dark");</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen">

<header class="bg-slate-900 dark:bg-slate-900 text-white border-b border-slate-700">
  <div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl md:text-3xl font-bold">Dasbor Bencana Indonesia</h1>
    <p class="text-slate-300 mt-1">Data kejadian bencana BNPB/DIBI tahun 1815–2014 · total dataset 17.878 kejadian</p>
    <button onclick="gantiTema()" class="mt-3 bg-slate-700 hover:bg-slate-600 text-white text-sm rounded-lg px-4 py-2">🌙 / ☀️ Ganti Tema</button>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

  <!-- Filter -->
  <form method="get" class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 grid grid-cols-2 md:grid-cols-5 gap-3">
    <label class="block">
      <span class="text-sm font-medium">Jenis bencana</span>
      <select name="event" class="mt-1 w-full border rounded-lg px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
        <option value="">Semua</option>
        <?php foreach (array_keys($events) as $e): ?>
          <option value="<?= htmlspecialchars($e) ?>" <?= $fEvent === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="block">
      <span class="text-sm font-medium">Provinsi</span>
      <select name="provinsi" class="mt-1 w-full border rounded-lg px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
        <option value="">Semua</option>
        <?php foreach (array_keys($provinces) as $p): ?>
          <option value="<?= htmlspecialchars($p) ?>" <?= $fProv === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="block">
      <span class="text-sm font-medium">Tahun dari</span>
      <input type="number" name="dari" value="<?= htmlspecialchars($fFrom) ?>" placeholder="1815" class="mt-1 w-full border rounded-lg px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    </label>
    <label class="block">
      <span class="text-sm font-medium">Tahun sampai</span>
      <input type="number" name="sampai" value="<?= htmlspecialchars($fTo) ?>" placeholder="2014" class="mt-1 w-full border rounded-lg px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
    </label>
    <div class="flex items-end gap-2">
      <button class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 w-full">Terapkan</button>
      <a href="index.php" class="bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg px-4 py-2">Reset</a>
    </div>
  </form>

  <!-- KPI -->
  <section class="grid grid-cols-2 md:grid-cols-5 gap-4">
    <?php
    $cards = [
      ['Total Kejadian', $kpi['events'], 'bg-blue-600'],
      ['Meninggal', $kpi['deaths'], 'bg-red-600'],
      ['Luka-luka', $kpi['injured'], 'bg-orange-500'],
      ['Terdampak', $kpi['affected'], 'bg-amber-500'],
      ['Rumah Rusak/Hancur', $kpi['houses'], 'bg-emerald-600'],
    ];
    foreach ($cards as [$label, $val, $color]): ?>
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
        <div class="h-1.5 rounded <?= $color ?> mb-3"></div>
        <div class="text-2xl font-bold"><?= fmt($val) ?></div>
        <div class="text-sm text-slate-500 dark:text-slate-400"><?= $label ?></div>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Grafik -->
  <section class="grid md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 md:col-span-2">
      <h2 class="font-semibold mb-3">Tren Kejadian per Tahun</h2>
      <canvas id="chartYear" height="90"></canvas>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
      <h2 class="font-semibold mb-3">Kejadian per Jenis Bencana</h2>
      <canvas id="chartEvent" height="220"></canvas>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-4">
      <h2 class="font-semibold mb-3">10 Provinsi Terbanyak Kejadian</h2>
      <canvas id="chartProv" height="220"></canvas>
    </div>
  </section>

  <!-- Tabel -->
  <section class="bg-white dark:bg-slate-800 rounded-xl shadow p-4 overflow-x-auto">
    <h2 class="font-semibold mb-3">10 Kejadian dengan Korban Jiwa Terbesar (sesuai filter)</h2>
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left border-b bg-slate-50 dark:bg-slate-700 dark:border-slate-600">
          <th class="py-2 px-2">Tanggal</th><th class="py-2 px-2">Jenis</th>
          <th class="py-2 px-2">Provinsi</th><th class="py-2 px-2">Kab/Kota</th>
          <th class="py-2 px-2 text-right">Meninggal</th><th class="py-2 px-2 text-right">Luka</th>
          <th class="py-2 px-2 text-right">Terdampak</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($top as $r): ?>
        <tr class="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
          <td class="py-2 px-2 whitespace-nowrap"><?= htmlspecialchars(col($r, $idx, 'Date (YMD)')) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars(col($r, $idx, 'Event')) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars(col($r, $idx, 'Province')) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars(col($r, $idx, 'District')) ?></td>
          <td class="py-2 px-2 text-right"><?= fmt(num($r, $idx, 'Deaths')) ?></td>
          <td class="py-2 px-2 text-right"><?= fmt(num($r, $idx, 'Injured')) ?></td>
          <td class="py-2 px-2 text-right"><?= fmt(num($r, $idx, 'Affected')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <footer class="text-sm text-slate-500 dark:text-slate-400 pb-6">
    Sumber data: BNPB/DIBI (dataset sesudah pembersihan: 17.878 baris, 26 kolom).
    Catatan: nilai 0 pada kolom dampak dapat berarti benar-benar nol atau tidak tercatat pada sumbernya.
  </footer>
</main>

<script>
function gantiTema() {
  const gelap = document.documentElement.classList.toggle("dark");
  localStorage.setItem("tema", gelap ? "gelap" : "terang");
  location.reload();
}
if (document.documentElement.classList.contains("dark")) { Chart.defaults.color = "#cbd5e1"; Chart.defaults.borderColor = "rgba(148,163,184,.25)"; }
const perYear  = <?= json_encode($perYear) ?>;
const perEvent = <?= json_encode($perEvent) ?>;
const topProv  = <?= json_encode($topProv) ?>;

new Chart(document.getElementById('chartYear'), {
  type: 'line',
  data: { labels: Object.keys(perYear),
    datasets: [{ label: 'Kejadian', data: Object.values(perYear), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.15)', fill: true, tension: .3, pointRadius: 0 }] },
  options: { responsive: true, scales: { x: { ticks: { maxTicksLimit: 20 } } } }
});
new Chart(document.getElementById('chartEvent'), {
  type: 'doughnut',
  data: { labels: Object.keys(perEvent),
    datasets: [{ data: Object.values(perEvent),
      backgroundColor: ['#2563eb','#dc2626','#f59e0b','#10b981','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b','#84cc16','#06b6d4','#a855f7','#eab308','#0ea5e9','#f43f5e','#22c55e','#6366f1','#d946ef','#78716c','#fb7185'] }] },
  options: { responsive: true, plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } } } }
});
new Chart(document.getElementById('chartProv'), {
  type: 'bar',
  data: { labels: Object.keys(topProv),
    datasets: [{ label: 'Kejadian', data: Object.values(topProv), backgroundColor: '#0ea5e9' }] },
  options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
});
</script>
</body>
</html>
