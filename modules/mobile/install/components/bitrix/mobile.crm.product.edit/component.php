<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;

$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['product_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_REQUEST['product_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

$isEditMode = false;
if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;

if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmProduct::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($isEditMode)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmProduct::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmProduct::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($isCopyMode)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmProduct::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmProduct::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$bVatMode = CCrmTax::isVatMode();

$arVatRatesListItems = array();
if ($bVatMode)
	$arVatRatesListItems = CCrmVat::GetVatRatesListItems();

// measure list items
$arResult['MEASURE_LIST_ITEMS'] = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
$measures = \Bitrix\Crm\Measure::getMeasures(100);
if (is_array($measures))
{
	foreach ($measures as $measure)
		$arResult['MEASURE_LIST_ITEMS'][$measure['ID']] = $measure['SYMBOL'];
	unset($measure);
}
unset($measures);

// Product properties
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'edit');
$arResult['PROP_USER_TYPES'] = $arPropUserTypeList;
$arProps = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
$arResult['PROPS'] = $arProps;

$baseCurrencyID = CCrmCurrency::GetBaseCurrencyID();
$bVarsFromForm = false;
$productFields = array();
if (check_bitrix_sessid())
{
	$bAjax = isset($_POST['ajax']);
	$bAjaxSubmit = isset($_POST['ajaxSubmit']);
	if (
		$_SERVER['REQUEST_METHOD'] == 'POST'
		&& (
			$bAjax
			|| $bAjaxSubmit
			|| isset($_POST['save'])
		)
	)
	{
		CUtil::JSPostUnescape();
		CUtil::decodeURIComponent($_FILES);

		$productID = $entityID;
		if (isset($_POST['NAME']))
		{
			$productFields['NAME'] = trim($_POST['NAME']);
		}

		if (isset($_POST['DESCRIPTION']))
		{
			$productFields['DESCRIPTION'] = $_POST['DESCRIPTION'];
		}

		if (isset($_POST['ACTIVE']))
		{
			$productFields['ACTIVE'] = $_POST['ACTIVE'] == 'Y' ? 'Y' : 'N';
		}

		if (isset($_POST['CURRENCY']))
		{
			$productFields['CURRENCY_ID'] = strval($_POST['CURRENCY']);
		}

		if (isset($_POST['PRICE']))
		{
			$productFields['PRICE'] = round(doubleval($_POST['PRICE']), 2);
		}

		if($productID <= 0)
		{
			if(empty($productFields['CURRENCY_ID']))
			{
				$productFields['CURRENCY_ID'] = $baseCurrencyID;
			}

			if(empty($productFields['PRICE']))
			{
				$productFields['PRICE'] = 0;
			}
		}

		if ($bVatMode)
		{
			if (isset($_POST['VAT_ID']))
			{
				$productFields['VAT_ID'] = intval($_POST['VAT_ID']);
			}

			if (isset($_POST['VAT_INCLUDED']))
			{
				$productFields['VAT_INCLUDED'] = ($_POST['VAT_INCLUDED'] === 'Y' || $_POST['VAT_INCLUDED'] === 'on') ? 'Y' : 'N';
			}
		}

		if (isset($_POST['MEASURE']))
		{
			$productFields['MEASURE'] = intval($_POST['MEASURE']);
		}

		$productFields['SECTION_ID'] = isset($_POST['SECTION']) ? intval($_POST['SECTION']) : 0;

		if (isset($_POST['SORT']))
		{
			$productFields['SORT'] = intval($_POST['SORT']);
		}

		foreach (array('DETAIL_PICTURE', 'PREVIEW_PICTURE') as $fieldID)
		{
			$productFields[$fieldID] = $_FILES[$fieldID];
			if (isset($_POST[$fieldID . '_del']) && $_POST[$fieldID . '_del'] == 'Y')
				$productFields[$fieldID]['del'] = 'Y';
		}

		if ($productID <= 0)
		{
			// Setup catalog ID for new product
			$productFields['CATALOG_ID'] = $catalogID > 0 ? $catalogID : CCrmCatalog::EnsureDefaultExists();
		}

		// Product properties values
		$arPropsValues = array();
		foreach ($arResult['PROPS'] as $propID => $arProp)
		{
			if ($arProp['PROPERTY_TYPE'] == 'F')
			{
				if (isset($_POST[$propID.'_del']))
					$arDel = $_POST[$propID.'_del'];
				else
					$arDel = array();
				$arPropsValues[$arProp['ID']] = array();
				if (isset($_FILES[$propID]))
				{
					CFile::ConvertFilesToPost($_FILES[$propID], $arPropsValues[$arProp['ID']]);
					foreach ($arPropsValues[$arProp['ID']] as $file_id => $arFile)
					{
						if (
							isset($arDel[$file_id])
							&& (
								(!is_array($arDel[$file_id]) && $arDel[$file_id]=='Y')
								|| (is_array($arDel[$file_id]) && $arDel[$file_id]['VALUE']=='Y')
							)
						)
						{
							if (isset($arPropsValues[$arProp['ID']][$file_id]['VALUE']))
								$arPropsValues[$arProp['ID']][$file_id]['VALUE']['del'] = 'Y';
							else
								$arPropsValues[$arProp['ID']][$file_id]['del'] = 'Y';
						}
					}
				}
			}
			elseif ($arProp['PROPERTY_TYPE'] == 'N')
			{
				if (is_array($_POST[$propID]) && !array_key_exists('VALUE', $_POST[$propID]))
				{
					$arPropsValues[$arProp['ID']] = array();
					foreach ($_POST[$propID] as $key=>$value)
					{
						if (is_array($value))
						{
							if (strlen($value['VALUE']))
								$arPropsValues[$arProp['ID']][$key] = doubleval($value['VALUE']);
						}
						else
						{
							if (strlen($value))
								$arPropsValues[$arProp['ID']][$key] = doubleval($value);
						}
					}
				}
				else
				{
					if (is_array($_POST[$propID]))
					{
						if (strlen($_POST[$propID]['VALUE']))
							$arPropsValues[$arProp['ID']] = doubleval($_POST[$propID]['VALUE']);
					}
					else
					{
						if (strlen($_POST[$propID]))
							$arPropsValues[$arProp['ID']] = doubleval($_POST[$propID]);
					}
				}
			}
			else
			{
				$arPropsValues[$arProp['ID']] = $_POST[$propID];
			}
		}
		if(count($arPropsValues))
		{
			$productFields['PROPERTY_VALUES'] = $arPropsValues;
			if($productID > 0)
			{
				//We have to read properties from database in order not to delete its values
				$dbPropV = CIBlockElement::GetProperty(
					$catalogID,
					$productID,
					'sort', 'asc',
					array('ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
				);
				while($arPropV = $dbPropV->Fetch())
				{
					if (isset($arPropV['USER_TYPE']) && !empty($arPropV['USER_TYPE'])
						&& !array_key_exists($arPropV['USER_TYPE'], $arPropUserTypeList))
						continue;

					if($arPropV['PROPERTY_TYPE'] != 'F' && !array_key_exists($arPropV['ID'], $arPropsValues))
					{
						if(!array_key_exists($arPropV['ID'], $productFields['PROPERTY_VALUES']))
							$productFields['PROPERTY_VALUES'][$arPropV['ID']] = array();

						$productFields['PROPERTY_VALUES'][$arPropV['ID']][$arPropV['PROPERTY_VALUE_ID']] = array(
							'VALUE' => $arPropV['VALUE'],
							'DESCRIPTION' => $arPropV['DESCRIPTION'],
						);
					}
				}
			}
		}

		$err = '';
		if ($productID > 0)
		{
			if (!CCrmProduct::Update($productID, $productFields))
			{
				$err = CCrmProduct::GetLastError();
				if (!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_UPDATE_UNKNOWN_ERROR');
				}
			}
		}
		else
		{
			$productID = CCrmProduct::Add($productFields);
			if (!$productID)
			{
				$err = CCrmProduct::GetLastError();
				if (!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_ADD_UNKNOWN_ERROR');
				}
			}
		}

		/*if ($bAjax || $bAjaxSubmit)
		{
			$APPLICATION->RestartBuffer();
			$ajaxResponse = array(
				'err' => '',
				'productId' => 0,
				'productData' => array()
			);
			if (isset($err[0]))
				$ajaxResponse['err'] = $err;
			else
			{
				$ajaxResponse['productId'] = $productID;
				$dbRes = CCrmProduct::GetList(array(), array('ID' => $productID, '~REAL_PRICE' => true), array('ID', 'NAME', 'ACTIVE', 'PRICE', 'CURRENCY_ID', 'MEASURE', 'VAT_ID', 'VAT_INCLUDED'), array('nTopCount' => 1));
				if ($row = $dbRes->Fetch())
				{
					if ($row['ACTIVE'] === 'Y')
					{
						$currencyTo = isset($_POST['currencyTo']) ? $_POST['currencyTo'] : '';
						$currencyFrom = isset($row['CURRENCY_ID']) ? $row['CURRENCY_ID'] : '';
						if (strlen($currencyFrom) > 0 && strlen($currencyTo) > 0 && $currencyFrom !== $currencyTo)
							$row['PRICE'] = CCrmCurrency::ConvertMoney(doubleval($row['PRICE']), $currencyFrom, $currencyTo);
						$ajaxResponse['productData'] = $row;
						$measureInfo = array();
						if (isset($row['MEASURE']) && intval($row['MEASURE']) > 0)
						{
							$measureInfo = \Bitrix\Crm\Measure::getProductMeasures(intval($row['ID']));
							$measureInfo = $measureInfo[intval($row['ID'])][0];
						}
						else
						{
							$measureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();
						}
						if (is_array($measureInfo) && isset($measureInfo['CODE']) && isset($measureInfo['SYMBOL']))
						{
							$ajaxResponse['measureData'] = array(
								'code' => $measureInfo['CODE'],
								'name' => $measureInfo['SYMBOL']
							);
						}
					}
				}
				unset($dbRes, $row);
			}
			Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
			echo CUtil::PhpToJsObject($ajaxResponse);
			exit();
		}
		else
		{*/
			$arJsonData = array();
			if (isset($err) && !empty($err))
			{
				$arJsonData = array("error" => preg_replace("/<br( )?(\/)?>/i", "\n", $err));
			}
			else
			{
				$arJsonData = array("success" => "Y", "itemId" => $productID);
			}

			$APPLICATION->RestartBuffer();
			echo \Bitrix\Main\Web\Json::encode($arJsonData);
			CMain::FinalActions();
			die();
		//}
	}
}

$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
if ($productID <= 0)
{
	$arResult['MODE'] = 'CREATE';

	$productIDParName = isset($arParams['PRODUCT_ID_PAR_NAME']) ? strval($arParams['PRODUCT_ID_PAR_NAME']) : '';
	if (strlen($productIDParName) == 0)
	{
		$productIDParName = 'product_id';
	}

	$productID = isset($_REQUEST[$productIDParName]) ? intval($_REQUEST[$productIDParName]) : 0;
}

$product = array();
if ($productID > 0)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	if (!($product = CCrmProduct::GetByID($productID, true)))
	{
		ShowError(GetMessage('CRM_PRODUCT_NOT_FOUND'));
		return;
	}
}

