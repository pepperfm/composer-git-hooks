<?php

declare(strict_types=1);

namespace BrainMaestro\GitHooks\Tests;

use BrainMaestro\GitHooks\Commands\HookCommand;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

class HookCommandTest extends TestCase
{
    #[Test]
    public function it_tests_hooks_that_exist(): void
    {
        foreach (self::$hooks as $hook => $script) {
            $command = new HookCommand($hook, $script, '.');
            $commandTester = new CommandTester($command);

            $commandTester->execute([]);
            $this->assertStringContainsString(str_replace('echo ', '', $script), $commandTester->getDisplay());
        }
    }

    #[Test]
    public function it_terminates_if_previous_hook_fails(): void
    {
        $hook = [
            'pre-commit' => [
                'echo execution-error;exit 1',
                'echo before-commit',
            ],
        ];

        $command = new HookCommand('pre-commit', $hook['pre-commit'], '.');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $this->assertStringContainsString('execution-error', $commandTester->getDisplay());
        $this->assertStringNotContainsString('before-commit', $commandTester->getDisplay());
    }
}
