<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\Requisite;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmContact::CheckImportPermission($userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

if(!function_exists('__CrmImportPrepareDupControlTab'))
{
	function __CrmImportPrepareDupControlTab(&$arResult, $arParams)
	{
		$arResult['FIELDS']['tab_2'] = array();
		$arResult['DUP_CONTROL_TYPES'] = array(
			'NO_CONTROL' => GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_DESCR'),
			'REPLACE' => GetMessage('CRM_FIELD_DUP_CONTROL_REPLACE_DESCR'),
			'MERGE' => GetMessage('CRM_FIELD_DUP_CONTROL_MERGE_DESCR'),
			'SKIP' => GetMessage('CRM_FIELD_DUP_CONTROL_SKIP_DESCR')
		);

		$dupControlTypeLock = '';
		$dupControlLockZoneStart = '';
		$dupControlLockZoneEnd = '';
		if(!RestrictionManager::isDuplicateControlPermitted())
		{
			$dupControlLockScript = RestrictionManager::getDuplicateControlRestriction()->prepareInfoHelperScript();
			$dupControlLockZoneStart =
				"<span onclick=\""
				. htmlspecialcharsbx(
					RestrictionManager::getDuplicateControlRestriction()->prepareInfoHelperScript()
				)
				. "; return false;\">"
			;
			$dupControlLockZoneEnd = '</span>';
			$dupControlTypeLock = '<span class="crm-dup-control-type-lock"></span>';
		}

		$dupCtrlPrefix = $arResult['DUP_CONTROL_PREFIX'] = 'dup_ctrl_';
		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_TYPE',
			'type' => 'custom',
			'value' =>
				'<div class="crm-dup-control-type-radio-title">'.GetMessage('CRM_FIELD_DUP_CONTROL_TITLE').':</div>'.
				'<div class="crm-dup-control-type-radio-wrap">'.
				'<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'no_control" name="IMPORT_DUP_CONTROL_TYPE" value="NO_CONTROL" checked="checked" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_CAPTION').'</label>'
				. $dupControlLockZoneStart .
				$dupControlTypeLock . '<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'replace" name="IMPORT_DUP_CONTROL_TYPE" value="REPLACE" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_REPLACE_CAPTION').'</label>'.
				$dupControlTypeLock . '<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'merge" name="IMPORT_DUP_CONTROL_TYPE" value="MERGE" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_MERGE_CAPTION').'</label>'.
				$dupControlTypeLock . '<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'skip" name="IMPORT_DUP_CONTROL_TYPE" value="SKIP" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_SKIP_CAPTION').'</label>'
				. $dupControlLockZoneEnd .
				'</div>',
			'colspan' => true
		);
		unset(
			$dupControlTypeLock,
			$dupControlLockZoneStart,
			$dupControlLockZoneEnd
		);

		$dupControlTypeDescrId = $arResult['DUP_CONTROL_TYPE_DESCR_ID'] = 'dup_ctrl_type_descr';
		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_TYPE_DESCR',
			'type' => 'custom',
			'value' => '<div class="crm-dup-control-type-info" id="'.$dupControlTypeDescrId.'">'.GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_DESCR').'</div>',
			'colspan' => true
		);

		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_CRITERION',
			'name' => GetMessage('CRM_GROUP_DUP_CONTROL_CRITERION'),
			'type' => 'section'
		);

		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_PERSON'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_PHONE',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_PHONE'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_EMAIL',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_EMAIL'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		return '';
	}
}
if(!function_exists('__CrmImportPrepareImportTab'))
{
	function __CrmImportPrepareImportTab(&$arResult, $arParams)
	{
		$arResult['FIELDS']['tab_3'] = array();
		ob_start();
		?><div class="crm_import_entity"><?=GetMessage('CRM_IMPORT_FINISH')?>: <span id="crm_import_entity">0</span> <span id="crm_import_entity_progress"><img src="/bitrix/components/bitrix/crm.contact.import/templates/.default/images/wait.gif" align="absmiddle"></span></div>
		<div id="crm_import_duplicate" class="crm_import_entity"><?=GetMessage('CRM_PROCESSED_DUPLICATES')?>: <span id="crm_import_entity_duplicate">0</span></div>
		<div id="crm_import_error" class="crm_import_error"><?=GetMessage('CRM_IMPORT_ERROR')?>: <span id="crm_import_entity_error">0</span></div>
		<div id="crm_import_errata" class="crm_import_error"><a id="crm_import_entity_errata" href="#"><?=GetMessage('CRM_IMPORT_ERRATA')?></a></div>
		<div id="crm_import_duplicate_file_wrapper" class="crm_import_duplicate_file"><a id="crm_import_duplicate_file_url" href="#"><?=GetMessage('CRM_IMPORT_DUPLICATE_URL')?></a></div>
		<div id="crm_import_example" class="crm_import_example"></div>
		<script>crmImportAjax("<?=$arParams['PATH_TO_CONTACT_IMPORTVCARD_STEP']?>");</script><?
		$html = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_FINISH',
			'name' => '',
			'colspan' => true,
			'type' => 'custom',
			'value' => $html
		);
	}
}
if(!function_exists('__CrmImportWriteDataToFile'))
{
	function __CrmImportWriteDataToFile($filePath, $data)
	{
		$file = fopen($filePath, 'ab');
		$fileSize = filesize($filePath);
		if(is_resource($file))
		{
			if($fileSize > 0)
			{
				fwrite($file, "\n");
			}

			fwrite($file, $data);

			fclose($file);
			unset($file);
		}
	}
}
if(!function_exists('__CrmImportContactAddressesToRequisite'))
{
	function __CrmImportContactAddressesToRequisite($contactFields, $sourceFields, $dupCtrlType, $impAddrPresetId)
	{
		$result = new Result();

		$contactAddressFields = array(
			'ADDRESS',
			'ADDRESS_2',
			'ADDRESS_CITY',
			'ADDRESS_POSTAL_CODE',
			'ADDRESS_REGION',
			'ADDRESS_PROVINCE',
			'ADDRESS_COUNTRY'
		);
		$addressFields = array(
			'ADDRESS_1',
			'ADDRESS_2',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY',
			'COUNTRY_CODE',
			'LOC_ADDR_ID'
		);
		$addresses = array();
		$addrPrefs = array(
			RequisiteAddress::Primary => ''
		);
		foreach ($addrPrefs as $addrTypeId => $addrFieldPref)
		{
			$addrExists = false;
			foreach ($contactAddressFields as $fieldName)
			{
				if (isset($sourceFields[$addrFieldPref.$fieldName]))
				{
					$addrExists = true;
					break;
				}
			}
			if ($addrExists)
			{
				$address = array();
				foreach ($addressFields as $fieldName)
				{
					if ($fieldName === 'ADDRESS_1')
						$contactFieldName = $addrFieldPref.'ADDRESS';
					else if ($fieldName === 'ADDRESS_2')
						$contactFieldName = $addrFieldPref.$fieldName;
					else
						$contactFieldName = $addrFieldPref.'ADDRESS_'.$fieldName;

					$address[$fieldName] =
						isset($sourceFields[$contactFieldName]) ? $sourceFields[$contactFieldName] : null;
				}
				$address['ANCHOR_TYPE_ID'] = CCrmOwnerType::Contact;
				$address['ANCHOR_ID'] = $contactFields['ID'];

				if (RequisiteAddress::isEmpty($address) && !empty($sourceFields['FULL_ADDRESS']))
				{
					$address['ADDRESS_1'] = $sourceFields['FULL_ADDRESS'];
				}

				if (!RequisiteAddress::isEmpty($address))
					$addresses[$addrTypeId] = $address;

				unset($address, $fieldName, $contactFieldName);
			}
			unset($addrExists);
		}

		if (!empty($addresses))
		{
			$errorOccured = false;
			$error = '';
			try
			{
				$clientFullName = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($contactFields['HONORIFIC']) ? $contactFields['HONORIFIC'] : '',
						'NAME' => isset($contactFields['NAME']) ? $contactFields['NAME'] : '',
						'LAST_NAME' => isset($contactFields['LAST_NAME']) ? $contactFields['LAST_NAME'] : '',
						'SECOND_NAME' => isset($contactFields['SECOND_NAME']) ? $contactFields['SECOND_NAME'] : ''
					)
				);
				$rqImportResult = Requisite\ImportHelper::importOldRequisiteAddresses(
					CCrmOwnerType::Contact,
					$contactFields['ID'],
					$dupCtrlType,
					$impAddrPresetId,
					array(
						EntityRequisite::COMPANY_NAME => isset($contactFields['COMPANY_TITLE']) ? $contactFields['COMPANY_TITLE'] : null,
						EntityRequisite::PERSON_FULL_NAME => $clientFullName,
						EntityRequisite::PERSON_FIRST_NAME => isset($contactFields['NAME']) ? $contactFields['NAME'] : null,
						EntityRequisite::PERSON_SECOND_NAME => isset($contactFields['SECOND_NAME']) ? $contactFields['SECOND_NAME'] : null,
						EntityRequisite::PERSON_LAST_NAME => isset($contactFields['LAST_NAME']) ? $contactFields['LAST_NAME'] : null,
						EntityRequisite::ADDRESS => $addresses
					)
				);
				if (!$rqImportResult->isSuccess())
				{
					$notCriticalErrors = array(
						EntityRequisite::ERR_DUP_CTRL_MODE_SKIP => true,
						EntityRequisite::ERR_IMP_PRESET_HAS_NO_ADDR_FIELD => true
					);
					foreach ($rqImportResult->getErrors() as $errorItem)
					{
						if (!isset($notCriticalErrors[$errorItem->getCode()]))
						{
							$errorOccured = true;
							$error = $errorItem->getMessage();
							break;
						}
					}
				}
			}
			catch (SystemException $e)
			{
				$errorOccured = true;
				$error = $e->getMessage();
			}

			if ($errorOccured)
				$result->addError(new Error($error));
		}

		return $result;
	}
}

