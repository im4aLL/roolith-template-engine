<?php
namespace Roolith\Template\Engine\Interfaces;

use Roolith\Template\Engine\Exceptions\Exception;

/**
 * View interface
 *
 * Defines the contract for rendering PHP templates.
 */
interface ViewInterface
{
    /**
     * Set view folder
     *
     * Sets the base directory for views.
     *
     * @param string $folderName Base directory for views.
     * @return static
     */
    public function setViewFolder(string $folderName): static;

    /**
     * Compile view
     *
     * Renders a template file with the given data and returns its output.
     *
     * @param string $filename View name to render.
     * @param array $data Template variables.
     * @return string Rendered template output.
     * @throws Exception for missing template.
     */
    public function compile(string $filename, array $data = []): string;

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

    /**
     * Set base URL
     *
     * Sets the base URL used for URL generation.
     *
     * @param string|false $baseUrl Base URL or false to disable it.
     * @return static
     */
    public function setBaseUrl(string|false $baseUrl): static;

    /**
     * Get base URL
     *
     * Returns the base URL used for URL generation.
     *
     * @return string|false Base URL or false when not set.
     */
    public function getBaseUrl(): string|false;
}
