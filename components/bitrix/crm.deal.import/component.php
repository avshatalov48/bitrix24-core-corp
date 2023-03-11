<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmDeal::CheckImportPermission($CrmPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $USER_FIELD_MANAGER;
use Bitrix\Crm\Category\DealCategory;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => 'ID'),
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE')),
	array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY')),
	array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID')),
	array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID')),
	array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY')),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID')),
	array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE')),
	array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION')),
	array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID')),
	array('id' => 'PRODUCT_PRICE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_PRICE')),
	array('id' => 'PRODUCT_QUANTITY', 'name' => GetMessage('CRM_COLUMN_PRODUCT_QUANTITY')),
	array('id' => 'CATEGORY_ID', 'name' => GetMessage('CRM_COLUMN_CATEGORY_ID')),
	array('id' => 'STAGE_ID', 'name' => GetMessage('CRM_COLUMN_STAGE_ID')),
	array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED')),
	array('id' => 'OPENED', 'name' => GetMessage('CRM_COLUMN_OPENED')),
	array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE_ID')),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
	array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE')),
	array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE')),
	array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_EVENT_DATE')),
	array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_ID')),
	array('id' => 'EVENT_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_EVENT_DESCRIPTION')),
	array('id' => 'ASSIGNED_BY_ID', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY_ID'))
);

$CCrmUserType->ListAddHeaders($arResult['HEADERS'], true);

$arRequireFields = Array();
$arRequireFields['TITLE'] = GetMessage('CRM_COLUMN_TITLE');

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY', $arParams['PATH_TO_DEAL_CATEGORY'], $APPLICATION->GetCurPage().'?category_id=#category_id#');
$arParams['PATH_TO_DEAL_IMPORT'] = CrmCheckPath('PATH_TO_DEAL_IMPORT', $arParams['PATH_TO_DEAL_IMPORT'], $APPLICATION->GetCurPage().'?import');

if(isset($_REQUEST['category_id']))
{
	$categoryID = (int)$_REQUEST['category_id'];
	if($categoryID === 0 || Bitrix\Crm\Category\DealCategory::isEnabled($categoryID))
	{
		$arResult['CATEGORY_ID'] = $categoryID;
	}
}