$arResult['FORM_ID'] = 'CRM_CONTACT_IMPORT_VCARD';
$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_IMPORTVCARD'] = CrmCheckPath('PATH_TO_CONTACT_IMPORTVCARD', $arParams['PATH_TO_CONTACT_IMPORTVCARD'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_IMPORTVCARD_STEP'] = CHTTP::urlAddParams($arParams['PATH_TO_CONTACT_IMPORTVCARD'], array('import'=>''));

$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('CONTACT_TYPE');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');

if (isset($_REQUEST['import']) && isset($_SESSION['CRM_IMPORT_FILE']))
{
	$APPLICATION->RestartBuffer();

	global 	$USER_FIELD_MANAGER;
	$CCrmFieldMulti = new CCrmFieldMulti();
	$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);

	$arResult = Array();
	$arResult['import'] = 0;
	$arResult['duplicate'] = 0;
	$arResult['duplicate_url'] = '';
	$arResult['error'] = 0;
	$arResult['error_data'] = array();
	$arResult['errata_url'] = '';
	$CCrmContact = new CCrmContact();

	$dupCtrlType = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] : '';
	if(
		!RestrictionManager::isDuplicateControlPermitted()
		|| !in_array($dupCtrlType, array('REPLACE', 'MERGE', 'SKIP'), true)
	)
	{
		$dupCtrlType = 'NO_CONTROL';
	}

	$enableDupCtrlByPerson = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME'] : false;
	$enableDupCtrlByPhone = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE'] : false;
	$enableDupCtrlByEmail = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL'] : false;

	$import = new Bitrix\Crm\Import\VCardImport();
	$dupChecker = new \Bitrix\Crm\Integrity\ContactDuplicateChecker();
	$processedQty = 0;
	$itemPerRequest = 5;
	$lastElementPosition = 0;

	$tempDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
	if($tempDir === '')
	{
		$tempDir = $_SESSION['CRM_IMPORT_TEMP_DIR'] = CTempFile::GetDirectoryName(1, array('crm', uniqid('contact_vcard_import_')));
		CheckDirPath($tempDir);
	}
	$errataFilePath = "{$tempDir}errata.csv";

	$enableDupFile = $dupCtrlType === 'SKIP';
	if($enableDupFile)
	{
		$duplicateFilePath = "{$tempDir}duplicate.csv";
	}

	$impAddrToRequisite = (isset($_SESSION['CRM_IMPORT_ADDR_TO_REQUISITE']) && $_SESSION['CRM_IMPORT_ADDR_TO_REQUISITE'] == 'Y');
	$impAddrPresetId = isset($_SESSION['CRM_IMPORT_ADDR_PRESET']) ? (int)$_SESSION['CRM_IMPORT_ADDR_PRESET'] : -1;

	$defaultContactTypeID = isset($_SESSION['CRM_IMPORT_DEFAULT_TYPE_ID']) ? $_SESSION['CRM_IMPORT_DEFAULT_TYPE_ID'] : '';
	$defaultSourceID = isset($_SESSION['CRM_IMPORT_DEFAULT_SOURCE_ID']) ? $_SESSION['CRM_IMPORT_DEFAULT_SOURCE_ID'] : '';
	$defaultSourceDescription = isset($_SESSION['CRM_IMPORT_DEFAULT_SOURCE_DESCRIPTION']) ? $_SESSION['CRM_IMPORT_DEFAULT_SOURCE_DESCRIPTION'] : '';
	$defaultOpened = isset($_SESSION['CRM_IMPORT_DEFAULT_OPENED']) ? $_SESSION['CRM_IMPORT_DEFAULT_OPENED'] : '';
	$defaultExport = isset($_SESSION['CRM_IMPORT_DEFAULT_EXPORT']) ? $_SESSION['CRM_IMPORT_DEFAULT_EXPORT'] : '';
	$defaultUserID = isset($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) ? intval($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) : 0;

	$reader = new Bitrix\Crm\VCard\VCardReader($_SESSION['CRM_IMPORT_FILE'], $_SESSION['CRM_IMPORT_FILE_POS']);
	$reader->open();
	while($reader->readElement())
	{
		$element = $reader->getElement();
		if(!$import->mapContact($element))
		{
			$processedQty++;
			if($processedQty === $itemPerRequest)
			{
				break;
			}

			continue;
		}

		$fields = $import->getFields();

		if($defaultContactTypeID !== '')
		{
			$fields['TYPE_ID'] = $defaultContactTypeID;
		}

		if($defaultSourceID !== '')
		{
			$fields['SOURCE_ID'] = $defaultSourceID;
		}

		if($defaultSourceDescription !== '')
		{
			$fields['SOURCE_DESCRIPTION'] = $defaultSourceDescription;
		}

		if($defaultOpened !== '')
		{
			$fields['OPENED'] = $defaultOpened;
		}

		if($defaultExport !== '')
		{
			$fields['EXPORT'] = $defaultExport;
		}

		if($defaultUserID > 0)
		{
			$fields['ASSIGNED_BY_ID'] = $defaultUserID;
		}

		$mappedFields = array_merge(
			$import->getMappedFields(),
				array('TYPE_ID', 'SOURCE_ID', 'SOURCE_DESCRIPTION', 'OPENED', 'EXPORT')
		);

		$mappedMultiFields = $import->getMappedMultiFields();

		$contactSourceFields = $fields;

		$isDuplicate = false;
		if($dupCtrlType !== 'NO_CONTROL'
			&& ($enableDupCtrlByPerson || $enableDupCtrlByPhone || $enableDupCtrlByEmail))
		{
			$fieldNames = array();
			if($enableDupCtrlByPerson)
			{
				$fieldNames[] = 'NAME';
				$fieldNames[] = 'SECOND_NAME';
				$fieldNames[] = 'LAST_NAME';
			}
			if($enableDupCtrlByPhone)
			{
				$fieldNames[] = 'FM.PHONE';
			}
			if($enableDupCtrlByEmail)
			{
				$fieldNames[] = 'FM.EMAIL';
			}

			$adapter = \Bitrix\Crm\EntityAdapterFactory::create($fields, CCrmOwnerType::Contact);
			$dups = $dupChecker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams($fieldNames));

			$dupIDs = array();
			foreach($dups as &$dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				if(empty($dupIDs))
				{
					$dupIDs = $dup->getEntityIDsByType(CCrmOwnerType::Contact);
				}
				else
				{
					$dupIDs = array_intersect($dupIDs, $dup->getEntityIDsByType(CCrmOwnerType::Contact));
				}
			}
			unset($dup);

			if(!empty($dupIDs))
			{
				$isDuplicate = true;

				if($dupCtrlType !== 'SKIP')
				{
					$dupItems = array();
					$dbResult = CCrmContact::GetListEx(array(), array('@ID' => $dupIDs, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('*', 'UF_*'));

					if(is_object($dbResult))
					{
						while($dupItem = $dbResult->Fetch())
						{
							$dupItem['FM'] = array();
							$dbMultiFields = CCrmFieldMulti::GetList(
								array('ID' => 'asc'),
								array('ENTITY_ID' => CCrmOwnerType::ContactName, 'ELEMENT_ID' => $dupItem['ID'])
							);
							while($multiFields = $dbMultiFields->Fetch())
							{
								$dupItem['FM'][$multiFields['TYPE_ID']][$multiFields['ID']] =
									array(
										'VALUE' => $multiFields['VALUE'],
										'VALUE_TYPE' => $multiFields['VALUE_TYPE']
									);
							}

							$dupItems[] = &$dupItem;
							unset($dupItem);
						}
					}

					//Preparing multifields
					$multiFieldValues = array();
					$multiFields = isset($fields['FM']) ? $fields['FM'] : array();
					if(!empty($multiFields))
					{
						foreach($mappedMultiFields as $type => &$valueTypes)
						{
							if(!isset($multiFields[$type]))
							{
								continue;
							}

							$multiFieldData = $multiFields[$type];
							if(empty($multiFieldData))
							{
								continue;
							}

							foreach($valueTypes as $valueType)
							{
								foreach($multiFieldData as $multiFieldItem)
								{
									$itemValueType = isset($multiFieldItem['VALUE_TYPE']) ? $multiFieldItem['VALUE_TYPE'] : '';
									$itemValue = isset($multiFieldItem['VALUE']) ? $multiFieldItem['VALUE'] : '';
									if($itemValueType === $valueType && $itemValue !== '')
									{
										if(!isset($multiFieldValues[$type]))
										{
											$multiFieldValues[$type] = array();
										}
										if(!isset($multiFieldValues[$type][$valueType]))
										{
											$multiFieldValues[$type][$valueType] = array();
										}
										$multiFieldValues[$type][$valueType][] = array(
											'VALUE' => $itemValue,
											'CODE' => \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::prepareCode($type, $itemValue)
										);
									}
								}
							}
						}
						unset($valueTypes);
					}

					$isRewrite = $dupCtrlType === 'REPLACE';
					foreach($dupItems as &$item)
					{
						foreach($mappedFields as $fieldName)
						{
							if($isRewrite)
							{
								if(isset($fields[$fieldName]))
								{
									$item[$fieldName] = $fields[$fieldName];
								}
							}
							else
							{
								if(isset($fields[$fieldName]) && !empty($fields[$fieldName]))
								{
									if($fieldName === 'COMMENTS')
									{
										// HACK: Ignore line break tags in HTML
										$comments = isset($item[$fieldName]) ? $item[$fieldName] : '';
										if($comments !== '')
										{
											$comments = trim(preg_replace('/<br[\/]?>/i', '', $comments));
										}
										if($comments === '')
										{
											$item['COMMENTS'] = $fields['COMMENTS'];
										}
									}
									elseif((!isset($item[$fieldName]) || empty($item[$fieldName])))
									{
										$item[$fieldName] = $fields[$fieldName];
									}
								}
							}
						}

						foreach($mappedMultiFields as $type => &$valueTypes)
						{
							if(!isset($multiFieldValues[$type]))
							{
								continue;
							}

							foreach($valueTypes as $valueType)
							{
								if(!isset($multiFieldValues[$type][$valueType]))
								{
									continue;
								}

								$values = $multiFieldValues[$type][$valueType];
								$valueCount = count($values);
								if($valueCount > 0)
								{
									if($isRewrite)
									{
										if(isset($item['FM'][$type]))
										{
											foreach($item['FM'][$type] as $k => $v)
											{
												if($v['VALUE_TYPE'] === $valueType)
												{
													//Mark item for delete
													unset($item['FM'][$type][$k]['VALUE']);
												}
											}
										}

										if(!isset($item['FM'][$type]))
										{
											$item['FM'][$type] = array();
										}

										for($i = 0; $i < $valueCount; $i++)
										{
											$item['FM'][$type]['n'.strval($i + 1)] = array(
												'VALUE_TYPE' => $valueType,
												'VALUE' => $values[$i]['VALUE']
											);
										}
									}
									else
									{
										if(isset($item['FM'][$type]) && !empty($item['FM'][$type]))
										{
											$valuesToAdd = array();
											foreach($values as &$value)
											{
												$code = $value['CODE'];
												$isFound = false;
												foreach($item['FM'][$type] as $k => &$v)
												{
													if($v['VALUE_TYPE'] === $valueType)
													{
														if($code === \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::prepareCode($type, $v['VALUE']))
														{
															$isFound = true;
															break;
														}
													}
												}
												unset($v);

												if(!$isFound)
												{
													$valuesToAdd[] = $value['VALUE'];
												}
											}
											unset($value);

											$valueToAddCount = count($valuesToAdd);
											if($valueToAddCount > 0)
											{
												for($i = 0; $i < $valueToAddCount; $i++)
												{
													$item['FM'][$type]['n'.strval($i + 1)] = array(
														'VALUE_TYPE' => $valueType,
														'VALUE' => $valuesToAdd[$i]
													);
												}
											}
										}
										else
										{
											if(!isset($item['FM'][$type]))
											{
												$item['FM'][$type] = array();
											}

											for($i = 0; $i < $valueCount; $i++)
											{
												$item['FM'][$type]['n'.strval($i + 1)] = array(
													'VALUE_TYPE' => $valueType,
													'VALUE' => $values[$i]['VALUE']
												);
											}
										}
									}
								}
							}
						}
						unset($valueTypes);

						$errorOccured = false;
						if ($impAddrToRequisite)
						{
							$importResult = __CrmImportContactAddressesToRequisite(
								$item, $contactSourceFields, $dupCtrlType, $impAddrPresetId
							);

							if (!$importResult->isSuccess())
							{
								$errorOccured = true;
								$errors = $importResult->getErrorMessages();
								if (!empty($errors))
								{
									$elementContent = $reader->getElementContent();
									$arResult['error']++;
									$arResult['error_data'][] = Array(
										'message' => CCrmComponentHelper::encodeErrorMessage((string)$errors[0] ?? ''),
										'data' => $elementContent
									);

									__CrmImportWriteDataToFile($errataFilePath, $elementContent);
								}
								unset($errors);
							}
						}

						if (!$errorOccured)
							$CCrmContact->Update($item['ID'], $item, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
					}
					unset($item);
				}
			}
		}

		if($isDuplicate)
		{
			$arResult['duplicate']++;
			if($enableDupFile)
			{
				__CrmImportWriteDataToFile($duplicateFilePath, $reader->getElementContent());
			}
		}
		else
		{
			if (isset($fields['COMPANY_TITLE']) && $fields['COMPANY_TITLE'] !== '')
			{
				$companyTitle = $fields['COMPANY_TITLE'];

				$dbResult = CCrmCompany::GetListEx(array(), array('TITLE' => $companyTitle, '@CATEGORY_ID' => 0,), false, false, array('ID'));
				$companyFields = is_object($dbResult) ? $dbResult->Fetch() : null;
				if(is_array($companyFields))
				{
					$fields['COMPANY_ID'] = $companyFields['ID'];
				}
				else
				{
					//Try to create new company
					$companyFields = array('TITLE' => $companyTitle);
					if(isset($fields['COMPANY_LOGO']) && is_array($fields['COMPANY_LOGO']))
					{
						$companyFields['LOGO'] = $fields['COMPANY_LOGO'];
					}

					$companyEntity = new CCrmCompany(false);
					$newCompanyID = $companyEntity->Add($companyFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
					if(is_integer($newCompanyID) && $newCompanyID > 0)
					{
						$fields['COMPANY_ID'] = $newCompanyID;
					}
				}
			}

			$fields['PERMISSION'] = 'IMPORT';
			if(!$CCrmContact->Add($fields, true, array('DISABLE_USER_FIELD_CHECK' => true)))
			{
				$elementContent = $reader->getElementContent();
				$arResult['error']++;
				$arResult['error_data'][] = Array(
					'message' => CCrmComponentHelper::encodeErrorMessage((string)$fields['RESULT_MESSAGE'] ?? ''),
					'data' => $elementContent
				);

				__CrmImportWriteDataToFile($errataFilePath, $elementContent);
			}
			elseif(!empty($fields))
			{
				if ($impAddrToRequisite)
				{
					$importResult = __CrmImportContactAddressesToRequisite($fields, $contactSourceFields, $dupCtrlType, $impAddrPresetId);

					if (!$importResult->isSuccess())
					{
						$errors = $importResult->getErrorMessages();
						if (!empty($errors))
						{
							$elementContent = $reader->getElementContent();
							$arResult['error']++;
							$arResult['error_data'][] = Array(
								'message' => CCrmComponentHelper::encodeErrorMessage((string)$errors[0] ?? ''),
								'data' => $elementContent
							);

							__CrmImportWriteDataToFile($errataFilePath, $elementContent);
						}
						unset($errors);
					}
				}

				$arResult['import']++;
			}

		}

		$processedQty++;
		$lastElementPosition = $reader->getElementBorderPosition();
		if($processedQty === $itemPerRequest)
		{
			break;
		}
	}
	$_SESSION['CRM_IMPORT_FILE_POS'] = $lastElementPosition > 0 ? $lastElementPosition : $reader->getFilePosition();
	$reader->close();

	if($arResult['error'] > 0)
	{
		$arResult['errata_url'] = SITE_DIR.'bitrix/components/bitrix/crm.contact.import.vcard/show_file.php?name=errata';
	}

	if($enableDupFile && $arResult['duplicate'] > 0)
	{
		$arResult['duplicate_url'] = SITE_DIR.'bitrix/components/bitrix/crm.contact.import.vcard/show_file.php?name=duplicate';
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}

$currentStep = 1;
if(isset($_POST['step']))
{
	$currentStep = (int)$_POST['step'];
	if($currentStep <= 0)
	{
		$currentStep = 1;
	}
}
$arResult['STEP'] = $currentStep;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if(isset($_POST['next']))
	{
		if($currentStep === 1)
		{
			$errorOccured = false;
			$errorMsg = '';

			if($_FILES['IMPORT_FILE']['error'] > 0)
			{
				$errorOccured = true;
				$errorMsg = GetMessage('CRM_CSV_NF_ERROR');
			}
			if (isset($_POST['IMPORT_ADDR_TO_REQUISITE']) && $_POST['IMPORT_ADDR_TO_REQUISITE'] == 'Y'
				&& isset($_POST['IMPORT_ADDR_PRESET']) && $_POST['IMPORT_ADDR_PRESET'] <= 0)
			{
				$errorOccured = true;
				if (!empty($errorMsg))
					$errorMsg .= '<br>';
				$errorMsg .= GetMessage('CRM_INVALID_IMP_ADDR_PRESET_ID');
			}

			if($errorOccured)
			{
				ShowError($errorMsg);
			}
			else
			{
				$file = new CFile();
				$error = $file->CheckFile($_FILES['IMPORT_FILE'], 0, false, 'vcf');
				if($error !== '')
				{
					ShowError($error);
				}
				else
				{
					if(isset($_SESSION['CRM_IMPORT_FILE']))
					{
						unset($_SESSION['CRM_IMPORT_FILE']);
					}

					$filePath = CTempFile::GetDirectoryName(12, 'crm');
					CheckDirPath($filePath);

					$_SESSION['CRM_IMPORT_FILE'] = $filePath.md5($_FILES['IMPORT_FILE']['tmp_name']).'.tmp';
					$_SESSION['CRM_IMPORT_FILE_POS'] = 0;
					move_uploaded_file($_FILES['IMPORT_FILE']['tmp_name'], $_SESSION['CRM_IMPORT_FILE']);
					@chmod($_SESSION['CRM_IMPORT_FILE'], BX_FILE_PERMISSIONS);

					if(isset($_POST['IMPORT_FILE_ENCODING']))
					{
						$fileEncoding = mb_strtolower($_POST['IMPORT_FILE_ENCODING']);

						if ($fileEncoding == '_' && isset($_POST['hidden_file_import_encoding']))
						{
							$fileEncoding = $_POST['hidden_file_import_encoding'];
						}

						if($fileEncoding !== '' && $fileEncoding !== '_' && $fileEncoding !== mb_strtolower(SITE_CHARSET))
						{
							$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'rb');
							$fileContents = fread($fileHandle, filesize($_SESSION['CRM_IMPORT_FILE']));
							fclose($fileHandle);
							unset($fileHandle);

							//HACK: Remove UTF-8/UTF-16 BOM
							if($fileEncoding === 'utf-8')
							{
								$bom = mb_substr($fileContents, 0, 3);
								if($bom === "\xEF\xBB\xBF")
								{
									$fileContents = mb_substr($fileContents, 3);
								}
							}
							elseif($fileEncoding === 'utf-16')
							{
								$bom = mb_substr($fileContents, 0, 2);
								if($bom === "\xFF\xFE" || $bom === "\xFE\xFF")
								{
									$fileContents = mb_substr($fileContents, 2);
								}
							}

							$fileContents = \Bitrix\Main\Text\Encoding::convertEncoding($fileContents, $fileEncoding, SITE_CHARSET);

							$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'wb');
							fwrite($fileHandle, $fileContents);
							fclose($fileHandle);
							unset($fileHandle);
						}
					}

					$defaultContactTypeID = isset($_POST['IMPORT_DEFAULT_TYPE_ID']) ? $_POST['IMPORT_DEFAULT_TYPE_ID'] : '';
					if($defaultContactTypeID !== '' && isset($arResult['TYPE_LIST'][$defaultContactTypeID]))
					{
						$_SESSION['CRM_IMPORT_DEFAULT_TYPE_ID'] = $defaultContactTypeID;
					}

					$defaultSourceID = isset($_POST['IMPORT_DEFAULT_SOURCE_ID']) ? $_POST['IMPORT_DEFAULT_SOURCE_ID'] : '';
					if($defaultSourceID !== '' && isset($arResult['SOURCE_LIST'][$defaultSourceID]))
					{
						$_SESSION['CRM_IMPORT_DEFAULT_SOURCE_ID'] = $defaultSourceID;
					}

					$_SESSION['CRM_IMPORT_DEFAULT_SOURCE_DESCRIPTION'] = isset($_POST['IMPORT_DEFAULT_SOURCE_DESCRIPTION']) ? strip_tags($_POST['IMPORT_DEFAULT_SOURCE_DESCRIPTION']) : '';
					$_SESSION['CRM_IMPORT_DEFAULT_OPENED'] = isset($_POST['IMPORT_DEFAULT_OPENED']) && mb_strtoupper($_POST['IMPORT_DEFAULT_OPENED']) === 'Y' ? 'Y' : 'N';
					$_SESSION['CRM_IMPORT_DEFAULT_EXPORT'] = isset($_POST['IMPORT_DEFAULT_EXPORT']) && mb_strtoupper($_POST['IMPORT_DEFAULT_EXPORT']) === 'Y' ? 'Y' : 'N';
					$_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID'] = isset($_POST['IMPORT_DEFAULT_RESPONSIBLE_ID']) ? $_POST['IMPORT_DEFAULT_RESPONSIBLE_ID'] : '';

					$_SESSION['CRM_IMPORT_ADDR_TO_REQUISITE'] = (isset($_POST['IMPORT_ADDR_TO_REQUISITE']) && $_POST['IMPORT_ADDR_TO_REQUISITE'] == 'Y') ? 'Y' : 'N';
					$_SESSION['CRM_IMPORT_ADDR_PRESET'] = (isset($_POST['IMPORT_ADDR_PRESET']) && $_POST['IMPORT_ADDR_PRESET'] > 0) ? (int)$_POST['IMPORT_ADDR_PRESET'] : 0;

					$error = __CrmImportPrepareDupControlTab($arResult, $arParams);
					if($error !== '')
					{
						ShowError($error);
					}
					else
					{
						$arResult['STEP'] = $currentStep + 1;
					}
				}
			}
		}
		else if ($arResult['STEP'] == 2)
		{
			$_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] = isset($_POST['IMPORT_DUP_CONTROL_TYPE']) ? $_POST['IMPORT_DUP_CONTROL_TYPE'] : '';
			if(
				!RestrictionManager::isDuplicateControlPermitted()
				|| $_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] === ''
			)
			{
				$_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] = 'NO_CONTROL';
			}

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_PERSON_NAME'] == 'Y' ? true: false;

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_PHONE'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_PHONE'] == 'Y' ? true: false;

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_EMAIL'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_EMAIL'] == 'Y' ? true: false;

			//CLEAR ERRATA BEFORE IMPORT START -->
			$tempDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
			if($tempDir !== '')
			{
				@unlink("{$tempDir}errata.vcf");
				@unlink("{$tempDir}duplicate.vcf");
				@rmdir($tempDir);
				unset($_SESSION['CRM_IMPORT_TEMP_DIR']);
			}
			//<-- CLEAR ERRATA BEFORE IMPORT START

			__CrmImportPrepareImportTab($arResult, $arParams);
			$arResult['STEP'] = $currentStep + 1;
		}
		else if ($arResult['STEP'] == 3)
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
			{
				if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
				{
					unset($_SESSION[$key]);
				}
			}

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array()));
		}
		else
		{
			$arResult['STEP'] = 1;
		}
	}
	else if (isset($_POST['previous']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
		{
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
			{
				unset($_SESSION[$key]);
			}
		}
		$arResult['STEP'] = 1;
	}
	else if (isset($_POST['cancel']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
		{
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
			{
				unset($_SESSION[$key]);
			}
		}
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array()));
	}
}

