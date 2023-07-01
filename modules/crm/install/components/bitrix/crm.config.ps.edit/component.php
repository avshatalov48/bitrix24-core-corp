<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Sale\PaySystem;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

$isSidePanel = (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y");

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/*
 * PATH_TO_PS_LIST
 * PATH_TO_PS_EDIT
 * PS_ID
 * PS_ID_PAR_NAME
 */

$arParams['PATH_TO_PS_LIST'] = CrmCheckPath('PATH_TO_PS_LIST', $arParams['PATH_TO_PS_LIST'], '');
$arParams['PATH_TO_PS_EDIT'] = CrmCheckPath('PATH_TO_PS_EDIT', $arParams['PATH_TO_PS_EDIT'], '?ps_id=#ps_id#&edit');

$psID = isset($arParams['PS_ID']) ? intval($arParams['PS_ID']) : 0;
$documentRoot = \Bitrix\Main\Application::getDocumentRoot();

if($psID <= 0)
{
	$psIDParName = isset($arParams['PS_ID_PAR_NAME']) ? strval($arParams['PS_ID_PAR_NAME']) : '';

	if($psIDParName == '')
		$psIDParName = 'ps_id';

	$psID = isset($_REQUEST[$psIDParName]) ? (int)$_REQUEST[$psIDParName] : 0;
}

$arPaySys = array();

if ($psID > 0)
{
	$arPaySys = \Bitrix\Sale\PaySystem\Manager::getById($psID);
	$personTypeIds = CSalePaySystem::getPaySystemPersonTypeIds($psID);
	$arPaySys['PARAMS'] = CSalePaySystemAction::getParamsByConsumer('PAYSYSTEM_'.$psID, $personTypeIds[0]);

	if (!$arPaySys)
	{
		ShowError(GetMessage('CRM_PS_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}
}

$arResult['PS_ID'] = $psID;
$arResult['PAY_SYSTEM'] = $arPaySys;

$arResult['FORM_ID'] = 'CRM_PS_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_PS_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_PS_LIST'],
	array()
);

if (check_bitrix_sessid())
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$psID = isset($_POST['ps_id']) ? (int)$_POST['ps_id'] : 0;
		$personTypeId = isset($_POST['PERSON_TYPE_ID']) ? intval($_POST['PERSON_TYPE_ID']) : 0;
		$handler = $_POST["ACTION_FILE"];
		$errorMessage = '';

		$arActFields = array(
			"PERSON_TYPE_ID" => $personTypeId,
			"ACTION_FILE" => CCrmPaySystem::getActionHandler($handler),
			"NEW_WINDOW" => (($_POST['NEW_WINDOW'] == "Y") ? "Y" : "N" ),
			"HAVE_PREPAY" => "N",
			"HAVE_RESULT" => "N",
			"ENTITY_REGISTRY_TYPE" => (mb_strpos($handler, 'quote_') !== false) ? REGISTRY_TYPE_CRM_QUOTE : REGISTRY_TYPE_CRM_INVOICE,
			"HAVE_ACTION" => "N",
			"HAVE_PAYMENT" => "N",
			"HAVE_RESULT_RECEIVE" => "N",
			"PS_MODE" => $_POST['PS_MODE']
		);

		if (isset($_POST['NAME']) && $_POST['NAME'] <> '')
		{
			$arActFields['NAME'] = trim($_POST['NAME']);
			$arActFields['PSA_NAME'] = $arActFields['NAME'];
		}
		else
		{
			$errorMessage .= GetMessage('CRM_PS_ERROR_NO_NAME').'<br>';
		}

		if ($handler === 'invoicedocument' && !$_POST['PS_MODE'])
		{
			$errorMessage .= GetMessage('CRM_PS_ERROR_DOCUMENT_TEMPLATE_EMPTY');
		}

		$arActFields['ACTIVE'] = (isset($_POST['ACTIVE']) && ($_POST['ACTIVE'] == "Y") ? "Y" : "N");
		$arActFields['SORT'] = (isset($_POST['SORT']) && ((int)$_POST['SORT'] > 0) ? (int)$_POST['SORT'] : 100);

		if (isset($_POST['DESCRIPTION']))
			$arActFields['DESCRIPTION'] = $_POST['DESCRIPTION'];

		if ($errorMessage == '')
		{
			if (isset($_POST["ACTION_FILE"]) && trim($_POST["ACTION_FILE"]) <> '')
				$actionFile = CCrmPaySystem::getActionPath($_POST["ACTION_FILE"]);
			else
				$errorMessage .= GetMessage("CRM_PS_EMPTY_SCRIP").".<br>";

			if (isset($actionFile))
			{
				$actionFile = str_replace("\\", "/", $actionFile);
				while (mb_substr($actionFile, mb_strlen($actionFile) - 1, 1) == "/")
					$actionFile = mb_substr($actionFile, 0, mb_strlen($actionFile) - 1);

				$pathToAction = $_SERVER["DOCUMENT_ROOT"].$actionFile;
				if (!file_exists($pathToAction))
					$errorMessage .= GetMessage("CRM_PS_NO_SCRIPT").".<br>";
			}

			if ($errorMessage === '')
			{
				$arActParams = array();

				if (mb_strpos($_POST['ACTION_FILE'], 'bill') !== 0 || empty($_POST['PS_MODE']))
				{
					if (isset($_POST['PS_ACTION_FIELDS_LIST']) && $_POST['PS_ACTION_FIELDS_LIST'] <> '')
					{
						$filedList = explode(",", $_POST['PS_ACTION_FIELDS_LIST']);

						$arPsActFields = CCrmPaySystem::getPSCorrespondence($_POST["ACTION_FILE"] ?: 'bill', $_POST["PS_MODE"]);
						CCrmPaySystem::rewritePSCorrByRqSource($personTypeId, $arPsActFields, array('PSA_CODE' => $handler));

						foreach ($filedList as $val)
						{
							$val = trim($val);

							if (empty($arPsActFields[$val]))
								continue;

							$typeTmp = $_POST["TYPE_".$val] ?? null;
							$valueTmp = $_POST["VALUE1_".$val] ?? null;
							$value2Tmp = $_POST["VALUE2_".$val] ?? null;

							if (is_string($typeTmp) && $typeTmp == '')
							{
								$valueTmp = $value2Tmp;
							}

							if ($val == 'USER_COLUMNS')
							{
								if (is_array($value2Tmp))
								{
									if (!is_array($valueTmp))
									{
										$valueTmp = [];
									}

									$valueTmp = array_replace_recursive($value2Tmp, $valueTmp);
									foreach ($valueTmp as $propId => $columns)
									{
										if (!isset($columns['ACTIVE']))
										{
											unset($valueTmp[$propId]);
										}
									}
								}
							}

							if ($arPsActFields[$val]['TYPE'] == 'FILE' && $typeTmp != 'FILE')
								continue;

							if ($typeTmp == 'FILE')
							{
								$valueTmp = array();
								if (array_key_exists("VALUE1_".$val, $_FILES))
								{
									if ($_FILES["VALUE1_".$val]["error"] == 0)
									{
										$imageSize = getimagesize($_FILES["VALUE1_".$val]['tmp_name']);
										if ($imageSize['bits'] > 8)
											$errorMessage .= GetMessage("CRM_PS_ERROR_IMAGE_DEPTH").".<br>";

										$error = CSalePdf::CheckImage($_FILES["VALUE1_".$val]);
										if ($error !== null)
										{
											$errorMessage .= GetMessage("CRM_PS_ERROR_IMAGE_ERROR").".<br>";
										}

										$imageFileError = CFile::CheckImageFile($_FILES["VALUE1_".$val]);

										if (is_null($imageFileError))
											$valueTmp = $_FILES["VALUE1_".$val];
										else
											$errorMessage .= $imageFileError . ".<br>";
									}
								}

								if (trim($_POST[$val."_del"]) == 'Y')
								{
									if (intval($arPaySys['PARAMS'][$val]['VALUE']) == 0)
										continue;

									$valueTmp['old_file'] = $arPaySys['PARAMS'][$val]['VALUE'];
									$valueTmp['del'] = trim($_POST[$val."_del"]);
								}

								if (empty($valueTmp))
								{
									$typeTmp  = $arPaySys['PARAMS'][$val]['TYPE'];
									$valueTmp = $arPaySys['PARAMS'][$val]['VALUE'];
								}
							}

							$arActParams[$val] = array(
								"TYPE" => $typeTmp,
								"VALUE" => $valueTmp
							);

							if ($arActParams[$val]['TYPE'] == 'FILE' && is_array($arActParams[$val]['VALUE']))
							{
								$arActParams[$val]['VALUE']['MODULE_ID'] = 'sale';
								CFile::SaveForDB($arActParams[$val], 'VALUE', 'sale/paysystem/field');
							}
						}
					}

					$arActParams['USER_COLUMNS']['VALUE'] = serialize($arActParams['USER_COLUMNS']['VALUE']);

					$arActFields['PARAMS'] = serialize($arActParams);
				}

				$path = \Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($handler);
				if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$path.'/handler.php'))
				{
					require_once $documentRoot.$path.'/handler.php';

					$className = \Bitrix\Sale\PaySystem\Manager::getClassNameFromPath($path);
					$arActFields['HAVE_PAYMENT'] = 'Y';
					if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\IPrePayable'))
						$arActFields['HAVE_PREPAY'] = 'Y';
					if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\ServiceHandler'))
						$arActFields['HAVE_RESULT_RECEIVE'] = 'Y';
					if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\IPayable'))
						$arActFields['HAVE_PRICE'] = 'Y';
				}
				else
				{
					if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$actionFile."/pre_payment.php"))
						$arActFields["HAVE_PREPAY"] = "Y";
					if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$actionFile."/result.php"))
						$arActFields["HAVE_RESULT"] = "Y";
					if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$actionFile."/action.php"))
						$arActFields["HAVE_ACTION"] = "Y";
					if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$actionFile."/payment.php"))
						$arActFields["HAVE_PAYMENT"] = "Y";
					if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$actionFile."/result_rec.php"))
						$arActFields["HAVE_RESULT_RECEIVE"] = "Y";
				}

				if ($psID === 0)
				{
					if (preg_match('/[a-z1-9_]+/i', $_POST['PS_MODE'], $psMode))
					{
						$image = '/bitrix/images/sale/sale_payments/'.$handler.'/'.$psMode[0].'.png';
						if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$image))
						{
							$arActFields['LOGOTIP'] = CFile::MakeFileArray($image);
							$arActFields['LOGOTIP']['MODULE_ID'] = "sale";
							CFile::SaveForDB($arActFields, 'LOGOTIP', 'sale/paysystem/logotip');
						}
					}

					if (!isset($arActFields['LOGOTIP']))
					{
						$image = '/bitrix/images/sale/sale_payments/'.$handler.'.png';
						if (\Bitrix\Main\IO\File::isFileExists($documentRoot.$image))
						{
							$arActFields['LOGOTIP'] = CFile::MakeFileArray($image);
							$arActFields['LOGOTIP']['MODULE_ID'] = "sale";
							CFile::SaveForDB($arActFields, 'LOGOTIP', 'sale/paysystem/logotip');
						}
					}
				}

				if ($errorMessage === '')
				{
					if ($psID > 0)
					{
						$result = PaySystem\Manager::update($psID, $arActFields);
					}
					else
					{
						$result = PaySystem\Manager::add($arActFields);
						if ($result->isSuccess())
						{
							$psID = $result->getId();
							PaySystem\Manager::update($psID, array('PAY_SYSTEM_ID' => $psID));
						}
					}

					if ($result->isSuccess())
					{
						if (array_key_exists('PARAMS', $arActFields))
						{
							$params = CSalePaySystemAction::prepareParamsForBusVal($psID, $arActFields);
							foreach ($params as $item)
								\Bitrix\Sale\BusinessValue::setMapping($item['CODE'], $item['CONSUMER'], $item['PERSON_TYPE_ID'], $item['MAP']);
						}

						if ($arActFields['PERSON_TYPE_ID'])
						{
							$params = array(
								'filter' => array(
									"SERVICE_ID" => $psID,
									"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
									"=CLASS_NAME" => '\\'.\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::class
								)
							);

							$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList($params);
							if ($data = $dbRes->fetch())
								$restrictionId = $data['ID'];
							else
								$restrictionId = 0;

							$fields = array(
								"SERVICE_ID" => $psID,
								"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
								"SORT" => 100,
								"PARAMS" => array('PERSON_TYPE_ID' => array($arActFields['PERSON_TYPE_ID']))
							);

							\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields, $restrictionId);
						}
					}
					else
					{
						$errorMessage .= implode("<br>", $result->getErrorMessages());
					}

					if (array_key_exists('YANDEX_PUBLIC_KEY', $_FILES) && $_FILES['YANDEX_PUBLIC_KEY']['tmp_name'])
					{
						$publicKey = file_get_contents($_FILES['YANDEX_PUBLIC_KEY']['tmp_name']);
						if (openssl_pkey_get_public($publicKey))
						{
							$shopId = \Bitrix\Sale\BusinessValue::get('YANDEX_INVOICE_SHOP_ID', 'PAYSYSTEM_'.$psID, $personTypeId);
							if ($shopId <> '')
							{
								$dbRes = \Bitrix\Sale\Internals\YandexSettingsTable::getById($shopId);
								if ($dbRes->fetch())
									\Bitrix\Sale\Internals\YandexSettingsTable::update($shopId, array('PUB_KEY' => $publicKey));
								else
									\Bitrix\Sale\Internals\YandexSettingsTable::add(array('SHOP_ID' => $shopId, 'PUB_KEY' => $publicKey));
							}
						}
						else
						{
							$errorMessage .= GetMessage('CRM_PS_ERROR_PUBLIC_KEY_LOAD');
						}
					}

					if (array_key_exists('YANDEX_PUBLIC_KEY_DEL', $_REQUEST))
					{
						$shopId = \Bitrix\Sale\BusinessValue::get('YANDEX_INVOICE_SHOP_ID', 'PAYSYSTEM_'.$psID, $personTypeId);
						if ($shopId <> '')
							\Bitrix\Sale\Internals\YandexSettingsTable::update($shopId, array('PUB_KEY' => ''));
					}
				}
			}
		}

		if ($errorMessage == '')
		{
			$urlPattern = (isset($_POST['apply']) ? $arParams['PATH_TO_PS_EDIT'] : $arParams['PATH_TO_PS_LIST']);
			if ($isSidePanel)
			{
				$urlPattern = $arParams['PATH_TO_PS_EDIT'];
				$urlPattern .= "?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER&SIDE_PANEL_REQUEST=Y";
				$urlPattern .= (isset($_POST['apply']) ? '&SIDE_PANEL_APPLY=Y' : '&SIDE_PANEL_SAVE=Y');
			}
			LocalRedirect(CComponentEngine::MakePathFromTemplate($urlPattern, array('ps_id' => $psID)));
		}
		else
		{
			$arActFields['PARAMS'] = unserialize($arActFields['PARAMS'], ['allowed_classes' => false]);
			$arResult['PAY_SYSTEM'] = $arActFields;
			if ($isSidePanel)
			{
				$arResult['SIDE_PANEL_ERROR'] = $errorMessage;
			}
			else
			{
				ShowError($errorMessage);
			}
			$arPaySys = $arActFields;
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$psID = isset($arParams['PS_ID']) ? intval($arParams['PS_ID']) : 0;

		$result = \Bitrix\Sale\PaySystem\Manager::delete($psID);
		if (!$result->isSuccess())
		{
			if ($isSidePanel)
			{
				$arResult['SIDE_PANEL_ERROR'] = implode('<br>', $result->getErrorMessages());
			}
			else
			{
				ShowError(implode('<br>', $result->getErrorMessages()));
			}
		}

		$urlPattern = $arParams['PATH_TO_PS_LIST'];
		if ($isSidePanel)
		{
			$urlPattern = $arParams['PATH_TO_PS_EDIT'];
			$urlPattern .= "?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER&SIDE_PANEL_REQUEST=Y&SIDE_PANEL_SAVE=Y";
		}
		LocalRedirect(CComponentEngine::MakePathFromTemplate($urlPattern, array()));
	}
}

