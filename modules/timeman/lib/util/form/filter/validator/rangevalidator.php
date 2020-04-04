<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

class RangeValidator extends BaseFormFilter
{
	private $range;
	private $strict = false;
	private $notInRange = false;

	protected $defaultMessage = '';

	/**
	 * @param array $range
	 * @param string $error
	 * @return $this
	 */
	public function configureRange($range, $error = '')
	{
		$this->range = $range;
		$this->defaultMessage = $error ?: $this->defaultMessage;
		return $this;
	}

	public function configureNotInRange($range)
	{
		$this->notInRange = true;
		$this->range = $range;
		return $this;
	}

	public function configureStrict($strict)
	{
		$this->strict = $strict;
		return $this;
	}

	protected function validateValue($value)
	{
		$res = false;
		if (is_array($this->range))
		{
			$res = in_array($value, $this->range, $this->strict);
		}

		return $this->notInRange !== $res ? null : [$this->defaultMessage, ['#RANGE#' => implode(', ', $this->range)]];
	}

	protected function isEmpty($value)
	{
		return $value === null || $value === '';
	}
}