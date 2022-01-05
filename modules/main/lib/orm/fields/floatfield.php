<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\ORM\Fields;

/**
 * Entity field class for enum data type
 * @package bitrix
 * @subpackage main
 */
class FloatField extends ScalarField
{
	/** @var int|null */
	protected $precision;

	/**
	 * FloatField constructor.
	 *
	 * @param       $name
	 * @param array $parameters deprecated, use configure* and add* methods instead
	 *
	 * @throws \Bitrix\Main\SystemException
	 */
	public function __construct($name, $parameters = array())
	{
		parent::__construct($name, $parameters);

		if(isset($parameters['scale']))
		{
			$this->precision = intval($parameters['scale']);
		}

		if(isset($parameters['precision']))
		{
			$this->precision = intval($parameters['precision']);
		}
	}

	/**
	 * @param int $precision
	 *
	 * @return $this
	 */
	public function configurePrecision($precision)
	{
		$this->precision = (int) $precision;
		return $this;
	}

	/**
	 * @param $scale
	 * @deprecated
	 *
	 * @return $this
	 */
	public function configureScale($scale)
	{
		$this->precision = (int) $scale;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getPrecision()
	{
		return $this->precision;
	}

	/**
	 * @deprecated
	 * @return int|null
	 */
	public function getScale()
	{
		return $this->precision;
	}

	/**
	 * @param mixed $value
	 *
	 * @return float|mixed
	 */
	public function cast($value)
	{
		if ($this->is_nullable && $value === null)
		{
			return $value;
		}

		$value = doubleval($value);

		if ($this->precision !== null)
		{
			$value = round($value, $this->precision);
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return float|mixed
	 * @throws \Bitrix\Main\SystemException
	 */
	public function convertValueFromDb($value)
	{
		return $this->getConnection()->getSqlHelper()->convertFromDbFloat($value);
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 * @throws \Bitrix\Main\SystemException
	 */
	public function convertValueToDb($value)
	{
		return $this->getConnection()->getSqlHelper()->convertToDbFloat($value);
	}

	/**
	 * @return string
	 */
	public function getGetterTypeHint()
	{
		return '\\float';
	}

	/**
	 * @return string
	 */
	public function getSetterTypeHint()
	{
		return '\\float';
	}
}