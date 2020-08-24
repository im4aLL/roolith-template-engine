<?php
namespace Roolith;

use Roolith\Exceptions\Exception;
use Roolith\Interfaces\ViewInterface;

class View implements ViewInterface
{
    protected $cacheFolder;
    protected $viewFolder;
    protected $fileExtension;

    public function __construct()
    {
        $this->fileExtension = 'html';
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
        if ($this->viewExists($filename)) {
            $lineArray = $this->readTemplateFile($filename);
            $parsedTemplateLineArray = $this->applyDefaultValue($lineArray);

            dd($parsedTemplateLineArray);

//            $tempTemplateFile = $this->saveFile($parsedTemplateLineArray);
//            $templateString = $this->parseTempTemplate($tempTemplateFile, $data);

//            return $templateString;
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

    private function getFilePathByName($filename)
    {
        return $this->viewFolder . '/' . $filename . '.' . $this->fileExtension;
    }

    private function readTemplateFile($filename)
    {
        $filepath = $this->getFilePathByName($filename);
        $fp = fopen($filepath, 'r');

        $content = fread($fp, filesize($filepath));
        $lines = explode("\n", $content);
        fclose($fp);

        return $lines;
    }

    private function applyDefaultValue($lineArray)
    {
        $result = [];

        foreach ($lineArray as $line) {
            $patternArray = [
                '/{{ /',
                '/ }}/',
            ];

            $replacementArray = [
                '<?php ',
                '; ?>',
            ];

            $result[] = preg_replace($patternArray, $replacementArray, $line);
        }

        return $result;
    }


}