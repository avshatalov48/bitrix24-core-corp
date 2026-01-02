<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service;

use Bitrix\Tasks\Integration\CRM\Fields\Mapper;

class CrmService
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