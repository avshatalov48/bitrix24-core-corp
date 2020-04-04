<?if(!Defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CUtil::InitJSCore();

$arParams['MULTIPLE'] = $arParams['MULTIPLE'] == 'N' ? 'N' : 'Y';

$arParams['INPUT_NAME'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;

if (isset($arParams['INPUT_VALUE']))
{
	if (!is_array($arParams['INPUT_VALUE']))
		$arParams['INPUT_VALUE'] = explode(',', $arParams['INPUT_VALUE']);

	if (is_array($arParams['ENTITY_TYPE']))
	{
		$arSettings = Array(
			'LEAD' => in_array('LEAD', $arParams['ENTITY_TYPE'])? 'Y': 'N',
			'CONTACT' => in_array('CONTACT', $arParams['ENTITY_TYPE'])? 'Y': 'N',
			'COMPANY' => in_array('COMPANY', $arParams['ENTITY_TYPE'])? 'Y': 'N',
			'DEAL' => in_array('DEAL', $arParams['ENTITY_TYPE'])? 'Y': 'N',
			'QUOTE' => in_array('QUOTE', $arParams['ENTITY_TYPE'])? 'Y': 'N',
			'PRODUCT' => in_array('PRODUCT', $arParams['ENTITY_TYPE'])? 'Y': 'N'
		);
	}
	else
	{
		$arSettings = Array(
			'LEAD' => $arParams['ENTITY_TYPE'] == 'LEAD'? 'Y': 'N',
			'CONTACT' => $arParams['ENTITY_TYPE'] == 'CONTACT'? 'Y': 'N',
			'COMPANY' => $arParams['ENTITY_TYPE'] == 'COMPANY'? 'Y': 'N',
			'DEAL' => $arParams['ENTITY_TYPE'] == 'DEAL'? 'Y': 'N',
			'QUOTE' => $arParams['ENTITY_TYPE'] == 'QUOTE'? 'Y': 'N',
			'PRODUCT' => $arParams['ENTITY_TYPE'] == 'PRODUCT'? 'Y': 'N'
		);
	}

	$arUserField = Array(
		'USER_TYPE' => 'crm',
		'FIELD_NAME' => $arParams['INPUT_NAME'],
		'MULTIPLE' => $arParams['MULTIPLE'],
		'SETTINGS' => $arSettings,
		'VALUE' => $arParams['INPUT_VALUE'],
	);

	if (isset($arParams['FILTER']) && $arParams['FILTER'] == true)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.field.filter',
			'crm',
			array(
				'arUserField' => $arUserField,
				'bVarsFromForm' => false,
				'form_name' => 'filter_'.$arParams['FORM_NAME']
			),
			false,
			array('HIDE_ICONS' => true)
		);
	}
	else
	{
		$APPLICATION->IncludeComponent(
			'bitrix:system.field.edit',
			'crm',
			array(
				'arUserField' => $arUserField,
				'bVarsFromForm' => false,
				'form_name' => isset($arParams['FORM_NAME']) && strlen($arParams['FORM_NAME']) > 0 ? 'form_'.$arParams['FORM_NAME'] : '',
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
	}
}
?>