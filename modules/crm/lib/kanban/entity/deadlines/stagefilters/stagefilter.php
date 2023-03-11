<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines\Stagefilters;

interface StageFilter
{
	public function applyFilter(string $stage, array $filter, string $fieldName): array;
}