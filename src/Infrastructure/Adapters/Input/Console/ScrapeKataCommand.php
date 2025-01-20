<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Dojo\Application\Service\KataScraperService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeKataCommand extends Command
{
    protected static $defaultName = 'kata:scrape';

    public function __construct(private KataScraperService $kataScraperService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Scrapes a kata from Codewars')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of the kata to scrape')
            ->addOption('katas-dir', 'd', InputOption::VALUE_OPTIONAL, 'Custom directory to store katas', 'katas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $katasDir = $input->getOption('katas-dir');
            $kata = $this->kataScraperService->scrapeKata($input->getArgument('url'));

            // Extract difficulty number from the string (e.g., "6 kyu" -> "6")
            preg_match('/(\d+)\s*kyu/', $kata->getDifficulty(), $matches);
            $kyuLevel = $matches[1] ?? '0';

            // Create the folder structure
            $language = strtolower($kata->getLanguage());
            $folderPath = sprintf(
                '%s/codewars/%s/%s_kyu/%s',
                $katasDir,
                $language,
                $kyuLevel,
                $this->sanitizeFolderName($kata->getName())
            );

            // Create directories recursively with proper permissions
            $oldUmask = umask(0);
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            umask($oldUmask);

            // Store kata info in JSON
            $jsonData = [
                'id' => $kata->getId(),
                'url' => $kata->getUrl(),
                'name' => $kata->getName(),
                'description' => $kata->getDescription(),
                'difficulty' => $kata->getDifficulty(),
                'language' => $kata->getLanguage(),
                'solutionPlaceholder' => $kata->getSolutionPlaceholder(),
                'tests' => $kata->getTests(),
                'tags' => $kata->getTags(),
                'category' => $kata->getCategory(),
                'author' => $kata->getAuthor(),
                'languagesAvailable' => $kata->getLanguagesAvailable(),
            ];

            // Save JSON file with proper permissions
            $jsonPath = sprintf('%s/%s.json', $folderPath, $this->sanitizeFolderName($kata->getName()));
            file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT));
            chmod($jsonPath, 0666);

            $output->writeln(sprintf("\n<info>Kata information saved to: %s</info>", $jsonPath));

            $output->writeln("\n<info>Kata Information:</info>");
            $output->writeln("<info>Name:</info> " . $kata->getName());
            $output->writeln("<info>Difficulty:</info> " . $kata->getDifficulty());
            $output->writeln("<info>Author:</info> " . ($kata->getAuthor() ?? 'Unknown'));
            $output->writeln("<info>Category:</info> " . ($kata->getCategory() ?? 'Uncategorized'));
            $output->writeln("<info>Tags:</info> " . implode(', ', $kata->getTags()));
            $output->writeln("\n<info>Description:</info>\n" . $kata->getDescription());
            $output->writeln("\n<info>Available Languages:</info> " . implode(', ', $kata->getLanguagesAvailable()));

            if ($kata->getSolutionPlaceholder()) {
                $output->writeln("\n<info>Solution Template:</info>\n" . $kata->getSolutionPlaceholder());
            }

            if ($kata->getTests()) {
                $output->writeln("\n<info>Tests:</info>\n" . $kata->getTests());
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<e>' . $e->getMessage() . '</e>');

            return Command::FAILURE;
        }
    }

    private function sanitizeFolderName(string $name): string
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
}
