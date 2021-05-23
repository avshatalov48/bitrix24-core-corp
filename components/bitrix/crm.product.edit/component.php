<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['LIST_SECTION_ID'] = isset($_REQUEST['list_section_id']) ? intval($_REQUEST['list_section_id']) : 0;

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], '?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], '?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);

$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
if ($productID <= 0)
{
	$productIDParName = isset($arParams['PRODUCT_ID_PAR_NAME']) ? strval($arParams['PRODUCT_ID_PAR_NAME']) : '';
	if ($productIDParName == '')
	{
		$productIDParName = 'product_id';
	}

	$productID = isset($_REQUEST[$productIDParName]) ? intval($_REQUEST[$productIDParName]) : 0;
}
$arResult['PRODUCT_ID'] = $productID;

$isCopyMode = $arResult['IS_COPY_MODE'] = (isset($_REQUEST['copy']) && !empty($_REQUEST['copy']));
$isEditMode = (!$isCopyMode && $productID > 0);

$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_PRODUCT_LIST'],
	array(
		'section_id' => isset($arResult['LIST_SECTION_ID'])
			? intval($arResult['LIST_SECTION_ID'])
			: 0
	)
);

$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;

$bVatMode = CCrmTax::isVatMode();

$arVatRatesListItems = array();
if ($bVatMode)
	$arVatRatesListItems = CCrmVat::GetVatRatesListItems();

// measure list items
$arResult['MEASURE_LIST_ITEMS'] = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
$measures = \Bitrix\Crm\Measure::getMeasures(0);
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
$product = [];

