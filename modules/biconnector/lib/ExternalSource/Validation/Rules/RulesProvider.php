<?php

namespace Bitrix\BIConnector\ExternalSource\Validation\Rules;

use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Type;

final class RulesProvider
{
	static function getRules(Type $type, array $datasetSettings): array
	{
		return match ($type)
		{
			Type::Csv => [
				FieldType::Int->value => [
					new IntegerIsNumericRule(),
				],
				FieldType::Double->value => [
					new DoubleIsNumericRule($datasetSettings[FieldType::Double->value]),
				],
				FieldType::Date->value => [
					new DateIsCorrectFormatRule($datasetSettings[FieldType::Date->value]),
				],
				FieldType::DateTime->value => [
					new DateIsCorrectFormatRule($datasetSettings[FieldType::DateTime->value]),
				],
				FieldType::Money->value => [
					new MoneyRule($datasetSettings[FieldType::Money->value]),
				],
			],
			Type::Source1C => [],
		};
	}
}
