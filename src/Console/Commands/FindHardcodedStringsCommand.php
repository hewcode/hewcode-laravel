<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'hew:find-hardcoded-strings')]
class FindHardcodedStringsCommand extends Command
{
    protected $signature = 'hew:find-hardcoded-strings
                            {path : The path to scan for hardcoded strings}';

    protected $description = 'Find hardcoded strings in React/TypeScript files that should be localized';

    protected array $patterns = [];

    protected array $excludePatterns = [];

    protected array $userFacingIndicators = [];

    public function handle(): int
    {
        $this->setupPatterns();

        $scanPath = $this->getScanPath();

        if (! file_exists($scanPath)) {
            $this->components->error("Path not found: {$scanPath}");

            return self::FAILURE;
        }

        $this->components->info('🔍 Scanning for hardcoded strings in React/TypeScript files...');
        $this->newLine();

        $results = $this->scanDirectory($scanPath);

        // Group results by file
        $groupedResults = [];
        foreach ($results as $result) {
            $relativePath = str_replace(base_path().'/', '', $result['file']);
            if (! isset($groupedResults[$relativePath])) {
                $groupedResults[$relativePath] = [];
            }
            $groupedResults[$relativePath][] = $result;
        }

        // Sort by file path
        ksort($groupedResults);

        // Display results
        $totalCount = 0;
        foreach ($groupedResults as $file => $fileResults) {
            $this->components->twoColumnDetail("<fg=blue>📄 {$file}</>");

            // Sort by line number
            usort($fileResults, fn ($a, $b) => $a['line'] <=> $b['line']);

            foreach ($fileResults as $result) {
                $totalCount++;
                $typeLabel = match ($result['type']) {
                    'jsx_content' => 'JSX',
                    'jsx_attributes' => 'ATTR',
                    'jsx_expression_attributes' => 'EXPR',
                    'general_strings' => 'STR',
                    'string_assignments' => 'VAR',
                    default => 'TEXT',
                };

                $this->line("  <fg=gray>Line {$result['line']}:</> <fg=yellow>[{$typeLabel}]</> <fg=green>\"{$result['text']}\"</>");

                // Show context (truncated if too long)
                $context = $result['context'];
                if (strlen($context) > 100) {
                    $context = substr($context, 0, 100).'...';
                }
                $this->line('    <fg=gray>→ '.htmlspecialchars($context).'</>');
                $this->newLine();
            }
        }

        // Summary
        $this->line(str_repeat('─', 80));
        if ($totalCount > 0) {
            $this->components->warn("⚠️  Found {$totalCount} potential hardcoded string(s) across ".count($groupedResults).' file(s)');
            $this->components->info('Review these strings and consider using the __() translation function.');
        } else {
            $this->components->info('✅ No hardcoded strings found!');
        }

        $this->newLine();
        $this->components->info('Note: This command may produce false positives. Review each match manually.');

        return self::SUCCESS;
    }

    protected function getScanPath(): string
    {
        $path = $this->argument('path');

        // If path is absolute, use it as is
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Otherwise, treat as relative to base_path
        return base_path($path);
    }

    protected function setupPatterns(): void
    {
        // Patterns to match hardcoded strings
        $this->patterns = [
            // Pattern 1: JSX content between tags: >text content<
            // Matches things like: <div>Hello World</div> or <button>Click me</button>
            'jsx_content' => '/>\s*([A-Z][A-Za-z0-9\s\',\.!?\-:]+)\s*</u',

            // Pattern 2: String literals in JSX attributes that look like user-facing text
            // title="Some text" or placeholder="Enter text"
            'jsx_attributes' => '/(title|placeholder|aria-label|alt)\s*=\s*["\']([A-Z][A-Za-z0-9\s\',\.!?\-:]{3,})["\']/',

            // Pattern 3: String literals in JSX expression attributes (inside curly braces)
            // title={condition ? 'Some text' : 'Other text'} or title={'Some text'}
            'jsx_expression_attributes' => '/(title|placeholder|aria-label|alt)\s*=\s*\{[^}]*?[\'"]([A-Z][a-z]+(?:\s+[a-z]+)*)[\'"]/',

            // Pattern 4: Any quoted string that looks like user-facing text (2+ words, starts with capital)
            // Catches strings missed by other patterns
            'general_strings' => '/[\'"]([A-Z][a-z]+\s+[a-z]+(?:\s+[a-z]+)*)[\'"]/',

            // Pattern 5: String literals assigned to variables/constants that look like UI text
            // const message = "Some text"
            'string_assignments' => '/(?:const|let|var)\s+\w+\s*=\s*["\']([A-Z][A-Za-z0-9\s\',\.!?\-:]{10,})["\']/',
        ];

        // Patterns to exclude (false positives)
        $this->excludePatterns = [
            '/^[A-Z][a-z]*$/',  // Single capitalized words (likely component names)
            '/^[A-Z_]+$/',      // All caps (likely constants)
            '/^\d/',            // Starts with number
            '/^https?:\/\//',   // URLs
            '/^\//',            // Paths
            '/\{.*\}/',         // Contains interpolation
            '/className|onClick|onChange|onSubmit|onChange|onBlur|onFocus|type|name|id|key|ref/', // Common React props
            '/__\(/',           // Already using translation function
            '/^[a-z\-]+$/',     // kebab-case (likely CSS classes)
            '/^\s*$/',          // Empty or whitespace only
        ];

        // Common English words that indicate user-facing text
        $this->userFacingIndicators = ['the', 'and', 'you', 'your', 'are', 'is', 'to', 'for', 'or', 'in', 'on'];
    }

    protected function shouldExclude(string $text): bool
    {
        $text = trim($text);

        if (strlen($text) < 3) {
            return true;
        }

        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    protected function isLikelyUserFacingText(string $text): bool
    {
        $lowerText = strtolower($text);
        foreach ($this->userFacingIndicators as $indicator) {
            if (str_contains($lowerText, ' '.$indicator.' ') ||
                str_starts_with($lowerText, $indicator.' ')) {
                return true;
            }
        }

        // If it contains multiple words and starts with capital letter
        return str_word_count($text) >= 2;
    }

    protected function scanDirectory(string $dir): array
    {
        $results = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jsx|tsx)$/', $file->getFilename())) {
                $filePath = $file->getPathname();
                $content = file_get_contents($filePath);
                $lines = explode("\n", $content);

                foreach ($this->patterns as $patternName => $pattern) {
                    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

                    foreach ($matches[1] as $index => $match) {
                        $text = $match[0];
                        $offset = $match[1];

                        // For JSX attributes patterns, the text is in the second capture group
                        if (($patternName === 'jsx_attributes' || $patternName === 'jsx_expression_attributes') && isset($matches[2][$index])) {
                            $text = $matches[2][$index][0];
                        }

                        if ($this->shouldExclude($text)) {
                            continue;
                        }

                        if (! $this->isLikelyUserFacingText($text)) {
                            continue;
                        }

                        // Find line number
                        $lineNumber = 1;
                        $currentOffset = 0;
                        foreach ($lines as $lineNum => $line) {
                            $currentOffset += strlen($line) + 1; // +1 for newline
                            if ($currentOffset > $offset) {
                                $lineNumber = $lineNum + 1;
                                break;
                            }
                        }

                        $results[] = [
                            'file' => $filePath,
                            'line' => $lineNumber,
                            'text' => $text,
                            'type' => $patternName,
                            'context' => trim($lines[$lineNumber - 1] ?? ''),
                        ];
                    }
                }
            }
        }

        return $results;
    }
}