if (array_key_exists('AJAX', $_REQUEST) && $_REQUEST['AJAX'] == 'Y')
{
	if ($_REQUEST['HANDLER'] == 'YANDEX_INVOICE')
	{
		$data = \Bitrix\Sale\PaySystem\Manager::getById($psID);
		$personTypeList = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($psID);
		$personTypeId = array_shift($personTypeList);

		$shopId = \Bitrix\Sale\BusinessValue::get('YANDEX_INVOICE_SHOP_ID', 'PAYSYSTEM_'.$psID, $personTypeId);
		if ($shopId <> '')
		{
			$dbRes = \Bitrix\Sale\Internals\YandexSettingsTable::getById($shopId);
			$yandexSettings = $dbRes->fetch();
			if ($yandexSettings)
			{
				$APPLICATION->RestartBuffer();

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=public_key.pem');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');

				$pkeyRes = openssl_get_privatekey($yandexSettings['PKEY']);
				$pkeyDetail = openssl_pkey_get_details($pkeyRes);
				echo $pkeyDetail['key'];
				die();
			}
		}
	}
}

$io = CBXVirtualIo::GetInstance();
$arPaySys['ACTION_FILE'] = $io->ExtractNameFromPath($arPaySys['ACTION_FILE'] ? $arPaySys['ACTION_FILE'] : 'bill');
$arResult['PAY_SYSTEM_LIST'] = CCrmPaySystem::getActionsList();

