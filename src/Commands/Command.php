<?php

declare(strict_types=1);

namespace BrainMaestro\GitHooks\Commands;

use BrainMaestro\GitHooks\Hook;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    protected string $dir;

    protected $composerDir;

    protected array $hooks;

    protected mixed $gitDir;

    protected mixed $lockDir;

    protected mixed $global;

    protected string $lockFile;

    private OutputInterface $output;

    abstract protected function init(InputInterface $input);

    abstract protected function command();

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->gitDir = $input->getOption('git-dir') ?: git_dir();
        $this->lockDir = $input->getOption('lock-dir');
        $this->global = $input->getOption('global');
        $this->dir = trim(
            $this->global && $this->gitDir === git_dir()
                ? dirname(global_hook_dir())
                : $this->gitDir
        );
        if ($this->global && empty($this->dir)) {
            $this->global_dir_fallback();
        }
        if ($this->gitDir === false) {
            $output->writeln('Git is not initialized. Skip setting hooks...');

            return SymfonyCommand::SUCCESS;
        }
        $this->lockFile = ($this->lockDir !== null ? ($this->lockDir . '/') : '') . Hook::LOCK_FILE;

        $dir = $this->global ? $this->dir : getcwd();

        $this->hooks = Hook::getValidHooks($dir);

        $this->init($input);
        $this->command();

        return SymfonyCommand::SUCCESS;
    }

    protected function global_dir_fallback(): void
    {
    }

    protected function info($info): void
    {
        $info = str_replace(['[', ']'], ['<info>', '</info>'], $info);

        $this->output->writeln($info);
    }

    protected function debug($debug): void
    {
        $debug = str_replace(['[', ']'], ['<comment>', '</comment>'], $debug);

        $this->output->writeln($debug, OutputInterface::VERBOSITY_VERBOSE);
    }

    protected function error($error): void
    {
        $this->output->writeln("<fg=red>$error</>");
    }
}
