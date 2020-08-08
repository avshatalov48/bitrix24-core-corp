<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

class CallbackValidator extends BaseFormFilter
{
	private $validatingCallback;
	private $skipOnArray = false;

	public function configureSkipOnArray($skip)
	{
		$this->skipOnArray = $skip;
		return $this;
	}

	public function configureCallback(callable $preFilter)
	{
		$this->validatingCallback = $preFilter;
		return $this;
	}

	/*** @inheritdoc*/
	protected function validateValue($value)
	{
		if (!$this->skipOnArray || !is_array($value))
		{
			$result = call_user_func($this->validatingCallback, $value);
			if (!$result)
			{
				return [$this->defaultErrorMessage, []];
			}
		}
		return null;
	}
}