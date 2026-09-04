<?php
namespace Roolith\Template\Engine;

use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Interfaces\ViewInterface;

class View implements ViewInterface
{
    protected ?string $viewFolder = null;
    protected string $fileExtension;
    protected array $templateData;
    protected string|false $baseUrl;

    public function __construct(?string $viewFolder = null)
    {
        $this->fileExtension = 'php';
        $this->baseUrl = false;
        $this->templateData = [];

        if ($viewFolder) {
            $this->setViewFolder($viewFolder);
        }
    }

    /**
     * @inheritDoc
     */
    public function setViewFolder(string $folderName): static
    {
        $this->viewFolder = $folderName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compile(string $filename, array $data = []): string
    {
        if ($this->viewExists($filename)) {
            $this->setTemplateData($data);

            $level = ob_get_level();
            ob_start();
            try {
                extract($this->getTemplateData(), EXTR_SKIP);
                include($this->getFilePathByName($filename));
                $output = ob_get_clean();
            } finally {
                if (ob_get_level() > $level) {
                    ob_end_clean();
                }
                $this->resetTemplateData();
            }

            return $output === false ? '' : $output;
        } else {
            $path = $this->getFilePathByName($filename);
            throw new Exception("$filename not exists! [resolved: $path]");
        }
    }

    /**
     * If view exists
     *
     * @param $filename
     * @return bool
     */
    private function viewExists(string $filename): bool
    {
        return file_exists($this->getFilePathByName($filename));
    }

    /**
     * Get file path
     *
     * @param string $filename
     * @return string
     * @throws \InvalidArgumentException for invalid view names
     */
    private function getFilePathByName(string $filename): string
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

        $isFilenameContainsDot = strpos($filename, '.') !== false;

        if ($this->viewFolder === null || $this->viewFolder === '') {
            throw new \InvalidArgumentException('Invalid view folder: must be a non-empty string.');
        }

        if (!$isFilenameContainsDot) {
            $candidate = $this->viewFolder . '/' . $filename . '.' . $this->fileExtension;
        } else {
            $updatedFilename = str_replace('.', '/', $filename);

            $candidate = $this->viewFolder . '/' . $updatedFilename . '.' . $this->fileExtension;
        }

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

    /**
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @param array $templateData
     * @return View
     */
    public function setTemplateData(array $templateData): static
    {
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetTemplateData(): static
    {
        $this->templateData = [];

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addTemplateData(array $data): static
    {
        $array = array_merge($this->getTemplateData(), $data);

        $this->setTemplateData($array);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inject(string $filename, array $data = []): static
    {
        if (count($data) > 0) {
            $this->addTemplateData($data);
        }

        if ($this->viewExists($filename)) {
            extract($this->getTemplateData(), EXTR_SKIP);
            include($this->getFilePathByName($filename));
        } else {
            $path = $this->getFilePathByName($filename);
            throw new Exception("$filename not exists! [resolved: $path]");
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function url(string $urlSuffix): string
    {
        $baseUrl = $this->getBaseUrl();
        $urlPrefix = $baseUrl ? $baseUrl : '/';

        return $urlPrefix . ltrim($urlSuffix, '/');
    }

    /**
     * @inheritDoc
     */
    public function escape(string $var): string
    {
        extract($this->getTemplateData(), EXTR_SKIP);

        if (!isset(${$var})) {
            throw new Exception('$' .$var . ' not defined!');
        }

        return htmlspecialchars((string) ${$var}, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(): string|false
    {
        return $this->baseUrl;
    }

    /**
     * @inheritDoc
     */
    public function setBaseUrl(string|false $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }
}
