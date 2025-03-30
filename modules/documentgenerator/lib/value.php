<?php

namespace Bitrix\DocumentGenerator;

abstract class Value
{
	protected $value;
	protected $options = [];

	/**
	 * Value constructor.
	 * @param $value
	 * @param array $options
	 */
	public function __construct($value, array $options = [])
	{
		$this->value = $value;
		$this->options = static::getDefaultOptions();
		$this->options = $this->getOptions($options);
	}

	/**
	 * @param string $modifier
	 * @return string
	 */
	abstract public function toString($modifier = '');

	public function __toString(): string
	{
		return $this->toString();
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions()
	{
		return [];
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return array
	 */
	protected static function getAliases()
	{
		return [];
	}

	/**
	 * @param string $modifier
	 * @return array
	 */
	public static function parseModifier($modifier)
	{
		if(is_array($modifier))
		{
			return $modifier;
		}
		elseif(is_object($modifier))
		{
			return [];
		}
		$modifier = (string)$modifier;
		if(empty($modifier))
		{
			return [];
		}
		$result = [];
		$aliases = static::getAliases();

		$pairs = explode(',', $modifier);
		foreach($pairs as $pair)
		{
			list($name, $value) = explode('=', $pair);
			$name = trim($name);
			$value = trim($value);
			if($name !== null && $value !== null)
			{
				if(mb_strtoupper($value) === 'Y')
				{
					$value = true;
				}
				if(mb_strtoupper($value) === 'N')
				{
					$value = false;
				}
				if(isset($aliases[$name]))
				{
					$result[$aliases[$name]] = $value;
				}
				else
				{
					$result[$name] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $modifier
	 * @return array
	 */
	protected function getOptions($modifier = null)
	{
		if(!$modifier)
		{
			$options = $this->options;
		}
		else
		{
			if(!is_array($this->options))
			{
				$this->options = [];
			}
			$options = array_merge($this->options, static::parseModifier($modifier));
		}
		if(!$options || empty($options))
		{
			$options = static::getDefaultOptions();
		}

		return $options;
	}
}
