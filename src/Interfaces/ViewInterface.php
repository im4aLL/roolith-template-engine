<?php
namespace Roolith\Interfaces;

use Roolith\Exceptions\Exception;

interface ViewInterface
{
    /**
     * @param $folderName string
     * @return $this
     */
    public function setViewFolder($folderName);

    /**
     * @param $filename string
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function compile($filename, $data = []);

    /**
     * @param $filename
     * @return $this
     * @throws Exception
     */
    public function inject($filename);

    /**
     * @param $urlSuffix
     * @return string
     */
    public function url($urlSuffix);

    /**
     * @param $var
     * @return string
     * @throws Exception
     */
    public function escape($var);
}