if (isset($productFields['NAME']))
{
	$product['NAME'] = $productFields['NAME'];
}

if (isset($productFields['DESCRIPTION']))
{
	$product['~DESCRIPTION'] = $productFields['DESCRIPTION'];
	$product['DESCRIPTION'] = htmlspecialcharsbx($productFields['DESCRIPTION']);
}

if (isset($productFields['ACTIVE']))
{
	$product['ACTIVE'] = $productFields['ACTIVE'];
}

if (isset($productFields['CURRENCY_ID']))
{
	$product['CURRENCY_ID'] = $productFields['CURRENCY_ID'];
}

if (isset($productFields['PRICE']))
{
	$product['PRICE'] = $productFields['PRICE'];
}

if ($bVatMode)
{
	if (isset($productFields['VAT_INCLUDED']))
	{
		$product['VAT_INCLUDED'] = $productFields['VAT_INCLUDED'];
	}

	if (isset($productFields['VAT_ID']))
	{
		$product['VAT_ID'] = $productFields['VAT_ID'];
	}
}

if (isset($productFields['MEASURE']))
{
	$product['MEASURE'] = $productFields['MEASURE'];
}

if (isset($productFields['SECTION_ID']))
{
	$product['SECTION_ID'] = $productFields['SECTION_ID'];
}
else if ($productID <= 0 && isset($arResult['LIST_SECTION_ID']))
{
	$product['SECTION_ID'] = intval($arResult['LIST_SECTION_ID']);
}

