<?php

namespace Bitrix\Tasks\Components\Kanban\Services;

use Bitrix\Tasks\Integration\CRM\Fields\Mapper;

class Crm
{
	public function getData(array $item): ?array
	{
		$result = [];
		$field = is_array($item['UF_CRM_TASK'] ?? null) ? $item['UF_CRM_TASK'] : [];
		$crmFields = (new Mapper())->map($field);
		foreach ($crmFields as $crmField)
		{
			if ($crmField->getCaption())
			{
				$result[] = [
					'name' => $crmField->getCaption(),
					'url' => $crmField->getUrl(),
				];
			}
		}

		return $result;
	}
}