$arResult['FIELDS']['tab_1'] = array();
//IMPORT_FILE -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE'),
	'params' => array(),
	'type' => 'file',
	'required' => true
);
//<-- IMPORT_FILE
$arResult['IMPORT_FILE'] = 'IMPORT_FILE';

//IMPORT_FILE_ENCODING -->
$encodings = array(
	'_' => GetMessage('CRM_FIELD_IMPORT_AUTO_DETECT_ENCODING'),
	'ascii' => 'ASCII',
	'UTF-8' => 'UTF-8',
	'UTF-16' => 'UTF-16',
	'windows-1251' => 'Windows-1251',
	'windows-1252' => 'Windows-1252',
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
//<-- IMPORT_FILE_ENCODING

//IMPORT_TYPE_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_TYPE_ID',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => !empty($arResult['TYPE_LIST']) ? key($arResult['TYPE_LIST']) : ''
);
//<-- IMPORT_TYPE_ID

//IMPORT_SOURCE_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => !empty($arResult['SOURCE_LIST']) ? key($arResult['SOURCE_LIST']) : ''
);
//<-- IMPORT_SOURCE_ID

//IMPORT_SOURCE_DESCRIPTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => ''
);
//<-- IMPORT_SOURCE_DESCRIPTION

//IMPORT_OPENED -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_OPENED',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_OPENED'),
	'type' => 'checkbox',
	'value' => false
);
//<-- IMPORT_OPENED

