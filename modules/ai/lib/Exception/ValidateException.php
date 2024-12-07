<?php declare(strict_types=1);

namespace Bitrix\AI\Exception;

class ValidateException extends \Exception
{
	public function __construct(protected string $fieldName, protected string $error, string $message = '')
	{
		parent::__construct($message);
	}

	public function getFieldName(): string
	{
		return $this->fieldName;
	}

	public function getError(): string
	{
		return $this->error;
	}
}
