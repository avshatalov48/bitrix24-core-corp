<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '');
$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], '');

//CUtil::InitJSCore(array('ajax', 'tooltip'));

$bVatMode = CCrmTax::isVatMode();

$vatRateListItems = array();
if ($bVatMode)
	$vatRateListItems = CCrmVat::GetVatRatesListItems();

$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
if($productID <= 0)
{
	$productIDParName = isset($arParams['PRODUCT_ID_PAR_NAME']) ? strval($arParams['PRODUCT_ID_PAR_NAME']) : '';
	if($productIDParName == '')
	{
		$productIDParName = 'product_id';
	}

	$productID = isset($_REQUEST[$productIDParName]) ? intval($_REQUEST[$productIDParName]) : 0;
}

$arResult['PRODUCT_ID'] = $productID;
$product = $productID > 0 ? CCrmProduct::GetByID($productID, true) : false;
$arResult['CATALOG_ID'] = $catalogID = isset($product['CATALOG_ID']) ?
	intval($product['CATALOG_ID']) :
	(isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : CCrmCatalog::EnsureDefaultExists());

if(!$product)
{
	ShowError(GetMessage('CRM_PRODUCT_NOT_FOUND'));
	@define('ERROR_404', 'Y');
	if($arParams['SET_STATUS_404'] === 'Y')
	{
		CHTTP::SetStatus("404 Not Found");
	}
	return;
}

// Product properties
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(
	false,
	[CCrmProductPropsHelper::OPERATION_EDIT]
);
$arResult['EDITABLE_PROP_USER_TYPES'] = $arPropUserTypeList;
$arResult['EDITABLE_PROPS'] = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(
	false,
	[CCrmProductPropsHelper::OPERATION_VIEW]
);
$arResult['PROP_USER_TYPES'] = $arPropUserTypeList;
$arProps = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
$arResult['PROPS'] = $arProps;

$arResult['PRODUCT'] = $product;

$arResult['FORM_ID'] = 'CRM_PRODUCT_SHOW';
$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST';

