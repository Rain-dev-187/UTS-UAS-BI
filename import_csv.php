<?php
// import_csv.php — bangun database SQLite (bencana.db) dari data.csv
// Jalankan sekali:  php import_csv.php   (atau buka dari browser)
// Setelah itu dasbor (index.php) membaca data dari database, bukan CSV lagi.

$dbFile = __DIR__ . '/bencana.db';
$csvFile = __DIR__ . '/data.csv';

$map = [
    'Serial' => 'serial', 'Event' => 'event', 'Code Province' => 'code_province',
    'Province' => 'province', 'Code District' => 'code_district', 'District' => 'district',
    'Date (YMD)' => 'date_ymd', 'Comments' => 'comments', 'Source' => 'source',
    'DataCards' => 'datacards', 'Deaths' => 'deaths', 'Injured' => 'injured',
    'Missing' => 'missing', 'Houses Destroyed' => 'houses_destroyed',
    'Houses Damaged' => 'houses_damaged', 'Victims' => 'victims', 'Affected' => 'affected',
    'Relocated' => 'relocated', 'Evacuated' => 'evacuated', 'Losses $USD' => 'losses_usd',
    'Losses $Local' => 'losses_local', 'Education centers' => 'education_centers',
    'Hospitals' => 'hospitals', 'Damages in crops Ha.' => 'damages_crops_ha',
    'Tahun' => 'tahun', 'Bulan' => 'bulan',
];

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('DROP TABLE IF EXISTS bencana');
$cols = implode(', ', array_map(fn($c) => "\"$c\"", array_values($map)));
$pdo->exec("CREATE TABLE bencana ($cols)");

$fh = fopen($csvFile, 'r');
$header = fgetcsv($fh);
$ph = implode(', ', array_fill(0, count($map), '?'));
$stmt = $pdo->prepare("INSERT INTO bencana ($cols) VALUES ($ph)");
$pdo->beginTransaction();
$n = 0;
while (($r = fgetcsv($fh)) !== false) {
    if (count($r) < count($header)) continue;
    $vals = [];
    foreach (array_keys($map) as $h) {
        $v = $r[array_search($h, $header)];
        $vals[] = ($v === '' ? null : $v);
    }
    $stmt->execute($vals);
    $n++;
}
$pdo->commit();
fclose($fh);
$pdo->exec('CREATE INDEX idx_event ON bencana(event)');
$pdo->exec('CREATE INDEX idx_province ON bencana(province)');
$pdo->exec('CREATE INDEX idx_tahun ON bencana(tahun)');
echo "Selesai: $n baris dimasukkan ke bencana.db\n";
