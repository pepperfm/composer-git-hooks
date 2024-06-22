<?php

declare(strict_types=1);

namespace BrainMaestro\GitHooks;

use Exception;

class Hook
{
    public const LOCK_FILE = 'cghooks.lock';

    public const CONFIG_SECTIONS = ['custom-hooks', 'stop-on-failure'];

    /**
     * Return hook contents
     *
     * @param string $dir
     * @param array|string $contents
     * @param string $hook
     *
     * @return string
     */
    public static function getHookContents(string $dir, array|string $contents, string $hook): string
    {
        if (is_array($contents)) {
            $commandsSequence = self::stopHookOnFailure($dir, $hook);
            $separator = $commandsSequence ? ' && \\'.PHP_EOL : PHP_EOL;
            $contents = implode($separator, $contents);
        }

        return $contents;
    }

    /**
     * Get config section of the composer config file.
     *
     * @param string $dir dir where to look for composer.json
     * @param string $section config section to fetch in the composer.json
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function getConfig(string $dir, string $section): array
    {
        if (!in_array($section, self::CONFIG_SECTIONS)) {
            throw new Exception("Invalid config section [$section]. Available sections: ".implode(', ', self::CONFIG_SECTIONS).'.');
        }

        $json = self::getComposerJson($dir);

        return $json['extra']['hooks']['config'][$section] ?? [];
    }

    /**
     * Check if the given hook is a sequence of commands.
     *
     * @param string $dir
     * @param string $hook
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function stopHookOnFailure(string $dir, string $hook): bool
    {
        return in_array($hook, self::getConfig($dir, 'stop-on-failure'));
    }

    /**
     * Get scripts section of the composer config file.
     *
     * @param string $dir Directory where to look for composer.json
     *
     * @return array
     */
    public static function getValidHooks(string $dir): array
    {
        $json = self::getComposerJson($dir);

        $possibleHooks = $json['extra']['hooks'] ?? [];

        return array_filter($possibleHooks, static function ($hook) use ($dir) {
            return self::isDefaultHook($hook) || self::isCustomHook($dir, $hook);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Check if a hook is valid
     *
     * @param mixed $hook
     */
    private static function isDefaultHook(mixed $hook): bool
    {
        return array_key_exists($hook, self::getDefaultHooks());
    }

    /**
     * Get all default git hooks
     */
    private static function getDefaultHooks(): array
    {
        return array_flip([
            'applypatch-msg',
            'commit-msg',
            'post-applypatch',
            'post-checkout',
            'post-commit',
            'post-merge',
            'post-receive',
            'post-rewrite',
            'post-update',
            'pre-applypatch',
            'pre-auto-gc',
            'pre-commit',
            'pre-push',
            'pre-rebase',
            'pre-receive',
            'prepare-commit-msg',
            'push-to-checkout',
            'update',
        ]);
    }

    /**
     * Check if a hook is valid
     *
     * @param string $dir
     * @param string $hook
     *
     * @return bool
     */
    private static function isCustomHook(string $dir, string $hook): bool
    {
        return in_array($hook, self::getCustomHooks($dir));
    }

    /**
     * Get custom hooks from config `custom-hooks` section.
     *
     * @param string $dir
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function getCustomHooks(string $dir): array
    {
        $customHooks = self::getConfig($dir, 'custom-hooks');

        if (!$customHooks) {
            return [];
        }

        $configIndex = array_search('config', $customHooks);

        if ($configIndex !== false) {
            unset($customHooks[$configIndex]);
        }

        return array_unique($customHooks);
    }

    /**
     * Reads and decodes composer.json content.
     *
     * @param string $dir
     *
     * @return array
     */
    private static function getComposerJson(string $dir): array
    {
        $composerFile = "$dir/composer.json";

        if (!file_exists($composerFile)) {
            return [];
        }

        $contents = file_get_contents($composerFile);

        return (array) json_decode($contents, true);
    }
}
