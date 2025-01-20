<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKataFilesCommand extends BaseKataCommand
{
    protected static $defaultName = 'kata:generate-files';

    protected function configure(): void
    {
        $this
            ->setDescription('Generates solution and test files from a kata JSON file')
            ->addArgument('kata-json', InputArgument::REQUIRED, 'Path to the kata JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $jsonPath = $input->getArgument('kata-json');

            if (!file_exists($jsonPath)) {
                throw new \RuntimeException('JSON file not found: ' . $jsonPath);
            }

            $jsonContent = file_get_contents($jsonPath);
            $kataData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON file: ' . json_last_error_msg());
            }

            // Get the base directory (where the JSON file is located)
            $baseDir = dirname($jsonPath);

            // Get file extension for the language
            $extension = $this->getLanguageExtension($kataData['language']);

            // Create solution file
            $solutionFile = $baseDir . '/solution.' . $extension;
            $this->writeFileWithPermissions($solutionFile, $kataData['solutionPlaceholder']);

            // Create test file
            $testFile = $baseDir . '/test.' . $extension;
            $this->writeFileWithPermissions($testFile, $kataData['tests']);

            // Create README.md
            $readmeContent = "# {$kataData['name']}\n\n";
            $readmeContent .= "Difficulty: {$kataData['difficulty']}\n\n";
            $readmeContent .= $kataData['description'];

            $readmeFile = $baseDir . '/README.md';
            $this->writeFileWithPermissions($readmeFile, $readmeContent);

            $output->writeln('<info>Files generated successfully:</info>');
            $output->writeln("- Solution: $solutionFile");
            $output->writeln("- Tests: $testFile");
            $output->writeln("- README: $readmeFile");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }
}
