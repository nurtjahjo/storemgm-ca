<?php
// file: patch_closing_data.php
require_once __DIR__ . '/vendor/autoload.php'; 

// CONFIG (Sesuaikan!)
$dbV1 = ['host' => 'localhost', 'name' => 'swaraksa_dbutama', 'user' => 'root', 'pass' => '300966'];
$dbV2 = ['host' => 'localhost', 'name' => 'nurtjahj_storemgm', 'user' => 'root', 'pass' => '300966'];
$profileStorage = __DIR__ . '/storage/app/protected_catalog/profiles/'; // Lokasi simpan

if (!is_dir($profileStorage)) mkdir($profileStorage, 0777, true);

echo "--- Patching Closing Data (V1 -> V2) ---\n";

try {
    $pdo1 = new PDO("mysql:host={$dbV1['host']};dbname={$dbV1['name']};charset=utf8mb4", $dbV1['user'], $dbV1['pass']);
    $pdo2 = new PDO("mysql:host={$dbV2['host']};dbname={$dbV2['name']};charset=utf8mb4", $dbV2['user'], $dbV2['pass']);
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ambil semua produk di V2 yang audiobook
    $stmt2 = $pdo2->query("SELECT id, title FROM storemgm_products WHERE type = 'audiobook'");
    $products = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    $stmtUpd = $pdo2->prepare("UPDATE storemgm_products SET closing_text = :txt, closing_audio_path = :aud WHERE id = :id");

    foreach ($products as $prod) {
        // 2. Cari pasangan di V1 berdasarkan JUDUL
        // (Asumsi judul unik/sama persis. Jika tidak, logic harus lebih canggih)
        $stmt1 = $pdo1->prepare("SELECT audiobook_penutup, audiobook_finish_audio FROM sa_audiobooks WHERE audiobook_title = ? LIMIT 1");
        $stmt1->execute([$prod['title']]);
        $v1Data = $stmt1->fetch(PDO::FETCH_ASSOC);

        if ($v1Data) {
            $closingText = $v1Data['audiobook_penutup'];
            $blobAudio = $v1Data['audiobook_finish_audio'];
            $audioPath = null;

            // 3. Simpan BLOB audio ke file
            if (!empty($blobAudio)) {
                $filename = $prod['id'] . '_finish.mp3';
                file_put_contents($profileStorage . $filename, $blobAudio);
                $audioPath = $filename; // Simpan nama file saja, karena di root profiles
            }

            // 4. Update Database V2
            if ($closingText || $audioPath) {
                $stmtUpd->execute([
                    ':txt' => $closingText,
                    ':aud' => $audioPath,
                    ':id' => $prod['id']
                ]);
                echo "Updated: {$prod['title']}\n";
                $count++;
            }
        }
    }

    echo "Selesai. $count produk diperbarui.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
