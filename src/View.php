<?php
namespace Roolith\Template\Engine;

use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Exceptions\InvalidArgumentException;
use Roolith\Template\Engine\Interfaces\ViewInterface;

/**
 * View
 *
 * Renders PHP templates with output buffering. Path resolution is
 * delegated to TemplatePathResolver, which is the source of truth
 * for view folder and file extension.
 */
class View implements ViewInterface
{
    protected ?string $viewFolder = null;
    protected array $templateData;
    protected string|false $baseUrl;
    protected TemplatePathResolver $pathResolver;

    /**
     * Create view
     *
     * Creates a view with an optional view folder and path resolver.
     * An explicit view folder argument overrides the resolver folder.
     *
     * @param string|null $viewFolder Base directory for views.
     * @param TemplatePathResolver|null $resolver Path resolver to use, created when null.
     */
    public function __construct(?string $viewFolder = null, ?TemplatePathResolver $resolver = null)
    {
        $this->baseUrl = false;
        $this->templateData = [];
        $this->pathResolver = $resolver ?? new TemplatePathResolver();

        $this->viewFolder = $this->pathResolver->getViewFolder();

        if ($viewFolder) {
            $this->setViewFolder($viewFolder);
        }
    }

    /**
     * Set view folder
     *
     * Validates the folder through the path resolver and mirrors it locally for backward compatibility.
     *
     * @param string $folderName Base directory for views.
     * @return static
     * @throws InvalidArgumentException for invalid view folder.
     */
    public function setViewFolder(string $folderName): static
    {
        $this->pathResolver->setViewFolder($folderName);
        $this->viewFolder = $this->pathResolver->getViewFolder();

        return $this;
    }

    /**
     * Get path resolver
     *
     * Returns the active resolver used for view name resolution.
     *
     * @return TemplatePathResolver Active path resolver.
     */
    public function getPathResolver(): TemplatePathResolver
    {
        return $this->pathResolver;
    }

    /**
     * Set path resolver
     *
     * Replaces the active resolver and mirrors its view folder locally for backward compatibility.
     *
     * @param TemplatePathResolver $resolver Path resolver to use.
     * @return static
     */
    public function setPathResolver(TemplatePathResolver $resolver): static
    {
        $this->pathResolver = $resolver;
        $this->viewFolder = $resolver->getViewFolder();

        return $this;
    }

    /**
     * Compile view
     *
     * Renders a template file with the given data and returns its output.
     *
     * @param string $filename View name to render.
     * @param array $data Template variables.
     * @return string Rendered template output.
     * @throws Exception for missing template.
     * @throws InvalidArgumentException for invalid view names.
     */
    public function compile(string $filename, array $data = []): string
    {
        if ($this->viewExists($filename)) {
            $this->setTemplateData($data);

            $level = ob_get_level();
            ob_start();
            try {
                $this->renderFile($this->getFilePathByName($filename), $this->getTemplateData());
                $output = ob_get_clean();
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
                $this->resetTemplateData();
            }

            return $output === false ? '' : $output;
        } else {
            $path = $this->getFilePathByName($filename);
            throw new Exception("View [$filename] does not exist. [resolved: $path]");
        }
    }

    /**
     * Render file in isolated scope
     *
     * Extracts template data with obscure locals so data keys like
     * `filename`, `data`, `level`, `output` or `path` remain readable
     * inside templates instead of colliding with renderer locals.
     *
     * @param string $__file Template file path.
     * @param array $__data Template variables.
     * @return void
     */
    private function renderFile(string $__file, array $__data): void
    {
        extract($__data, EXTR_OVERWRITE);
        include $__file;
    }

    /**
     * Check if view exists
     *
     * Resolves the view name and checks whether the template file exists.
     *
     * @param string $filename View name to check.
     * @return bool True when the resolved template file exists.
     */
    private function viewExists(string $filename): bool
    {
        return file_exists($this->getFilePathByName($filename));
    }

