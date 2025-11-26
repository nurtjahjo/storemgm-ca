<?php
// file: generate_audio_epubs.php
// Jalankan: php generate_audio_epubs.php

require_once __DIR__ . '/vendor/autoload.php';

// --- KONFIGURASI ---
$dbConfig = [
    'host' => 'localhost',
    'name' => 'nurtjahj_storemgm', 
    'user' => 'root',
    'pass' => '300966'
];

$baseStorage = __DIR__ . '/storage/app';
$dirs = [
    // Perhatikan path sumber file fisik Anda
    'covers'     => $baseStorage . '/protected_catalog/covers/',
    'profiles'   => $baseStorage . '/protected_catalog/profiles/',
    'narrations' => $baseStorage . '/private_content/narrations/',
    'audios'     => $baseStorage . '/private_content/audios/',
    'output'     => $baseStorage . '/private_content/books/'
];

// Pastikan output dir ada
if (!is_dir($dirs['output'])) mkdir($dirs['output'], 0755, true);

echo "==========================================================\n";
echo "   AUDIO-EPUB GENERATOR (WEBP + MP3 EMBEDDED)             \n";
echo "==========================================================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Ambil Produk Target (Audiobook, Published, Belum punya source file)
    $sql = "SELECT id, title, author_id, cover_image_path, profile_audio_path
            FROM storemgm_products 
            WHERE type = 'audiobook' 
              AND published_at IS NOT NULL 
              AND source_file_path IS NULL";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Ditemukan " . count($products) . " audiobook untuk diproses.\n\n";

    foreach ($products as $prod) {
        echo "Memproses: {$prod['title']}...\n";
        
        $epubFilename = $prod['id'] . '.epub';
        $epubPath = $dirs['output'] . $epubFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($epubPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            echo "  [ERROR] Gagal membuat file ZIP.\n";
            continue;
        }

        // Variabel penampung untuk OPF
        $manifestItems = "";
        $spineItems = "";
        $navPoints = "";
        $playOrder = 1;

        // A. MIMETYPE
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

        // B. CONTAINER XML
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>');

        // C. PROSES COVER (WEBP)
        // Kita pakai nama file cover dari DB (biasanya _thumb), kita ganti jadi _large untuk kualitas
        $coverBasename = $prod['id'] . '_large.webp'; 
        $sourceCoverPath = $dirs['covers'] . $coverBasename;
        
        if (file_exists($sourceCoverPath)) {
            // Masukkan file WebP langsung
            $zip->addFile($sourceCoverPath, 'OEBPS/Images/cover.webp');
            
            // Tambahkan halaman HTML Cover
            $htmlCover = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Cover</title></head>
<body style="margin:0;padding:0;text-align:center;">
    <div style="height:100vh;display:flex;justify-content:center;align-items:center;">
        <img src="../Images/cover.webp" alt="Cover" style="max-height:100%;max-width:100%;"/>
    </div>
</body>
</html>';
            $zip->addFromString('OEBPS/Text/cover.xhtml', $htmlCover);

            // Register Manifest & Spine
            $manifestItems .= '<item id="cover-img" href="Images/cover.webp" media-type="image/webp" properties="cover-image"/>' . "\n";
            $manifestItems .= '<item id="cover-page" href="Text/cover.xhtml" media-type="application/xhtml+xml"/>' . "\n";
            $spineItems .= '<itemref idref="cover-page"/>' . "\n";
        } else {
            echo "  [INFO] Cover large tidak ditemukan: $coverBasename\n";
        }

        // D. PROSES AUDIO PROFIL (INTRO)
        // Kita gunakan nama dari DB atau konvensi {id}.mp3
        $profileAudioName = $prod['profile_audio_path'] ?? ($prod['id'] . '.mp3');
        $sourceProfilePath = $dirs['profiles'] . $profileAudioName;

        if (file_exists($sourceProfilePath)) {
            $zip->addFile($sourceProfilePath, 'OEBPS/Audio/intro.mp3');
            
            // Buat Halaman Intro
            $htmlIntro = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Introduction</title></head>
<body>
    <h1>Introduction / Profil Buku</h1>
    <p>Dengarkan profil buku ini:</p>
    <audio controls="controls" src="../Audio/intro.mp3" style="width:100%;">
        Browser Anda tidak mendukung elemen audio.
    </audio>
</body>
</html>';
            $zip->addFromString('OEBPS/Text/intro.xhtml', $htmlIntro);

            $manifestItems .= '<item id="audio-intro" href="Audio/intro.mp3" media-type="audio/mpeg"/>' . "\n";
            $manifestItems .= '<item id="page-intro" href="Text/intro.xhtml" media-type="application/xhtml+xml"/>' . "\n";
            $spineItems .= '<itemref idref="page-intro"/>' . "\n";
            
            $navPoints .= '<navPoint id="navPoint-0" playOrder="'.$playOrder++.'"><navLabel><text>Introduction</text></navLabel><content src="Text/intro.xhtml"/></navPoint>' . "\n";
        }

        // E. PROSES KONTEN BAB (NASKAH + AUDIO)
        $sqlBab = "SELECT id, title, content_text_path, content_audio_path, chapter_order 
                   FROM storemgm_product_contents 
                   WHERE product_id = :pid 
                   ORDER BY chapter_order ASC";
        $stmtBab = $pdo->prepare($sqlBab);
        $stmtBab->execute([':pid' => $prod['id']]);
        $chapters = $stmtBab->fetchAll(PDO::FETCH_ASSOC);

        foreach ($chapters as $i => $bab) {
            $chapNum = $i + 1;
            
            // 1. Ambil Naskah HTML
            $htmlFilename = basename($bab['content_text_path']); // misal: UUID.html
            $sourceHtmlPath = $dirs['narrations'] . $htmlFilename;
            $babContent = file_exists($sourceHtmlPath) ? file_get_contents($sourceHtmlPath) : "<p>[Teks tidak tersedia]</p>";

            // 2. Ambil Audio MP3
            $audioFilename = basename($bab['content_audio_path']); // misal: UUID.mp3
            $sourceAudioPath = $dirs['audios'] . $audioFilename;
            $hasAudio = false;

            if (file_exists($sourceAudioPath)) {
                $zip->addFile($sourceAudioPath, "OEBPS/Audio/chap_{$chapNum}.mp3");
                $manifestItems .= '<item id="audio-chap-'.$chapNum.'" href="Audio/chap_'.$chapNum.'.mp3" media-type="audio/mpeg"/>' . "\n";
                $hasAudio = true;
            }

            // 3. Susun XHTML Bab (Embed Audio Player)
            $audioPlayer = $hasAudio 
                ? '<div style="margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                     <strong>Putar Audio Bab Ini:</strong><br/>
                     <audio controls="controls" src="../Audio/chap_'.$chapNum.'.mp3" style="width:100%; margin-top:5px;"></audio>
                   </div>' 
                : '';

            $xhtml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . htmlspecialchars($bab['title']) . '</title>
<style>body { font-family: sans-serif; line-height: 1.6; margin: 1em; }</style>
</head>
<body>
<h2>' . htmlspecialchars($bab['title']) . '</h2>
' . $audioPlayer . '
<hr/>
' . $babContent . '
</body>
</html>';

            $zip->addFromString("OEBPS/Text/chap_{$chapNum}.xhtml", $xhtml);

            // 4. Update Manifest & Spine
            $manifestItems .= '<item id="page-chap-'.$chapNum.'" href="Text/chap_'.$chapNum.'.xhtml" media-type="application/xhtml+xml"/>' . "\n";
            $spineItems .= '<itemref idref="page-chap-'.$chapNum.'"/>' . "\n";
            
            $navPoints .= '<navPoint id="navPoint-'.$chapNum.'" playOrder="'.$playOrder++.'">
                <navLabel><text>' . htmlspecialchars($bab['title']) . '</text></navLabel>
                <content src="Text/chap_'.$chapNum.'.xhtml"/>
            </navPoint>' . "\n";
        }

        // F. BUAT FILE OPF (METADATA)
        $opf = '<?xml version="1.0" encoding="utf-8"?>
<package version="3.0" unique-identifier="BookId" xmlns="http://www.idpf.org/2007/opf">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:identifier id="BookId">urn:uuid:' . $prod['id'] . '</dc:identifier>
    <dc:title>' . htmlspecialchars($prod['title']) . '</dc:title>
    <dc:language>id</dc:language>
    <meta property="dcterms:modified">' . date('Y-m-d\TH:i:s\Z') . '</meta>
  </metadata>
  <manifest>
    <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
    ' . $manifestItems . '
  </manifest>
  <spine toc="ncx">
    ' . $spineItems . '
  </spine>
</package>';
        $zip->addFromString('OEBPS/content.opf', $opf);

        // G. BUAT NCX (TOC)
        $ncx = '<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
  <head>
    <meta name="dtb:uid" content="urn:uuid:' . $prod['id'] . '"/>
    <meta name="dtb:depth" content="1"/>
    <meta name="dtb:totalPageCount" content="0"/>
    <meta name="dtb:maxPageNumber" content="0"/>
  </head>
  <docTitle><text>' . htmlspecialchars($prod['title']) . '</text></docTitle>
  <navMap>
    ' . $navPoints . '
  </navMap>
</ncx>';
        $zip->addFromString('OEBPS/toc.ncx', $ncx);

        $zip->close();

        // H. UPDATE DATABASE
        // Simpan path relatif: books/{uuid}.epub
        $dbPath = 'books/' . $epubFilename;
        $upd = $pdo->prepare("UPDATE storemgm_products SET source_file_path = :path WHERE id = :id");
        $upd->execute([':path' => $dbPath, ':id' => $prod['id']]);

        echo "  [OK] Selesai. File tersimpan di $dbPath\n";
    }

    echo "\nSemua proses selesai.\n";

} catch (Exception $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
}