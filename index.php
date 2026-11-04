<?php
// Dasbor Bencana Indonesia — PHP + Tailwind (CDN) + Chart.js (CDN)
// Data: database SQLite bencana.db (dibangun dari data.csv oleh import_csv.php)

$dbFile = __DIR__ . '/bencana.db';
if (!file_exists($dbFile)) {
    die('Database bencana.db belum ada. Jalankan dulu: php import_csv.php');
}
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Daftar unik untuk filter
$events = $pdo->query('SELECT DISTINCT event FROM bencana ORDER BY event')->fetchAll(PDO::FETCH_COLUMN);
$provinces = $pdo->query('SELECT DISTINCT province FROM bencana ORDER BY province')->fetchAll(PDO::FETCH_COLUMN);

// Filter dari form (GET)
$fEvent = $_GET['event'] ?? '';
$fProv  = $_GET['provinsi'] ?? '';
$fFrom  = $_GET['dari'] ?? '';
$fTo    = $_GET['sampai'] ?? '';

$where = [];
$params = [];
if ($fEvent !== '') { $where[] = 'event = ?';    $params[] = $fEvent; }
if ($fProv !== '')  { $where[] = 'province = ?'; $params[] = $fProv; }
if ($fFrom !== '')  { $where[] = 'tahun >= ?';   $params[] = (int)$fFrom; }
if ($fTo !== '')    { $where[] = 'tahun <= ?';   $params[] = (int)$fTo; }
$w = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

function q($pdo, $sql, $params) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
}

// Agregasi KPI
$row = q($pdo, "SELECT COUNT(*) AS events, COALESCE(SUM(deaths),0) AS deaths,
    COALESCE(SUM(injured),0) AS injured, COALESCE(SUM(affected),0) AS affected,
    COALESCE(SUM(houses_destroyed),0) + COALESCE(SUM(houses_damaged),0) AS houses
    FROM bencana$w", $params)->fetch(PDO::FETCH_ASSOC);
$kpi = ['events' => (int)$row['events'], 'deaths' => $row['deaths'], 'injured' => $row['injured'],
        'affected' => $row['affected'], 'houses' => $row['houses']];

$perYear = [];
foreach (q($pdo, "SELECT tahun, COUNT(*) AS c FROM bencana$w GROUP BY tahun ORDER BY tahun", $params) as $r) {
    $perYear[(int)$r['tahun']] = (int)$r['c'];
}
$perEvent = [];
foreach (q($pdo, "SELECT event, COUNT(*) AS c FROM bencana$w GROUP BY event ORDER BY c DESC", $params) as $r) {
    $perEvent[$r['event']] = (int)$r['c'];
}
$topProv = [];
foreach (q($pdo, "SELECT province, COUNT(*) AS c FROM bencana$w GROUP BY province ORDER BY c DESC LIMIT 10", $params) as $r) {
    $topProv[$r['province']] = (int)$r['c'];
}

// 10 kejadian dengan korban jiwa terbesar (sesuai filter)
$top = q($pdo, "SELECT date_ymd, event, province, district, deaths, injured, affected
    FROM bencana$w ORDER BY deaths DESC LIMIT 10", $params)->fetchAll(PDO::FETCH_ASSOC);

function fmt($n) { return number_format($n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dasbor Bencana Indonesia</title>
<script src="assets/tailwind.js"></script>
<script>tailwind.config = { darkMode: "class" };</script>
<script>if (localStorage.getItem("tema") === "gelap") document.documentElement.classList.add("dark");</script>
<script src="assets/chart.js"></script>
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
        <?php foreach ($events as $e): ?>
          <option value="<?= htmlspecialchars($e) ?>" <?= $fEvent === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="block">
      <span class="text-sm font-medium">Provinsi</span>
      <select name="provinsi" class="mt-1 w-full border rounded-lg px-2 py-2 dark:bg-slate-700 dark:border-slate-600">
        <option value="">Semua</option>
        <?php foreach ($provinces as $p): ?>
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
          <td class="py-2 px-2 whitespace-nowrap"><?= htmlspecialchars($r['date_ymd']) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars($r['event']) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars($r['province']) ?></td>
          <td class="py-2 px-2"><?= htmlspecialchars($r['district'] ?? '') ?></td>
          <td class="py-2 px-2 text-right"><?= fmt($r['deaths']) ?></td>
          <td class="py-2 px-2 text-right"><?= fmt($r['injured']) ?></td>
          <td class="py-2 px-2 text-right"><?= fmt($r['affected']) ?></td>
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
