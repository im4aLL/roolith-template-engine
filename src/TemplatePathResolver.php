<?php
namespace Roolith\Template\Engine;

/**
 * Template path resolver
 *
 * Validates view names and resolves them to file paths inside the view folder.
 * View names use `/` as the canonical directory separator.
 */
class TemplatePathResolver
{
    protected ?string $viewFolder = null;
    protected string $fileExtension;

    /**
     * Create path resolver
     *
     * Creates a resolver with an optional view folder and file extension.
     *
     * @param string|null $viewFolder Base directory for views.
     * @param string $fileExtension Template file extension without leading dot.
     */
    public function __construct(?string $viewFolder = null, string $fileExtension = 'php')
    {
        $this->fileExtension = $fileExtension;

        if ($viewFolder !== null) {
            $this->setViewFolder($viewFolder);
        }
    }

    /**
     * Set view folder
     *
     * Validates that the folder exists and stores its normalized path.
     *
     * @param string $folderName Base directory for views.
     * @return static
     * @throws \InvalidArgumentException for invalid view folder.
     */
    public function setViewFolder(string $folderName): static
    {
        if ($folderName === '' || !is_dir($folderName)) {
            throw new \InvalidArgumentException("Invalid view folder [$folderName]: directory does not exist.");
        }

        $this->viewFolder = rtrim($folderName, '/\\');

        return $this;
    }

    /**
     * Get view folder
     *
     * Returns the normalized base directory for views.
     *
     * @return string|null Normalized view folder or null when not set.
     */
    public function getViewFolder(): ?string
    {
        return $this->viewFolder;
    }

    /**
     * Set file extension
     *
     * Sets the template file extension used when resolving view names.
     *
     * @param string $fileExtension Template file extension with or without leading dot.
     * @return static
     * @throws \InvalidArgumentException for empty extension.
     */
    public function setFileExtension(string $fileExtension): static
    {
        if ($fileExtension === '') {
            throw new \InvalidArgumentException('Invalid file extension: must be a non-empty string.');
        }

        $this->fileExtension = ltrim($fileExtension, '.');

        return $this;
    }

    /**
     * Get file extension
     *
     * Returns the template file extension used when resolving view names.
     *
     * @return string Template file extension without leading dot.
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
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
     * @throws \InvalidArgumentException for invalid view names
     */
    public function resolve(string $filename): string
    {
        if ($filename === '') {
            throw new \InvalidArgumentException('Invalid view name: must be a non-empty string.');
        }

        if (strpos($filename, ':') !== false) {
            throw new \InvalidArgumentException("Invalid view name [$filename]: stream wrappers are not allowed.");
        }

        if ($filename[0] === '/' || $filename[0] === '\\') {
            throw new \InvalidArgumentException("Invalid view name [$filename]: absolute paths are not allowed.");
        }

        if (strpos($filename, '\\') !== false) {
            throw new \InvalidArgumentException("Invalid view name [$filename]: backslash separators are not allowed.");
        }

        if (strpos($filename, '..') !== false) {
            throw new \InvalidArgumentException("Invalid view name [$filename]: parent directory segments are not allowed.");
        }

        if (!preg_match('#^[A-Za-z0-9_-]+(?:[./][A-Za-z0-9_-]+)*$#', $filename)) {
            throw new \InvalidArgumentException("Invalid view name [$filename]: allowed characters are letters, numbers, _, -, / and .");
        }

        // Canonical separator is `/`. A `.` is treated as an alias for `/`
        // so `partials.header` and `partials/header` resolve to the same file.
        $isFilenameContainsDot = strpos($filename, '.') !== false;

        if ($this->viewFolder === null || $this->viewFolder === '') {
            throw new \InvalidArgumentException('Invalid view folder: must be a non-empty string.');
        }

        if (!$isFilenameContainsDot) {
            $candidate = $this->viewFolder . '/' . $filename . '.' . $this->fileExtension;
        } else {
            trigger_error(
                "View name [$filename]: dot separator is deprecated, use '/' instead (e.g. '" . str_replace('.', '/', $filename) . "').",
                E_USER_DEPRECATED
            );
            $updatedFilename = str_replace('.', '/', $filename);

            $candidate = $this->viewFolder . '/' . $updatedFilename . '.' . $this->fileExtension;
        }

        $candidate = preg_replace('#/+#', '/', $candidate);

        $baseReal = realpath($this->viewFolder);

        if ($baseReal !== false) {
            $resolved = realpath($candidate);

            if ($resolved !== false) {
                $prefix = rtrim($baseReal, '/\\') . DIRECTORY_SEPARATOR;

                if ($resolved !== $baseReal && strpos($resolved, $prefix) !== 0) {
                    throw new \InvalidArgumentException("Invalid view name [$filename]: resolved path escapes view folder.");
                }

                return $resolved;
            }
        }

        return $candidate;
    }
}
