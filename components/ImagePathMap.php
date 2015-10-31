<?php

namespace voskobovich\image\dynamic\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\Cache;


/**
 * Class ImagePathMap
 * @package voskobovich\image\dynamic\components
 */
class ImagePathMap extends Component
{
    /**
     * Cache component
     * @var string
     */
    public $cache;

    /**
     * Cache component
     * @var \yii\caching\Cache
     */
    private $_cache;

    /**
     * Prefix
     * @var string
     */
    public $prefix = 'tmp.img.';

    /**
     * Init
     */
    public function init()
    {
        parent::init();

        $this->_cache = Yii::$app->cache;
        if ($this->cache) {
            $this->_cache = Yii::$app->get($this->cache);
        }

        if (!$this->_cache instanceof Cache) {
            throw new InvalidConfigException('Param "cache" must be contain name component which implements "\yii\caching\Cache"');
        }
    }

    /**
     * Add new row
     * @param $name
     * @param $path
     */
    public function add($name, $path)
    {
        $this->_cache->add($this->prefix . $name, $path);
    }

    /**
     * Set row value
     * @param $name
     * @param $path
     */
    public function set($name, $path)
    {
        $this->_cache->add($this->prefix . $name, $path);
    }

    /**
     * Set row value
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->_cache->get($this->prefix . $name);
    }

    /**
     * Check exist name
     * @param $name
     * @return mixed
     */
    public function exists($name)
    {
        return $this->_cache->exists($this->prefix . $name);
    }

    /**
     * Delete row by name
     * @param $name
     * @return mixed
     */
    public function delete($name)
    {
        return $this->_cache->delete($this->prefix . $name);
    }
}