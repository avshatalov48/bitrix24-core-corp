<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Mapper;

interface FieldsMapperInterface
{
	public function map(array $item): array;
}
