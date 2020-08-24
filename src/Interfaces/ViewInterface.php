<?php
namespace Roolith\Interfaces;

use Roolith\Exceptions\Exception;

interface ViewInterface
{
    /**
     * @param $folderName string
     * @return $this
     */
    public function setCacheFolder($folderName);

    /**
     * @param $folderName string
     * @return $this
     */
    public function setViewFolder($folderName);

    /**
     * @param $extension string
     * @return $this
     */
    public function setViewFileExtension($extension);

    /**
     * @param $filename string
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function compile($filename, $data = []);
}