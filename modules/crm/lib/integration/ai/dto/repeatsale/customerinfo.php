<?php

namespace Bitrix\Crm\Integration\AI\Dto\RepeatSale;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Main\Result;

final class CustomerInfo extends Dto
{
	public ?string $lastPurchaseDate = null;
	public ?string $lastPurchaseDetails = null;
	public ?string $ordersOverview = null;
	public ?string $detailedIssuesSummary = null;

	protected function getValidators(array $fields): array
	{
		return [
			new class($this) extends Validator {
				public function validate(array $fields): Result
				{
					$result = new Result();
					if (
						empty($fields['lastPurchaseDate'])
						&& empty($fields['lastPurchaseDetails'])
						&& empty($fields['ordersOverview'])
						&& empty($fields['detailedIssuesSummary'])
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
