<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

class Quote extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::QuoteName;
	}

	public function getStatusEntityId(): string
	{
		return 'QUOTE_STATUS';
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY'];
	}

	protected function getPersistentFilterFields(): array
	{
		return [];
	}

	public function getFilterPresets(): array
	{
		$processStatusIDs = [];
		foreach (\CCrmStatus::GetStatus($this->getStatusEntityId()) as $status)
		{
			if(empty($status['SEMANTICS']) || $status['SEMANTICS'] === PhaseSemantics::PROCESS)
			{
				$processStatusIDs[] = $status['STATUS_ID'];
			}
		}

		$user = $this->getCurrentUserInfo();

		return [
			'filter_new' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_NEW'),
				'fields' => [
					'STATUS_ID' => [
						'selDRAFT' => 'DRAFT',
					],
				],
			],
			'filter_my' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_MY'),
				'disallow_for_all' => true,
				'fields' => [
					'ASSIGNED_BY_ID_name' => $user['name'],
					'ASSIGNED_BY_ID' => $user['id'],
				]
			],
			'filter_my_in_work' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_QT_MY_WORK'),
				'disallow_for_all' => true,
				'default' => true,
				'fields' => [
					'ASSIGNED_BY_ID_name' => $user['name'],
					'ASSIGNED_BY_ID' => $user['id'],
					'STATUS_ID' => $processStatusIDs,
				]
			],
		];
	}

	public function isCustomPriceFieldsSupported(): bool
	{
		return false;
	}

	public function isInlineEditorSupported(): bool
	{
		return false;
	}

	public function isEntitiesLinksInFilterSupported(): bool
	{
		return true;
	}

	public function isActivityCountersSupported(): bool
	{
		return false;
	}

	public function getCloseDateFieldName(): ?string
	{
		return 'CLOSEDATE';
	}

	public function prepareFilterField(string $field): string
	{
		if(in_array(
			$field,
			['PRODUCT_ID', 'STATUS_ID', 'COMPANY_ID', 'LEAD_ID','DEAL_ID', 'CONTACT_ID', 'MYCOMPANY_ID']
		))
		{
			return '=' . $field;
		}

		return $field;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['PRICE'] = $item['OPPORTUNITY'];
		if ($item['BEGINDATE'])
		{
			$item['FORMAT_TIME'] = false;
			$item['DATE'] = $item['BEGINDATE'];
		}
		else
		{
			$item['DATE'] = $item['DATE_CREATE'];
		}

		$item = parent::prepareItemCommonFields($item);

		return $item;
	}
}