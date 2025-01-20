<?php

namespace Dojo\Infrastructure\Adapters\Input\Console;

use Dojo\Application\Service\KataScraperService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapeKataCommand extends BaseKataCommand
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

            // If it's a relative path starting with ./ or ../, resolve it from /dojo
            if (preg_match('/^\.\.?\//', $katasDir)) {
                $katasDir = '/dojo/' . $katasDir;
            }
            // If it's not an absolute path, make it relative to /dojo
            elseif (!str_starts_with($katasDir, '/')) {
                $katasDir = '/dojo/' . $katasDir;
            }

            // Clean up the path
            $katasDir = rtrim($katasDir, '/');

            $kata = $this->kataScraperService->scrapeKata($input->getArgument('url'));

            $kyuLevel = $this->getKyuLevel($kata->getDifficulty());

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
            $this->createDirectoryWithPermissions($folderPath);

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

            // Save JSON file with proper permissions (hidden file)
            $jsonPath = sprintf('%s/.%s.json', $folderPath, $this->sanitizeFolderName($kata->getName()));
            $this->writeFileWithPermissions($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT));

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

            return self::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }
}
