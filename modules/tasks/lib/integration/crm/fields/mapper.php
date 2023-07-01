<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

use Bitrix\Main\Loader;

class Mapper
{
	public const CRM_FIELDS = [
		'UF_CRM_TASK_LEAD' => 'L',
		'UF_CRM_TASK_CONTACT' => 'C',
		'UF_CRM_TASK_COMPANY' => 'CO',
		'UF_CRM_TASK_DEAL' => 'D',
	];

	public function map(array $crmFields): Collection
	{
		if (!Loader::includeModule('crm') || empty($crmFields))
		{
			return new Collection(...[]);
		}

		$collection = [];
		foreach ($crmFields as $value)
		{
			[$type, $id] = explode('_', $value);

			$id = (int)$id;
			$typeId = \CCrmOwnerType::ResolveID(\CCrmOwnerTypeAbbr::ResolveName($type));
			if ($typeId === \CCrmOwnerType::Undefined)
			{
				continue;
			}

			$caption = \CCrmOwnerType::GetCaption($typeId, $id);
			$caption = is_string($caption) ? $caption : '';
			$url = \CCrmOwnerType::GetEntityShowPath($typeId, $id);
			$collection[] = new Crm(
				$id,
				$typeId,
				$caption,
				$url
			);
		}

		return new Collection(...$collection);
	}
}