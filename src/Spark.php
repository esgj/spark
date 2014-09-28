<?php

/**
* Spark - Dependency Injection Container (DIC) for PHP.
*
* @package Spark
* @author  Espen Gjendem <espen@onesoft.no>
* @license http://opensource.org/licenses/mit
* @version 1.0.0
*/

namespace Spark;

class Spark
{
    /**
    * @var array Registry
    */
    private $_r = [];

    /**
    * Binds object into registry.
    *
    * @param string $c Class
    * @param mixed  $d Object|Closure
    *
    * @return boolean
    */
    public function bind($c, $d)
    {
        if ($this->_iB($c)) throw new SparkException("Object of type '{$c}' already exists");
        if (is_callable($d)) $d = call_user_func($d);
        if (!is_object($d)) throw new SparkException("Only objects can be bound into registry");
        $this->_r[$c] = $d;

        return true;
    }

    /**
    * Creates new object or returns existing object.
    *
    * @param string  $c  Class
    * @param string  $m  Method
    * @param boolean $rD Return dependencies
    *
    * @return mixed Object|Array
    */
    public function make($c, $m = null, $rD = false)
    {
        $m = ($m) ? $m : '__construct';
        $objects = [];

        if ($this->_iB($c)) return $this->_r[$c];

        try {
            $cR = new \ReflectionClass($c);
        } catch (\ReflectionException $e) {
            throw new SparkException($e->getMessage());
        }

        if ($cR->hasMethod($m)) {
            $dC = $cR->getMethod($m)->getNumberOfRequiredParameters();

            if ($dC > 0) {
                foreach ($this->_gD($c, $m, $dC) as $cN) $objects[] = $this->make($cN);

                return ($rD) ? $objects : $cR->newInstanceArgs($objects);
            }
        }

        return ($rD) ? false : new $c;
    }

    /**
    * Returns dependencies only.
    *
    * @param string $s Object|Class
    * @param string $m Method
    *
    * @return array Objects
    */
    public function getDependencies($s, $m = null) {
        if (is_object($s)) return $this->make(get_class($s), $m, true);

        return $this->make($s, $m, true);
    }

    /**
    * Checks if object exists in the registry.
    *
    * @param string $c Class
    *
    * @return boolean
    */
    private function _iB($c)
    {
        return (array_key_exists($c, $this->_r)) ? true : false;
    }

    /**
    * Returns registry list without objects.
    *
    * @return array Classes
    */
    public function getRegistry()
    {
        $a = [];

        foreach ($this->_r as $n => $o) $a[] = $n;

        return $a;
    }

    /**
    * Parses class parameters for dependencies.
    *
    * @return array Classes
    */
    private function _gD($c, $m, $dC)
    {
        $cA = [];

        for ($i = 0; $i < $dC; $i++) {
            $pA = explode(' ', new \ReflectionParameter([$c, $m], $i));
            if (!preg_match('/^\$[a-zA-Z]{1,}$/', $pA[4])) {
                $cA[] = $pA[4];
            }
        }

        return $cA;
    }
}

class SparkException extends \Exception
{
    /**
    * Class constructor.
    *
    * @param string $m Message
    * @param int    $c Code
    */
    public function __construct($m, $c = 0)
    {
        parent::__construct($m, $c);
    }
}