<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKataFilesCommand extends Command
{
    protected static $defaultName = 'kata:generate-files';

    private const LANGUAGE_EXTENSIONS = [
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
        'swift' => 'swift'
    ];

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

            // Create solution directory
            $solutionDir = $baseDir . '/solution';
            if (!is_dir($solutionDir)) {
                mkdir($solutionDir, 0777, true);
            }

            // Create tests directory
            $testsDir = $baseDir . '/tests';
            if (!is_dir($testsDir)) {
                mkdir($testsDir, 0777, true);
            }

            // Get file extension for the language
            $language = strtolower($kataData['language']);
            $extension = self::LANGUAGE_EXTENSIONS[$language] ?? 'txt';

            // Create solution file
            $solutionFile = $solutionDir . '/solution.' . $extension;
            file_put_contents($solutionFile, $kataData['solutionPlaceholder']);
            chmod($solutionFile, 0666);

            // Create test file in the tests directory
            $testFile = $testsDir . '/test.' . $extension;
            file_put_contents($testFile, $kataData['tests']);
            chmod($testFile, 0666);

            // Create README.md
            $readmeContent = "# {$kataData['name']}\n\n";
            $readmeContent .= "Difficulty: {$kataData['difficulty']}\n\n";
            $readmeContent .= $kataData['description'];

            $readmeFile = $baseDir . '/README.md';
            file_put_contents($readmeFile, $readmeContent);
            chmod($readmeFile, 0666);

            $output->writeln('<info>Files generated successfully:</info>');
            $output->writeln("- Solution: $solutionFile");
            $output->writeln("- Tests: $testFile");
            $output->writeln("- README: $readmeFile");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
