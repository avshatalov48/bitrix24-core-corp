<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError('CRM_MODULE_NOT_INSTALLED');
	return 0;
}

$arResult['ITEMS'] = array();
$utmList = \Bitrix\Crm\UtmTable::getCodeNames();
if (is_array($arParams['FIELDS']))
{
	foreach ($utmList as $utmCode => $utmName)
	{
		if (isset($arParams['FIELDS']['~' . $utmCode]))
		{
			$value = $arParams['FIELDS']['~' . $utmCode];
		}
		else if (isset($arParams['FIELDS'][$utmCode]))
		{
			$value = $arParams['FIELDS'][$utmCode];
		}
		else
		{
			continue;
		}

		$arResult['ITEMS'][] = array(
			'CODE' => $utmCode,
			'NAME' => mb_strtolower($utmCode),
			'VALUE' => urldecode($value)
		);
	}
}

$this->IncludeComponentTemplate();