$userNameFormats = \Bitrix\Crm\Format\PersonNameFormatter::getAllDescriptions();
if(isset($_REQUEST['getSample']) && $_REQUEST['getSample'] == 'csv')
{
	$APPLICATION->RestartBuffer();

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	Header("Content-Disposition: attachment;filename=deal.csv");
	Header("Content-Transfer-Encoding: binary");

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$typeList = CCrmStatus::GetStatusListEx('DEAL_TYPE');
	$stageList = CCrmStatus::GetStatusListEx('DEAL_STAGE');

	$arDemo = array(
		'TITLE' => GetMessage('CRM_SAMPLE_TITLE'),
		'TYPE_ID' => $typeList['SALE'],
		'PROBABILITY' => '50',
		'STAGE_ID' => $stageList['NEW']
	);

	$arProduct =  CCrmProduct::GetByOriginID('CRM_DEMO_PRODUCT_BX_CMS');
	if($arProduct)
	{
		$arDemo['PRODUCT_ID'] = $arProduct['~NAME'];
		$arDemo['PRODUCT_QUANTITY'] = '1';
		$arDemo['PRODUCT_PRICE'] = $arDemo['OPPORTUNITY'] = $arProduct['~PRICE'];
		$arDemo['CURRENCY_ID'] = $arProduct['~CURRENCY_ID'];
	}
	else
	{
		$arDemo['OPPORTUNITY'] = GetMessage('CRM_SAMPLE_OPPORTUNITY');
		$arDemo['CURRENCY_ID'] = GetMessage('CRM_SAMPLE_CURRENCY_ID');
	}

	foreach($arResult['HEADERS'] as $arField):
		echo '"', str_replace('"', '""', $arField['name']),'";';
	endforeach;
	echo "\n";
	foreach($arResult['HEADERS'] as $arField):
		echo isset($arDemo[$arField['id']])? '"'.str_replace('"', '""', $arDemo[$arField['id']]).'";': '"";';
	endforeach;
	echo "\n";
	die();
}
else if (isset($_REQUEST['import']) && isset($_SESSION['CRM_IMPORT_FILE']))
{
	$APPLICATION->RestartBuffer();

	global 	$USER_FIELD_MANAGER;
	$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

	require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

	$arStatus['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
	$arStatus['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
	$arStatus['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
	$arStatus['CLOSED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
	$arStatus['OPENED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));

	$csvFile = new CCSVData();
	$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
	$csvFile->SetFieldsType('R');
	$csvFile->SetPos($_SESSION['CRM_IMPORT_FILE_POS']);
	$csvFile->SetFirstHeader($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER']);
	$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

	$arResult = array(
		'import' => 0,
		'error' => 0,
		'error_data' => array()
	);

	$CCrmDeal = new CCrmDeal();
	$arDeals = array();

	$filePos = 0;
	$usersByID = array();
	$usersByName = array();
	$defaultUserID =  isset($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) ? intval($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) : 0;
	$userNameFormat = isset($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
		&& \Bitrix\Crm\Format\PersonNameFormatter::isDefined($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
			? intval($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
			: \Bitrix\Crm\Format\PersonNameFormatter::FirstLast;

	while($arData = $csvFile->Fetch())
	{
		$arResult['column'] = count($arData);
		$dealID = '';

		$arDeal = array(
			'__CSV_DATA__' => array($arData)
		);

		$arProductRow = array();
		foreach ($arData as $key => $data)
		{
			if (isset($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]) && !empty($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]))
			{
				$currentKey = mb_strtoupper($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]);
				$data = trim(htmlspecialcharsback($data));

				if ($currentKey === 'ID')
				{
					$dealID = $data;
					continue;
				}

				if (mb_strpos($currentKey, '~') === 0 || empty($data))
				{
					continue;
				}

				if ($currentKey == 'TYPE_ID')
				{
					$data = htmlspecialcharsbx($data);

					if(isset($arStatus['TYPE_LIST'][$data]))
					{
						// 1. Try to interpret value as ID
						$arDeal[$currentKey] = $data;
					}
					else
					{
						// 2. Try to interpret value as TITLE. If not found leave value as is
						$result = array_search($data, $arStatus['TYPE_LIST']);
						$arDeal[$currentKey] = $result !== false ? $result : $data;
					}
				}
				else if ($currentKey == 'CATEGORY_ID')
				{
					if(!isset($arDeal['CATEGORY_ID']))
					{
						$categoryID = is_numeric($data) && DealCategory::isEnabled($data)
							? (int)$data
							: max(DealCategory::resolveByName($data), 0);
						$arDeal['CATEGORY_ID'] = $categoryID;

						if(isset($arDeal['STAGE_NAME']))
						{
							$allStages = \Bitrix\Crm\Category\DealCategory::getStageList($categoryID);
							$stageID = DealCategory::getStageByName($arDeal['STAGE_NAME'], $arDeal['CATEGORY_ID']);
							if($stageID === '')
							{
								$stageID = current(array_keys($allStages));
							}
							$arDeal['STAGE_ID'] = $stageID;
							unset($arDeal['STAGE_NAME']);
						}
					}
				}
				else if ($currentKey == 'STAGE_ID')
				{
					$categoryID = isset($arDeal['CATEGORY_ID']) ? $arDeal['CATEGORY_ID'] : -1;
					if($categoryID < 0)
					{
						if(DealCategory::hasStage($data))
						{
							$arDeal['STAGE_ID'] = $data;
							$arDeal['CATEGORY_ID'] = DealCategory::resolveFromStageID($data);
						}
						else
						{
							//Postpone resolving of STAGE_ID to CATEGORY_ID
							$arDeal['STAGE_NAME'] = $data;
						}
					}
					else
					{
						if(DealCategory::hasStage($data, $categoryID))
						{
							$arDeal['STAGE_ID'] = $data;
						}
						else
						{
							$allStages = \Bitrix\Crm\Category\DealCategory::getStageList($categoryID);
							$stageID = DealCategory::getStageByName($data, $categoryID);
							if($stageID === '')
							{
								$stageID = current(array_keys($allStages));
							}
							$arDeal['STAGE_ID'] = $stageID;
						}
					}
				}
				else if ($currentKey  == 'CURRENCY_ID')
				{
					$currency = CCrmCurrency::GetByName($data);
					if(!$currency)
					{
						$currency = CCrmCurrency::GetByID($data);
					}

					$arDeal[$currentKey] = $currency ? $currency['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
				}
				elseif ($currentKey == 'SOURCE_ID')
				{
					$data = htmlspecialcharsbx($data);

					if(isset($arStatus['SOURCE_LIST'][$data]))
					{
						// 1. Try to interpret value as ID
						$arDeal[$currentKey] = $data;
					}
					else
					{
						$result = array_search($data, $arStatus['SOURCE_LIST']);
						$arDeal[$currentKey] = $result !== false ? $result : $data;
					}
				}
				else if ($currentKey  == 'PRODUCT_ID')
				{
					// For compatibility
					$arProduct = CCrmProduct::GetByOriginID('CRM_PROD_'.$data);
					if(is_array($arProduct))
					{
						$arProductRow = array(
							'PRODUCT_ID' => $arProduct['ID'],
							'QUANTITY' => 1
						);
						// PRICE equals to OPPORTUNITY. We will set PRICE latter
					}
					else
					{
						$arProduct = CCrmProduct::GetByName($data);
						if($arProduct)
						{
							$arProductRow['PRODUCT_ID'] = $arProduct['ID'];
						}
						else
						{
							$arProductRow['PRODUCT_ID'] = 0;
						}
						$arProductRow['PRODUCT_NAME'] = $data;
					}
				}
				elseif($currentKey == 'PRODUCT_PRICE')
				{
					// Process price only if product has been resolved
					if(isset($arProductRow['PRODUCT_ID']))
					{
						$arProductRow['PRICE'] = doubleval($data);
					}
				}
				elseif($currentKey == 'PRODUCT_QUANTITY')
				{
					// Process quntity only if product has been resolved
					if(isset($arProductRow['PRODUCT_ID']))
					{
						$arProductRow['QUANTITY'] = is_numeric($data) ? doubleval($data) : 1;
					}
				}
				elseif ($currentKey  == 'EVENT_ID')
				{
					$data = htmlspecialcharsbx($data);

					if(isset($arStatus['EVENT_LIST'][$data]))
					{
						$arDeal[$currentKey] = $data;
					}
					else
					{
						$result = array_search($data, $arStatus['EVENT_LIST']);
						$arDeal[$currentKey] = $result !== false ? $result : $data;
					}
				}
				elseif ($currentKey  == 'CLOSED' || $currentKey  == 'OPENED')
				{
					$arDeal[$currentKey] = isset($arStatus[$currentKey.'_LIST'][$data])? $data: array_search($data, $arStatus[$currentKey.'_LIST']);
					if ($arDeal[$currentKey] === false)
						unset($arDeal[$currentKey]);
				}
				elseif ($currentKey == 'COMPANY_ID')
				{
					$obRes = CCrmCompany::GetListEx(array(), array('TITLE' => $data, '@CATEGORY_ID' => 0), false, false, array('ID'));
					if (($arRow = $obRes->Fetch()) !== false)
						$arDeal[$currentKey] = $arRow['ID'];
				}
				elseif ($currentKey == 'CONTACT_ID')
				{
					$obRes = CCrmContact::GetListEx(array(), array('FULL_NAME' => $data, '@CATEGORY_ID' => 0), false, false, array('ID'));
					if (($arRow = $obRes->Fetch()) !== false)
						$arDeal[$currentKey] = $arRow['ID'];
				}
				elseif ($currentKey == 'ASSIGNED_BY_ID')
				{
					$userID = 0;
					if(is_numeric($data))
					{
						// 1. Try to interpret value as user ID
						$userID = is_int($data) ? $data : intval($data);
						if($userID > 0 && !isset($usersByID[$userID]))
						{
							$dbUsers = CUser::GetList('ID', 'ASC', array('ID_EQUAL_EXACT'=> $userID), array('FIELDS' => array('ID')));
							$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
							if(is_array($user))
							{
								$usersByID[$userID] = $user;
							}
							else
							{
								//Reset user
								$userID = 0;
							}
						}
					}
					else
					{
						if(preg_match('/^.+\[\s*(\d+)\s*]$/', $data, $m) === 1)
						{
							// 2. Try to interpret value as user name with ID
							$userID = intval($m[1]);
							if($userID > 0 && !isset($usersByID[$userID]))
							{
								$dbUsers = CUser::GetList('ID', 'ASC', array('ID_EQUAL_EXACT'=> $userID), array('FIELDS' => array('ID')));
								$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
								if(is_array($user))
								{
									$usersByID[$userID] = $user;
								}
								else
								{
									//Reset user
									$userID = 0;
								}
							}
						}
						else
						{
							// 3. Try to interpret value as user name (#NAME# #LAST_NAME#)
							if(isset($usersByName[$data]))
							{
								$userID = intval($usersByName[$data]['ID']);
							}
							else
							{
								$nameParts = array();
								if(\Bitrix\Crm\Format\PersonNameFormatter::tryParseName($data, $userNameFormat, $nameParts))
								{
									$userFilter = array();
									if(isset($nameParts['NAME']) && $nameParts['NAME'] !== '')
									{
										$userFilter['NAME'] = $nameParts['NAME'];
									}
									if(isset($nameParts['SECOND_NAME']) && $nameParts['SECOND_NAME'] !== '')
									{
										$userFilter['SECOND_NAME'] = $nameParts['SECOND_NAME'];
									}
									if(isset($nameParts['LAST_NAME']) && $nameParts['LAST_NAME'] !== '')
									{
										$userFilter['LAST_NAME'] = $nameParts['LAST_NAME'];
									}

									$userFilter['IS_REAL_USER'] = 'Y';
									$user = \Bitrix\Main\UserTable::getList(
										array(
											'order' => array('ID' => 'ASC'),
											'filter' => $userFilter,
											'select' => array('ID'),
											'limit' => 1
										)
									)->fetch();
									if(is_array($user))
									{
										$userID = $user['ID'] = intval($user['ID']);
										$usersByName[$data] = $user;
									}
								}
							}
						}
					}
					if($userID > 0)
					{
						$arDeal['ASSIGNED_BY_ID'] = $userID;
					}
					elseif($defaultUserID > 0)
					{
						$arDeal['ASSIGNED_BY_ID'] = $defaultUserID;
					}
				}
				else
				{
					// Finally try to internalize user type values
					$arDeal[$currentKey] = $CCrmUserType->Internalize($currentKey, $data, ',');
				}
			}
		}

		if(isset($arProductRow['PRODUCT_ID']))
		{
			if(!isset($arDeal['PRODUCT_ROWS']))
			{
				$arDeal['PRODUCT_ROWS'] = array();
			}

			$arDeal['PRODUCT_ROWS'][] = $arProductRow;
		}

		if (!isset($arDeal['ASSIGNED_BY_ID']) && $defaultUserID > 0)
		{
			$arDeal['ASSIGNED_BY_ID'] = $defaultUserID;
		}

		$canBreak = true; // We cant break while read multiproduct deal

		if($dealID !== '')
		{
			if(isset($arDeals[$dealID]))
			{
				$canBreak = false;

				// Merging of source data
				$arPrevDeal = $arDeals[$dealID];
				$arDeal['__CSV_DATA__'] = array_merge($arDeal['__CSV_DATA__'], $arPrevDeal['__CSV_DATA__']);

				// Try to merge product rows
				if(isset($arPrevDeal['PRODUCT_ROWS']))
				{
					if(isset($arDeal['PRODUCT_ROWS']))
					{
						$arDeal['PRODUCT_ROWS'] = array_merge($arDeal['PRODUCT_ROWS'], $arPrevDeal['PRODUCT_ROWS']);
					}
					else
					{
						$arDeal['PRODUCT_ROWS'] = $arPrevDeal['PRODUCT_ROWS'];
					}
				}
				unset($arDeals[$dealID]);
			}
		}
		else
		{
			$dealID = uniqid();
		}

		// For compatibility only. Try sync product PRICE
		if(isset($arDeal['PRODUCT_ROWS'])
			&& count($arDeal['PRODUCT_ROWS']) == 1
			&& !isset($arDeal['PRODUCT_ROWS'][0]['PRICE'])
			&& isset($arDeal['OPPORTUNITY']))
		{
			$arDeal['PRODUCT_ROWS'][0]['PRICE'] = doubleval($arDeal['OPPORTUNITY']);
		}

		if($canBreak && count($arDeals) >= 20)
		{
			break;
		}

		$arDeals[$dealID] = $arDeal;
		$filePos = $csvFile->GetPos();
	}

	$categoryID = isset($_SESSION['CRM_IMPORT_FILE_CATEGORY_ID']) ? max($_SESSION['CRM_IMPORT_FILE_CATEGORY_ID'], 0) : 0;
	foreach($arDeals as $arDeal)
	{
		$arDeal['PERMISSION'] = 'IMPORT';
		if(!isset($arDeal['CATEGORY_ID']))
		{
			$arDeal['CATEGORY_ID'] = $categoryID;
		}

		if(!isset($arDeal['STAGE_ID']) && isset($arDeal['STAGE_NAME']) && isset($arDeal['CATEGORY_ID']))
		{
			$arDeal['STAGE_ID'] = DealCategory::getStageByName($arDeal['STAGE_NAME'], $arDeal['CATEGORY_ID']);
			unset($arDeal['STAGE_NAME']);
		}

		if(!isset($arDeal['STAGE_ID']) || $arDeal['STAGE_ID'] === '')
		{
			$allStages = \Bitrix\Crm\Category\DealCategory::getStageList($arDeal['CATEGORY_ID']);
			if(!empty($allStages))
			{
				$arDeal['STAGE_ID'] = current(array_keys($allStages));
			}
		}

		if(!isset($arDeal['CURRENCY_ID']) || $arDeal['CURRENCY_ID'] === '')
		{
			$arDeal['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}

		if (!$CCrmDeal->Add($arDeal))
		{
			$arResult['error']++;
			$arResult['error_data'][] = array(
				'message' => CCrmComponentHelper::encodeErrorMessage((string)($arDeal['RESULT_MESSAGE'] ?? '')),
				'data' => $arDeal['__CSV_DATA__']
			);
		}
		else if (!empty($arDeal))
		{
			if(isset($arDeal['PRODUCT_ROWS']) && count($arDeal['PRODUCT_ROWS']) > 0)
			{
				if(!CCrmDeal::SaveProductRows($arDeal['ID'], $arDeal['PRODUCT_ROWS']))
				{
					$arResult['error']++;
					$arResult['error_data'][] = array(
						'message' => CCrmComponentHelper::encodeErrorMessage((string)CCrmProductRow::GetLastError()), // HACK: Get error from nested class
						'data' => $arDeal['__CSV_DATA__']
					);
				}
			}
			$arResult['import']++;
		}
	}

	$_SESSION['CRM_IMPORT_FILE_POS'] = $filePos;
	$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = false;

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	CMain::FinalActions();
	die();
}
else if(isset($_REQUEST['complete_import']))
{
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(array('RESULT' => 'SUCCESS'));
	CMain::FinalActions();
	die();
}

$strError = '';
$arResult['STEP'] = isset($_POST['step'])? intval($_POST['step']): 1;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if (isset($_POST['next']))
	{
		if ($arResult['STEP'] == 1)
		{
			if ($_FILES['IMPORT_FILE']['error'] > 0)
				ShowError(GetMessage('CRM_CSV_NF_ERROR'));
			elseif (($strError = CFile::CheckFile($_FILES['IMPORT_FILE'], 0, false, 'csv,txt')) == '')
			{
				$arFields = Array(''=>'');
				$arFieldsUpper = Array();
				foreach($arResult['HEADERS'] as $arField):
					//echo '"'.$arField['name'].'";';
					$arFields[$arField['id']] = $arField['name'];
					$arFieldsUpper[$arField['id']] = mb_strtoupper($arField['name']);
					if ($arField['mandatory'] == 'Y')
						$arRequireFields[$arField['id']] = $arField['name'];
				endforeach;

				if (isset($_SESSION['CRM_IMPORT_FILE']))
					unset($_SESSION['CRM_IMPORT_FILE']);

				$sTmpFilePath = CTempFile::GetDirectoryName(12, 'crm');
				CheckDirPath($sTmpFilePath);
				$_SESSION['CRM_IMPORT_FILE_CATEGORY_ID'] = isset($_POST['category_id']) ? (int)$_POST['category_id'] : -1;
				$_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] = isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE'] = $sTmpFilePath.md5($_FILES['IMPORT_FILE']['tmp_name']).'.tmp';
				$_SESSION['CRM_IMPORT_FILE_POS'] = 0;

				move_uploaded_file($_FILES['IMPORT_FILE']['tmp_name'], $_SESSION['CRM_IMPORT_FILE']);
				@chmod($_SESSION['CRM_IMPORT_FILE'], BX_FILE_PERMISSIONS);

				if (isset($_POST['IMPORT_FILE_ENCODING']))
				{
					$fileEncoding = $_POST['IMPORT_FILE_ENCODING'];

					if ($fileEncoding == '_' && isset($_POST['hidden_file_import_encoding']))
					{
						$fileEncoding = $_POST['hidden_file_import_encoding'];
					}

					if($fileEncoding !== '' && $fileEncoding !== '_' && $fileEncoding !== mb_strtolower(SITE_CHARSET))
					{
						$convertCharsetErrorMsg = '';
						$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'rb');
						$fileContents = fread($fileHandle, filesize($_SESSION['CRM_IMPORT_FILE']));
						fflush($fileHandle);
						fclose($fileHandle);

						//HACK: Remove UTF-8 BOM
						if($fileEncoding === 'utf-8' && mb_substr($fileContents, 0, 3) === "\xEF\xBB\xBF")
						{
							$fileContents = mb_substr($fileContents, 3);
						}

						$fileContents = CharsetConverter::ConvertCharset($fileContents, $fileEncoding, SITE_CHARSET, $convertCharsetErrorMsg);

						$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'wb');
						fwrite($fileHandle, $fileContents);
						fflush($fileHandle);
						fclose($fileHandle);

						clearstatcache();
					}
				}

				$_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID'] = isset($_POST['IMPORT_DEFAULT_RESPONSIBLE_ID']) ? $_POST['IMPORT_DEFAULT_RESPONSIBLE_ID'] : '';
				$_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'] = isset($_POST['IMPORT_NAME_FORMAT'])
					&& \Bitrix\Crm\Format\PersonNameFormatter::isDefined($_POST['IMPORT_NAME_FORMAT'])
						? intval($_POST['IMPORT_NAME_FORMAT'])
						: \Bitrix\Crm\Format\PersonNameFormatter::FirstLast;

				if ($_POST['IMPORT_FILE_SEPORATOR'] == 'semicolon')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ';';
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'comma')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ',';
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'tab')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = "\t";
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'space')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ' ';

				require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

				$csvFile = new CCSVData();
				$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
				$csvFile->SetFieldsType('R');
				$csvFile->SetFirstHeader(false);
				$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

				$iRow = 1;
				$arHeader = Array();
				$arRows = Array();
				while($arData = $csvFile->Fetch())
				{
					if ($iRow == 1)
					{
						foreach($arData as $key => $value):
							if ($_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] && empty($value))
								continue;
							if ($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
								$arHeader[$key] = empty($value)? GetMessage('CRM_COLUMN_HEADER').' '.($key+1): trim($value);
							else
								$arHeader[$key] = GetMessage('CRM_COLUMN_HEADER').' '.($key+1);
						endforeach;
						if (!$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
							foreach($arHeader as $key => $value)
								$arRows[$iRow][$key] = $arData[$key];
					}
					else
						foreach($arHeader as $key => $value)
							$arRows[$iRow][$key] = $arData[$key];

					if ($iRow > 5)
						break;

					$iRow++;
				}
				$_SESSION['CRM_IMPORT_FILE_HEADERS'] = $arHeader;

				$arResult['FIELDS']['tab_2'] = array();
				if (count($arRequireFields)>0)
				{
					ob_start();
					?>
					<div class="crm_import_require_fields">
						<?=GetMessage('CRM_REQUIRE_FIELDS')?>: <b><?=implode('</b>, <b>', $arRequireFields)?></b>.
					</div>
					<?
					$sVal = ob_get_contents();
					ob_end_clean();
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_REQUIRE_FIELDS',
						'name' => "",
						'colspan' => true,
						'type' => 'custom',
						'value' => $sVal
					);
				}

				foreach ($arHeader as $key => $value)
				{
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_FILE_FIELD_'.$key,
						'name' => $value,
						'items' => $arFields,
						'type' => 'list',
						'value' => isset($arFields[mb_strtoupper($value)])? mb_strtoupper($value) : array_search(mb_strtoupper($value), $arFieldsUpper),
					);
				}
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE',
					'name' => GetMessage('CRM_SECTION_IMPORT_ASSOC_EXAMPLE'),
					'type' => 'section'
				);
				ob_start();
				?>
				<div id="crm_import_example" class="crm_import_example">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table">
						<tr>
							<?foreach ($arHeader as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						<?foreach ($arRows as $arRow):?>
							<tr>
							<?foreach ($arRow as $row):?>
								<td><?=htmlspecialcharsbx($row)?></td>
							<?endforeach;?>
							</tr>
						<?endforeach;?>
					</table>
				</div>
				<script type="text/javascript">
					windowSizes = BX.GetWindowSize(document);
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
				</script>
				<?
				$sVal = ob_get_contents();
				ob_end_clean();
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE_TABLE',
					'name' => "",
					'colspan' => true,
					'type' => 'custom',
					'value' => $sVal
				);
				if (count($arHeader) == 1)
					ShowError(GetMessage('CRM_CSV_SEPORATOR_ERROR'));
				else
					$arResult['STEP'] = 2;
			}
			else
				ShowError($strError);

		}
		else if ($arResult['STEP'] == 2)
		{
			$arResult['FIELDS']['tab_3'] = array();

			$arConfig = Array();
			foreach ($_POST as $key => $value)
				if(mb_strpos($key, 'IMPORT_FILE_FIELD_') !== false)
					$_SESSION['CRM_'.$key] = $value;

			ob_start();
			?>
				<div class="crm_import_entity"><?=GetMessage('CRM_IMPORT_FINISH')?>: <span id="crm_import_entity">0</span> <span id="crm_import_entity_progress"><img src="/bitrix/components/bitrix/crm.contact.import/templates/.default/images/wait.gif" align="absmiddle"></span></div>
				<div id="crm_import_error" class="crm_import_error"><?=GetMessage('CRM_IMPORT_ERROR')?>: <span id="crm_import_entity_error">0</span></div>
				<div id="crm_import_example" class="crm_import_example" style="display:none">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table" id="crm_import_example_table">
						<tbody id="crm_import_example_table_body">
						<tr>
							<?foreach ($_SESSION['CRM_IMPORT_FILE_HEADERS'] as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						</tbody>
					</table>
				</div>
				<script type="text/javascript">
					windowSizes = BX.GetWindowSize(document);
					BX('crm_import_example').style.height = "44px";
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
					crmImportAjax('<?=$APPLICATION->GetCurPage()?>');
				</script>
			<?
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_3'][] = array(
				'id' => 'IMPORT_FINISH',
				'name' => "",
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
			$arResult['STEP'] = 3;
		}
		else if ($arResult['STEP'] == 3)
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
				if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
					unset($_SESSION[$key]);

			$categoryID = isset($_POST['category_id']) ? (int)$_POST['category_id'] : -1;
			if($categoryID >= 0)
			{
				LocalRedirect(
					CComponentEngine::makePathFromTemplate(
						$arParams['PATH_TO_DEAL_CATEGORY'],
						array('category_id' => $categoryID)
					)
				);
			}
			else
			{
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
			}
		}
		else
			$arResult['STEP'] = 1;
	}
	else if (isset($_POST['previous']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		$arResult['STEP'] = 1;
	}
	else if (isset($_POST['cancel']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		$categoryID = isset($_POST['category_id']) ? (int)$_POST['category_id'] : -1;
		if($categoryID >= 0)
		{
			LocalRedirect(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_DEAL_CATEGORY'],
					array('category_id' => $categoryID)
				)
			);
		}
		else
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
		}

	}
}

