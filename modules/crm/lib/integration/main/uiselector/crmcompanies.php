<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use CCrmCompany;
use CCrmFieldMulti;
use CCrmOwnerType;
use CCrmStatus;
use CFile;

class CrmCompanies extends CrmEntity
{
	use GetEntityIdsByEmailTrait;

	public const PREFIX_SHORT = 'CO_';
	public const PREFIX_FULL = 'CRMCOMPANY';

	protected const SORT_SELECTED = 200;
	protected const DATA_CLASS = CCrmCompany::class;
	protected const CACHE_DIR = 'b_crm_company';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Company;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMCOMPANIES;
	}

	protected function getGroupsList(): array
	{
		return [
			'crmcompanies' => [
				'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMCOMPANIES'),
				'TYPE_LIST' => [ static::getHandlerType() ],
				'DESC_LESS_MODE' => 'N',
				'SORT' => 20,
			],
		];
	}

	protected static function prepareEntity($data, $options = []): array
	{
		static
			$companyTypeList = null,
			$companyIndustryList = null;

		$prefix = static::getPrefix($options);

		if ($companyTypeList === null)
		{
			$companyTypeList = CCrmStatus::getStatusListEx('COMPANY_TYPE');
		}

		if ($companyIndustryList === null)
		{
			$companyIndustryList = CCrmStatus::getStatusListEx('INDUSTRY');
		}

		$descList = [];
		if (isset($companyTypeList[$data['COMPANY_TYPE']]))
		{
			$descList[] = $companyTypeList[$data['COMPANY_TYPE']];
		}
		if (isset($companyIndustryList[$data['INDUSTRY']]))
		{
			$descList[] = $companyIndustryList[$data['INDUSTRY']];
		}

		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'companies',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(Emoji::decode(str_replace([';', ','], ' ', $data['TITLE']))),
			'desc' => htmlspecialcharsbx(implode(', ', $descList))
		];

		if (array_key_exists('DATE_CREATE', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
		}

		if (
			!empty($data['LOGO'])
			&& intval($data['LOGO']) > 0
		)
		{
			$imageFields = CFile::resizeImageGet(
				$data['LOGO'],
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
					'id' => 'companies',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMCOMPANIES'),
					'sort' => 30
				]
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

			$res = CCrmCompany::getListEx(
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
			'TITLE',
			'COMPANY_TYPE',
			'INDUSTRY',
			'LOGO',
			'HAS_EMAIL',
			'DATE_CREATE',
		];
	}

	protected function prepareOptionalFilter(array $filter, array $options): array
	{
		if (
			isset($options['onlyMy'])
			&& $options['onlyMy'] === 'Y'
		)
		{
			$filter['=IS_MY_COMPANY'] = 'Y';
		}

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
				'?TITLE' => $search,
				'@CATEGORY_ID' => 0,
				'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
			];
		}

		return
			is_array($filter)
				? $this->prepareOptionalFilter($filter, $options)
				: false
			;
	}
}
