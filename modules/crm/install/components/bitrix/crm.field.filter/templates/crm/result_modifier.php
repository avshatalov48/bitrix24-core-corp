<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();


if(!CModule::IncludeModule('crm'))
	return;

global $USER;
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$arParams['ENTITY_TYPE'] = Array();
if ($arParams['arUserField']['SETTINGS']['LEAD'] == 'Y' && !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
	$arParams['ENTITY_TYPE'][] = 'LEAD';
if ($arParams['arUserField']['SETTINGS']['CONTACT'] == 'Y' && !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
	$arParams['ENTITY_TYPE'][] = 'CONTACT';
if ($arParams['arUserField']['SETTINGS']['COMPANY'] == 'Y' && !$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	$arParams['ENTITY_TYPE'][] = 'COMPANY';
if ($arParams['arUserField']['SETTINGS']['DEAL'] == 'Y' && CCrmDeal::CheckReadPermission(0, $userPermissions))
	$arParams['ENTITY_TYPE'][] = 'DEAL';
if ($arParams['arUserField']['SETTINGS']['QUOTE'] == 'Y' && !$userPermissions->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
	$arParams['ENTITY_TYPE'][] = 'QUOTE';
if ($arParams['arUserField']['SETTINGS']['PRODUCT'] == 'Y' && $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
	$arParams['ENTITY_TYPE'][] = 'PRODUCT';

$arResult['PREFIX'] = 'N';
if (count($arParams['ENTITY_TYPE']) > 1)
	$arResult['PREFIX'] = 'Y';

$arResult['MULTIPLE'] = $arParams['arUserField']['MULTIPLE'];
if (!is_array($arResult['VALUE']))
	$arResult['VALUE'] = array($arResult['VALUE']);

$arResult['SELECTED'] = array();
foreach ($arResult['VALUE'] as $key => $value)
	if (!empty($value))
		$arResult['SELECTED'][$value] = $value;

// last 50 entity
$arSettings = $arParams["arUserField"]['SETTINGS'];
if (isset($arSettings['LEAD']) && $arSettings['LEAD'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'lead';
	$IDs = CCrmLead::GetTopIDs(50, 'DESC', $userPermissions);
	if (!empty($IDs))
	{
		$obRes = CCrmLead::GetListEx(
			array('ID' => 'DESC'),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE', 'FULL_NAME', 'STATUS_ID')
		);

		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'L_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$arResult['ELEMENT'][] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => $arRes['FULL_NAME'],
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
					array(
						'lead_id' => $arRes['ID']
					)
				),
				'type'  => 'lead',
				'selected' => $sSelected
			);
		}
	}
}
if (isset($arSettings['CONTACT']) && $arSettings['CONTACT'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'contact';
	$IDs = CCrmContact::GetTopIDs(50, 'DESC', $userPermissions);
	if (!empty($IDs))
	{
		$obRes = CCrmContact::GetListEx(
			array('ID' => 'DESC'),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'FULL_NAME', 'COMPANY_TITLE', 'PHOTO')
		);

		while ($arRes = $obRes->Fetch())
		{
			$imageUrl = '';
			if (isset($arRes['PHOTO']) && $arRes['PHOTO'] > 0)
			{
				$arImg =  CFile::ResizeImageGet($arRes['PHOTO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				if(is_array($arImg) && isset($arImg['src']))
				{
					$imageUrl = CHTTP::URN2URI($arImg['src']);
				}
			}

			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'C_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$arResult['ELEMENT'][] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['FULL_NAME'])),
				'desc'  => empty($arRes['COMPANY_TITLE'])? '': $arRes['COMPANY_TITLE'],
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $arRes['ID']
					)
				),
				'image' => $imageUrl,
				'type'  => 'contact',
				'selected' => $sSelected
			);
		}
	}
}
if (isset($arSettings['COMPANY']) && $arSettings['COMPANY'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'company';
	$IDs = CCrmCompany::GetTopIDs(50, 'DESC', $userPermissions);
	if (!empty($IDs))
	{
		$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
		$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
		$obRes = CCrmCompany::GetListEx(
			array('ID' => 'DESC'),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO')
		);

		while ($arRes = $obRes->Fetch())
		{
			$imageUrl = '';
			if (isset($arRes['LOGO']) && $arRes['LOGO'] > 0)
			{
				$arImg =  CFile::ResizeImageGet($arRes['LOGO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				if(is_array($arImg) && isset($arImg['src']))
				{
					$imageUrl = CHTTP::URN2URI($arImg['src']);
				}
			}

			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'CO_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$arDesc = Array();
			if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
				$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
			if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
				$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];

			$arResult['ELEMENT'][] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => implode(', ', $arDesc),
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $arRes['ID']
					)
				),
				'image' => $imageUrl,
				'type'  => 'company',
				'selected' => $sSelected
			);
		}
	}
}
if (isset($arSettings['DEAL']) && $arSettings['DEAL'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'deal';
	$IDs = CCrmDeal::GetTopIDs(50, 'DESC', $userPermissions);
	if (!empty($IDs))
	{
		$obRes = CCrmDeal::GetListEx(
			array('ID' => 'DESC'),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
		);

		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'D_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
			$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

			$arResult['ELEMENT'][] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => $clientTitle,
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
					array(
						'deal_id' => $arRes['ID']
					)
				),
				'type'  => 'deal',
				'selected' => $sSelected
			);
		}
	}
}
if (isset($arSettings['QUOTE']) && $arSettings['QUOTE'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'quote';
	$IDs = CCrmQuote::GetTopIDs(50, 'DESC', $userPermissions);
	if (!empty($IDs))
	{
		$obRes = CCrmQuote::GetList(
			array('ID' => 'DESC'),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
		);

		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
			$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];
			$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

			$arResult['ELEMENT'][] = Array(
				'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
				'desc' => $clientTitle,
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array('quote_id' => $arRes['ID'])
				),
				'type' => 'quote',
				'selected' => $sSelected
			);
		}
	}
}
if (isset($arSettings['PRODUCT']) && $arSettings['PRODUCT'] == 'Y')
{
	$arResult['ENTITY_TYPE'][] = 'product';

	$arSelect = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID');
	$arPricesSelect = $arVatsSelect = array();
	$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
	$obRes = CCrmProduct::GetList(array('ID' => 'DESC'), array('ACTIVE' => 'Y'), $arSelect, 50);

	$arProducts = $arProductId = array();
	while ($arRes = $obRes->Fetch())
	{
		foreach ($arPricesSelect as $fieldName)
			$arRes[$fieldName] = null;
		foreach ($arVatsSelect as $fieldName)
			$arRes[$fieldName] = null;
		$arProductId[] = $arRes['ID'];
		$arProducts[$arRes['ID']] = $arRes;
	}
	CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect);
	unset($arProductId, $arPricesSelect, $arVatsSelect);

	foreach ($arProducts as $arRes)
	{
		$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'PROD_'.$arRes['ID']: $arRes['ID'];
		if (isset($arResult['SELECTED'][$arRes['SID']]))
		{
			unset($arResult['SELECTED'][$arRes['SID']]);
			$sSelected = 'Y';
		}
		else
			$sSelected = 'N';

		$arResult['ELEMENT'][] = array(
			'title' => $arRes['NAME'],
			'desc' => CCrmProduct::FormatPrice($arRes),
			'id' => $arRes['SID'],
			'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_product_show'),
				array(
					'product_id' => $arRes['ID']
				)
			),
			'type'  => 'product',
			'selected' => $sSelected
		);
	}
	unset($arProducts);
}

