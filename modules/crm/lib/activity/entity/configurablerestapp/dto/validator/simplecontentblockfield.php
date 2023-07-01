<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\Validator;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

final class SimpleContentBlockField extends Validator
{
	protected string $fieldToCheck;
	protected bool $isCollection;

	public function __construct(\Bitrix\Crm\Dto\Dto $dto, string $fieldToCheck, bool $isCollection)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
		$this->isCollection = $isCollection;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (array_key_exists($this->fieldToCheck, $fields))
		{
			if ($this->isCollection)
			{
				foreach ($fields[$this->fieldToCheck] as $fieldValue)
				{
					if (is_array($fieldValue))
					{
						$result = $this->validateContentBlockType($fieldValue);
					}
				}
			}
			else
			{
				if (is_array($fields[$this->fieldToCheck]))
				{
					$result = $this->validateContentBlockType($fields[$this->fieldToCheck]);
				}
			}
		}

		return $result;
	}

	private function validateContentBlockType(array $contentBlockData): Result
	{
		$result = new Result();

		if (!in_array($contentBlockData['type'] ?? '', [
			Dto\ContentBlockDto::TYPE_TEXT,
			Dto\ContentBlockDto::TYPE_LINK,
			Dto\ContentBlockDto::TYPE_DEADLINE,
		]))
		{
			$result->addError($this->getWrongFieldError('type', 'ContentBlockDto'));
		}

		return $result;
	}
}
