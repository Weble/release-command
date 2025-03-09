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

    public function handle(): void
    {
        if (! $this->isGitSetup()) {
            error('Git is not setup in this project.');

            return;
        }

        if (! $this->isGitFlowSetup()) {
            error('Git Flow not found');

            return;
        }

        Process::run('git checkout develop');

        $bumpedVersion = $this->option('release-version');

        if ($bumpedVersion === null) {
            $this->task('Git and Git Flow setup correctly. Checking current version...');

            $currentVersion = str(Process::run('git describe --tags --abbrev=0')->output())->trim()->toString();
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
                default: 'patch'
            );

            $bumpedVersion = $this->bumpVersion($versionPieces, $versionType);
        }

        $this->task('Bumped Version: '.$bumpedVersion);

        if (! $this->confirm(sprintf('Confirm releasing of version %s?', $bumpedVersion), true)) {
            error('Process interrupted');

            return;
        }

        $this->task('Creating release: '.$bumpedVersion);

        $process = Process::run('git flow release start '.$bumpedVersion);
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $process = Process::run(sprintf('git flow release finish  -m "v%s" \'%s\'', $bumpedVersion, $bumpedVersion));
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $this->task('Release Created: '.$bumpedVersion);

        $process = Process::run('git checkout develop');
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $process = Process::run('git merge main');
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $this->task('Switched back to develop. Release completed.');

        $push = ! $this->option('local') || select(
                label: 'Push to origin?',
                options: [
                    1 => 'Yes',
                    0 => 'No',
                ],
                default: 1,
            );

        if (! $push) {
            $this->task('Release created. Remember to push to origin');

            return;
        }

        $this->task('Pushing to origin...');

        $process = Process::run('git push origin develop');
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $process = Process::run('git push origin main');
        if ($process->failed()) {
            error($process->errorOutput());

            return;
        }

        $this->task('Pushed');
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

    private function isGitFlowSetup(): bool
    {
        return stripos(file_get_contents(getcwd().'/.git/config'), '[gitflow') !== false;
    }

    private function isGitSetup(): bool
    {
        return file_exists(getcwd().'/.git/config');
    }
}
