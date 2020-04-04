<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	$APPLICATION->ThrowException(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	$APPLICATION->ThrowException(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

function applyNumberTemplateSettings($entityName, $template, $number, $prefix, $randomLength, $datePeriod)
{
	global $APPLICATION;

	$strWarning = '';

	if (!checkNumberValue(
		$template,
		$number,
		$prefix))
	{
		if ($template == "PREFIX")
			$strWarning .= GetMessage("CRM_NUMBER_PREFIX_WARNING", array("#PREFIX#" => $prefix)).'<br>';
		elseif ($template == "NUMBER")
			$strWarning .= GetMessage("CRM_NUMBER_NUMBER_WARNING", array("#NUMBER#" => $number)).'<br>';
	}

	if(strlen($strWarning) > 0)
	{
		$APPLICATION->ThrowException($strWarning);
		return;
	}

	// number generation algorithm
	if (isset($template))
	{
		$optionPrefix = (strlen($entityName) > 0) ? $entityName.'_' : '';
		switch ($template)
		{
			case 'NUMBER':
				COption::SetOptionString("crm", $optionPrefix."number_template", "NUMBER");
				COption::SetOptionString("crm", $optionPrefix."number_data", intval($number));
				break;

			case 'PREFIX':
				COption::SetOptionString("crm", $optionPrefix."number_template", "PREFIX");
				COption::SetOptionString("crm", $optionPrefix."number_data", $prefix);
				break;

			case 'RANDOM':
				COption::SetOptionString("crm", $optionPrefix."number_template", "RANDOM");
				COption::SetOptionString("crm", $optionPrefix."number_data", intval($randomLength));
				break;

			case 'USER':
				COption::SetOptionString("crm", $optionPrefix."number_template", "USER");
				COption::SetOptionString("crm", $optionPrefix."number_data", "");
				break;

			case 'DATE':
				COption::SetOptionString("crm", $optionPrefix."number_template", "DATE");
				COption::SetOptionString("crm", $optionPrefix."number_data", $datePeriod);
				break;

			default:
				COption::SetOptionString("crm", $optionPrefix."number_template", "");
				COption::SetOptionString("crm", $optionPrefix."number_data", "");
				break;
		}
	}
}

function checkNumberValue($templateType, $number_data, $number_prefix)
{
	$res = true;

	switch ($templateType)
	{
		case 'NUMBER':

			if (strlen($number_data) <= 0
				|| strlen($number_data) > 7
				|| intval($number_data) != $number_data
				|| intval($number_data) < intval(COption::GetOptionString("sale", "number_data", ""))
			)
				$res = false;

			break;

		case 'PREFIX':

			if (strlen($number_prefix) <= 0
				|| strlen($number_prefix) > 7
				|| preg_match('/[^a-zA-Z0-9_-]/', $number_prefix)
			)
				$res = false;

			break;
	}

	return $res;
}

$entityName = '';
foreach ($_POST as $k => $rnd)
{
	if (preg_match('/^rnd_cfg_number_\d{8}/', $k) && preg_match('/^\d{8}$/', $rnd))
	{
		$entityName = substr($k, 23 + ((strlen($k) > 23) ? 1 : 0));
		if ($entityName === '_')
			$entityName = '';
		
		if (isset($_POST[$rnd.'_template'])
			&& isset($_POST[$rnd.'_number'])
			&& isset($_POST[$rnd.'_prefix'])
			&& isset($_POST[$rnd.'_random_length'])
			&& isset($_POST[$rnd.'_date_period']))
		{
			applyNumberTemplateSettings(
				$entityName,
				$_POST[$rnd.'_template'],
				$_POST[$rnd.'_number'],
				$_POST[$rnd.'_prefix'],
				$_POST[$rnd.'_random_length'],
				$_POST[$rnd.'_date_period']
			);
		}
	}
}
?>