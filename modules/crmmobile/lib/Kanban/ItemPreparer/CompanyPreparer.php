<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Main\Localization\Loc;

final class CompanyPreparer extends ListPreparer
{
	protected function getClient(array $item, array $params = []): ?array
	{
		$data = $this->getSelfContactInfo($item, \CCrmOwnerType::CompanyName);
		if (empty($data))
		{
			return [];
		}

		$data['title'] = Loc::getMessage('M_CRM_KANBAN_COMPANY_FIELD_CONTACT');
		$data['hidden'] = true;
		$data['contact'] = empty($item['CONTACT']) ? [] : $item['CONTACT'];

		return $data;
	}
}
