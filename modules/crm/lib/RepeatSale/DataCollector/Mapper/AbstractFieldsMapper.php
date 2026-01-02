<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Mapper;

use Bitrix\Crm\Format\TextHelper;
use CCrmContentType;
use CCrmOwnerType;

abstract class AbstractFieldsMapper
{
	public const TYPE_ID = '';
	public function __construct(protected readonly int $entityTypeId)
	{}

	abstract public function map(array $item): array;

	final protected function filterFields(array $entity, string $type = ''): array
	{
		if ($this->entityTypeId !== CCrmOwnerType::Deal)
		{
			return []; // temporary other types are not supported
		}

		if ($type === SystemFieldsMapper::TYPE_ID)
		{
			return array_filter(
				$entity,
				static fn ($val) => !str_starts_with($val, 'UF_CRM'),
				ARRAY_FILTER_USE_KEY
			);
		}

		if ($type === UserFieldsMapper::TYPE_ID)
		{
			return array_filter(
				$entity,
				static fn($val) => str_starts_with($val, 'UF_CRM'),
				ARRAY_FILTER_USE_KEY
			);
		}

		return $entity;
	}

	// region normalize data
	final protected function normalizeText(string $input): string
	{
		$input = TextHelper::cleanTextByType($input, CCrmContentType::BBCode);

		return trim(str_replace('&nbsp;', '', $input));
	}

	final protected function normalizeBoolean(string $flag): string
	{
		return $flag === 'Y' ? 'yes' : 'no';
	}
	// endregion
}
