<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Main\Localization\Loc;

final class ContactPreparer extends ListPreparer
{
	protected function getClient(array $item, array $params = []): ?array
	{
		$data = $this->getSelfContactInfo($item, \CCrmOwnerType::ContactName);

		if (!empty($data))
		{
			$data['hidden'] = true;
		}

		$companyId = ($item['COMPANY_ID'] ?? null);
		if ($companyId)
		{
			$companyData = [
				'title' => Loc::getMessage('M_CRM_KANBAN_CONTACT_FIELD_COMPANY'),
				'hidden' => !isset($params['displayValues']['COMPANY_ID']),
				'company' => $item['COMPANY'],
			];

			$data = array_merge($data, $companyData);
		}

		return (empty($data) ? null : $data);
	}

	protected function getItemName(array $item): string
	{
		return \CCrmContact::PrepareFormattedName(
			[
				'HONORIFIC' => $item['HONORIFIC'] ?? '',
				'NAME' => $item['NAME'] ?? '',
				'SECOND_NAME' => $item['SECOND_NAME'] ?? '',
				'LAST_NAME' => $item['LAST_NAME'] ?? '',
			]
		);
	}

	protected function prepareFields(array $item = [], array $params = []): array
	{
		unset($params['displayValues']['COMPANY_ID']);

		return parent::prepareFields($item, $params);
	}
}
