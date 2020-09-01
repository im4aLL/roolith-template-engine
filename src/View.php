<?php
namespace Roolith\Template\Engine;

use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Interfaces\ViewInterface;

class View implements ViewInterface
{
    protected $viewFolder;
    protected $fileExtension;
    protected $templateData;
    protected $baseUrl;

    public function __construct($viewFolder = null)
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
    public function setViewFolder($folderName)
    {
        $this->viewFolder = $folderName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compile($filename, $data = [])
    {
        if ($this->viewExists($filename)) {
            $this->setTemplateData($data);

            ob_start();
            extract($this->getTemplateData(), EXTR_SKIP);
            include($this->getFilePathByName($filename));
            $output = ob_get_contents();
            ob_end_clean();

            $this->resetTemplateData();
            return $output;
        } else {
            throw new Exception("$filename not exists!");
        }
    }

    /**
     * If view exists
     *
     * @param $filename
     * @return bool
     */
    private function viewExists($filename)
    {
        return file_exists($this->getFilePathByName($filename));
    }

    /**
     * Get file path
     *
     * @param $filename
     * @return string
     */
    private function getFilePathByName($filename)
    {
        return $this->viewFolder . '/' . $filename . '.' . $this->fileExtension;
    }

    /**
     * @return array
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * @param array $templateData
     * @return View
     */
    public function setTemplateData($templateData)
    {
        $this->templateData = $templateData;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetTemplateData()
    {
        $this->templateData = [];

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function addTemplateData($data)
    {
        $array = array_merge($this->getTemplateData(), $data);

        $this->setTemplateData($array);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inject($filename, $data = [])
    {
        if (count($data) > 0) {
            $this->addTemplateData($data);
        }

        if ($this->viewExists($filename)) {
            extract($this->getTemplateData(), EXTR_SKIP);
            include($this->getFilePathByName($filename));
        } else {
            throw new Exception("$filename not exists!");
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function url($urlSuffix)
    {
        $baseUrl = $this->getBaseUrl();
        $urlPrefix = $baseUrl ? $baseUrl : '/';

        return $urlPrefix . ltrim($urlSuffix, '/');
    }

    /**
     * @inheritDoc
     */
    public function escape($var)
    {
        extract($this->getTemplateData(), EXTR_SKIP);

        if (!isset(${$var})) {
            throw new Exception('$' .$var . ' not defined!');
        }

        return htmlspecialchars(${$var}, ENT_QUOTES);
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param mixed $baseUrl
     * @return View
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }
}
