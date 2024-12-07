<?php

namespace Bitrix\Sign\Controllers\V1\Integration\Crm;

class Field extends \Bitrix\Sign\Engine\Controller
{
	public function listNameToCaptionMapAction(): array
	{
		$result = [];
		$treeFields = \Bitrix\Sign\Integration\CRM::getEntityFields();

		foreach ($treeFields as $categoryItem)
		{
			if (empty($categoryItem['FIELDS']))
			{
				continue;
			}

			foreach ($categoryItem['FIELDS'] as $fieldItem)
			{
				$result[$fieldItem['name']] = $fieldItem['caption'];
			}
		}

		return $result;
	}
}