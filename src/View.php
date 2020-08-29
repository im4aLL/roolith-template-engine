<?php
namespace Roolith;

use Roolith\Exceptions\Exception;
use Roolith\Interfaces\ViewInterface;

class View implements ViewInterface
{
    protected $cacheFolder;
    protected $viewFolder;
    protected $fileExtension;
    protected $templateData;

    public function __construct()
    {
        $this->fileExtension = 'php';
        $this->templateData = [];
    }

    /**
     * @inheritDoc
     */
    public function setCacheFolder($folderName)
    {
        $this->cacheFolder = $folderName;

        return $this;
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
    public function setViewFileExtension($extension)
    {
        $this->fileExtension = $extension;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compile($filename, $data = [])
    {
        $this->setTemplateData($data);

        if ($this->viewExists($filename)) {
            ob_start();
            extract($this->getTemplateData());
            include($this->getFilePathByName($filename));
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        } else {
            throw new Exception("$filename not exists!");
        }

        $this->resetTemplateData();
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
     * @inheritDoc
     */
    public function inject($filename)
    {
        if ($this->viewExists($filename)) {
            extract($this->getTemplateData());
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
        return '/' . $urlSuffix;
    }

    /**
     * @inheritDoc
     */
    public function escape($var)
    {
        return htmlspecialchars($var, ENT_QUOTES);
    }
}