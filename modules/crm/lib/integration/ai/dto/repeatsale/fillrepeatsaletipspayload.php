<?php

namespace Bitrix\Crm\Integration\AI\Dto\RepeatSale;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Main\Result;

final class FillRepeatSaleTipsPayload extends Dto
{
	public ?CustomerInfo $customerInfo = null;
	public ?ActionPlan $actionPlan = null;

	protected function getValidators(array $fields): array
	{
		return [
			new Validator\ObjectField($this, 'customerInfo'),
			new Validator\ObjectField($this, 'actionPlan'),
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();

					if (
						empty($fields['customerInfo'])
						&& empty($fields['actionPlan'])
					)
					{
						$result->addError(ErrorCode::getInvalidPayloadError());
					}

					return $result;
				}
			},
		];
	}
}