$arResult['PERSON_TYPE_LIST'] = CCrmPaySystem::getPersonTypesList();
$ptID = $arPaySys['PERSON_TYPE_ID'] ? $arPaySys['PERSON_TYPE_ID'] : key($arResult['PERSON_TYPE_LIST']);
$className = PaySystem\Manager::getClassNameFromPath($arPaySys['ACTION_FILE']);
if (!class_exists($className))
{
	$path = PaySystem\Manager::getPathToHandlerFolder($arPaySys['ACTION_FILE']);
	$fullPath = $documentRoot.$path.'/handler.php';
	if ($path && \Bitrix\Main\IO\File::isFileExists($fullPath))
		require_once $fullPath;
}

$handlerModeList = array();
if (class_exists($className))
	$handlerModeList = $className::getHandlerModeList();

if ($handlerModeList)
	$arResult['PS_MODE'] = $handlerModeList;


if ($arPaySys['ACTION_FILE'] == 'yandexinvoice')
{
	$arResult['SECURITY'] = array();

	$personTypeList = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($psID);
	$personTypeId = array_shift($personTypeList);

	$shopId = \Bitrix\Sale\BusinessValue::get('YANDEX_INVOICE_SHOP_ID', 'PAYSYSTEM_'.$psID, $personTypeId);

	$yandexSettings = array();
	if ($shopId <> '')
	{
		$dbRes = \Bitrix\Sale\Internals\YandexSettingsTable::getById($shopId);
		$yandexSettings = $dbRes->fetch();
	}

	if ($yandexSettings)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PS_EDIT'], array('ps_id' => $psID));
		$value = '<a href="'.$url.'?AJAX=Y&HANDLER=YANDEX_INVOICE&ncc=1">'.GetMessage('CRM_PS_ACT_SEC_DOWNLOAD').'</a>';
		$name = GetMessage('CRM_PS_ACT_SEC_GENERATE_PUB_KEY');
	}
	else
	{
		$value = "<input type='button' value='".GetMessage('CRM_PS_ACT_SEC_GENERATE')."' onclick='BX.crmPaySys.getPrivateKey();'>";
		$name = GetMessage('CRM_PS_ACT_SEC_GENERATE_PKEY');
	}

	$arResult['SECURITY']['PKEY'] = array(
		'NAME' => $name,
		'VALUE' => $value
	);

	if ($yandexSettings['PUB_KEY'] <> '')
		$value = GetMessage('CRM_PS_PUB_KEY_YANDEX_SUCCESS')." <br><input type='checkbox' name='YANDEX_PUBLIC_KEY_DEL'> ".GetMessage('CRM_PS_ACT_SEC_DEL');
	else
		$value = "<input type='file' name='YANDEX_PUBLIC_KEY' value='".GetMessage('CRM_PS_ACT_SEC_LOAD')."'>";

	$arResult['SECURITY']['PUB_KEY'] = array(
		'NAME' => GetMessage('CRM_PS_ACT_SEC_GENERATE_PUB_KEY_YANDEX'),
		'VALUE' => $value
	);
}

