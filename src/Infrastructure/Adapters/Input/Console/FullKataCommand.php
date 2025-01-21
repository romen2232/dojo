<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Dojo\Application\Service\KataScraperService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FullKataCommand extends Command
{
    protected static $defaultName = 'kata:full';

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
        'swift' => 'swift',
    ];

    public function __construct(
        private KataScraperService $kataScraperService,
        private ScrapeKataCommand $scrapeKataCommand,
        private GenerateKataFilesCommand $generateKataFilesCommand
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Scrapes a kata from Codewars and generates the solution files')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of the kata to scrape')
            ->addOption('katas-dir', 'd', InputOption::VALUE_OPTIONAL, 'Custom directory to store katas', 'katas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // First, run the scrape command
            $result = $this->scrapeKataCommand->run($input, $output);
            if ($result !== self::SUCCESS) {
                return $result;
            }

            // Get the path to the generated JSON file
            $katasDir = $input->getOption('katas-dir');
            $kata = $this->kataScraperService->scrapeKata($input->getArgument('url'));
            $kyuLevel = $this->getKyuLevel($kata->getDifficulty());
            $language = strtolower($kata->getLanguage());
            $kataName = $this->sanitizeFolderName($kata->getName());

            $jsonPath = sprintf(
                '%s/codewars/%s/%s_kyu/%s/.%s.json',
                $katasDir,
                $language,
                $kyuLevel,
                $kataName,
                $kataName
            );

            // Create new input for generate files command
            $generateInput = new ArrayInput([
                'kata-json' => $jsonPath,
            ]);

            // Run the generate files command
            return $this->generateKataFilesCommand->run($generateInput, $output);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }

    private function sanitizeFolderName(string $name): string
    {
        // Remove special characters and convert spaces to underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\s-]/', '', $name);
        $sanitized = strtolower(trim($sanitized));

        return str_replace(' ', '_', $sanitized);
    }

    private function getKyuLevel(string $difficulty): string
    {
        preg_match('/(\d+)\s*kyu/', $difficulty, $matches);

        return $matches[1] ?? '0';
    }
}
