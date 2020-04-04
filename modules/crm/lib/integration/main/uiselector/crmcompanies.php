<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmCompanies extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'CO_';
	const PREFIX_FULL = 'CRMCOMPANY';

	private static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& strtolower($options['prefixType']) == 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	private static function prepareEntity($data, $options = [])
	{
		static
			$companyTypeList = null,
			$companyIndustryList = null;

		$prefix = self::getPrefix($options);

		if ($companyTypeList === null)
		{
			$companyTypeList = \CCrmStatus::getStatusListEx('COMPANY_TYPE');
		}

		if ($companyIndustryList === null)
		{
			$companyIndustryList = \CCrmStatus::getStatusListEx('INDUSTRY');
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
			'id' => $prefix.$data['ID'],
			'entityType' => 'companies',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(str_replace(array(';', ','), ' ', $data['TITLE'])),
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
			$imageFields = \CFile::resizeImageGet(
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
			$res = \CCrmFieldMulti::getList(
				array('ID' => 'asc'),
				array(
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'TYPE_ID' => \CCrmFieldMulti::EMAIL,
					'ELEMENT_ID' => $data['ID'],
				)
			);
			while ($multiFields = $res->Fetch())
			{
				if (!empty($multiFields['VALUE']))
				{
					$result['email'] = htmlspecialcharsbx($multiFields['VALUE']);
					break;
				}
			}
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Company, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Company) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		$entityType = Handler::ENTITY_TYPE_CRMCOMPANIES;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmcompanies' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMCOMPANIES'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 20
					)
				),
				'SORT_SELECTED' => 200
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastCompaniesIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
			foreach ($lastItems[$entityType] as $value)
			{
				$lastCompaniesIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedCompaniesIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedCompaniesIdList[] = str_replace($prefix, '', $value);
			}
		}

		$companiesIdList = array_merge($selectedCompaniesIdList, $lastCompaniesIdList);
		$companiesIdList = array_slice($companiesIdList, 0, count($selectedCompaniesIdList) > 20 ? count($selectedCompaniesIdList) : 20);
		$companiesIdList = array_unique($companiesIdList);

		$companiesList = [];

		$filter = [
			'CHECK_PERMISSIONS' => 'Y'
		];
		$order = [];

		if (!empty($companiesIdList))
		{
			$filter['ID'] = $companiesIdList;
			$navParams = false;
		}
		else
		{
			$order = [
				'ID' => 'DESC'
			];
			$navParams = [ 'nTopCount' => 10 ];
		}

		if (
			isset($entityOptions['onlyWithEmail'])
			&& $entityOptions['onlyWithEmail'] == 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		$res = \CCrmCompany::getListEx(
			$order,
			$filter,
			false,
			$navParams,
			['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO', 'HAS_EMAIL', 'DATE_CREATE']
		);

		while ($companyFields = $res->fetch())
		{
			$companiesList[$prefix.$companyFields['ID']] = self::prepareEntity($companyFields, $entityOptions);
		}

		if (empty($lastCompaniesIdList))
		{
			$result["ITEMS_LAST"] = array_keys($companiesList);
		}

		$result['ITEMS'] = $companiesList;

		if (!empty($selectedItems[$entityType]))
		{
			$hiddenItemsList = array_diff($selectedItems[$entityType], array_keys($companiesList));
			$hiddenItemsList = array_map(function($code) use ($prefix) { return preg_replace('/^'.$prefix.'(\d+)$/', '$1', $code); }, $hiddenItemsList);

			if (!empty($hiddenItemsList))
			{
				$filter = [
					'@ID' => $hiddenItemsList,
					'CHECK_PERMISSIONS' => 'N'
				];

				if (
					isset($entityOptions['onlyWithEmail'])
					&& $entityOptions['onlyWithEmail'] == 'Y'
				)
				{
					$filter['=HAS_EMAIL'] = 'Y';
				}

				$res = \CCrmCompany::getListEx(
					[],
					$filter,
					false,
					false,
					['ID']
				);
				while($companyFields = $res->fetch())
				{
					$result['ITEMS_HIDDEN'][] = $prefix.$companyFields["ID"];
				}
			}
		}

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = array(
				array(
					'id' => 'companies',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMCOMPANIES'),
					'sort' => 30
				)
			);
		}

		return $result;
	}

	public function search($params = array())
	{
		$result = array(
			'ITEMS' => array(),
			'ADDITIONAL_INFO' => array()
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : array());
		$search = $requestFields['searchString'];
		$prefix = self::getPrefix($entityOptions);

		if (
			strlen($search) > 0
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = [
				'SEARCH_CONTENT' => $search,
				'%TITLE' => $search,
				'__ENABLE_SEARCH_CONTENT_PHONE_DETECTION' => false
			];

			if (
				isset($entityOptions['onlyMy'])
				&& $entityOptions['onlyMy'] == 'Y'
			)
			{
				$filter['=IS_MY_COMPANY'] = 'Y';
			}

			if (
				isset($entityOptions['onlyWithEmail'])
				&& $entityOptions['onlyWithEmail'] == 'Y'
			)
			{
				$filter['=HAS_EMAIL'] = 'Y';
			}

			$res = \CCrmCompany::getListEx(
				[],
				$filter,
				false,
				['nTopCount' => 20],
				['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO', 'HAS_EMAIL', 'DATE_CREATE']
			);

			while ($companyFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$companyFields['ID']] = self::prepareEntity($companyFields, $entityOptions);
			}

			if (
				!empty($entityOptions['searchById'])
				&& $entityOptions['searchById'] == 'Y'
				&& intval($search) == $search
				&& intval($search) > 0
			)
			{
				$res = \CCrmCompany::getListEx(
					[],
					[
						'=ID' => intval($search)
					],
					false,
					['nTopCount' => 1],
					['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO', 'HAS_EMAIL', 'DATE_CREATE']
				);

				while ($companyFields = $res->fetch())
				{
					$result["ITEMS"][$prefix.$companyFields['ID']] = self::prepareEntity($companyFields, $entityOptions);
				}
			}
		}

		return $result;
	}
}