$arResult['ACTION_FILE'] = $arPaySys['ACTION_FILE'];
if ($arResult['ACTION_FILE'] === 'invoicedocument')
{
	$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.templates');
	$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
	$uri = new \Bitrix\Main\Web\Uri($componentPath);
	$params = [
		'PROVIDER' => \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice::class,
		'MODULE' => 'crm'
	];
	$arResult['INVOICE_DOC_ADD_LINK'] = $uri->addParams($params)->getLocator();
}
$arResult['PS_ACT_FIELDS'] = CCrmPaySystem::getPSCorrespondence($arPaySys['ACTION_FILE'], $arPaySys['PS_MODE']);
CCrmPaySystem::rewritePSCorrByRqSource($ptID, $arResult['PS_ACT_FIELDS'], array('PSA_CODE' => $arPaySys['ACTION_FILE']));
$arResult['ACTION_FIELDS_LIST'] =  implode(',', array_keys($arResult['PS_ACT_FIELDS']));
$arResult['SIMPLE_MODE'] = CCrmPaySystem::isFormSimple();

$arResult['USER_FIELDS'] = array();
$quoteUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmQuote::$sUFEntityID, 0, LANGUAGE_ID);
foreach($quoteUserFields as $name => $field)
	$arResult['USER_FIELDS']['quote'][$name] = $field['EDIT_FORM_LABEL'];

$invoiceUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmInvoice::$sUFEntityID, 0, LANGUAGE_ID);
foreach($invoiceUserFields as $name => $field)
{
	if ($field['EDIT_FORM_LABEL'] !== '')
		$arResult['USER_FIELDS']['bill'][$name] = $field['EDIT_FORM_LABEL'];
}

$arResult['FIELDS'] = array();
// gather requisite fields used in action settings
$requisiteFieldsUsed = array();
$fieldName = '';
foreach ($arResult['PS_ACT_FIELDS'] as $idCorr => $arCorr)
{
	if(isset($arPaySys['PARAMS'][$idCorr])
		&& isset($arPaySys['PARAMS'][$idCorr]['TYPE'])
		&& $arPaySys['PARAMS'][$idCorr]['TYPE'] === 'REQUISITE'
		&& isset($arPaySys['PARAMS'][$idCorr]['VALUE'])
	)
	{
		$fieldName = trim(strval($arPaySys['PARAMS'][$idCorr]['VALUE']));
		if (!empty($fieldName))
			$requisiteFieldsUsed[$fieldName] = true;
	}
}
unset($fieldName);

// requisite fields
$arResult['REQUISITE_FIELDS'] = CCrmPaySystem::getRequisiteFieldSelectItems(array_keys($requisiteFieldsUsed));