// Product properties values
/*$arResult['PRODUCT_PROPS'] = array();*/
$arPropertyValues = array();
if ($productID > 0/* && count($arProps) > 0*/)
{
	$rsProperties = CIBlockElement::GetProperty(
		$catalogID,
		$productID,
		array(
			'sort' => 'asc',
			'id' => 'asc',
			'enum_sort' => 'asc',
			'value_id' => 'asc',
		),
		array(
			'ACTIVE' => 'Y',
			'EMPTY' => 'N',
			'CHECK_PERMISSIONS' => 'N'
		)
	);
	$prevPropID = '';
	$prevPropMultipleValuesInfo = array();
	while ($arProperty = $rsProperties->Fetch())
	{
		if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
			&& !array_key_exists($arProperty['USER_TYPE'], $arPropUserTypeList))
			continue;

		$propID = 'PROPERTY_' . $arProperty['ID'];

		// region Prepare multiple values
		if (!empty($prevPropID) && $propID !== $prevPropID && !empty($prevPropMultipleValuesInfo))
		{
			foreach ($prevPropMultipleValuesInfo as $valueInfo)
			{
				$methodName = $prevPropMultipleValuesInfo['methodName'];
				$method = $prevPropMultipleValuesInfo['propertyInfo']['PROPERTY_USER_TYPE'][$methodName];
				$params = [
					$prevPropMultipleValuesInfo['propertyInfo'],
					[
						"VALUE" => $prevPropMultipleValuesInfo['value'],
					],
					[],
				];
				$arPropertyValues[$prevPropID] = call_user_func_array($method, $params);
			}
		}
		// endregion Prepare multiple values

		if ($propID !== $prevPropID)
		{
			$prevPropID = $propID;
			$prevPropMultipleValuesInfo = array();
		}

		if(!isset($arPropertyValues[$propID]))
			$arPropertyValues[$propID] = array();

		$userTypeMultipleWithMultipleMethod = $userTypeMultipleWithSingleMethod =
		$userTypeSingleWithSingleMethod = false;
		if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
			&& is_array($arPropUserTypeList[$arProperty['USER_TYPE']]))
		{
			$userTypeMultipleWithMultipleMethod = (
				isset($arProperty['MULTIPLE']) && $arProperty['MULTIPLE'] === 'Y'
				&& array_key_exists('GetPublicViewHTMLMulty', $arPropUserTypeList[$arProperty['USER_TYPE']])
			);
			$userTypeMultipleWithSingleMethod = (
				isset($arProperty['MULTIPLE']) && $arProperty['MULTIPLE'] === 'Y'
				&& array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProperty['USER_TYPE']])
			);
			$userTypeSingleWithSingleMethod = (
				(!isset($arProperty['MULTIPLE']) || $arProperty['MULTIPLE'] !== 'Y')
				&& array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProperty['USER_TYPE']])
			);
		}
		if ($userTypeMultipleWithMultipleMethod || $userTypeMultipleWithSingleMethod
			|| $userTypeSingleWithSingleMethod)
		{
			$propertyInfo = $arProps[$propID];
			$propertyInfo['PROPERTY_USER_TYPE'] = $arPropUserTypeList[$arProperty['USER_TYPE']];
			$methodName = $userTypeMultipleWithMultipleMethod ? 'GetPublicViewHTMLMulty' : 'GetPublicViewHTML';
			if ($userTypeMultipleWithMultipleMethod)
			{
				if (is_array($prevPropMultipleValuesInfo['value']))
				{
					$prevPropMultipleValuesInfo['value'][] = $arProperty["VALUE"];
				}
				else
				{
					$prevPropMultipleValuesInfo['propertyInfo'] = $propertyInfo;
					$prevPropMultipleValuesInfo['methodName'] = $methodName;
					$prevPropMultipleValuesInfo['value'] = array($arProperty["VALUE"]);
				}
			}
			else
			{
				$htmlControlName = [];
				if (CCrmProductPropsHelper::isTypeSupportingUrlTemplate($propertyInfo))
				{
					$htmlControlName = [
						'DETAIL_URL' => CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_PRODUCT_SHOW'],
							[
								'product_id' => $arProperty['VALUE'],
							]
						),
					];
				}

				$method = $arPropUserTypeList[$arProperty['USER_TYPE']][$methodName];
				$params = [
					$propertyInfo,
					[
						"VALUE" => $arProperty["VALUE"]
					],
					$htmlControlName,
				];
				$value = call_user_func_array($method, $params);
				if ($arProperty['USER_TYPE'] === \CIBlockPropertyHTML::USER_TYPE)
				{
					$value = HTMLToTxt($value);
				}
				$arPropertyValues[$propID][] = $value;
			}
			unset($propertyInfo);
		}
		elseif($arProperty["PROPERTY_TYPE"] == "L")
		{
			$arPropertyValues[$propID][] = htmlspecialcharsex($arProperty["VALUE_ENUM"]);
		}
		else
		{
			$arPropertyValues[$propID][] = nl2br(htmlspecialcharsex($arProperty["VALUE"]));
		}
	}

	// region Prepare multiple values for last property
	if (!empty($prevPropID) && !empty($prevPropMultipleValuesInfo))
	{
		foreach ($prevPropMultipleValuesInfo as $valueInfo)
		{
			$methodName = $prevPropMultipleValuesInfo['methodName'];
			$method = $prevPropMultipleValuesInfo['propertyInfo']['PROPERTY_USER_TYPE'][$methodName];
			$params = [
				$prevPropMultipleValuesInfo['propertyInfo'],
				[
					"VALUE" => $prevPropMultipleValuesInfo['value'],
				],
				[],
			];
			$arPropertyValues[$prevPropID] = call_user_func_array($method, $params);
		}
	}
	// endregion Prepare multiple values for last property

	unset($rsProperties, $arProperty, $propID, $prevPropID, $prevPropMultipleValuesInfo);
}
$arResult['PROPERTY_VALUES'] = $arPropertyValues;
unset($arPropertyValues);

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'product_info',
	'name' => GetMessage('CRM_SECTION_PRODUCT_INFO'),
	'type' => 'section',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => 'ID',
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => $product['ID'],
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_PRODUCT_FIELD_NAME'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($product['~NAME']) ? $product['~NAME'] : '',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_DESCRIPTION'),
	'type' => 'custom',
	'value' => (isset($product['DESCRIPTION_TYPE']) && $product['DESCRIPTION_TYPE'] === 'text') ?
		htmlspecialcharsEx($product['~DESCRIPTION']) : HTMLToTxt($product['~DESCRIPTION']),
	'params' => array(),
	'isTactile' => true,
	'isHidden' => !(isset($product['~DESCRIPTION']) && $product['~DESCRIPTION'] <> '')

);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' => GetMessage('CRM_FIELD_ACTIVE'),
	'type' => 'label',
	'params' => array(),
	'value' => GetMessage(isset($product['ACTIVE']) && $product['ACTIVE'] == 'Y' ? 'MAIN_YES' : 'MAIN_NO'),
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY',
	'name' => GetMessage('CRM_FIELD_CURRENCY'),
	'type' => 'label',
	'params' => array(),
	'value' => '',
	'isTactile' => true,
	'isHidden' => true
);

