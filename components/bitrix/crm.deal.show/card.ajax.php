<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAuthorized() && CCrmDeal::CheckReadPermission(0, $CCrmPerms)))
	return;

$arResult = array();
$entityId = $_GET['USER_ID'];
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/iu', '', $_GET['USER_ID']);
$iDealId = (int) $_GET['USER_ID'];
$iVersion = (!empty($_GET["version"]) ? intval($_GET["version"]) : 1);

if ($iDealId > 0)
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$obRes = CCrmDeal::GetListEx(array(), array('=ID' => $iDealId));
	$arDeal = $obRes->Fetch();
	if ($arDeal == false)
		return ;

	$arResult['STAGE_LIST'] = CCrmDeal::GetStageNames(
		isset($arDeal['CATEGORY_ID']) ? (int)$arDeal['CATEGORY_ID'] : 0
	);

	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'DEAL', 'ELEMENT_ID' => $iDealId));
	while($ar = $res->Fetch())
		if (empty($arDeal[$ar['COMPLEX_ID']]))
			$arDeal[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arDeal['PATH_TO_DEAL_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Deal, $iDealId, false);
	$arDeal['PATH_TO_DEAL_EDIT'] = \CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Deal, $iDealId, false);
	$arDeal['PATH_TO_CONTACT_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Contact, $arDeal['CONTACT_ID'], false);
	$arDeal['PATH_TO_COMPANY_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $arDeal['COMPANY_ID'], false);

	$arDeal['CONTACT_FORMATTED_NAME'] = $arDeal['CONTACT_ID'] <= 0 ? ''
		: CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arDeal['CONTACT_HONORIFIC']) ? $arDeal['CONTACT_HONORIFIC'] : '',
					'NAME' => isset($arDeal['CONTACT_NAME']) ? $arDeal['CONTACT_NAME'] : '',
					'LAST_NAME' => isset($arDeal['CONTACT_LAST_NAME']) ? $arDeal['CONTACT_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arDeal['CONTACT_SECOND_NAME']) ? $arDeal['CONTACT_SECOND_NAME'] : ''
				)
		);

	$strName = ($iVersion >= 2 ? '<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['TITLE']).'</a>' : '');
	$arProductRows = CCrmDeal::LoadProductRows($arDeal['ID']);

	if ($iVersion >= 2)
	{
		$fields = '';

		if (!empty($arDeal['STAGE_ID']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_STAGE_ID').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.htmlspecialcharsbx($arResult['STAGE_LIST'][$arDeal['STAGE_ID']]).'</span></span>
			</span>';
		}
		if(count($arProductRows) > 0)
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span></span>
			</span>';
		}
		if (!empty($arDeal['OPPORTUNITY']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_OPPORTUNITY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration"><nobr>'.number_format($arDeal['OPPORTUNITY'], 2, ',', ' ').' '.htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID'])).'</nobr></span></span>
			</span>';
		}
		if (!empty($arDeal['PROBABILITY']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_PROBABILITY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.intval($arDeal['PROBABILITY']).'%</span></span>
			</span>';
		}
		$fields .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arDeal['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span></span>
		</span>';
		if (!empty($arDeal['COMPANY_TITLE']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_COMPANY_TITLE').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arDeal['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['COMPANY_TITLE']).'</a></span>
			</span>';
		}
		if (!empty($arDeal['CONTACT_FORMATTED_NAME']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_CONTACT_FULL_NAME').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arDeal['PATH_TO_CONTACT_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['CONTACT_FORMATTED_NAME']).'</a></span>
			</span>';
		}

		$strCard = '<div class="bx-ui-tooltip-info-data-cont" id="bx_user_info_data_cont_'.htmlspecialcharsbx($entityId).'"><div class="bx-ui-tooltip-info-data-info crm-tooltip-info">'.$fields.'</div></div>';
	}
	else
	{
		$strCard = '
<div class="bx-user-info-data-cont-video bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name">
		<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
		if (!empty($arDeal['STAGE_ID']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STAGE_ID').'</span>:
		<span class="fields enumeration">'.htmlspecialcharsbx($arResult['STAGE_LIST'][$arDeal['STAGE_ID']]).'</span>
		<br />';
		}

		if(count($arProductRows) > 0)
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>:<span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span><br />';
		}

		if (!empty($arDeal['OPPORTUNITY']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_OPPORTUNITY').'</span>:
		<span class="fields enumeration"><nobr>'.number_format($arDeal['OPPORTUNITY'], 2, ',', ' ').' '.htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID'])).'</nobr></span>
		<br />';
		}
		if (!empty($arDeal['PROBABILITY']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PROBABILITY').'</span>:
		<span class="fields enumeration">'.intval($arDeal['PROBABILITY']).'%</span>
		<br />';
		}
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arDeal['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />';
		if (!empty($arDeal['COMPANY_TITLE']))
		{
			$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_COMPANY_TITLE')).'</span>:
		<a href="'.$arDeal['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['COMPANY_TITLE']).'</a>
		<br />';
		}
		if (!empty($arDeal['CONTACT_FORMATTED_NAME']))
		{
			$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_CONTACT_FULL_NAME')).'</span>:
		<a href="'.$arDeal['PATH_TO_CONTACT_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arDeal['CONTACT_FORMATTED_NAME']).'</a>
		<br />';
		}
		$strCard .= '</div>
</div>';
	}

	$strPhoto = '<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'" class="bx-ui-tooltip-info-data-photo no-photo" target="_blank"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon bx-icon-show">
		<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'" target="_blank">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arDeal['PATH_TO_DEAL_EDIT'].'" target="_blank">'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Name' => $strName,
		'Card' => $strCard,
		'Photo' => $strPhoto
	);
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject(array('RESULT' => $arResult));
if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}
include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
die();

?>