if (check_bitrix_sessid())
{
	$bAjax = isset($_POST['ajax']);
	$bAjaxSubmit = isset($_POST['ajaxSubmit']);
	if ($_SERVER['REQUEST_METHOD'] == 'POST'
		&& (
			$bAjax
			|| $bAjaxSubmit
			|| isset($_POST['saveAndView'])
			|| isset($_POST['saveAndAdd'])
			|| isset($_POST['apply'])
		))
	{
		if ($bAjax || $bAjaxSubmit)
		{
			CUtil::JSPostUnescape();
			CUtil::decodeURIComponent($_FILES);
		}

		$errors = array();
		$postProductID = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
		$srcProductFields = [];

		if ($isCopyMode && $postProductID > 0)
		{
			$srcProductFields = CCrmProduct::GetByID($postProductID);
		}

		if (isset($_POST['NAME']))
		{
			$productFields['NAME'] = trim($_POST['NAME']);
			if(mb_strlen($productFields['NAME']) > 255)
			{
				$errors[] = GetMessage('CRM_PRODUCT_NAME_IS_TOO_LONG');
			}
		}
		else if (isset($srcProductFields['~NAME']))
		{
			$productFields['NAME'] = trim($srcProductFields['~NAME']);
		}

		$check = false;
		if (isset($_POST['DESCRIPTION']))
		{
			$description = isset($_POST['DESCRIPTION']) ? trim($_POST['DESCRIPTION']) : '';
			$productFields['DESCRIPTION_TYPE'] = 'text';
			$isNeedSanitize = (!$bAjaxSubmit && $description !== '' && (mb_strpos($description, '<') !== false));
			if ($isNeedSanitize)
			{
				$description = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($description);
				$productFields['DESCRIPTION_TYPE'] = 'html';
			}
			$productFields['DESCRIPTION'] = $description;
			unset($description, $isNeedSanitize);
		}
		else if (isset($srcProductFields['~DESCRIPTION']))
		{
			$productFields['DESCRIPTION'] = $srcProductFields['~DESCRIPTION'];
			if (isset($srcProductFields['~DESCRIPTION_TYPE']))
			{
				$productFields['DESCRIPTION_TYPE'] = $srcProductFields['~DESCRIPTION_TYPE'];
			}
		}

		if (isset($_POST['ACTIVE']))
		{
			$productFields['ACTIVE'] = $_POST['ACTIVE'] == 'Y' ? 'Y' : 'N';
		}
		else if (isset($srcProductFields['~ACTIVE']))
		{
			$productFields['ACTIVE'] = $srcProductFields['~ACTIVE'];
		}

		if (isset($_POST['CURRENCY']))
		{
			$productFields['CURRENCY_ID'] = strval($_POST['CURRENCY']);
		}
		else if (isset($srcProductFields['~CURRENCY']))
		{
			$productFields['CURRENCY'] = $srcProductFields['~CURRENCY'];
		}

		if (isset($_POST['PRICE']))
		{
			$productFields['PRICE'] = CCrmProductHelper::parseFloat($_POST['PRICE'], 2);
		}
		else if (isset($srcProductFields['~PRICE']))
		{
			$productFields['PRICE'] = $srcProductFields['~PRICE'];
		}

		if($postProductID <= 0)
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
			else if (isset($srcProductFields['~VAT_ID']))
			{
				$productFields['VAT_ID'] = $srcProductFields['~VAT_ID'];
			}

			if (isset($_POST['VAT_INCLUDED']))
			{
				$productFields['VAT_INCLUDED'] = ($_POST['VAT_INCLUDED'] === 'Y' || $_POST['VAT_INCLUDED'] === 'on') ? 'Y' : 'N';
			}
			else if (isset($srcProductFields['~VAT_INCLUDED']))
			{
				$productFields['VAT_INCLUDED'] = $srcProductFields['~VAT_INCLUDED'];
			}
		}

		if (isset($_POST['MEASURE']))
		{
			$productFields['MEASURE'] = intval($_POST['MEASURE']);
		}
		else if (isset($srcProductFields['~MEASURE']))
		{
			$productFields['MEASURE'] = $srcProductFields['~MEASURE'];
		}

		if (isset($_POST['SECTION']))
		{
			$productFields['SECTION_ID'] = intval($_POST['SECTION']);
		}
		else if (isset($srcProductFields['~SECTION']))
		{
			$productFields['SECTION_ID'] = $srcProductFields['~SECTION'];
		}

		if (isset($_POST['SORT']))
		{
			$productFields['SORT'] = intval($_POST['SORT']);
		}
		else if (isset($srcProductFields['~SORT']))
		{
			$productFields['SORT'] = $srcProductFields['~SORT'];
		}

		foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $fieldID)
		{
			$delFile = (isset($_POST[$fieldID . '_del']) && $_POST[$fieldID . '_del'] == 'Y');
			if (is_array($_FILES[$fieldID])
				&& ((!isset($_FILES[$fieldID]['error']) || intval($_FILES[$fieldID]['error']) === 0)))
			{
				$productFields[$fieldID] = $_FILES[$fieldID];
			}
			else if (!$delFile && isset($srcProductFields['~'.$fieldID]) && $srcProductFields['~'.$fieldID] > 0)
			{
				$fileInfo = CFile::MakeFileArray($srcProductFields['~'.$fieldID]);
				if (is_array($fileInfo))
				{
					$productFields[$fieldID] = $fileInfo;
				}
				else
				{
					$productFields[$fieldID] = [];
				}
				unset($fileInfo);
			}
			else
			{
				$productFields[$fieldID] = [];
			}
			if (!$isCopyMode && $delFile)
			{
				$productFields[$fieldID]['del'] = 'Y';
			}
		}
		unset($delFile);

		if ($postProductID <= 0 || $isCopyMode)
		{
			// Setup catalog ID for new product
			$productFields['CATALOG_ID'] = $catalogID > 0 ? $catalogID : CCrmCatalog::EnsureDefaultExists();
		}

		// Product properties values
		$arPropsValues = array();
		foreach ($arResult['PROPS'] as $propID => $arProp)
		{
			if ($arProp['PROPERTY_TYPE'] === 'F')
			{
				if (isset($_POST[$propID.'_del']))
				{
					$arDel = $_POST[$propID.'_del'];
				}
				else
				{
					$arDel = array();
				}
				$arPropsValues[$arProp['ID']] = array();
				if (isset($_FILES[$propID]))
				{
					CFile::ConvertFilesToPost($_FILES[$propID], $arPropsValues[$arProp['ID']]);
					foreach ($arPropsValues[$arProp['ID']] as $fileID => $arFile)
					{
						if (
							isset($arDel[$fileID])
							&& (
								(!is_array($arDel[$fileID]) && $arDel[$fileID]=='Y')
								|| (is_array($arDel[$fileID]) && $arDel[$fileID]['VALUE'] === 'Y')
							)
						)
						{
							if (isset($arPropsValues[$arProp['ID']][$fileID]['VALUE']))
							{
								$arPropsValues[$arProp['ID']][$fileID]['VALUE']['del'] = 'Y';
							}
							else
							{
								$arPropsValues[$arProp['ID']][$fileID]['del'] = 'Y';
							}
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
							if($value['VALUE'] <> '')
							{
								$arPropsValues[$arProp['ID']][$key] = doubleval($value['VALUE']);
							}
						}
						else
						{
							if($value <> '')
							{
								$arPropsValues[$arProp['ID']][$key] = doubleval($value);
							}
						}
					}
				}
				else
				{
					if (is_array($_POST[$propID]))
					{
						if($_POST[$propID]['VALUE'] <> '')
						{
							$arPropsValues[$arProp['ID']] = doubleval($_POST[$propID]['VALUE']);
						}
					}
					else
					{
						if($_POST[$propID] <> '')
						{
							$arPropsValues[$arProp['ID']] = doubleval($_POST[$propID]);
						}
					}
				}
			}
			else if (array_key_exists($propID, $_POST))
			{
				$arPropsValues[$arProp['ID']] = $_POST[$propID];
			}
		}
		if(!empty($arPropsValues) || $isCopyMode)
		{
			$productFields['PROPERTY_VALUES'] = $arPropsValues;
			if($postProductID > 0)
			{
				// We have to read properties from database in order not to delete its values
				$dbPropV = CIBlockElement::GetProperty(
					$catalogID,
					$postProductID,
					'sort', 'asc',
					array('ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
				);
				while($arPropV = $dbPropV->Fetch())
				{
					$propID = $arPropV['ID'];
					$propValId = $arPropV['PROPERTY_VALUE_ID'];

					if($arPropV['PROPERTY_TYPE'] === 'F')
					{
						if ($isCopyMode)
						{
							$propVal = null;
							if (is_array($arPropsValues[$arPropV['ID']][$propValId]['VALUE']))
							{
								$propVal = &$arPropsValues[$arPropV['ID']][$propValId]['VALUE'];
							}
							if (!is_array($productFields['PROPERTY_VALUES'][$arPropV['ID']][$propValId]['VALUE']))
							{
								$productFields['PROPERTY_VALUES'][$arPropV['ID']][$propValId]['VALUE'] = array();
							}
							$fileUploaded = false;
							$deleteOldFile = false;
							if (is_array($propVal) && !empty($propVal))
							{
								if (!isset($propVal['error']) || intval($propVal['error']) === 0)
								{
									$fileUploaded = true;
								}
								if (isset($propVal['del']) && $propVal['del'] == 'Y')
								{
									$deleteOldFile = true;
								}
							}
							if ($fileUploaded)
							{
								$productFields['PROPERTY_VALUES'][$arPropV['ID']][$propValId]['VALUE'] = $propVal;
							}
							else if (!$deleteOldFile)
							{
								$fileInfo = CFile::MakeFileArray($arPropV['VALUE']);
								if (is_array($fileInfo))
								{
									$productFields['PROPERTY_VALUES'][$arPropV['ID']][$propValId]['VALUE'] = $fileInfo;
								}
								unset($fileInfo);
							}
							unset($fileUploaded, $deleteOldFile, $propVal);
						}
					}
					else if(!array_key_exists($arPropV['ID'], $arPropsValues))
					{
						if(!array_key_exists($arPropV['ID'], $productFields['PROPERTY_VALUES']))
							$productFields['PROPERTY_VALUES'][$arPropV['ID']] = array();

						$productFields['PROPERTY_VALUES'][$arPropV['ID']][$propValId] = array(
							'VALUE' => $arPropV['VALUE'],
							'DESCRIPTION' => $arPropV['DESCRIPTION'],
						);
					}

					unset($propValId);
				}
			}
		}
		unset($srcProductFields);

		if(empty($errors))
		{
			if (!$isCopyMode && $postProductID > 0)
			{
				if (!CCrmProduct::Update($postProductID, $productFields))
				{
					$err = CCrmProduct::GetLastError();
					if ($err === '')
					{
						$err = GetMessage('CRM_PRODUCT_UPDATE_UNKNOWN_ERROR');
					}
					$errors[] = $err;
				}
			}
			else
			{
				$postProductID = CCrmProduct::Add($productFields);
				if (!$postProductID)
				{
					$err = CCrmProduct::GetLastError();
					if ($err === '')
					{
						$err = GetMessage('CRM_PRODUCT_ADD_UNKNOWN_ERROR');
					}
					$errors[] = $err;
				}
			}
		}

		if ($bAjax || $bAjaxSubmit)
		{
			$APPLICATION->RestartBuffer();
			$ajaxResponse = array(
				'err' => '',
				'productId' => 0,
				'productData' => array()
			);
			if (!empty($errors))
				$ajaxResponse['err'] = implode("\n", $errors);
			else
			{
				$ajaxResponse['productId'] = $postProductID;
				$dbRes = CCrmProduct::GetList(array(), array('ID' => $postProductID, '~REAL_PRICE' => true), array('ID', 'NAME', 'ACTIVE', 'PRICE', 'CURRENCY_ID', 'MEASURE', 'VAT_ID', 'VAT_INCLUDED'), array('nTopCount' => 1));
				if ($row = $dbRes->Fetch())
				{
					if ($row['ACTIVE'] === 'Y')
					{
						$currencyTo = isset($_POST['currencyTo']) ? $_POST['currencyTo'] : '';
						$currencyFrom = isset($row['CURRENCY_ID']) ? $row['CURRENCY_ID'] : '';
						if ($currencyFrom <> '' && $currencyTo <> '' && $currencyFrom !== $currencyTo)
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
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
			exit();
		}
		else
		{
			if (!empty($errors))
			{
				ShowError(implode("<br/>", $errors));
				$bVarsFromForm = true;
			}
			else
			{
				$redirectUrl = '';
				if (isset($_POST['apply']))
					$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_EDIT'],
						array('product_id' => $postProductID));
				else if (isset($_POST['saveAndAdd']))
					$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_EDIT'],
						array('product_id' => 0));
				else
					$redirectUrl = $arResult['BACK_URL'];

				LocalRedirect(
					CHTTP::urlAddParams(
						$redirectUrl,
						array(
							'list_section_id' => isset($arResult['LIST_SECTION_ID']) ? $arResult['LIST_SECTION_ID'] : 0
						)
					)
				);
			}
		}
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$err = '';
		$postProductID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
		$product = $postProductID > 0 ? CCrmProduct::GetByID($postProductID, true) : null;
		if ($product)
		{
			if (!CCrmProduct::Delete($postProductID))
			{
				$err = CCrmProduct::GetLastError();
				if (!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_DELETE_UNKNOWN_ERROR');
				}
			}
		}

		if (isset($err[0]))
		{
			ShowError($err);
			$bVarsFromForm = true;
		}
		else
		{
			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					array('section_id' => isset($arResult['LIST_SECTION_ID']) ? $arResult['LIST_SECTION_ID'] : 0)
				)
			);
		}
	}
}

