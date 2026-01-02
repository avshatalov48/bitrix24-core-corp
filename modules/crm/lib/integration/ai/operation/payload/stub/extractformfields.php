<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Stub;

use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\ExtractFormFields as ExtractFormFieldsPayload;
use Bitrix\Crm\Integration\AI\Operation\Payload\StubInterface;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Types\DateType;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Main\UserField\Types\EnumType;
use Bitrix\Main\UserField\Types\IntegerType;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\Web\Json;

final class ExtractFormFields implements StubInterface
{
	public function __construct(private readonly ItemIdentifier $identifier)
	{
	}

	public function makeStub(): mixed
	{
		$fields = [
			// unallocated data
			'comment' => [
				'This is stub of an unallocated data',
				'Imagine that it was returned by AI',
				'(Some super magic info here)',
			],
		];

		$suitableFields = ExtractFormFieldsPayload::getAllSuitableFields($this->identifier->getEntityTypeId());
		foreach ($suitableFields as $fieldDescription)
		{
			if ($fieldDescription['MULTIPLE'])
			{
				$numberOfElements = Random::getInt(-1, 3);
				if ($numberOfElements < 0)
				{
					$value = null;
				}
				else
				{
					$value = [];
					while (count($value) < $numberOfElements)
					{
						$value[] = $this->generateSingleStubValue(
							$fieldDescription['TYPE'],
							$fieldDescription['VALUES'] ?? null
						);
					}
				}
			}
			else
			{
				$value = $this->generateSingleStubValue(
					$fieldDescription['TYPE'],
					$fieldDescription['VALUES'] ?? null
				);
			}
			
			$fields[$fieldDescription['NAME']] = $value;
		}

		return Json::encode($fields);
	}
	
	private function generateSingleStubValue(string $type, ?array $values = null): string|int|null|float
	{
		return match ($type)
		{
			StringType::USER_TYPE_ID => Random::getString(4, true),
			IntegerType::USER_TYPE_ID => Random::getInt(0, 10_000),
			DoubleType::USER_TYPE_ID => Random::getInt(0, 100_000) * 0.1,
			DateType::USER_TYPE_ID => $this->generateDateTimeValue(),
			EnumType::USER_TYPE_ID => $this->generateEnumValue($values),
			default => null,
		};
	}
	
	private function generateDateTimeValue(bool $withTime = false): string
	{
		$currentYear =(new DateTime())->format('Y');
		$startTimestamp = DateTime::createFromUserTime('01.01.' . $currentYear)->getTimestamp();
		$endTimestamp = DateTime::createFromUserTime('31.12.' . $currentYear)->getTimestamp();

		return DateTime::createFromTimestamp(random_int($startTimestamp, $endTimestamp))
			->format($withTime ? 'd.m.Y H:i:s' : 'd.m.Y')
		;
	}
	
	private function generateEnumValue(?array $values): string
	{
		if (empty($values))
		{
			return '';
		}

		$values = array_values($values);

		return (string)$values[Random::getInt(0, count($values) - 1)];
	}
}
