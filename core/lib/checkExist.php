<?php

namespace Gov2lib;

/**
 * Utility class to verify application directory and file existence.
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2017-10-28
 * @version 2.0 - PHP 8.3 refactor
 */
class checkExist extends dsnSource
{
    public function __construct(string $dsn = '')
    {
        parent::__construct();
        $this->connectDB($dsn);
    }

    /**
     * Check if an app directory exists.
     */
    public function checkAppDir(string $appDir): ?string
    {
        $dir = __DIR__ . '/../../apps';
        $dirs = array_slice(scandir($dir), 2);

        foreach ($dirs as $val) {
            if ($val === $appDir) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Check if a file exists within an app directory.
     */
    public function checkAppFile(string $appDir, string $appFile): ?string
    {
        $dir = __DIR__ . "/../../apps/{$appDir}/";

        if (!is_dir($dir)) {
            return null;
        }

        $files = array_slice(scandir($dir), 2);

        foreach ($files as $val) {
            if ($val === $appFile) {
                return $val;
            }
        }

        return null;
    }
}