if (isset($productFields['SORT']))
{
	$product['SORT'] = $productFields['SORT'];
}

foreach (array('DETAIL_PICTURE', 'PREVIEW_PICTURE') as $fieldID)
{
	if (isset($productFields[$fieldID]))
		$product[$fieldID] = $productFields[$fieldID];
	else
		$product[$fieldID] = '';
}

$arResult['PRODUCT_ID'] = $productID;
$arResult['PRODUCT'] = $product;
$isEditMode = $productID > 0;

$arResult['CATALOG_TYPE_ID'] = CCrmCatalog::GetCatalogTypeID();
$arResult['CATALOG_ID'] = $catalogID =
	isset($product['CATALOG_ID'])
		? intval($product['CATALOG_ID'])
		: CCrmCatalog::EnsureDefaultExists();

$arResult['FORM_ID'] = 'CRM_PRODUCT_EDIT';
$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST';

// Product properties values
$arResult['PRODUCT_PROPS'] = array();
if ($productID > 0 && count($arProps) > 0)
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
	while ($arProperty = $rsProperties->Fetch())
	{
		if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
			&& !array_key_exists($arProperty['USER_TYPE'], $arPropUserTypeList))
			continue;

		$prop_id = $arProperty['ID'];
		if (!array_key_exists($prop_id, $arResult['PRODUCT_PROPS']))
		{
			$arResult['PRODUCT_PROPS'][$prop_id] = $arProperty;
			unset($arResult['PRODUCT_PROPS'][$prop_id]['DESCRIPTION']);
			unset($arResult['PRODUCT_PROPS'][$prop_id]['VALUE_ENUM_ID']);
			unset($arResult['PRODUCT_PROPS'][$prop_id]['VALUE_ENUM']);
			unset($arResult['PRODUCT_PROPS'][$prop_id]['VALUE_XML_ID']);
			$arResult['PRODUCT_PROPS'][$prop_id]['FULL_VALUES'] = array();
			$arResult['PRODUCT_PROPS'][$prop_id]['VALUES_LIST'] = array();
		}

		$arResult['PRODUCT_PROPS'][$prop_id]['FULL_VALUES'][$arProperty['PROPERTY_VALUE_ID']] = array(
			'VALUE' => $arProperty['VALUE'],
			'DESCRIPTION' => $arProperty['DESCRIPTION'],
		);
		$arResult['PRODUCT_PROPS'][$prop_id]['VALUES_LIST'][$arProperty['PROPERTY_VALUE_ID']] = $arProperty['VALUE'];
	}
	unset($rsProperties, $arProperty);
}

