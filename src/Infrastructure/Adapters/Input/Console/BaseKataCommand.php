<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Symfony\Component\Console\Command\Command;

abstract class BaseKataCommand extends Command
{
    protected const LANGUAGE_EXTENSIONS = [
        'python' => 'py',
        'javascript' => 'js',
        'typescript' => 'ts',
        'java' => 'java',
        'c#' => 'cs',
        'c++' => 'cpp',
        'php' => 'php',
        'ruby' => 'rb',
        'rust' => 'rs',
        'go' => 'go',
        'kotlin' => 'kt',
        'scala' => 'scala',
        'swift' => 'swift',
    ];

    protected function sanitizeFolderName(string $name): string
    {
        // Remove special characters except alphanumeric, spaces, and hyphens
        $sanitized = preg_replace('/[^a-zA-Z0-9\s-]/', '', $name);
        // Replace multiple spaces with a single space
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        // Trim spaces from beginning and end
        $sanitized = trim($sanitized);
        // Replace spaces with underscores
        $sanitized = str_replace(' ', '_', $sanitized);

        // Convert to lowercase
        return strtolower($sanitized);
    }

    protected function createDirectoryWithPermissions(string $path): void
    {
        // If it's a relative path starting with ./ or ../, resolve it from /dojo
        if (preg_match('/^\.\.?\//', $path)) {
            $path = '/dojo/' . $path;
        }
        // If it's not an absolute path, make it relative to /dojo
        elseif (!str_starts_with($path, '/')) {
            $path = '/dojo/' . $path;
        }

        // Clean up the path (remove double slashes)
        $path = preg_replace('#/+#', '/', $path);

        if (!is_dir($path)) {
            $oldUmask = umask(0);
            if (!@mkdir($path, 0777, true)) {
                $error = error_get_last();

                throw new \RuntimeException(sprintf(
                    'Directory "%s" was not created: %s',
                    $path,
                    $error['message'] ?? 'Unknown error'
                ));
            }
            umask($oldUmask);
            // Ensure the directory has the correct permissions even if mkdir didn't set them
            @chmod($path, 0777);
        }
    }

    protected function writeFileWithPermissions(string $path, string $content): void
    {
        // Ensure the parent directory exists with proper permissions
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $this->createDirectoryWithPermissions($dir);
        }

        // Write the file with proper permissions
        if (@file_put_contents($path, $content) === false) {
            $error = error_get_last();

            throw new \RuntimeException(sprintf(
                'Failed to write file %s: %s',
                $path,
                $error['message'] ?? 'Unknown error'
            ));
        }

        // Ensure the file has the correct permissions
        @chmod($path, 0666);
    }

    protected function getKyuLevel(string $difficulty): string
    {
        preg_match('/(\d+)\s*kyu/', $difficulty, $matches);

        return $matches[1] ?? '0';
    }

    protected function getLanguageExtension(string $language): string
    {
        return self::LANGUAGE_EXTENSIONS[strtolower($language)] ?? 'txt';
    }
}