// bank detail fields
$arResult['BANK_DETAIL_FIELDS'] = CCrmPaySystem::getBankDetailFieldSelectItems();

// company fields
$arResult['CRM_COMPANY_FIELDS'] = array(
	'TITLE' => GetMessage('CRM_PS_COMPANY_FIELD_TITLE'),
	'PHONE' => GetMessage('CRM_PS_COMPANY_FIELD_PHONE'),
	'EMAIL' => GetMessage('CRM_PS_COMPANY_FIELD_EMAIL'),
);
$ar = CCrmFieldMulti::GetEntityTypeList('PHONE');
foreach($ar as $valueType => $value)
{
	if ($valueType === 'HOME')
		continue;

	$arResult['CRM_COMPANY_FIELDS']['PHONE_'.$valueType] = $value;
}
$ar = CCrmFieldMulti::GetEntityTypeList('EMAIL');
foreach($ar as $valueType => $value)
	$arResult['CRM_COMPANY_FIELDS']['EMAIL_'.$valueType] = $value;

// contact fields
$arResult['CRM_CONTACT_FIELDS'] = array(
	'FULL_NAME' => GetMessage('CRM_PS_CONTACT_FIELD_FULL_NAME'),
	'PHONE' => GetMessage('CRM_PS_CONTACT_FIELD_PHONE'),
	'EMAIL' => GetMessage('CRM_PS_CONTACT_FIELD_EMAIL')
);
$ar = CCrmFieldMulti::GetEntityTypeList('PHONE');
foreach($ar as $valueType => $value)
	$arResult['CRM_CONTACT_FIELDS']['PHONE_'.$valueType] = $value;
