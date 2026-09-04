<?php
namespace Roolith\Template\Engine\Interfaces;

use Roolith\Template\Engine\Exceptions\Exception;

/**
 * View interface
 *
 * App-level contract for rendering PHP templates.
 * Extends TemplateContextInterface so every View
 * can be used as `$this` inside templates.
 * The concrete View class exposes additional seams for testing and
 * composition (getPathResolver, setPathResolver, getTemplateData,
 * setTemplateData, resetTemplateData, addTemplateData) which are
 * intentionally not part of this minimal contract to keep it narrow.
 */
interface ViewInterface extends TemplateContextInterface
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
