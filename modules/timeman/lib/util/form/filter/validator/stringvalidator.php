<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;
use CUtil;

class StringValidator extends BaseFormFilter
{
	private $length;
	private $max;
	private $min;
	private $minError = '';
	private $maxError = '';
	private $lengthError = '';

	public function configureMinLength($min, $errorMessage = null)
	{
		$this->min = $min;
		$this->minError = $errorMessage ?: $this->minError;
		return $this;
	}

	public function configureMaxLength($max, $errorMessage = null)
	{
		$this->max = $max;
		$this->maxError = $errorMessage ?: $this->maxError;
		return $this;
	}

	public function configureExactLength($length, $errorMessage = null)
	{
		$this->length = $length;
		$this->lengthError = $errorMessage ?: $this->length;
		return $this;
	}

	protected function isEmpty($value)
	{
		return $value === null || $value === '';
	}

	protected function validateValue($value)
	{
		if (!is_string($value))
		{
			return [$this->defaultErrorMessage, []];
		}

		$length = strlen($value);

		if ($this->min !== null && $length < $this->min)
		{
			return [$this->minError, ['#MIN#' => $this->min]];
		}
		if ($this->max !== null && $length > $this->max)
		{
			return [$this->maxError, ['#MAX#' => $this->max]];
		}
		if ($this->length !== null && $length !== $this->length)
		{
			return [$this->lengthError, ['#LENGTH#' => $this->length]];
		}

		return null;
	}
}