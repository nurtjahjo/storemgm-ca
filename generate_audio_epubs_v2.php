<?php
// file: generate_audio_epubs_v3.php

require_once __DIR__ . '/vendor/autoload.php';

// --- KONFIGURASI DATABASE ---
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '300966',
    'db_store' => 'nurtjahj_storemgm',
    'db_user'  => 'nurtjahj_usermgmdb'
];

// --- KONFIGURASI PATH ---
$baseStorage = __DIR__ . '/storage/app';
$dirs = [
    'covers'     => $baseStorage . '/protected_catalog/covers/',
    'profiles'   => $baseStorage . '/protected_catalog/profiles/',
    'narrations' => $baseStorage . '/private_content/narrations/',
    'audios'     => $baseStorage . '/private_content/audios/',
    'output'     => $baseStorage . '/private_content/books/'
];

// Pastikan output dir ada
if (!is_dir($dirs['output'])) mkdir($dirs['output'], 0755, true);

echo "==========================================================\n";
echo "   AUDIO-EPUB GENERATOR V3 (CLEAN PLAYER + VISUAL TOC)    \n";
echo "==========================================================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['db_store']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Helper: Ambil Nama Lengkap User
    function getUserFullName($pdo, $userId, $dbUser) {
        if (empty($userId)) return "-";
        
        $sqlProfile = "SELECT uprofil_value FROM {$dbUser}.usermgmca_user_profiles 
                       WHERE uprofil_id = :uid AND uprofil_key = 'user_fullname' LIMIT 1";
        $stmt = $pdo->prepare($sqlProfile);
        $stmt->execute([':uid' => $userId]);
        $res = $stmt->fetchColumn();
        
        if ($res) return $res;

        $sqlUser = "SELECT name FROM {$dbUser}.usermgmca_users WHERE id = :uid LIMIT 1";
        $stmt = $pdo->prepare($sqlUser);
        $stmt->execute([':uid' => $userId]);
        $res = $stmt->fetchColumn();

        return $res ? $res : "Unknown";
    }

    // 1. Ambil Produk Target
    $sql = "SELECT id, title, synopsis, author_id, narrator_id, cover_image_path, profile_audio_path
            FROM {$dbConfig['db_store']}.storemgm_products 
            WHERE type = 'audiobook' 
              AND published_at IS NOT NULL 
              AND source_file_path IS NULL";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Ditemukan " . count($products) . " audiobook antrian.\n\n";

    foreach ($products as $prod) {
        echo ">> Memproses: {$prod['title']} ({$prod['id']})\n";
        
        $epubFilename = $prod['id'] . '.epub';
        $epubPath = $dirs['output'] . $epubFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($epubPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            echo "   [ERROR] Gagal membuat file ZIP.\n";
            continue;
        }

        // Variabel penampung
        $manifestItems = "";
        $spineItems = "";
        $navPoints = ""; // Untuk NCX (Logic Navigation)
        $tocListHtml = ""; // Untuk Halaman Visual TOC
        $playOrder = 1;

        // --- A. MIMETYPE ---
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

        // --- B. CONTAINER XML ---
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>');

        // --- C. COVER IMAGE & PAGE ---
        $coverBasename = $prod['id'] . '_large.webp';
        $sourceCoverPath = $dirs['covers'] . $coverBasename;
        
        if (!file_exists($sourceCoverPath) && !empty($prod['cover_image_path'])) {
             $sourceCoverPath = $dirs['covers'] . basename($prod['cover_image_path']);
        }

        if (file_exists($sourceCoverPath)) {
            $zip->addFile($sourceCoverPath, 'OEBPS/Images/cover.webp');
            
            $htmlCover = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head><title>Cover</title></head>
<body style="margin:0;padding:0;text-align:center;">
    <div style="height:100vh;display:flex;justify-content:center;align-items:center;">
        <img src="../Images/cover.webp" alt="Cover" style="max-height:100%;max-width:100%;"/>
    </div>
</body>
</html>';
            $zip->addFromString('OEBPS/Text/cover.xhtml', $htmlCover);

            $manifestItems .= '<item id="cover-img" href="Images/cover.webp" media-type="image/webp" properties="cover-image"/>' . "\n";
            $manifestItems .= '<item id="page-cover" href="Text/cover.xhtml" media-type="application/xhtml+xml"/>' . "\n";
            $spineItems .= '<itemref idref="page-cover"/>' . "\n";
        }

        // --- D. HALAMAN PROFIL (Include Audio Player tanpa Caption) ---
        $authorName   = getUserFullName($pdo, $prod['author_id'], $dbConfig['db_user']);
        $narratorName = getUserFullName($pdo, $prod['narrator_id'], $dbConfig['db_user']);
        $synopsisHtml = nl2br(htmlspecialchars($prod['synopsis'] ?? ''));

        // Cek Audio Profil
        $profileAudioCode = "";
        $profileAudioName = $prod['profile_audio_path'] ?? ($prod['id'] . '.mp3');
        $sourceProfilePath = $dirs['profiles'] . $profileAudioName;
        if (!file_exists($sourceProfilePath)) $sourceProfilePath = $dirs['profiles'] . basename($profileAudioName);

        if (file_exists($sourceProfilePath) && is_file($sourceProfilePath)) {
            $zip->addFile($sourceProfilePath, 'OEBPS/Audio/intro.mp3');
            $manifestItems .= '<item id="audio-intro" href="Audio/intro.mp3" media-type="audio/mpeg"/>' . "\n";
            
            // Player polos, nodownload
            $profileAudioCode = '<div style="margin: 1.5em 0; text-align: center;">
                <audio controls="controls" controlsList="nodownload" src="../Audio/intro.mp3" style="width:100%;">
                </audio>
            </div>';
        }

        $htmlProfile = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
    <title>Profil Buku</title>
    <style>
        body { font-family: sans-serif; margin: 1.5em; line-height: 1.6; }
        h1 { text-align: center; color: #333; margin-bottom: 0.2em; }
        .meta { text-align: center; font-size: 0.9em; color: #555; }
        .synopsis { text-align: justify; margin-top: 1em; }
        hr { border: 0; border-top: 1px solid #ccc; margin: 1em 0; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($prod['title']) . '</h1>
    
    <div class="meta">
        <p><strong>Penulis:</strong> ' . htmlspecialchars($authorName) . '<br/>
        <strong>Narator:</strong> ' . htmlspecialchars($narratorName) . '</p>
    </div>

    ' . $profileAudioCode . '
    
    <hr/>
    
    <h3>Sinopsis</h3>
    <div class="synopsis">
        <p>' . $synopsisHtml . '</p>
    </div>
</body>
</html>';
        
        $zip->addFromString('OEBPS/Text/profile.xhtml', $htmlProfile);
        
        $manifestItems .= '<item id="page-profile" href="Text/profile.xhtml" media-type="application/xhtml+xml"/>' . "\n";
        $spineItems .= '<itemref idref="page-profile"/>' . "\n";
        
        // Navigasi NCX
        $navPoints .= '<navPoint id="navPoint-profile" playOrder="'.$playOrder++.'"><navLabel><text>Profil Buku</text></navLabel><content src="Text/profile.xhtml"/></navPoint>' . "\n";

        // --- E. PERSIAPAN LOOP BAB (Untuk generate file dan TOC Link) ---
        $sqlBab = "SELECT id, title, content_text_path, content_audio_path, chapter_order 
                   FROM {$dbConfig['db_store']}.storemgm_product_contents 
                   WHERE product_id = :pid 
                   ORDER BY chapter_order ASC";
        $stmtBab = $pdo->prepare($sqlBab);
        $stmtBab->execute([':pid' => $prod['id']]);
        $chapters = $stmtBab->fetchAll(PDO::FETCH_ASSOC);

        // Kita simpan string itemref spine bab di variable sementara, karena kita mau insert TOC page SEBELUM bab
        $spineChapters = ""; 

        foreach ($chapters as $i => $bab) {
            $chapNum = $i + 1;
            $chapFilename = "chap_{$chapNum}.xhtml";
            $chapTitle = htmlspecialchars($bab['title']);
            
            // 1. Ambil Konten Teks
            $htmlFilename = basename($bab['content_text_path']);
            $sourceHtmlPath = $dirs['narrations'] . $htmlFilename;
            $babContent = file_exists($sourceHtmlPath) ? file_get_contents($sourceHtmlPath) : "<p><i>[Konten teks belum tersedia]</i></p>";

            // 2. Audio Player (Polos, No Download)
            $audioFilename = basename($bab['content_audio_path']);
            $sourceAudioPath = $dirs['audios'] . $audioFilename;
            $audioPlayer = '';

            if (file_exists($sourceAudioPath) && is_file($sourceAudioPath)) {
                $zip->addFile($sourceAudioPath, "OEBPS/Audio/chap_{$chapNum}.mp3");
                $manifestItems .= '<item id="audio-chap-'.$chapNum.'" href="Audio/chap_'.$chapNum.'.mp3" media-type="audio/mpeg"/>' . "\n";
                
                $audioPlayer = '<div style="margin-bottom: 20px; padding: 5px; background: #f9f9f9; border-radius: 5px;">
                     <audio controls="controls" controlsList="nodownload" src="../Audio/chap_'.$chapNum.'.mp3" style="width:100%;"></audio>
                   </div>';
            }

            // 3. Buat File XHTML Bab
            $xhtml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
<title>' . $chapTitle . '</title>
<style>body { font-family: sans-serif; line-height: 1.6; margin: 1em; } img { max-width: 100%; }</style>
</head>
<body>
<h2>' . $chapTitle . '</h2>
' . $audioPlayer . '
<hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;"/>
' . $babContent . '
</body>
</html>';

            $zip->addFromString("OEBPS/Text/{$chapFilename}", $xhtml);

            // 4. Update Manifest & Spine Chapter
            $manifestItems .= '<item id="page-chap-'.$chapNum.'" href="Text/'.$chapFilename.'" media-type="application/xhtml+xml"/>' . "\n";
            $spineChapters .= '<itemref idref="page-chap-'.$chapNum.'"/>' . "\n";

            // 5. Tambahkan ke Navigasi NCX
            $navPoints .= '<navPoint id="navPoint-'.$chapNum.'" playOrder="'.$playOrder++.'">
                <navLabel><text>' . $chapTitle . '</text></navLabel>
                <content src="Text/'.$chapFilename.'"/>
            </navPoint>' . "\n";

            // 6. Tambahkan ke List Visual TOC
            $tocListHtml .= "<li><a href=\"{$chapFilename}\">{$chapTitle}</a></li>\n";
        }

        // --- F. MEMBUAT HALAMAN VISUAL TOC (Daftar Isi Fisik) ---
        // Dibuat setelah loop agar list bab lengkap
        if (!empty($chapters)) {
            $htmlToc = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
    <title>Daftar Isi</title>
    <style>
        body { font-family: sans-serif; margin: 1.5em; }
        h1 { text-align: center; border-bottom: 1px solid #ccc; padding-bottom: 0.5em; }
        ul { list-style-type: none; padding: 0; }
        li { margin: 0.5em 0; border-bottom: 1px dashed #eee; padding: 5px; }
        a { text-decoration: none; color: #000; display: block; width: 100%; }
        a:hover { color: #555; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Daftar Isi</h1>
    <ul>
        ' . $tocListHtml . '
    </ul>
</body>
</html>';
            $zip->addFromString('OEBPS/Text/toc_page.xhtml', $htmlToc);
            
            // Tambahkan ke Manifest
            $manifestItems .= '<item id="page-toc" href="Text/toc_page.xhtml" media-type="application/xhtml+xml"/>' . "\n";
            
            // Tambahkan ke Spine (SEBELUM BAB, SETELAH PROFIL)
            // Urutan saat ini di $spineItems: Cover -> Profil
            $spineItems .= '<itemref idref="page-toc"/>' . "\n";
        }

        // Gabungkan spine bab setelah TOC
        $spineItems .= $spineChapters;

        // --- G. MEMBUAT NAV.XHTML (Semantic Navigation - Hidden but Standard) ---
        // Ini TOC "Sistem" untuk menu aplikasi
        $navXhtml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head><title>Navigation</title></head>
<body>
    <nav epub:type="toc" id="toc">
        <h1>Daftar Isi</h1>
        <ol>
            <li><a href="profile.xhtml">Profil Buku</a></li>
            ' . (!empty($chapters) ? '<li><a href="toc_page.xhtml">Daftar Isi</a></li>' : '') . '
            ' . $tocListHtml . '
        </ol>
    </nav>
</body>
</html>';
        $zip->addFromString('OEBPS/Text/nav.xhtml', $navXhtml);
        $manifestItems .= '<item id="nav" href="Text/nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>' . "\n";


        // --- H. BUAT FILE NCX (Legacy Navigation) ---
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

        // --- I. FILE OPF ---
        $opf = '<?xml version="1.0" encoding="utf-8"?>
<package version="3.0" unique-identifier="BookId" xmlns="http://www.idpf.org/2007/opf">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
    <dc:identifier id="BookId">urn:uuid:' . $prod['id'] . '</dc:identifier>
    <dc:title>' . htmlspecialchars($prod['title']) . '</dc:title>
    <dc:creator>' . htmlspecialchars($authorName) . '</dc:creator>
    <dc:language>id</dc:language>
    <meta property="dcterms:modified">' . gmdate('Y-m-d\TH:i:s\Z') . '</meta>
    <meta name="cover" content="cover-img"/>
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

        $zip->close();

        // --- J. UPDATE DATABASE ---
        $dbPath = 'books/' . $epubFilename;
        $upd = $pdo->prepare("UPDATE {$dbConfig['db_store']}.storemgm_products SET source_file_path = :path WHERE id = :id");
        $upd->execute([':path' => $dbPath, ':id' => $prod['id']]);

        echo "   [OK] Tersimpan: $dbPath\n";
    }

    echo "\nSelesai. Semua file diproses.\n";

} catch (Exception $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
}