$arResult['PRODUCT_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['PRODUCT_EDIT_URL_TEMPLATE'],
	array('product_id' => $entityID)
);

// FIELDS
$arResult['FIELDS'] = array();

if ($isEditMode)
{
	$arResult['FIELDS'][] = array(
		'id' => 'ID',
		'name' => 'ID',
		'params' => array('size' => 50),
		'value' => $product['ID'],
		'type' => 'label'
	);
}

$arResult['FIELDS'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_PRODUCT_NAME'),
	'params' => array('size' => 50),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($product['~NAME']) ? $product['~NAME'] : '',
	'required' => true
);

$arResult['FIELDS'][] = array(
	'id' => 'DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_PRODUCT_DESCRIPTION'),
	'params' => array('size' => 50),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label',
	'value' => isset($product['~DESCRIPTION']) ? $product['~DESCRIPTION'] : ''
);

$arResult['FIELDS'][] = array(
	'id' => 'ACTIVE',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_ACTIVE')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($product['ACTIVE']) ? $product['ACTIVE'] : ($isEditMode ? 'N' : 'Y')
);

$arCurrencies = CCrmCurrencyHelper::PrepareListItems();
if ($arResult["IS_EDIT_PERMITTED"])
	$value = isset($product['CURRENCY_ID']) ? $product['CURRENCY_ID'] : $baseCurrencyID;
