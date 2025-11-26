<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Logger;

use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use RuntimeException;
use InvalidArgumentException;
use Throwable;

class FileLogger implements LoggerInterface
{
    /**
     * Menyimpan path dasar ke direktori log.
     * @var string
     */
    private string $logDirectory;

    public function __construct(?string $logDirectory = null)
    {
        if (empty($logDirectory)) {
            throw new InvalidArgumentException("Direktori log tidak boleh kosong.");
        }
        $this->logDirectory = rtrim($logDirectory, '/\\');
    }

    public function log(string $message, string $level = 'INFO', string $filename = 'storemgm.log'): void
    {
        try {
            if (!is_dir($this->logDirectory)) {
                if (!mkdir($this->logDirectory, 0775, true)) {
                    // Jika gagal buat folder, silent fail atau lempar exception tergantung kebijakan
                    return; 
                }
            }

            $timestamp = new DateTime();
            // Format nama file log: storemgm.2025-11.log (rotasi bulanan sederhana)
            $monthSuffix = $timestamp->format('Y-m'); 
            $pathInfo = pathinfo($filename);
            $name = $pathInfo['filename'];
            $ext = $pathInfo['extension'] ?? 'log';
            
            $finalFilename = "{$name}.{$monthSuffix}.{$ext}";
            $logFilePath = "{$this->logDirectory}/{$finalFilename}";
            
            $formattedMessage = "[{$timestamp->format('Y-m-d H:i:s')}] [{$level}] {$message}" . PHP_EOL;

            file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);

        } catch (Throwable $e) {
            // Logger tidak boleh menyebabkan aplikasi crash
            // Dalam produksi, mungkin kirim ke stderr
            error_log("FileLogger Error: " . $e->getMessage());
        }
    }
}
