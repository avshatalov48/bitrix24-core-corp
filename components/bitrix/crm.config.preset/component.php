<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
/** @var array $arParams */

global $APPLICATION;

$arDefaultUrlTemplates404 = array(
	'list' => '#entity_type#/',
	'edit' => '#entity_type#/edit/#preset_id#/',
	'ufields' => '#entity_type#/ufields/'
);

$arDefaultVariableAliases404 = array();

$arDefaultVariableAliases = array();

$arComponentVariables = array(
	'preset_id',
	'entity_type',
	'mode'
);

if($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if(!$componentPage)
		$componentPage = 'list';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	$arResult = array(
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}
else
{
	$arVariables = array();
	if(!isset($arParams['VARIABLE_ALIASES']['ID']))
		$arParams['VARIABLE_ALIASES']['ID'] = 'ID';

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'list'; //default page

	if(isset($arVariables['mode']))
	{
		switch($arVariables['mode'])
		{
			case 'edit':
				if(isset($arVariables['preset_id']))
					$componentPage = 'edit';
				break;
			case 'list':
				$componentPage = 'list';
				break;
			case 'ufields':
				$componentPage = 'ufields';
				break;
		}
	}

	$arResult = array(
		'FOLDER' => '',
		'URL_TEMPLATES' => array(
			'edit' => $APPLICATION->GetCurPage()
				.'?'.$arVariableAliases['mode'].'=edit'
				.'&'.$arVariableAliases['entity_type'].'=#entity_type#'
				.'&'.$arVariableAliases['preset_id'].'=#preset_id#',
			'list' => $APPLICATION->GetCurPage()
				.'?'.$arVariableAliases['mode'].'=list'
				.'&'.$arVariableAliases['entity_type'].'=#entity_type#',
			'ufields' => $APPLICATION->GetCurPage()
				.'?'.$arVariableAliases['mode'].'=ufields'
				.'&'.$arVariableAliases['entity_type'].'=#entity_type#'
		),
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}

if (CModule::IncludeModule('crm'))
{
	if (!isset($arResult['VARIABLES']))
		$arResult['VARIABLES'] = array();
	if (!isset($arResult['VARIABLES']['entity_type']))
		$arResult['VARIABLES']['entity_type'] = \Bitrix\Crm\EntityPreset::Requisite;
}

$this->IncludeComponentTemplate($componentPage);
?>