$ar = CCrmFieldMulti::GetEntityTypeList('EMAIL');
foreach($ar as $valueType => $value)
	$arResult['CRM_CONTACT_FIELDS']['EMAIL_'.$valueType] = $value;

if (\Bitrix\Main\Loader::includeModule('iblock'))
{
	$arFilter = array('IBLOCK_ID' => intval(\CCrmCatalog::EnsureDefaultExists()), 'CHECK_PERMISSIONS' => 'N', '!PROPERTY_TYPE' => 'G');

	$dbRes = \CIBlockProperty::GetList(array(), $arFilter);
	while ($arRow = $dbRes->Fetch())
		$arResult['USER_COLUMN_FIELDS'][$arRow['ID']] = $arRow['NAME'];

	$usedProps = array();
	if (isset($arPaySys['PARAMS']['USER_COLUMNS']))
	{
		$userProps = unserialize($arPaySys['PARAMS']['USER_COLUMNS']['VALUE'], ['allowed_classes' => false]);
		if ($userProps)
		{
			foreach ($userProps as $propId => $fields)
			{
				foreach ($fields as $name => $value)
				{
					$caption = GetMessage('CRM_COLUMN_'.$name);
					if ($name === 'NAME')
						$caption .= ' '.$arResult['USER_COLUMN_FIELDS'][$propId];

					$arResult['PS_ACT_FIELDS']['USER_COLUMNS['.$propId.']['.$name.']'] = array(
						'NAME' => $caption,
						'GROUP' => 'COLUMN_SETTINGS',
						'SORT' => 6100,
						'TYPE' => $name === 'ACTIVE' ? 'CHECKBOX' : '',
						'VALUE' => $value,
						'DESCR' => '',
					);
				}
			}
		}
	}
}

$fieldsByGroups = array();
foreach ($arResult['PS_ACT_FIELDS'] as $idCorr => $arCorr)
{
	if (!array_key_exists($arCorr['GROUP'], $fieldsByGroups))
		$fieldsByGroups[$arCorr['GROUP']] = array();

	$fieldsByGroups[$arCorr['GROUP']][$idCorr] = $arCorr;
}
$arResult['PS_ACT_FIELDS_BY_GROUP'] = $fieldsByGroups;
$arResult['FIELD_LIST'] = $arResult['FIELDS'];

