<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use CCrmContact;
use CCrmFieldMulti;
use CCrmOwnerType;

class CrmContacts extends CrmEntity
{
	use GetEntityIdsByEmailTrait;

	public const PREFIX_SHORT = 'C_';
	public const PREFIX_FULL = 'CRMCONTACT';

	protected const SORT_SELECTED = 100;
	protected const DATA_CLASS = CCrmContact::class;
	protected const CACHE_DIR = 'b_crm_contact';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Contact;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMCONTACTS;
	}

	protected static function prepareEntity($data, $options = [])
	{
		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'contacts',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(
				CCrmContact::prepareFormattedName([
						'HONORIFIC' => $data['HONORIFIC'] ?? '',
						'NAME' => isset($data['NAME']) ? Emoji::decode($data['NAME']) : '',
						'SECOND_NAME' => isset($data['SECOND_NAME']) ? Emoji::decode($data['SECOND_NAME']) : '',
						'LAST_NAME' => isset($data['LAST_NAME']) ? Emoji::decode($data['LAST_NAME']) : ''
				])
			),
			'desc' => htmlspecialcharsbx($data['COMPANY_TITLE'])
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			!empty($data['PHOTO'])
			&& intval($data['PHOTO']) > 0
		)
		{
			$imageFields = \CFile::resizeImageGet(
				$data['PHOTO'],
				['width' => 100, 'height' => 100],
				BX_RESIZE_IMAGE_EXACT
			);
			$result['avatar'] = $imageFields['src'];
		}

		if (
			!empty($data['HAS_EMAIL'])
			&& $data['HAS_EMAIL'] == 'Y'
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
							&& $options['onlyWithEmail'] == 'Y'
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
			&& $options['returnItemUrl'] == 'Y'
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
			'crmcontacts' => [
				'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMCONTACTS'),
				'TYPE_LIST' => [ static::getHandlerType() ],
				'DESC_LESS_MODE' => 'N',
				'SORT' => 10
			]
		];
	}

	public function getTabList($params = []): array
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = [
				[
					'id' => 'contacts',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMCONTACTS'),
					'sort' => 20,
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
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$res = CCrmContact::getListEx(
				$this->getSearchOrder(),
				$filter,
				false,
				['nTopCount' => 20],
				$this->getSearchSelect()
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

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'COMPANY_TITLE',
			'PHOTO',
			'HAS_EMAIL',
			'DATE_CREATE',
		];
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

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = false;

		$searchParts = preg_split ('/[\s]+/', $search, 2, PREG_SPLIT_NO_EMPTY);
		if(count($searchParts) < 2)
		{
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
					'SEARCH_CONTENT' => $search,
					'?FULL_NAME' => $search,
					'@CATEGORY_ID' => 0,
					'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
				];
			}
		}
		else
		{
			$filter = [
				'SEARCH_CONTENT' => $search,
				'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false,
				'@CATEGORY_ID' => 0,
				'LOGIC' => 'AND'
			];

			for ($i = 0; $i < 2; $i++)
			{
				$filter["__INNER_FILTER_NAME_$i"] = ['%FULL_NAME' => $searchParts[$i]];
			}
		}

		return
			is_array($filter)
				? $this->prepareOptionalFilter($filter, $options)
				: false
		;
	}
}
