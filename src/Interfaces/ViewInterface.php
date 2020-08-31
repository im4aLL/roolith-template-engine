<?php
namespace Roolith\Template\Engine\Interfaces;

use Roolith\Template\Engine\Exceptions\Exception;

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
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function inject($filename, $data = []);

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