else
	$value = isset($product['CURRENCY_ID']) ? $arCurrencies[$product['CURRENCY_ID']] : $arCurrencies[$baseCurrencyID];

$arResult['FIELDS'][] = array(
	'id' => 'CURRENCY',
	'name' => GetMessage('CRM_FIELD_CURRENCY'),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arCurrencies,
	'value' => $value
);

$arResult['FIELDS'][] = array(
	'id' => 'PRICE',
	'name' => GetMessage('CRM_FIELD_PRICE'),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'params' => array(),
	'value' => isset($product['PRICE']) ? strval(round(doubleval($product['PRICE']), 2)) : ''
);

if ($bVatMode)
{
	$arResult['FIELDS'][] = array(
		'id' => 'VAT_ID',
		'name' => GetMessage('CRM_FIELD_VAT_ID'),
		'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
		'items' => $arVatRatesListItems,
		'value' => isset($product['VAT_ID']) ? $product['VAT_ID'] : ''
	);

	$arResult['FIELDS'][] = array(
		'id' => 'VAT_INCLUDED',
		'type' => 'checkbox',
		"items" => array(
			"Y" => GetMessage('CRM_FIELD_VAT_INCLUDED')
		),
		'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
		'value' => isset($product['VAT_INCLUDED']) ? $product['VAT_INCLUDED'] : ''
	);
}

$arResult['FIELDS'][] = array(
	'id' => 'MEASURE',
	'name' => GetMessage('CRM_FIELD_MEASURE'),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['MEASURE_LIST_ITEMS'],
	'value' => isset($product['MEASURE']) ? $product['MEASURE'] : ''
);

$arResult['FIELDS'][] = array(
	'id' => 'SECTION',
	'name' => GetMessage('CRM_FIELD_SECTION'),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => CCrmProductHelper::PrepareSectionListItems($catalogID, true),
	'value' => isset($product['SECTION_ID']) ? $product['SECTION_ID'] : ''
);

$arResult['FIELDS'][] = array(
	'id' => 'SORT',
	'name' => GetMessage('CRM_FIELD_SORT'),
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'params' => array(),
	'value' => isset($product['SORT']) ? $product['SORT'] : '100'
);

$arResult['FIELDS'][] = array(
	'id' => 'PREVIEW_PICTURE',
	'name' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'),
	'type' => 'file',
	'maxCount' => 1,
	'value' => $product['~PREVIEW_PICTURE']
);

$arResult['FIELDS'][] = array(
	'id' => 'DETAIL_PICTURE',
	'name' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE'),
	'type' => 'file',
	'maxCount' => 1,
	'value' => $product['~DETAIL_PICTURE']
);

