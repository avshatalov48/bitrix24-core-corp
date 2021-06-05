<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service;
use Bitrix\Crm\Settings\QuoteSettings;
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

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.quote.details';
	}

	protected function getInlineEditorConfiguration(\CBitrixComponent $component): array
	{
		/** @var \CrmQuoteDetailsComponent $component */
		return $component->prepareKanbanConfiguration();
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
		return QuoteSettings::getCurrent()->isFactoryEnabled();
	}

	public function isEntitiesLinksInFilterSupported(): bool
	{
		return true;
	}

	public function isActivityCountersSupported(): bool
	{
		return false;
	}

	public function isNeedToRunAutomation(): bool
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

		// emulating crm element user field to render value properly
		if ($item[Item\Quote::FIELD_NAME_LEAD_ID] > 0)
		{
			$item[Item\Quote::FIELD_NAME_LEAD_ID] = 'L_' . $item[Item\Quote::FIELD_NAME_LEAD_ID];
		}
		if ($item[Item\Quote::FIELD_NAME_DEAL_ID] > 0)
		{
			$item[Item\Quote::FIELD_NAME_DEAL_ID] = 'D_' . $item[Item\Quote::FIELD_NAME_DEAL_ID];
		}

		return $item;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	protected function getColumnId(array $data): string
	{
		return $data['STATUS_ID'];
	}

	public function getRequiredFieldsByStages(array $stages): array
	{
		$factory = Service\Container::getInstance()->getFactory($this->getTypeId());
		return static::getRequiredFieldsByStagesByFactory(
			$factory,
			$this->getRequiredUserFieldNames(),
			$stages
		);
	}

	public function getTypeInfo(): array
	{
		return array_merge(
			parent::getTypeInfo(),
			[
				'hasPlusButtonTitle' => true,
				'useFactoryBasedApproach' => true,
				'canUseCallListInPanel' => true,
				'showPersonalSetStatusNotCompletedText' => true,
				'kanbanItemClassName' => 'crm-kanban-item crm-kanban-item-invoice',
			]
		);
	}

	public function getAdditionalFields(bool $clearCache = false): array
	{
		$fields = parent::getAdditionalFields($clearCache);

		// emulating crm element user field to render value properly
		if (isset($fields[Item\Quote::FIELD_NAME_LEAD_ID]))
		{
			$fields[Item\Quote::FIELD_NAME_LEAD_ID]['type'] = 'crm';
			$fields[Item\Quote::FIELD_NAME_LEAD_ID]['settings'] = [
				'LEAD' => 'Y',
			];
		}
		if (isset($fields[Item\Quote::FIELD_NAME_DEAL_ID]))
		{
			$fields[Item\Quote::FIELD_NAME_DEAL_ID]['type'] = 'crm';
			$fields[Item\Quote::FIELD_NAME_DEAL_ID]['settings'] = [
				'DEAL' => 'Y',
			];
		}

		return $fields;
	}
}
