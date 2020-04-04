<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

class NumberValidator extends BaseFormFilter
{
	private $min;
	private $max;
	private $integerOnly;
	private $minError = null;
	private $intError;
	private $maxError = null;
	protected $defaultErrorMessage = null;

	protected $skipOnError = false;
	protected $skipOnEmpty = true;
	protected $integerPattern = '/^\s*[+-]?\d+\s*$/';
	protected $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

	public function configureMin($minValue, $errorText = '')
	{
		if (!is_numeric($minValue))
		{
			return $this;
		}
		$this->min = $minValue;
		$this->minError = $errorText ?: $this->minError;
		return $this;
	}

	public function configureMax($maxValue, $errorText = '')
	{
		if (!is_numeric($maxValue))
		{
			return $this;
		}
		$this->maxError = $errorText ?: $this->maxError;
		$this->max = $maxValue;
		return $this;
	}

	/**
	 * @param bool $isIntegerOnly
	 * @return $this
	 */
	public function configureIntegerOnly($isIntegerOnly, $errorText = '')
	{
		$this->integerOnly = (bool)$isIntegerOnly;
		$this->intError = $errorText ?: $this->intError;

		return $this;
	}

	private function isNotNumber($value)
	{
		return is_array($value)
			   || is_bool($value)
			   || (is_object($value) && !method_exists($value, '__toString'))
			   || (!is_object($value) && !is_scalar($value) && $value !== null);
	}

	protected function validateValue($value)
	{
		if ($this->isNotNumber($value))
		{
			return [$this->defaultErrorMessage, []];
		}
		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		if (!preg_match($pattern, $this->normalizeNumber($value)))
		{
			return [$this->intError ?: $this->defaultErrorMessage, []];
		}
		if ($this->min !== null && $value < $this->min)
		{
			return [$this->minError ?: $this->defaultErrorMessage, ['#MIN#' => $this->min]];
		}
		if ($this->max !== null && $value > $this->max)
		{
			return [$this->maxError ?: $this->defaultErrorMessage, ['#MAX#' => $this->max]];
		}
		return null;
	}

	private function normalizeNumber($number)
	{
		return str_replace(',', '.', $number);
	}

	protected function isEmpty($value)
	{
		return $value === null || $value === '';
	}
}