//IMPORT_EXPORT -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_EXPORT',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_EXPORT_NEW'),
	'type' => 'checkbox',
	'value' => false
);
//<-- IMPORT_EXPORT

//IMPORT_RESPONSIBLE -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_RESPONSIBLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_RESPONSIBLE'),
	'type' => 'intranet_user_search',
	'componentParams' => array(
		'NAME' => 'crm_contact_import_responsible',
		'INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_ID',
		'SEARCH_INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_NAME'
	),
	'value' => CCrmPerms::GetCurrentUserID()
);
//<-- IMPORT_RESPONSIBLE

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_ADDR_PARAMS',
	'name' => GetMessage('CRM_SECTION_IMPORT_ADDR_PARAMS'),
	'type' => 'section'
);

$paramValue = Bitrix\Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled() ? 'N' : 'Y';
if (isset($_POST['IMPORT_ADDR_TO_REQUISITE']))
{
	$paramValue = ($_POST['IMPORT_ADDR_TO_REQUISITE'] == 'Y') ? 'Y': 'N';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_ADDR_TO_REQUISITE',
	'name' => GetMessage('CRM_FIELD_IMPORT_ADDR_TO_REQUISITE'),
	'type' => 'checkbox',
	'value' => $paramValue
);
unset($paramValue);