// Product properties
$propsFormData = array();
foreach ($arProps as $propID => $arProp)
{
	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE']))
	{
		if ($bVarsFromForm && isset($_POST[$propID]))
		{
			$propsFormData[$propID] = $_POST[$propID];
		}
		else if ($arResult['PRODUCT_ID'])
		{
			if (isset($arResult['PRODUCT_PROPS'][$arProp['ID']]))
			{
				$propsFormData[$propID] = $arResult['PRODUCT_PROPS'][$arProp['ID']]['FULL_VALUES'];
				if ($arProp['MULTIPLE'] == 'Y')
					$propsFormData[$propID]['n0'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
			else
			{
				$propsFormData[$propID]['n0'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
		}
		else
		{
			$propsFormData[$propID] = array(
				'n0' => array(
					'VALUE' => $arProp['DEFAULT_VALUE'],
					'DESCRIPTION' => '',
				)
			);
			if ($arProp['MULTIPLE'] == 'Y')
			{
				if (is_array($arProp['DEFAULT_VALUE']) || strlen($arProp['DEFAULT_VALUE']))
					$propsFormData[$propID]['n1'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'L')
	{
		if ($bVarsFromForm && isset($_POST[$propID]))
		{
			$propsFormData[$propID] = $_POST[$propID];
		}
		else if ($arResult['PRODUCT_ID'])
		{
			if (isset($arResult['PRODUCT_PROPS'][$arProp['ID']]))
				$propsFormData[$propID] = $arResult['PRODUCT_PROPS'][$arProp['ID']]['VALUES_LIST'];
			else
				$propsFormData[$propID] = array();
		}
		else
		{
			$propsFormData[$propID] = array();
			$prop_enums = CIBlockProperty::GetPropertyEnum($arProp['ID']);
			while ($ar_enum = $prop_enums->Fetch())
				if ($ar_enum['DEF'] == 'Y')
					$propsFormData[$propID][] =$ar_enum['ID'];
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'F')
	{
		if ($arResult['PRODUCT_ID'])
		{
			if (isset($arResult['PRODUCT_PROPS'][$arProp['ID']]))
			{
				$propsFormData[$propID] = $arResult['PRODUCT_PROPS'][$arProp['ID']]['FULL_VALUES'];
				if ($arProp['MULTIPLE'] == 'Y')
					$propsFormData[$propID]['n0'] = array('VALUE' => $arProp['DEFAULT_VALUE'], 'DESCRIPTION' => '');
			}
			else
			{
				$propsFormData[$propID]['n0'] = array('VALUE' => $arProp['DEFAULT_VALUE'], 'DESCRIPTION' => '');
			}
		}
		else
		{
			$propsFormData[$propID] = array(
				'n0' => array('VALUE' => $arProp['DEFAULT_VALUE'], 'DESCRIPTION' => ''),
			);
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'G' || $arProp['PROPERTY_TYPE'] == 'E')
	{
		if ($bVarsFromForm && isset($_POST[$propID]))
		{
			$propsFormData[$propID] = $_POST[$propID];
		}
		else if ($arResult['PRODUCT_ID'])
		{
			if (isset($arResult['PRODUCT_PROPS'][$arProp['ID']]))
				$propsFormData[$propID] = $arResult['PRODUCT_PROPS'][$arProp['ID']]['VALUES_LIST'];
			else
				$propsFormData[$propID] = array();
		}
		else
		{
			$propsFormData[$propID] = array($arProp['DEFAULT_VALUE']);
		}
	}
	else//if ($arProp['PROPERTY_TYPE'] == 'S' || $arProp['PROPERTY_TYPE'] == 'N')
	{
		if ($bVarsFromForm && isset($_POST[$propID]))
		{
			$propsFormData[$propID] = $_POST[$propID];
		}
		else if ($arResult['PRODUCT_ID'])
		{
			if (isset($arResult['PRODUCT_PROPS'][$arProp['ID']]))
			{
				$propsFormData[$propID] = $arResult['PRODUCT_PROPS'][$arProp['ID']]['FULL_VALUES'];
				if ($arProp['MULTIPLE'] == 'Y')
					$propsFormData[$propID]['n0'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
			else
			{
				$propsFormData[$propID]['n0'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
		}
		else
		{
			$propsFormData[$propID] = array(
				'n0' => array('VALUE' => $arProp['DEFAULT_VALUE'], 'DESCRIPTION' => ''),
			);
			if ($arProp['MULTIPLE'] == 'Y')
			{
				if (is_array($arProp['DEFAULT_VALUE']) || strlen($arProp['DEFAULT_VALUE']))
					$propsFormData[$propID]['n1'] = array('VALUE' => '', 'DESCRIPTION' => '');
			}
		}
	}
}
$arResult['PROPS_FORM_DATA'] = array();
foreach($propsFormData as $key => $value)
{
	$arResult['PROPS_FORM_DATA']['~'.$key] = $value;
	if(is_array($value))
	{
		foreach($value as $key1 => $value1)
		{
			if(is_array($value1))
			{
				foreach($value1 as $key2 => $value2)
					if(!is_array($value2))
						$value[$key1][$key2] = htmlspecialcharsbx($value2);
			}
			else
			{
				$value[$key1] = htmlspecialcharsbx($value1);
			}
		}
		$arResult['PROPS_FORM_DATA'][$key] = $value;
	}
	else
	{
		$arResult['PROPS_FORM_DATA'][$key] = htmlspecialcharsbx($value);
	}
}

$this->IncludeComponentTemplate();
?>