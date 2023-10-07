<?php

declare(strict_types=1);

namespace T3DOCS\ExceptionCodes\Git;

use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\IRunner;
use CzProject\GitPhp\RunnerResult;

final class CustomGitRepository extends GitRepository
{
    protected string|null $userName = null;
    protected string|null $userEmail = null;

    public function __construct(
        $repository,
        IRunner|null $runner = null,
        private string|null $defaultUserName = null,
        private string|null $defaultUserEmail = null,
    ) {
        parent::__construct($repository, $runner);
    }

    /**
     * @return array{GIT_USER_NAME?: string, GIT_USER_EMAIL?: string}
     */
    protected function getGitEnvironmentVariables(): array
    {
        return [
            'GIT_AUTHOR_NAME' => $this->userName(),
            'GIT_AUTHOR_EMAIL' => $this->userEmail(),
            'GIT_COMMITTER_NAME' => $this->userName(),
            'GIT_COMMITTER_EMAIL' => $this->userEmail(),
            'EMAIL' => $this->userEmail(),
        ];
    }

    public function userName(): string|null
    {
        return $this->userEmail ??= $this->detectUserEmail();
    }

    public function userEmail(): string|null
    {
        return $this->userName ??= $this->detectUserEmail();
    }

    /**
     * Detects the git userName based on local, global and environment variable with fallback to default.
     * @return string|null
     */
    protected function detectUserName(): string|null
    {
        try {
            $userEmail = trim($this->runWithoutEnv('config', ['--local', '--get' => 'user.name'])->getOutputAsString());
            if ($userEmail !== '') {
                return $userEmail;
            }
        } catch(GitException) {}

        try {
            $userEmail = trim($this->runWithoutEnv('config', ['--global', '--get' => 'user.name'])->getOutputAsString());
            if ($userEmail !== '') {
                return $userEmail;
            }
        } catch(GitException) {}

        $userEmail = trim((string)getenv('GIT_USER_NAME') ?? '');
        if ($userEmail !== '') {
            return $userEmail;
        }

        return $this->defaultUserName ?? 'unknown';
    }

    /**
     * Detects the git userEmail based on local, global and environment variable with fallback to default.
     *
     * @return string|null
     */
    protected function detectUserEmail(): string|null
    {
        try {
            $userEmail = trim($this->runWithoutEnv('config', ['--local', '--get' => 'user.email'])->getOutputAsString());
            if ($userEmail !== '') {
                return $userEmail;
            }
        } catch(GitException) {}

        try {
            $userEmail = trim($this->runWithoutEnv('config', ['--global', '--get' => 'user.email'])->getOutputAsString());
            if ($userEmail !== '') {
                return $userEmail;
            }
        } catch(GitException) {}

        $userEmail = trim((string)getenv('GIT_USER_EMAIL') ?? '');
        if ($userEmail !== '') {
            return $userEmail;
        }

        return $this->defaultUserEmail ?? 'user@example.tld';
    }

    /**
     * Runs command.
     * @param  mixed ...$args
     * @return RunnerResult
     * @throws GitException
     */
    protected function run(...$args)
    {
        $result = $this->runner->run($this->repository, $args, $this->getGitEnvironmentVariables());

        if (!$result->isOk()) {
            echo "Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()})." . PHP_EOL;
            echo 'CODE: ' . $result->getExitCode() . PHP_EOL;;
            echo 'OUTPUT: ' . PHP_EOL . implode(PHP_EOL, $result->getOutput());
            echo 'ERROR: ' . PHP_EOL . implode(PHP_EOL, $result->getErrorOutput());
            throw new GitException("Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()}).", $result->getExitCode(), NULL, $result);
        }

        return $result;
    }

    /**
     * Runs command.
     * @param  mixed ...$args
     * @return RunnerResult
     * @throws GitException
     */
    protected function runWithoutEnv(...$args)
    {
        $result = $this->runner->run($this->repository, $args);

        if (!$result->isOk()) {
            throw new GitException("Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()}).", $result->getExitCode(), NULL, $result);
        }

        return $result;
    }
}