$arResult['FORM_ID'] = 'CRM_DEAL_IMPORT';

$arResult['FIELDS']['tab_1'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE'),
	'params' => array(),
	'type' => 'file',
	'required' => true
);
$arResult['IMPORT_FILE'] = 'IMPORT_FILE';

$encodings = array(
	'_' => GetMessage('CRM_FIELD_IMPORT_AUTO_DETECT_ENCODING'),
	'ascii' => 'ASCII',
	'UTF-8' => 'UTF-8',
	'UTF-16' => 'UTF-16',
	'windows-1251' => 'Windows-1251',
	'Windows-1252' => 'Windows-1252',
	'iso-8859-1' => 'ISO-8859-1',
	'iso-8859-2' => 'ISO-8859-2',
	'iso-8859-3' => 'ISO-8859-3',
	'iso-8859-4' => 'ISO-8859-4',
	'iso-8859-5' => 'ISO-8859-5',
	'iso-8859-6' => 'ISO-8859-6',
	'iso-8859-7' => 'ISO-8859-7',
	'iso-8859-8' => 'ISO-8859-8',
	'iso-8859-9' => 'ISO-8859-9',
	'iso-8859-10' => 'ISO-8859-10',
	'iso-8859-13' => 'ISO-8859-13',
	'iso-8859-14' => 'ISO-8859-14',
	'iso-8859-15' => 'ISO-8859-15',
	'koi8-r' => 'KOI8-R'
);