    /**
     * Get file path
     *
     * View names use `/` as the canonical directory separator
     * (e.g. `partials/header` resolves to `partials/header.php`).
     *
     * A `.` separator (e.g. `partials.header`) is still resolved the
     * same way for backward compatibility, but it is deprecated and
     * triggers `E_USER_DEPRECATED`. New code should use `/`.
     *
     * @param string $filename View name to resolve.
     * @return string Resolved template file path.
     * @throws InvalidArgumentException for invalid view names
     */
    private function getFilePathByName(string $filename): string
    {
        $resolved = $this->pathResolver->resolve($filename);
        $this->viewFolder = $this->pathResolver->getViewFolder();

        return $resolved;
    }

    /**
     * Get template data
     *
     * Returns the variables currently available to templates.
     *
     * @return array Current template variables.
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * Set template data
     *
     * Replaces the variables available to templates.
     *
     * @param array $templateData Template variables.
     * @return static
     */
    public function setTemplateData(array $templateData): static
    {
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * Reset template data
     *
     * Clears all variables available to templates.
     *
     * @return static
     */
    public function resetTemplateData(): static
    {
        $this->templateData = [];

        return $this;
    }

    /**
     * Add template data
     *
     * Merges the given variables into the current template data.
     *
     * @param array $data Additional template variables.
     * @return static
     */
    public function addTemplateData(array $data): static
    {
        $array = array_merge($this->getTemplateData(), $data);

        $this->setTemplateData($array);

        return $this;
    }

    /**
     * Inject view
     *
     * Renders a partial inside the current template without leaking
     * its data into sibling or parent templates.
     *
     * @param string $filename View name to inject.
     * @param array $data Additional variables for the partial.
     * @return static
     * @throws Exception for missing template.
     * @throws InvalidArgumentException for invalid view names.
     */
    public function inject(string $filename, array $data = []): static
    {
        $savedTemplateData = $this->getTemplateData();

        if (count($data) > 0) {
            $this->addTemplateData($data);
        }

        try {
            if ($this->viewExists($filename)) {
                $this->renderFile($this->getFilePathByName($filename), $this->getTemplateData());
            } else {
                $path = $this->getFilePathByName($filename);
                throw new Exception("View [$filename] does not exist. [resolved: $path]");
            }
        } finally {
            $this->setTemplateData($savedTemplateData);
        }

        return $this;
    }

    /**
     * Build URL
     *
     * Joins the base URL with the given suffix, normalizing slashes.
     *
     * @param string $urlSuffix Path to append to the base URL.
     * @return string Joined URL.
     */
    public function url(string $urlSuffix): string
    {
        $baseUrl = $this->getBaseUrl();

        if (!$baseUrl) {
            return '/' . ltrim($urlSuffix, '/');
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($urlSuffix, '/');
    }

    /**
     * Escape value
     *
     * Escapes a value for safe HTML output using UTF-8.
     * Only scalar values, null and stringable objects are supported.
     *
     * @param mixed $value Value to escape.
     * @return string Escaped HTML string.
     * @throws InvalidArgumentException for arrays or non-stringable objects.
     */
    public function e(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_array($value)) {
            throw new InvalidArgumentException('Escape expects scalar, null or stringable value, array given.');
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            throw new InvalidArgumentException('Escape expects stringable object, non-stringable object given.');
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape variable
     *
     * Escapes a named template variable for safe HTML output.
     *
     * @param string $var Name of the template variable.
     * @return string Escaped HTML string.
     * @throws Exception when the variable is not defined.
     */
    public function escape(string $var): string
    {
        $data = $this->getTemplateData();

        if (!array_key_exists($var, $data)) {
            throw new Exception('$' .$var . ' not defined!');
        }

        return $this->e($data[$var]);
    }

    /**
     * Get base URL
     *
     * Returns the base URL used for URL generation.
     *
     * @return string|false Base URL or false when not set.
     */
    public function getBaseUrl(): string|false
    {
        return $this->baseUrl;
    }

    /**
     * Set base URL
     *
     * Sets the base URL used for URL generation.
     *
     * @param string|false $baseUrl Base URL or false to disable it.
     * @return static
     */
    public function setBaseUrl(string|false $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }
}