$price = CCrmProduct::FormatPrice($product);
if($price <> '')
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PRICE',
		'name' => GetMessage('CRM_FIELD_PRICE'),
		'type' => 'label',
		'params' => array(),
		'value' => $price,
		'isTactile' => true
	);
}

if ($bVatMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'VAT_ID',
		'name' => GetMessage('CRM_FIELD_VAT_ID'),
		'type' => 'label',
		'params' => array(),
		'value' => htmlspecialcharsbx((isset($product['VAT_ID']) && $product['VAT_ID'] > 0) ? $vatRateListItems[$product['VAT_ID']] : $vatRateListItems['']),
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'VAT_INCLUDED',
		'name' => GetMessage('CRM_FIELD_VAT_INCLUDED'),
		'type' => 'label',
		'params' => array(),
		'value' => GetMessage(isset($product['VAT_INCLUDED']) && $product['VAT_INCLUDED'] == 'Y' ? 'MAIN_YES' : 'MAIN_NO'),
		'isTactile' => true
	);
}

$measure = \Bitrix\Crm\Measure::getMeasureById((int)$product['MEASURE']);
$measureName = (!empty($measure) ? $measure['SYMBOL'] : GetMessage('CRM_MEASURE_NOT_SELECTED'));
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'MEASURE',
	'name' => GetMessage('CRM_FIELD_MEASURE'),
	'type' => 'label',
	'params' => array(),
	'value' => htmlspecialcharsbx($measureName),
	'isTactile' => true
);

$productSectionID = isset($product['SECTION_ID']) ? $product['SECTION_ID'] : 0;
$productSectionName = '';
if($productSectionID > 0)
{
	$sectionListItems = array();
	$rsSection = CIBlockSection::GetByID($productSectionID);
	if($arSection = $rsSection->Fetch())
	{
		$productSectionName = $arSection['NAME'];
	}
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECTION',
	'name' => GetMessage('CRM_FIELD_SECTION'),
	'type' => 'label',
	'value' => htmlspecialcharsbx(empty($productSectionName) ? GetMessage('CRM_SECTION_NOT_SELECTED') : $productSectionName),
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' => GetMessage('CRM_FIELD_SORT'),
	'type' => 'label',
	'params' => array(),
	'value' => isset($product['SORT']) ? $product['SORT'] : '',
	'isTactile' => true
);

$arFields = array(
	'PREVIEW_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'),
	'DETAIL_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE')
);
$html = '';
$obFileControl = $obFile = null;
foreach ($arFields as $fieldID => $fieldName)
{
	$html = '';
	if (isset($product['~'.$fieldID]))
	{
		$obFile = new CCrmProductFile(
			$arResult['PRODUCT_ID'],
			$fieldID,
			$product['~'.$fieldID]
		);

		$obFileControl = new CCrmProductFileControl($obFile, $fieldID);

		$html = '<nobr>'.$obFileControl->GetHTML(array(
				'show_input' => false,
				'max_size' => 102400,
				'max_width' => 150,
				'max_height' => 150,
				'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],
				'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
				'download_text' => GetMessage("CRM_PRODUCT_PROP_DOWNLOAD"),
			)).'</nobr>';
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => $fieldID,
		'name' => $fieldName,
		'type' => 'custom',
		'value' => $html,
		'isTactile' => true,
		'isHidden' => !($html <> '')
	);
}
unset($arFields, $fieldID, $fieldName, $obFile, $obFileControl, $html);

/*if($FIELD_ID == "PREVIEW_PICTURE" || $FIELD_ID == "DETAIL_PICTURE")
{
	$obFile = new CListFile(
		$arResult["IBLOCK_ID"],
		0, //section_id
		$arRow["data"]["ID"],
		$FIELD_ID,
		$value
	);
	$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

	$obFileControl = new CListFileControl($obFile, $FIELD_ID);

	$value = '<nobr>'.$obFileControl->GetHTML(array(
			'show_input' => false,
			'max_size' => 102400,
			'max_width' => 50,
			'max_height' => 50,
			'url_template' => $arParams["~LIST_FILE_URL"],
			'a_title' => GetMessage("CT_BLL_ENLARGE"),
			'download_text' => GetMessage("CT_BLL_DOWNLOAD"),
		)).'</nobr>';
}*/

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');