$siteEncoding = mb_strtolower(SITE_CHARSET);
$arResult['ENCODING_SELECTOR_ID'] = 'import_file_encoding';
$arResult['HIDDEN_FILE_IMPORT_ENCODING'] = 'hidden_file_import_encoding';

foreach (array_keys($encodings) as $key)
{
	if ($key !== '_')
		$arResult['CHARSETS'][] = $key;
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_ENCODING',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_ENCODING'),
	'params' => array('id' => $arResult['ENCODING_SELECTOR_ID']),
	'items' => $encodings,
	'type' => 'list',
	'value' => '_'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_RESPONSIBLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_RESPONSIBLE'),
	'type' => 'intranet_user_search',
	'componentParams' => array(
		'NAME' => 'crm_deal_import_responsible',
		'INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_ID',
		'SEARCH_INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_NAME'
	),
	'value' => CCrmPerms::GetCurrentUserID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_NAME_FORMAT',
	'name' => GetMessage('CRM_FIELD_NAME_FORMAT'),
	'items' => $userNameFormats,
	'type' => 'list',
	'value' => \Bitrix\Crm\Format\PersonNameFormatter::FirstLast
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_EXAMPLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_EXAMPLE'),
	'params' => array(),
	'type' => 'label',
	'value' => '<a href="?getSample=csv&ncc=1">'.GetMessage('CRM_DOWNLOAD').'</a>'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FORMAT',
	'name' => GetMessage('CRM_SECTION_IMPORT_FILE_FORMAT'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SEPORATOR',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR'),
	'items' => Array(
		'semicolon' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SEMICOLON'),
		'comma' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_COMMA'),
		'tab' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_TAB'),
		'space' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SPACE'),
	),
	'type' => 'list',
	'value' => isset($_POST['IMPORT_FILE_SEPORATOR'])? $_POST['IMPORT_FILE_SEPORATOR']: 'semicolon'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FIRST_HEADER',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_FIRST_HEADER'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'N'? 'N': 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SKIP_EMPTY',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SKIP_EMPTY'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'N'? 'N': 'Y'
);

for ($i = 1; $i <= 3; $i++):
	if ($arResult['STEP'] != $i)
		$arResult['FIELDS']['tab_'.$i] = array();
endfor;

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');

?>
