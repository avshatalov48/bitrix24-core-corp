<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

class RegularExpressionValidator extends BaseFormFilter
{
	private $pattern;
	private $notPattern;
	protected $defaultMessage = '';

	public function configurePattern($pattern, $errorCode = '')
	{
		$this->pattern = $pattern;
		$this->defaultMessage = $errorCode ?: $this->defaultMessage;
		return $this;
	}

	public function configureNotPattern($pattern)
	{
		$this->notPattern = true;
		$this->pattern = $pattern;
		return $this;
	}

	protected function validateValue($value)
	{
		$valid = false;
		if ($this->pattern)
		{
			$valid = !is_array($value) &&
				(!$this->notPattern && preg_match($this->pattern, $value)
				|| $this->notPattern && !preg_match($this->pattern, $value));
		}

		return $valid ? null : [$this->defaultMessage, []];
	}
}