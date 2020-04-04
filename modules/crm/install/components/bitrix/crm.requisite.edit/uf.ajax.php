<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION, $CACHE_MANAGER;
if(!function_exists('__CrmConfigFieldEditEndResponse'))
{
	function __CrmConfigFieldEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	__CrmConfigFieldEditEndResponse(array('ERROR' => 'Could not include crm module.'));
}

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'ADD_FIELD' - add new field
 */

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmConfigFieldEditEndResponse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmConfigFieldEditEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

CUtil::JSPostUnescape();
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === 'ADD_FIELD')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmConfigFieldEditEndResponse(array('ERROR' => "The parameter 'data' is not found or empty."));
	}

	$userTypeID = isset($data['USER_TYPE_ID']) ? strtolower($data['USER_TYPE_ID']) : '';
	$entityID = isset($data['ENTITY_ID']) ? $data['ENTITY_ID'] : '';
	$sort = isset($data['SORT']) ? max(intval($data['SORT']), 100) : 100;
	$isMultiple = isset($data['MULTIPLE']) ? strtoupper($data['MULTIPLE']) : '';
	if($isMultiple !== 'Y' && $isMultiple !== 'N')
	{
		$isMultiple = 'N';
	}

	$isMandatory = isset($data['MANDATORY']) ? strtoupper($data['MANDATORY']) : '';
	if($isMandatory !== 'Y' && $isMandatory !== 'N')
	{
		$isMandatory = 'N';
	}

	$showInFilter = isset($data['SHOW_FILTER']) ? strtoupper($data['SHOW_FILTER']) : '';
	if($showInFilter !== 'Y' && $showInFilter !== 'N')
	{
		$showInFilter = 'N';
	}
	$defaultValue = isset($data['DEFAULT_VALUE']) ? $data['DEFAULT_VALUE'] : '';

	$formLabel = isset($data['EDIT_FORM_LABEL']) ? trim($data['EDIT_FORM_LABEL'], " \n\r\t\x0") : '';

	$errorMessages = array();
	if($userTypeID === '')
	{
		$errorMessages[] = "The field 'USER_TYPE_ID' is not defined or empty.";
	}
	elseif(!in_array($userTypeID, array('string', 'integer', 'double', 'boolean', 'datetime'), true))
	{
		$errorMessages[] = "The user type '{$userTypeID}' is not supported in current context.";
	}

	if($entityID === '')
	{
		$errorMessages[] = "The field 'ENTITY_ID' is not defined or empty.";
	}
	else if ($entityID !== 'CRM_REQUISITE')
	{
		$errorMessages[] = "The field 'ENTITY_ID' is incorrect.";
	}

	if($formLabel === '')
	{
		$errorMessages[] = "The field 'EDIT_FORM_LABEL' is not defined or empty.";
	}

	if(!empty($errorMessages))
	{
		__CrmConfigFieldEditEndResponse(array('ERROR' => implode("\n", $errorMessages)));
	}

	$fields = array(
		'USER_TYPE_ID' => $userTypeID,
		'ENTITY_ID' => $entityID,
		'SORT' => $sort,
		'MULTIPLE' => $isMultiple,
		'MANDATORY' => $isMandatory,
		'SHOW_FILTER' => $showInFilter == 'Y' ? 'E' : 'N',
		'SHOW_IN_LIST' => 'Y'
	);

	$fields['SETTINGS'] = array();
	switch ($userTypeID)
	{
		case 'string':
		case 'integer':
		{
			if($defaultValue !== '')
			{
				$fields['SETTINGS']['DEFAULT_VALUE'] = $defaultValue;
			}
		}
		break;
		case 'double':
		{
			if($defaultValue !== '')
			{
				$fields['SETTINGS']['DEFAULT_VALUE'] = $defaultValue;
			}
			$fields['SETTINGS']['PRECISION'] = 2;
		}
		break;
		case 'boolean':
		{
			if($defaultValue !== '')
			{
				$fields['SETTINGS']['DEFAULT_VALUE'] = $defaultValue;
			}
			$fields['MULTIPLE'] = 'N';
			$fields['SETTINGS']['DISPLAY'] = isset($data['DISPLAY']) ? strtoupper($data['DISPLAY']) : 'CHECKBOX';
		}
		break;
		case 'datetime':
		{
			if(isset($data['DT_DEFAULT_VALUE']) && $data['DT_DEFAULT_VALUE'] !== '')
			{
				$fields['SETTINGS']['DEFAULT_VALUE']['VALUE'] = $data['DT_DEFAULT_VALUE'];
			}
			if(isset($data['DT_TYPE']) && $data['DT_TYPE'] !== '')
			{
				$fields['SETTINGS']['DEFAULT_VALUE']['TYPE'] = $data['DT_TYPE'];
			}
		}
		break;
	}

	$fields['EDIT_FORM_LABEL'] = array();
	$fields['LIST_COLUMN_LABEL'] = array();
	$fields['LIST_FILTER_LABEL'] = array();

	$langDbResult = CLanguage::GetList($by = '', $order = '');
	while($lang = $langDbResult->Fetch())
	{
		$lid = $lang['LID'];
		$fields['EDIT_FORM_LABEL'][$lid] = $fields['LIST_COLUMN_LABEL'][$lid] = $fields['LIST_FILTER_LABEL'][$lid] = $formLabel;
	}
	global $USER_FIELD_MANAGER;
	$fieldName = $fields['FIELD_NAME'] = 'UF_CRM_'.time();

	$userField = new CUserTypeEntity();
	$fieldID = $userField->Add($fields);

	if($fieldID > 0)
	{
		//Clear components cache
		$CACHE_MANAGER->ClearByTag("crm_fields_list_{$entityID}");
		__CrmConfigFieldEditEndResponse(array('RESULT' => array('ID' => $fieldID, 'FIELD_NAME' => $fieldName)));
	}
	else
	{
		__CrmConfigFieldEditEndResponse(array('ERROR' => "Could not create user feld."));
	}
}
else
{
	__CrmConfigFieldEditEndResponse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}


