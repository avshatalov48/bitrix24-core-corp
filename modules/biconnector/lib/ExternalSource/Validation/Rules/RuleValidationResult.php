<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

final class RuleValidationResult
{
	public readonly string $message;
	public readonly bool $isSuccess;

	public function __construct(bool $isSuccess = true, string $message = '')
	{
		$this->message = $message;
		$this->isSuccess = $isSuccess;
	}
}
