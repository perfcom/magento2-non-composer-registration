<?php
/**
 * @copyright   perfcom.dev - https://perfcom.dev
 */

declare(strict_types=1);

namespace Perfcom\NonComposerRegistration;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use const DIRECTORY_SEPARATOR;
use function file_exists;
use function getcwd;
use const GLOB_NOSORT;
use function rename;
use function unlink;

final class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private const REGISTRATION_GLOB_LIST = 'app/etc/registration_globlist.php';
    private const NON_COMPOSER_COMPONENT_REGISTRATION = 'app/etc/NonComposerComponentRegistration.php';
    private const NON_COMPOSER_COMPONENT_REGISTRATION_EXCLUDE = 'app/etc/NonComposerComponentRegistrationExclude.php';
    private const BACKUP_NON_COMPOSER_COMPONENT_REGISTRATION = 'app/etc/NonComposerComponentRegistration.php.backup';

    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $basePath = getcwd();
        $nonComposerComponentFilePath = $basePath . DIRECTORY_SEPARATOR . self::NON_COMPOSER_COMPONENT_REGISTRATION;
        $backupFilePath = $basePath . DIRECTORY_SEPARATOR . self::BACKUP_NON_COMPOSER_COMPONENT_REGISTRATION;
        if (file_exists($backupFilePath)) {
            unlink($nonComposerComponentFilePath);
            rename($backupFilePath, $nonComposerComponentFilePath);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'nonComposerRegistration',
            ScriptEvents::POST_UPDATE_CMD => 'nonComposerRegistration'
        ];
    }

    public function nonComposerRegistration(Event $event): void
    {
        $io = $event->getIO();
        $io->write('<info>Generating NonComposerComponentRegistration file...</info>');

        $basePath = getcwd();
        $fileName = self::NON_COMPOSER_COMPONENT_REGISTRATION;
        $nonComposerComponentFilePath = $basePath . DIRECTORY_SEPARATOR . $fileName;
        $backupFilePath = $basePath . DIRECTORY_SEPARATOR . self::BACKUP_NON_COMPOSER_COMPONENT_REGISTRATION;

        if (file_exists($nonComposerComponentFilePath) && !file_exists($backupFilePath)) {
            rename($nonComposerComponentFilePath, $backupFilePath);
            $io->write('<comment>Backup of ' . $nonComposerComponentFilePath . ' at ' . $backupFilePath . '</comment>');
        }

        $this->dumpRegistration($basePath, $fileName);

        $io->write('<info>Dumped at <comment>`' . $nonComposerComponentFilePath . '`</comment>!</info>');
    }

    /**
     * @param string $basePath
     * @param array $registrationFiles
     * @return array
     */
    public function excludeDisabled(string $basePath, array $registrationFiles): array
    {
        if (! file_exists($basePath . DIRECTORY_SEPARATOR . self::NON_COMPOSER_COMPONENT_REGISTRATION_EXCLUDE)) {
            return $registrationFiles;
        }

        $excludeList = require $basePath . DIRECTORY_SEPARATOR . self::NON_COMPOSER_COMPONENT_REGISTRATION_EXCLUDE;

        $finalRegistrationFiles = [];
        foreach ($registrationFiles as $registrationFile) {
            $path = implode('/', array_slice(explode('/', dirname($registrationFile)), -2));
            if (\in_array($path, $excludeList, true)) {
                continue;
            }
            $finalRegistrationFiles[] = $registrationFile;

        }
        return $finalRegistrationFiles;
    }

    private function dumpRegistration(string $basePath, string $fileName): void
    {
        $filePath = $basePath . DIRECTORY_SEPARATOR . $fileName;

        $globPatterns = require $basePath . DIRECTORY_SEPARATOR . self::REGISTRATION_GLOB_LIST;

        $registrationFiles = [];

        foreach ($globPatterns as $globPattern) {
            $files = \glob($basePath . '/' . $globPattern, GLOB_NOSORT);

            if ($files === false) {
                continue;
            }
            $registrationFiles = array_merge($registrationFiles, $files);
        }

        $registrationFiles = $this->excludeDisabled($basePath, $registrationFiles);

        $registrationFilesString = var_export($registrationFiles, true);
        $phpCode = <<<PHP
<?php

\$registrationFiles = $registrationFilesString;

foreach (\$registrationFiles as \$registrationFile) {
    require_once \$registrationFile;
}
PHP;

        file_put_contents($filePath, $phpCode);

    }
}