$arTabs = array();
$arParamsTemplate = array();
$fieldsGroups = \Bitrix\Sale\BusinessValue::getGroups();
foreach ($fieldsByGroups as $group => $fields)
{
	foreach ($fields as $idCorr => $arCorr)
	{
		if ($arCorr['TYPE'] == 'SELECT')
			$arCorr['OPTIONS'] = $arCorr['VALUE'];

		if(is_array($arPaySys['PARAMS']) && array_key_exists($idCorr, $arPaySys['PARAMS'])
			&& is_array($arPaySys['PARAMS'][$idCorr])
			&& array_key_exists('TYPE', $arPaySys['PARAMS'][$idCorr])
			&& array_key_exists('VALUE', $arPaySys['PARAMS'][$idCorr])
			)
		{
			if ($arCorr['TYPE'] == 'FILE')
			{
				$arCorr['VALUE'] = CFile::ShowImage(
					$arPaySys['PARAMS'][$idCorr]['VALUE'],
					150, 150,
					'id="' . $idCorr . '_preview_img"'
				);
			}
			elseif ($arCorr['TYPE'] == 'SELECT' || $arCorr['TYPE'] == 'CHECKBOX')
			{
				$arCorr['VALUE'] = $arPaySys['PARAMS'][$idCorr]['VALUE'];
			}
			else if ($arCorr['TYPE'] === 'USER_COLUMN_LIST')
			{
				if ($arPaySys['PARAMS'][$idCorr]['VALUE'])
				{
					$userColumns = unserialize($arPaySys['PARAMS'][$idCorr]['VALUE'], ['allowed_classes' => false]);
					if ($userColumns)
					{
						foreach ($userColumns as $id => $columns)
							$arCorr['VALUE']['PROPERTY_'.$id] = array('NAME' => $columns['NAME'], 'SORT' => $columns['SORT']);
					}
				}
			}
			else if (
				$arPaySys['PARAMS'][$idCorr]['TYPE'] !== null &&
				$arPaySys['PARAMS'][$idCorr]['VALUE'] !== null
			)
			{
				$arCorr['TYPE'] = $arPaySys['PARAMS'][$idCorr]['TYPE'];
				$arCorr['VALUE'] = $arPaySys['PARAMS'][$idCorr]['VALUE'];
			}
		}

		if ($arCorr['TYPE'] == '' || $arCorr['TYPE'] === 'CHECKBOX'|| $arCorr['TYPE'] === 'USER_COLUMN_LIST')
			$arParamsTemplate[$idCorr] = $arCorr['VALUE'];

		$res  = ' ' . CCrmPaySystem::getActionSelector($idCorr, $arCorr);
		$res .= ' '.CCrmPaySystem::getActionValueSelector(
				$idCorr, $arCorr, $ptID, $arResult['ACTION_FILE'], $arResult['USER_FIELDS'],
				$arResult['REQUISITE_FIELDS'], $arResult['BANK_DETAIL_FIELDS'],
				$arResult['CRM_COMPANY_FIELDS'], $arResult['CRM_CONTACT_FIELDS']
			);

		if (!array_key_exists($group, $arTabs))
		{
			$arTabs[$group] = array(
				'id' => $group,
				'name' => $fieldsGroups[$group]['NAME'],
				'icon' => '',
				'fields'=> array()
			);
		}

		$arTabs[$group]['fields'][$idCorr] = array(
			'id' => $idCorr,
			'name' => $arCorr['NAME'],
			'title' => $arCorr['DESCR'],
			'type' => 'custom',
			'value' => $res
		);
	}
}

unset($arResult['PS_ACT_FIELDS_BY_GROUP']['USER_COLUMNS']);
unset($arTabs['COLUMN_SETTINGS']['fields']['USER_COLUMNS']);
$arResult['BUSINESS_VALUE'] = $arTabs;

if (!$arResult['PAY_SYSTEM'])
{
	$arResult['PAY_SYSTEM']['ACTION_FILE'] = 'bill';
	$arResult['PAY_SYSTEM']['PERSON_TYPE_ID'] = $ptID;
}

$service = new \Bitrix\Sale\PaySystem\Service($arResult['PAY_SYSTEM']);
if ($service)
{
	$arParamsTemplate = array_merge($service->getDemoParams(), $arParamsTemplate);

	$service->setTemplateMode(\Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
	$service->setTemplateParams($arParamsTemplate);
	if (mb_strpos($service->getField('ACTION_FILE'), 'bill') !== false || mb_strpos($service->getField('ACTION_FILE'), 'quote') !== false)
	{
		$payment = PaySystem\Manager::getPaymentObjectByData($arResult['PAY_SYSTEM']);
		$result = $service->showTemplate($payment, 'template');
		$arResult['TEMPLATE'] = $result->getTemplate();
	}
}

$this->IncludeComponentTemplate();
?>