if ($productID > 0)
{
	if (!($product = CCrmProduct::GetByID($productID, true)))
	{
		ShowError(GetMessage('CRM_PRODUCT_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if ($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus('404 Not Found');
		}
		return;
	}
}

if (isset($productFields['NAME']))
{
	$product['~NAME'] = $productFields['NAME'];
	$product['NAME'] = htmlspecialcharsbx($productFields['NAME']);
}

if (isset($productFields['DESCRIPTION']))
{
	$product['~DESCRIPTION'] = $productFields['DESCRIPTION'];
	$product['DESCRIPTION'] = htmlspecialcharsbx($productFields['DESCRIPTION']);
}

if (isset($productFields['DESCRIPTION_TYPE']))
{
	$product['~DESCRIPTION_TYPE'] = $productFields['DESCRIPTION_TYPE'];
	$product['DESCRIPTION_TYPE'] = htmlspecialcharsbx($productFields['DESCRIPTION_TYPE']);
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

$arResult['PRODUCT'] = $product;

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

// FIELDS
$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'product_info',
	'name' => GetMessage('CRM_SECTION_PRODUCT_INFO'),
	'type' => 'section'
);

if ($isEditMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ID',
		'name' => 'ID',
		'params' => array('size' => 50),
		'value' => $product['ID'],
		'type' => 'label'
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_PRODUCT_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($product['~NAME']) ? $product['~NAME'] : '',
	'required' => true
);

$description = isset($product['~DESCRIPTION']) ? $product['~DESCRIPTION'] : '';
$descriptionType = (isset($product['~DESCRIPTION_TYPE']) && $product['~DESCRIPTION_TYPE'] === 'html') ? 'html' : 'text';
if ($descriptionType === 'text')
{
	$description = nl2br($description);
}
$sanitizer = new \CBXSanitizer();
$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
$description = $sanitizer->SanitizeHtml($description);
unset($sanitizer);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_PRODUCT_DESCRIPTION'),
	'params' => array(),
	'type' => 'lhe',
	'componentParams' => array(
		'inputName' => 'DESCRIPTION',
		'inputId' => 'DESCRIPTION',
		'height' => '180',
		'content' => $description,
		'bReplaceTabToNbsp' => false,
		'bUseFileDialogs' => false,
		'bFloatingToolbar' => false,
		'bArisingToolbar' => false,
		'bResizable' => true,
		'bSaveOnBlur' => true,
		'toolbarConfig' => array(
			'Bold', 'Italic', 'Underline', 'Strike',
			'BackColor', 'ForeColor',
			'CreateLink', 'DeleteLink',
			'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
		)
	)
);
unset($description);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' => GetMessage('CRM_FIELD_ACTIVE'),
	'type' => 'checkbox',
	'params' => array(),
	'value' => isset($product['ACTIVE']) ? $product['ACTIVE'] : ($isEditMode ? 'N' : 'Y')
);


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY',
	'name' => GetMessage('CRM_FIELD_CURRENCY'),
	'type' => 'list',
	'items' => CCrmCurrencyHelper::PrepareListItems(),
	'value' => isset($product['CURRENCY_ID']) ? $product['CURRENCY_ID'] : $baseCurrencyID
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRICE',
	'name' => GetMessage('CRM_FIELD_PRICE'),
	'type' => 'text',
	'params' => array(),
	'value' => isset($product['PRICE']) ? strval(round(doubleval($product['PRICE']), 2)) : ''
);

