<?php

namespace Bitrix\Crm\Dto\Validator\Logic;

use Bitrix\Crm\Dto\Contract\Validator;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Result;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

class LogicOr implements Validator
{
	/** @var Validator[] $validators */
	private array $validators = [];

	private const ERROR_MESSAGE_TRIM_CHARACTERS = ". \t\n\r\0\x0B";
	private const ERROR_MESSAGES_SEPARATOR = ', ';

	public function __construct(private readonly Dto $dto, array $validators)
	{
		foreach ($validators as $validator)
		{
			if ($validator instanceof Validator)
			{
				$this->validators[] = $validator;
			}
		}
	}

	public function validate(array $fields): \Bitrix\Main\Result
	{
		$errorCollection = new ErrorCollection();
		foreach ($this->validators as $validator)
		{
			$result = $validator->validate($fields);
			if ($result->isSuccess())
			{
				return Result::success();
			}

			$errorCollection->add($result->getErrors());
		}

		if ($errorCollection->isEmpty())
		{
			return Result::success();
		}

		return Result::fail($this->buildError($errorCollection));
	}

	private function buildError(ErrorCollection $errorCollection): Error
	{
		$messages = [];
		$customData = [];

		/** @var Error $error */
		foreach ($errorCollection->toArray() as $error)
		{
			$messages[] = trim($error->getMessage(), self::ERROR_MESSAGE_TRIM_CHARACTERS);
			$customData[] = [
				'ERROR_CODE' => $error->getCode(),
				...$error->getCustomData(),
			];
		}

		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_LOGIC_LOGIC_OR_ERROR', [
				'#PARENT_OBJECT#' => $this->dto->getName(),
				'#ERROR_MESSAGES#' => implode(self::ERROR_MESSAGES_SEPARATOR,  $messages),
			]),
			code: 'LOGIC_OR_ERRORS',
			customData: $customData,
		);
	}
}
