<?php

namespace Gov2lib;

/**
 * Base exception handler for the Gov2lib framework.
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2017-09-30
 */
class customException extends \Exception
{
    /**
     * Handle an exception by parsing its "Code:Message" format
     * and delegating to the document error handler.
     */
    public function exceptionHandler(string $e): void
    {
        global $doc, $pageID;

        $parts = explode(':', $e, 2);
        $code = $parts[0] ?? 'Error';
        $message = $parts[1] ?? $e;

        // Detect MySQL connection errors and hint at dsnSource
        $connKeywords = ['Unable to connect to MySQL', 'Access denied', 'Connection refused', "Can't connect to"];
        foreach ($connKeywords as $keyword) {
            if (stripos($e, $keyword) !== false) {
                $stage = defined('STAGE') ? STAGE : 'dev';
                $message = "Koneksi database gagal. Periksa file apps/{$pageID}/xml/dsnSource.{$stage}.xml";
                $code = 'DatabaseConnection';
                break;
            }
        }

        $doc->error($code, $message);
    }
}