if ($bVatMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'VAT_ID',
		'name' => GetMessage('CRM_FIELD_VAT_ID'),
		'type' => 'list',
		'items' => $arVatRatesListItems,
		'value' => isset($product['VAT_ID']) ? $product['VAT_ID'] : ''
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'VAT_INCLUDED',
		'name' => GetMessage('CRM_FIELD_VAT_INCLUDED'),
		'type' => 'checkbox',
		'value' => isset($product['VAT_INCLUDED']) ? $product['VAT_INCLUDED'] : ''
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'MEASURE',
	'name' => GetMessage('CRM_FIELD_MEASURE'),
	'type' => 'list',
	'items' => $arResult['MEASURE_LIST_ITEMS'],
	'value' => isset($product['MEASURE']) ? $product['MEASURE'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECTION',
	'name' => GetMessage('CRM_FIELD_SECTION'),
	'type' => 'list',
	'items' => CCrmProductHelper::PrepareSectionListItems($catalogID, true),
	'value' => isset($product['SECTION_ID']) ? $product['SECTION_ID'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' => GetMessage('CRM_FIELD_SORT'),
	'type' => 'text',
	'params' => array(),
	'value' => isset($product['SORT']) ? $product['SORT'] : '100'
);

$arFields = array(
	'PREVIEW_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'),
	'DETAIL_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE')
);
$html = '';
$obFileControl = $obFile = null;
foreach ($arFields as $fieldID => $fieldName)
{
	$obFile = new CCrmProductFile(
		$productID,
		$fieldID,
		$product['~'.$fieldID]
	);

	$obFileControl = new CCrmProductFileControl($obFile, $fieldID);

	$html = $obFileControl->GetHTML(array(
		'max_size' => 102400,
		'max_width' => 150,
		'max_height' => 150,
		'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],
		'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
		'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
	));

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => $fieldID,
		'name' => $fieldName,
		'type' => 'custom',
		'value' => $html
	);
}
unset($arFields, $fieldID, $fieldName, $obFile, $obFileControl, $html);

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
		else if ($productID)
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
				if (is_array($arProp['DEFAULT_VALUE']) || mb_strlen($arProp['DEFAULT_VALUE']))
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
		else if ($productID)
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
		if ($productID)
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
		else if ($productID)
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
		else if ($productID)
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
				if (is_array($arProp['DEFAULT_VALUE']) || mb_strlen($arProp['DEFAULT_VALUE']))
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
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');