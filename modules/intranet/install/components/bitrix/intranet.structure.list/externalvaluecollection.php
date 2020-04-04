<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CExternalValueCollection implements ArrayAccess
{
	protected $filterName = null;
	protected $source = array();
	protected $rules = null;
	protected $filterSource = array();

	public function __construct($filterName, array $source, array $rules = array())
	{
		$this
			->setFilterName($filterName)
			->setSource($source)
			->setRules($rules)
		;
	}

	/**
	 * Export filterSource in array
	 * @return array
	 */
	public function exportToArray()
	{
		$export = array();
		$lengthKey = strlen($this->filterName . '_');
		foreach ($this->getFilterSource() as $key => $value)
		{
			$export[substr($key, $lengthKey)] = $value;
		}
		unset($value);

		return $export;
	}

	/**
	 * @param      $name
	 * @param null $default
	 * @param bool $setPrefix
	 * @return null
	 */
	public function getVar($name, $default = null, $setPrefix = true)
	{
		$name = $this->generateName($name, $setPrefix);

		return isset($this->filterSource[$name]) ? $this->filterSource[$name] : $default;
	}

	/**
	 * @param      $name
	 * @param      $value
	 * @param bool $setPrefix
	 * @return     $this
	 */
	public function setVar($name, $value, $setPrefix = true)
	{
		$this->filterSource[$this->generateName($name, $setPrefix)] = $value;

		return $this;
	}

	/**
	 * @param      $name
	 * @param bool $setPrefix
	 * @return     $this
	 */
	public function filterVar($name, $setPrefix = true)
	{
		$generateName = $this->generateName($name, $setPrefix);
		$val          = filter_var_array(array($generateName => $this->getVar($generateName, null, false)), $this->getRule($generateName, null, false));

		$this->setVar($generateName, $val[$generateName], false);

		return $this;
	}

	/**
	 * @param      $name
	 * @param null $default
	 * @param bool $setPrefix
	 * @return null
	 */
	public function getUnsafeVar($name, $default = null,  $setPrefix = true)
	{
		$name = $this->generateName($name, $setPrefix);

		return isset($this->source[$name]) ? $this->source[$name] : $default;
	}

	/**
	 * @param      $name
	 * @param      $value
	 * @param bool $setPrefix
	 */
	public function setUnsafeVar($name, $value, $setPrefix = true)
	{
		$this->source[$this->generateName($name, $setPrefix)] = $value;
	}

	public function prepareSource()
	{
		$this->setFilterSource(filter_var_array(array_merge($this->getSource(), $this->getFilterSource()), $this->getRules()));

		return $this;
	}

	public function setRules(array $rules)
	{
		foreach ($rules as $varName => $rule)
		{
			if (!$this->isFilterVar($varName))
			{
				$this->setRule($varName, $rule);
			}
			else
			{
				$this->setRule($varName, $rule);
			}
		}

		return $this;
	}

	/**
	 * @param      $name
	 * @param      $rule
	 * @param bool $setPrefix
	 * @return     $this
	 */
	public function setRule($name, $rule,  $setPrefix = true)
	{
		$this->rules[$this->generateName($name, $setPrefix)] = $rule;

		return $this;
	}

	public function getRule($name, $default = null, $setPrefix = true)
	{
		$name = $this->generateName($name, $setPrefix);

		return isset($this->rules[$name]) ? $this->rules[$name] : $default;
	}

	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * @param $filterName
	 * @return $this
	 */
	public function setFilterName($filterName)
	{
		$this->filterName = $filterName;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getFilterName()
	{
		return $this->filterName;
	}

	/**
	 * @param $source
	 * @return $this
	 */
	public function setSource(array $source)
	{
		$this->source = $source;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * @param $filterSource
	 * @return $this
	 */
	public function setFilterSource($filterSource)
	{
		$this->filterSource = $filterSource;

		return $this;
	}

	public function getFilterSource()
	{
		return $this->filterSource;
	}

	/**
	 * @param      $name
	 * @param bool $setPrefix
	 * @return string
	 */
	protected function generateName($name, $setPrefix = false)
	{
		return ($setPrefix ? $this->getFilterName() . '_' : '') . $name;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected function isFilterVar($name)
	{
		return 0 === strpos($name, $this->getFilterName());
	}

	/**
	 * @return string
	 */
	public function getQueryString()
	{
		return http_build_query($this->getFilterSource());
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		if($isUnsafe = strpos($offset, '~') === 0)
		{
			$offset = substr($offset, 1);
		}
		$offset = $this->generateName($offset, true);

		return array_key_exists($offset, $isUnsafe? $this->source : $this->filterSource);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null
	 */
	public function offsetGet($offset)
	{
		return (strpos($offset, '~') === 0)?
			$this->getUnsafeVar(substr($offset, 1)):
			$this->getVar($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		(strpos($offset, '~') === 0)?
			$this->setUnsafeVar(substr($offset, 1), $value, true):
			$this->setVar($offset, $value, true);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		if (strpos($offset, '~') === 0)
		{
			unset($this->source[$this->generateName(substr($offset, 1), true)]);
		}
		else
		{
			unset($this->filterSource[$this->generateName($offset, true)]);
		}

	}
}
