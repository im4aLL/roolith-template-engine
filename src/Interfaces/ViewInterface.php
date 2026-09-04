<?php
namespace Roolith\Template\Engine\Interfaces;

use Roolith\Template\Engine\Exceptions\Exception;

interface ViewInterface
{
    /**
     * @param string $folderName
     * @return $this
     */
    public function setViewFolder(string $folderName): static;

    /**
     * @param string $filename
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function compile(string $filename, array $data = []): string;

    /**
     * @param string $filename
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function inject(string $filename, array $data = []): static;

    /**
     * @param string $urlSuffix
     * @return string
     */
    public function url(string $urlSuffix): string;

    /**
     * @param string $var
     * @return string
     * @throws Exception
     */
    public function escape(string $var): string;

    /**
     * Set base url
     *
     * @return $this
     */
    public function setBaseUrl(string|false $baseUrl): static;

    /**
     * Get base url
     *
     * @return false|string
     */
    public function getBaseUrl(): string|false;
}
