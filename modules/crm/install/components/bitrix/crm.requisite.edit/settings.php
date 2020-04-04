<?php

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/** @global CUser $USER */
global $USER;

if ($USER->IsAuthorized() && check_bitrix_sessid())
{

	$actionSaveSettings = ($_REQUEST['action'] === 'savesettings');
	$actionEnable = ($_REQUEST['action'] === 'enable');
	if($actionSaveSettings || $actionEnable)
	{
		CUtil::decodeURIComponent($_REQUEST);

		$presetId = 0;
		if (isset($_REQUEST['FORM_ID']))
		{
			$formId = strval($_REQUEST['FORM_ID']);
			if (!empty($formId))
			{
				$matches = array();
				if (preg_match('/_PID(\d+)$/', $formId, $matches))
					$presetId = intval($matches[1]);
			}
		}
		$presetInfo = null;
		if ($presetId > 0
			&& (($actionSaveSettings && is_array($_REQUEST['tabs'])) || $actionEnable)
			&& \Bitrix\Main\Loader::includeModule('crm'))
		{
			// region Form settings
			$formSettingsId = preg_replace('/[^a-z0-9_]/i', '', $formId);
			if (preg_match('/^([a-z0-9_]+)_(n?\d+)_PID(\d+)$/i', $formId, $matches))
			{
				$formSettingsId = $matches[1].'_0_PID'.$matches[3];
				$aOptions = CUserOptions::GetOption('crm.requisite.edit', $formSettingsId, array());

				if ($actionEnable)
				{
					$aOptions['settings_disabled'] = ($_REQUEST['enabled'] == 'Y'? 'N':'Y');
				}
				else if ($actionSaveSettings)
				{
					$aOptions['tabs'] = $_REQUEST['tabs'];
					$aOptions['settings_disabled'] = 'N';

					if($_REQUEST['set_default_settings'] == 'Y' && $USER->CanDoOperation('edit_other_settings'))
					{
						if($_REQUEST['delete_users_settings'] == 'Y')
						{
							CUserOptions::DeleteOptionsByName('crm.requisite.edit', $formSettingsId);
						}
						CUserOptions::SetOption('crm.requisite.edit', $formSettingsId, $aOptions, true);
					}
				}

				CUserOptions::SetOption('crm.requisite.edit', $formSettingsId, $aOptions);
			}
			// endregion Form settings

			$canEditPreset = \Bitrix\Crm\EntityPreset::checkUpdatePermission();
			if ($actionSaveSettings && $canEditPreset)
			{
				$preset = new \Bitrix\Crm\EntityPreset();
				$presetInfo = $preset->getById($presetId);
				if (is_array($presetInfo))
				{
					if (!is_array($presetInfo['SETTINGS']))
						$presetInfo['SETTINGS'] = array();

					// index fields
					$presetFields = array();
					foreach ($preset->settingsGetFields($presetInfo['SETTINGS']) as $index => $fieldInfo)
						$presetFields[$fieldInfo['FIELD_NAME']] = array('index' => $index, 'info' => $fieldInfo);
					unset($index, $fieldInfo);

					$requisite = new \Bitrix\Crm\EntityRequisite();
					$fields = array_merge($requisite->getRqFields(), $requisite->getUserFields());
					$fieldsAllowed = array();
					foreach ($fields as $fieldName)
						$fieldsAllowed[$fieldName] = true;
					unset($fields, $fieldName);

					$requisiteFieldTitles = $requisite->getFieldsTitles($presetInfo['COUNTRY_ID']);

					$formFields = array();
					$sort = 490;
					foreach ($_REQUEST['tabs'] as &$tab)
					{
						if (is_array($tab['fields']))
						{
							foreach ($tab['fields'] as $field)
							{
								if (isset($field['id']) && !empty($field['id'])
									&& isset($field['type']) && $field['type'] !== 'section')
								{
									$fieldName = strval($field['id']);
									$fieldTitle = isset($field['name']) ? strval($field['name']) : '';
									$fieldInShortList = false;
									if (isset($field['isRQ']) && $field['isRQ'] === 'true'
										&& isset($field['inShortList']) && $field['inShortList'] === 'true')
									{
										$fieldInShortList = true;
									}
									if (isset($fieldsAllowed[$fieldName]))
									{
										if (!isset($formFields[$fieldName]))
											$sort += 10;
										$formFields[$fieldName] = array(
											'title' => $fieldTitle,
											'sort' => $sort,
											'inShortList' => $fieldInShortList
										);
									}
								}
							}
						}
					}
					unset($tab, $field, $fieldName, $fieldTitle);

					// delete fields
					/*foreach ($presetFields as $fieldName => $fieldInfo)
					{
						if (!isset($formFields[$fieldName]))
						{
							$preset->settingsDeleteField(
								$presetInfo['SETTINGS'],
								$fieldInfo['info']['ID'],
								$fieldInfo['index']
							);
							unset($presetFields[$fieldName]);
						}
					}
					unset($fieldName, $fieldInfo);*/

					// update fields
					foreach ($presetFields as $fieldName => $fieldInfo)
					{
						if (isset($formFields[$fieldName]))
						{
							$fieldTitle = trim(strval($formFields[$fieldName]['title']));
							if (isset($requisiteFieldTitles[$fieldName]))
							{
								if ($fieldTitle === $requisiteFieldTitles[$fieldName])
									$fieldTitle = '';
							}
							$preset->settingsUpdateField(
								$presetInfo['SETTINGS'],
								array(
									'ID' => $fieldInfo['info']['ID'],
									'FIELD_TITLE' => $fieldTitle,
									'SORT' => $formFields[$fieldName]['sort'],
									'IN_SHORT_LIST' => ($formFields[$fieldName]['inShortList'] ? 'Y' : 'N')
								),
								$fieldInfo['index']
							);
						}
					}
					unset($fieldName, $fieldInfo);

					// add fields
					$maxSort = $sort = 0;
					foreach ($presetFields as $field)
					{
						$sort = isset($field['info']['SORT']) ? (int)$field['info']['SORT'] : 0;
						if ($sort > $maxSort)
							$maxSort = $sort;
					}
					unset($sort, $field);
					foreach ($formFields as $fieldName => $fieldInfo)
					{
						if (!isset($presetFields[$fieldName]))
						{
							$fieldTitle = trim(strval($formFields[$fieldName]['title']));
							if (isset($requisiteFieldTitles[$fieldName]))
							{
								if ($fieldTitle === $requisiteFieldTitles[$fieldName])
									$fieldTitle = '';
							}
							$field = array(
								'FIELD_NAME' => $fieldName,
								'FIELD_TITLE' => $fieldTitle,
								'IN_SHORT_LIST' => ($formFields[$fieldName]['inShortList'] ? 'Y' : 'N'),
								'SORT' => $maxSort += 10
							);
							$preset->settingsAddField($presetInfo['SETTINGS'], $field);
						}
					}
					unset($fieldName, $fieldInfo, $field);

					// save fields
					$preset->update($presetId, array('SETTINGS' => $presetInfo['SETTINGS']));
				}
			}
		}
	}
	else if($_REQUEST["action"] == "savelastselectedpreset")
	{
		$presetId = 0;
		if (isset($_REQUEST['presetId']))
			$presetId = (int)$_REQUEST['presetId'];
		if ($presetId < 0)
			$presetId = 0;

		$entityTypeId = 0;
		if (isset($_REQUEST['requisiteEntityTypeId']))
			$entityTypeId = (int)$_REQUEST['requisiteEntityTypeId'];
		if ($entityTypeId < 0)
			$entityTypeId = 0;

		if ($entityTypeId > 0 && \Bitrix\Main\Loader::includeModule('crm'))
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			if ($requisite->checkEntityType($entityTypeId))
			{
				$optionData = \CUserOptions::GetOption('crm', 'crm_preset_last_selected', array());
				if (!is_array($optionData))
					$optionData = array();
				$optionData[$entityTypeId] = $presetId;
				\CUserOptions::SetOption('crm', 'crm_preset_last_selected', $optionData);
			}
		}
	}
	else if($_REQUEST["action"] == "savelastselectedrequisite")
	{
		$entityTypeId = 0;
		if (isset($_REQUEST['requisiteEntityTypeId']))
			$entityTypeId = (int)$_REQUEST['requisiteEntityTypeId'];
		if ($entityTypeId < 0)
			$entityTypeId = 0;

		$entityId = 0;
		if (isset($_REQUEST['requisiteEntityId']))
			$entityId = (int)$_REQUEST['requisiteEntityId'];
		if ($entityId < 0)
			$entityId = 0;

		$requisiteId = 0;
		if (isset($_REQUEST['requisiteId']))
			$requisiteId = (int)$_REQUEST['requisiteId'];
		if ($requisiteId < 0)
			$requisiteId = 0;

		$bankDetailId = 0;
		if (isset($_REQUEST['bankDetailId']))
			$bankDetailId = (int)$_REQUEST['bankDetailId'];
		if ($bankDetailId < 0)
			$bankDetailId = 0;

		if ($entityTypeId > 0 && $entityId > 0 && $requisiteId > 0 && \Bitrix\Main\Loader::includeModule('crm'))
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			if ($requisite->validateEntityExists($entityTypeId, $entityId))
			{
				$settings = $requisite->loadSettings($entityTypeId, $entityId);
				if (!is_array($settings))
					$settings = array();
				$settings['REQUISITE_ID_SELECTED'] = $requisiteId;
				$settings['BANK_DETAIL_ID_SELECTED'] = $bankDetailId;
				$requisite->saveSettings($entityTypeId, $entityId, $settings);
			}
		}
	}
}
echo "OK";
