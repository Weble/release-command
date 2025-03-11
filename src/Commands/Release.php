<?php

namespace Weble\ReleaseCommand\Commands;

use Composer\Semver\VersionParser;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use UnexpectedValueException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;

class Release extends Command
{
    protected $signature = 'release {--release-version=} {--version-type=} {--local}';

    protected $description = 'Release a new version';

    public function handle(): int
    {
        $gitConfigPath = $this->gitConfigPath();
        if ($gitConfigPath === null) {
            error('Git is not setup in this project.');

            return self::FAILURE;
        }

        $contents = file_get_contents($gitConfigPath);
        if (! $this->isGitFlowSetup($contents)) {
            error('Git Flow not found');

            return self::FAILURE;
        }

        $branches = $this->extractGitFlowBranchConfiguration($contents);
        $develop = $branches['develop'] ?? 'develop';
        $main = $branches['master'] ?? 'main';
        $git = config('release-command.git_bin', 'git');

        // We need to be in develop first
        $currentBranch = str(Process::run("$git rev-parse --abbrev-ref HEAD")->output())->trim()->toString();
        if ($currentBranch !== $develop) {

            if ($this->confirm("Your not in the $develop branch. Would you like to checkout the $develop branch first?")) {
                $process = Process::run("$git checkout $develop");
                if ($process->failed()) {
                    error($process->errorOutput());

                    return self::FAILURE;
                }
            } else {
                error("You need to be in the $develop branch to release using Git Flow");

                return self::FAILURE;
            }
        }

        $bumpedVersion = $this->option('release-version');

        if ($bumpedVersion === null) {
            $this->task('Git and Git Flow setup correctly. Checking current version...');

            $currentVersion = str(Process::run("$git describe --tags --abbrev=0")->output())->trim()->toString();
            try {
                $currentVersion = (new VersionParser)->normalize($currentVersion);
            } catch (UnexpectedValueException) {
                $currentVersion = '0.0.0';
            }

            $versionPieces = str($currentVersion)->explode('.')->take(3);
            $currentVersion = $versionPieces->join('.');

            $versionType = $this->option('version-type') ?? select(
                label: 'Bump version. Current Version is '.$currentVersion,
                options: [
                    'patch' => 'Patch ('.$this->bumpVersion($versionPieces, 'patch').')',
                    'minor' => 'Minor ('.$this->bumpVersion($versionPieces, 'minor').')',
                    'major' => 'Major ('.$this->bumpVersion($versionPieces, 'major').')',
                ],
                default: config('release-command.default_version_bump', 'patch')
            );

            $bumpedVersion = $this->bumpVersion($versionPieces, $versionType);
        }

        $this->task('Bumped Version: '.$bumpedVersion);

        if (! $this->confirm(sprintf('Confirm releasing of version %s?', $bumpedVersion), true)) {
            error('Process interrupted');

            return self::FAILURE;
        }

        $this->task('Creating release: '.$bumpedVersion);

        $process = Process::run("$git flow release start $bumpedVersion");
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $process = Process::run(sprintf($git.' flow release finish  -m "v%s" \'%s\'', $bumpedVersion, $bumpedVersion));
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $this->task('Release Created: '.$bumpedVersion);

        $process = Process::run("$git checkout $develop");
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $process = Process::run("$git merge $main");
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $this->task("Switched back to $develop. Release completed.");

        $origin = config('release-command.git_remote_name', 'origin');

        $push = ! $this->option('local') || select(
            label: "Push to $origin?",
            options: [
                1 => 'Yes',
                0 => 'No',
            ],
            default: config('release-command.push_to_origin', true) ? 1 : 0,
        );

        if (! $push) {
            $this->task('Release created. Remember to push to '.$origin);

            return self::SUCCESS;
        }

        $this->task("Pushing to $origin...");

        $process = Process::run("$git push $origin $develop");
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $process = Process::run("$git push $origin $main");
        if ($process->failed()) {
            error($process->errorOutput());

            return self::FAILURE;
        }

        $this->task('Pushed!');

        return self::SUCCESS;
    }

    private function bumpVersion(Collection $originalVersionPieces, string $versionType): string
    {
        $versionPieces = clone $originalVersionPieces;

        $index = match ($versionType) {
            'major' => 0,
            'minor' => 1,
            default => 2,
        };

        // Bump correct version number piece
        $versionPieces[$index] = str($versionPieces[$index])->toInteger() + 1;

        // Reset to 0 next pieces
        for ($i = $index + 1; $i < $versionPieces->count(); $i++) {
            $versionPieces[$i] = 0;
        }

        return $versionPieces->join('.');
    }

    /**
     * return ['develop' => 'develop', 'master' => 'main']
     */
    private function extractGitFlowBranchConfiguration(string $content): array
    {
        // Extract the [gitflow "branch"] section
        if (! preg_match('/\[gitflow "branch"](.*?)\n\[/', $content, $sectionMatch)) {
            return [];
        }

        $branchSection = $sectionMatch[1];

        // Extract develop and master values
        preg_match_all('/\s*(develop|master)\s*=\s*(\S+)/', $branchSection, $matches, PREG_SET_ORDER);

        $results = [];
        foreach ($matches as $match) {
            $results[$match[1]] = $match[2];
        }

        return $results;
    }

    private function isGitFlowSetup(string $contents): bool
    {
        return stripos($contents, '[gitflow') !== false;
    }

    private function gitConfigPath(): ?string
    {
        $candidates = [
            getcwd().'/.git/config',
            base_path().'/.git/config',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
