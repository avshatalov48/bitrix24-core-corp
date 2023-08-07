<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

final class CompanyPreparer extends ListPreparer
{
	protected function getClient(array $item, array $params = []): ?array
	{
		$client = $this->getSelfContactInfo($item, \CCrmOwnerType::CompanyName);
		if (empty($client))
		{
			return [];
		}

		$client['hidden'] = true;

		return $client;
	}
}