if (!empty($arResult['SELECTED']))
{
	foreach ($arResult['SELECTED'] as $value)
	{
		if($arParams['PREFIX'])
		{
			$ar = explode('_', $value);
			$arSelected[CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
		}
		else
		{
			if (is_numeric($value))
				$arSelected[$arParams['ENTITY_TYPE'][0]][] = $value;
			else
			{
				$ar = explode('_', $value);
				$arSelected[CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
			}
		}
	}

	if ($arParams['arUserField']['SETTINGS']['LEAD'] == 'Y'
		&& isset($arSelected['LEAD']) && !empty($arSelected['LEAD'])
	)
	{
		$arSelect = array('ID', 'TITLE', 'FULL_NAME', 'STATUS_ID');
		$obRes = CCrmLead::GetListEx(array('ID' => 'DESC'), array('ID' => $arSelected['LEAD']), false, false, $arSelect);
		$ar = Array();
		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'L_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$ar[] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => $arRes['FULL_NAME'],
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
					array(
						'lead_id' => $arRes['ID']
					)
				),
				'type'  => 'lead',
				'selected' => $sSelected
			);
		}
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
	if ($arParams['arUserField']['SETTINGS']['CONTACT'] == 'Y'
		&& isset($arSelected['CONTACT']) && !empty($arSelected['CONTACT'])
	)
	{
		$arSelect = array('ID', 'FULL_NAME', 'COMPANY_TITLE', 'PHOTO');
		$obRes = CCrmContact::GetListEx(array('ID' => 'DESC'), array('ID' => $arSelected['CONTACT']), false, false, $arSelect);
		$ar = Array();
		while ($arRes = $obRes->Fetch())
		{
			$imageUrl = '';
			if (isset($arRes['PHOTO']) && $arRes['PHOTO'] > 0)
			{
				$arImg =  CFile::ResizeImageGet($arRes['PHOTO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				if(is_array($arImg) && isset($arImg['src']))
				{
					$imageUrl = CHTTP::URN2URI($arImg['src']);
				}
			}

			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'C_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$ar[] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['FULL_NAME'])),
				'desc'  => empty($arRes['COMPANY_TITLE'])? '': $arRes['COMPANY_TITLE'],
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $arRes['ID']
					)
				),
				'image' => $imageUrl,
				'type'  => 'contact',
				'selected' => $sSelected
			);
		}
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
	if (true || $arParams['arUserField']['SETTINGS']['COMPANY'] == 'Y'
	&& isset($arSelected['COMPANY']) && !empty($arSelected['COMPANY']))
	{
		$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
		$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
		$arSelect = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO');
		$obRes = CCrmCompany::GetList(array('ID' => 'DESC'), Array('ID' => 1), $arSelect);
		$ar = Array();
		while ($arRes = $obRes->Fetch())
		{
			$imageUrl = '';
			if (isset($arRes['LOGO']) && $arRes['LOGO'] > 0)
			{
				$arImg =  CFile::ResizeImageGet($arRes['LOGO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				if(is_array($arImg) && isset($arImg['src']))
				{
					$imageUrl = CHTTP::URN2URI($arImg['src']);
				}
			}

			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'CO_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$arDesc = Array();
			if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
				$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
			if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
				$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];


			$ar[] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => implode(', ', $arDesc),
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $arRes['ID']
					)
				),
				'image' => $imageUrl,
				'type'  => 'company',
				'selected' => $sSelected
			);
		}
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
	if ($arParams['arUserField']['SETTINGS']['DEAL'] == 'Y'
	&& isset($arSelected['DEAL']) && !empty($arSelected['DEAL']))
	{
		$arSelect = array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
		$ar = Array();
		$obRes = CCrmDeal::GetList(array('ID' => 'DESC'), Array('ID' => $arSelected['DEAL']), $arSelect);
		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'D_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
			$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

			$ar[] = Array(
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => $clientTitle,
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
					array(
						'deal_id' => $arRes['ID']
					)
				),
				'type'  => 'deal',
				'selected' => $sSelected
			);
		}
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
	if ($arParams['arUserField']['SETTINGS']['QUOTE'] == 'Y'
	&& isset($arSelected['QUOTE']) && !empty($arSelected['QUOTE']))
	{
		$ar = Array();
		$obRes = CCrmQuote::GetList(
			array('ID' => 'DESC'),
			array('ID' => $arSelected['QUOTE']),
			false,
			false,
			array('ID', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
		);
		while ($arRes = $obRes->Fetch())
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'D_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
			$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

			$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

			$ar[] = Array(
				'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
				'desc' => $clientTitle,
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
					array(
						'quote_id' => $arRes['ID']
					)
				),
				'type'  => 'quote',
				'selected' => $sSelected
			);
		}
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
	if (isset($arSettings['PRODUCT'])
		&& $arSettings['PRODUCT'] == 'Y'
		&& isset($arSelected['PRODUCT'])
		&& !empty($arSelected['PRODUCT']))
	{
		$ar = array();

		$arSelect = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID');
		$arPricesSelect = $arVatsSelect = array();
		$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
		$obRes = CCrmProduct::GetList(
			array('ID' => 'DESC'),
			array('ID' => $arSelected['PRODUCT']),
			$arSelect
		);

		$arProducts = $arProductId = array();
		while ($arRes = $obRes->Fetch())
		{
			foreach ($arPricesSelect as $fieldName)
				$arRes[$fieldName] = null;
			foreach ($arVatsSelect as $fieldName)
				$arRes[$fieldName] = null;
			$arProductId[] = $arRes['ID'];
			$arProducts[$arRes['ID']] = $arRes;
		}
		CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect);
		unset($arProductId, $arPricesSelect, $arVatsSelect);

		foreach ($arProducts as $arRes)
		{
			$arRes['SID'] = $arResult['PREFIX'] == 'Y'? 'D_'.$arRes['ID']: $arRes['ID'];
			if (isset($arResult['SELECTED'][$arRes['SID']]))
			{
				unset($arResult['SELECTED'][$arRes['SID']]);
				$sSelected = 'Y';
			}
			else
				$sSelected = 'N';

			$ar[] = array(
				'title' => $arRes['NAME'],
				'desc' => CCrmProduct::FormatPrice($arRes),
				'id' => $arRes['SID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_product_show'),
					array(
						'product_id' => $arRes['ID']
					)
				),
				'type'  => 'product',
				'selected' => $sSelected
			);
		}
		unset($arProducts);
		$arResult['ELEMENT'] = array_merge($ar, $arResult['ELEMENT']);
	}
}

?>