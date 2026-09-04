<?php
namespace Roolith\Template\Engine\Interfaces;

use Roolith\Template\Engine\Exceptions\Exception;

/**
 * Template context interface
 *
 * Narrow type seen as `$this` inside templates.
 * Exposes only helpers needed while rendering.
 * App setup methods like `compile`, `setViewFolder`
 * and `setBaseUrl` stay on `ViewInterface`.
 */
interface TemplateContextInterface
{
    /**
     * Inject view
     *
     * Renders a partial inside the current template.
     *
     * @param string $filename View name to inject.
     * @param array $data Additional variables for the partial.
     * @return static
     * @throws Exception for missing template.
     */
    public function inject(string $filename, array $data = []): static;

    /**
     * Build URL
     *
     * Joins the base URL with the given suffix.
     *
     * @param string $urlSuffix Path to append to the base URL.
     * @return string Joined URL.
     */
    public function url(string $urlSuffix): string;

    /**
     * Escape value
     *
     * Escapes a value for safe HTML output.
     *
     * @param mixed $value Value to escape.
     * @return string Escaped HTML string.
     */
    public function e(mixed $value): string;

    /**
     * Escape variable
     *
     * Escapes a named template variable for safe HTML output.
     *
     * @param string $var Name of the template variable.
     * @return string Escaped HTML string.
     * @throws Exception when the variable is not defined.
     */
    public function escape(string $var): string;
}
