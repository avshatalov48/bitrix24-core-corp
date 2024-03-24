<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Integration\Main\UISelector\EntitySelection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use CCrmFieldMulti;
use CCrmLead;
use CCrmOwnerType;

class CrmLeads extends CrmEntity
{
	use GetEntityIdsByEmailTrait;

	public const PREFIX_SHORT = 'L_';
	public const PREFIX_FULL = 'CRMLEAD';

	protected const SORT_SELECTED = 400;
	protected const DATA_CLASS = CCrmLead::class;
	protected const CACHE_DIR = 'b_crm_lead';

	protected const SELECTED_ITEMS_STRATEGY = EntitySelection\Preparer::SELECTED_ITEMS_FOR_LEAD_STRATEGY;

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Lead;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMLEADS;
	}

	protected static function prepareEntity($data, $options = []): array
	{
		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'leads',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(Emoji::decode($data['TITLE'])),
			'desc' => htmlspecialcharsbx(
				CCrmLead::prepareFormattedName(
					[
						'HONORIFIC' => $data['HONORIFIC'] ?? '',
						'NAME' => $data['NAME'] ?? '',
						'SECOND_NAME' => $data['SECOND_NAME'] ?? '',
						'LAST_NAME' => $data['LAST_NAME'] ?? ''
					]
				)
			)
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			!empty($data['HAS_EMAIL'])
			&& $data['HAS_EMAIL'] === 'Y'
		)
		{
			$multiEmailsList = [];
			$found = false;

			$res = CCrmFieldMulti::getList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => static::getOwnerTypeName(),
					'TYPE_ID' => CCrmFieldMulti::EMAIL,
					'ELEMENT_ID' => $data['ID'],
				]
			);
			while ($multiFields = $res->Fetch())
			{
				if (!empty($multiFields['VALUE']))
				{
					$multiEmailsList[] = htmlspecialcharsbx($multiFields['VALUE']);
					if (!$found)
					{
						$result['email'] = htmlspecialcharsbx($multiFields['VALUE']);
						if (
							isset($options['onlyWithEmail'])
							&& $options['onlyWithEmail'] === 'Y'
						)
						{
							$result['desc'] = $result['email'];
						}
						$found = true;
					}
				}
			}
			$result['multiEmailsList'] = $multiEmailsList;
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] === 'Y'
		)
		{
			$result['url'] = CCrmOwnerType::getEntityShowPath(static::getOwnerType(), $data['ID']);
			$result['urlUseSlider'] = (CCrmOwnerType::isSliderEnabled(static::getOwnerType()) ? 'Y' : 'N');
		}

		return $result;
	}

	protected function getGroupsList(): array
	{
		return [
			'crmleads' => [
				'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMLEADS'),
				'TYPE_LIST' => [ static::getHandlerType() ],
				'DESC_LESS_MODE' => 'N',
				'SORT' => 40,
			],
		];
	}

	public function getTabList($params = []): array
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] === 'Y'
		)
		{
			$result = [
				[
					'id' => 'leads',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMLEADS'),
					'sort' => 40,
				],
			];
		}

		return $result;
	}

	public function search($params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => [],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : []);
		$search = $requestFields['searchString'];
		$prefix = static::getPrefix($entityOptions);

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] !== 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$select = $this->getSearchSelect();

			$res = CCrmLead::getListEx(
				$this->getSearchOrder(),
				$filter,
				false,
				['nTopCount' => 20],
				$select
			);

			$resultItems = [];

			while ($entityFields = $res->fetch())
			{
				$resultItems[$prefix . $entityFields['ID']] = static::prepareEntity($entityFields, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = false;

		if (check_email($search, true))
		{
			$entityIdList = $this->getEntityIdsByEmail($search);
			if (!empty($entityIdList))
			{
				$filter = ['@ID' => $entityIdList];
			}
		}
		else
		{
			$filter = [
				'LOGIC' => 'OR',
				'?FULL_NAME' => $search,
				'?TITLE' => $search
			];

			$filter = [
				'SEARCH_CONTENT' => $search,
				'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false,
				'__INNER_FILTER_1' => $filter,
			];
		}

		return
			is_array($filter)
				? $this->prepareOptionalFilter($filter, $options)
				: false
		;
	}

	protected function getInitFilter(array $entitiesIds = [], array $selectedEntitiesIds = []): array
	{
		$filter = parent::getInitFilter($entitiesIds, $selectedEntitiesIds);
		unset($filter['@CATEGORY_ID']);

		return $filter;
	}

	protected function prepareOptionalFilter(array $filter, array $options): array
	{
		if (
			isset($options['onlyWithEmail'])
			&& $options['onlyWithEmail'] === 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		return $filter;
	}

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'TITLE',
			'HONORIFIC',
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'STATUS_ID',
			'DATE_CREATE',
			'HAS_EMAIL',
		];
	}
}