// region Preset list
$preset = new EntityPreset();
if (isset($_POST['IMPORT_ADDR_PRESET']))
	$defPresetId = $_POST['IMPORT_ADDR_PRESET'];
else
	$defPresetId = EntityRequisite::getDefaultPresetId(CCrmOwnerType::Contact);
$presetList = array(0 => GetMessage('CRM_FIELD_IMPORT_ADDR_PRESET_UNDEFINED'));
$res = $preset->getList(array(
	'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
	'filter' => array(
		'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
		'=ACTIVE' => 'Y'
	),
	'select' => array('ID', 'NAME')
));
while ($row = $res->fetch())
{
	$presetTitle = trim(strval($row['NAME']));
	if (empty($presetTitle))
		$presetTitle = '['.$row['ID'].'] - '.GetMessage('CRM_REQUISITE_PRESET_NAME_EMPTY');
	$presetList[$row['ID']] = $presetTitle;
}
unset($preset, $res, $row, $presetTitle);
if (!isset($presetList[$defPresetId]))
	$defPresetId = 0;
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_ADDR_PRESET',
	'name' => GetMessage('CRM_FIELD_IMPORT_ADDR_PRESET'),
	'items' => $presetList,
	'type' => 'list',
	'value' => $defPresetId
);
unset($presetList, $defPresetId);
// endregion Preset list

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');