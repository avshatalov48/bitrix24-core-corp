<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAuthorized() && CCrmQuote::CheckReadPermission(0, $CCrmPerms)))
	return;

$arResult = array();
$entityId = $_GET['USER_ID'];
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL|QUOTE)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iQuoteId = (int) $_GET['USER_ID'];
$iVersion = (!empty($_GET["version"]) ? intval($_GET["version"]) : 1);

if ($iQuoteId > 0)
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	global $APPLICATION;

	$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_STATUS');

	$obRes = CCrmQuote::GetList(array(), array('ID' => $iQuoteId));
	$arQuote = $obRes->Fetch();
	if ($arQuote == false)
		return ;
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'QUOTE', 'ELEMENT_ID' => $iQuoteId));
	while($ar = $res->Fetch())
		if (empty($arQuote[$ar['COMPLEX_ID']]))
			$arQuote[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arQuote['PATH_TO_QUOTE_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Quote, $iQuoteId, false);
	$arQuote['PATH_TO_QUOTE_EDIT'] = \CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Quote, $iQuoteId, false);
	$arQuote['PATH_TO_CONTACT_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Contact, $arQuote['CONTACT_ID'], false);
	$arQuote['PATH_TO_COMPANY_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $arQuote['COMPANY_ID'], false);

	$arQuote['CONTACT_FORMATTED_NAME'] = $arQuote['CONTACT_ID'] <= 0 ? ''
		: CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arQuote['CONTACT_HONORIFIC']) ? $arQuote['CONTACT_HONORIFIC'] : '',
					'NAME' => isset($arQuote['CONTACT_NAME']) ? $arQuote['CONTACT_NAME'] : '',
					'LAST_NAME' => isset($arQuote['CONTACT_LAST_NAME']) ? $arQuote['CONTACT_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arQuote['CONTACT_SECOND_NAME']) ? $arQuote['CONTACT_SECOND_NAME'] : ''
				)
		);

	$strName = ($iVersion >= 2 ? '<a href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'" target="_blank">'.htmlspecialcharsbx(empty($arQuote['TITLE']) ? $arQuote['QUOTE_NUMBER'] : $arQuote['QUOTE_NUMBER'].' - '.$arQuote['TITLE']).'</a>' : '');
	$arProductRows = CCrmQuote::LoadProductRows($arQuote['ID']);

	if ($iVersion >= 2)
	{
		$fields = '';

		if (!empty($arQuote['STATUS_ID']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_STATUS_ID').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.$arResult['STATUS_LIST'][$arQuote['STATUS_ID']].'</span></span>
			</span>';
		}
		if(count($arProductRows) > 0)
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span></span>
			</span>';
		}
		if (!empty($arQuote['OPPORTUNITY']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_OPPORTUNITY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration"><nobr>'.number_format($arQuote['OPPORTUNITY'], 2, ',', ' ').' '.htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arQuote['CURRENCY_ID'])).'</nobr></span></span>
			</span>';
		}
		$fields .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arQuote['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span></span>
		</span>';
		if (!empty($arQuote['COMPANY_TITLE']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_COMPANY_TITLE').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arQuote['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arQuote['COMPANY_TITLE']).'</a></span>
			</span>';
		}
		if (!empty($arQuote['CONTACT_FORMATTED_NAME']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_CONTACT_FULL_NAME').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arQuote['PATH_TO_CONTACT_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arQuote['CONTACT_FORMATTED_NAME']).'</a></span>
			</span>';
		}

		$strCard = '<div class="bx-ui-tooltip-info-data-cont" id="bx_user_info_data_cont_'.htmlspecialcharsbx($entityId).'"><div class="bx-ui-tooltip-info-data-info crm-tooltip-info">'.$fields.'</div></div>';
	}
	else
	{
		$strCard = '
<div class="bx-user-info-data-cont-video bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name">
		<a href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'">'.htmlspecialcharsbx(empty($arQuote['TITLE']) ? $arQuote['QUOTE_NUMBER'] : $arQuote['QUOTE_NUMBER'].' - '.$arQuote['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
		if (!empty($arQuote['STATUS_ID']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STATUS_ID').'</span>:
		<span class="fields enumeration">'.$arResult['STATUS_LIST'][$arQuote['STATUS_ID']].'</span>
		<br />';
		}

		if(count($arProductRows) > 0)
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>:<span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span><br />';
		}

		if (!empty($arQuote['OPPORTUNITY']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_OPPORTUNITY').'</span>:
		<span class="fields enumeration"><nobr>'.number_format($arQuote['OPPORTUNITY'], 2, ',', ' ').' '.htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arQuote['CURRENCY_ID'])).'</nobr></span>
		<br />';
		}
		/*if (!empty($arQuote['PROBABILITY']))
		{
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PROBABILITY').'</span>:
			<span class="fields enumeration">'.intval($arQuote['PROBABILITY']).'%</span>
			<br />';
		}*/
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arQuote['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />';
		if (!empty($arQuote['COMPANY_TITLE']))
		{
			$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_COMPANY_TITLE')).'</span>:
		<a href="'.$arQuote['PATH_TO_COMPANY_SHOW'].'">'.htmlspecialcharsbx($arQuote['COMPANY_TITLE']).'</a>
		<br />';
		}
		if (!empty($arQuote['CONTACT_FORMATTED_NAME']))
		{
			$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_CONTACT_FULL_NAME')).'</span>:
		<a href="'.$arQuote['PATH_TO_CONTACT_SHOW'].'">'.$arQuote['CONTACT_FORMATTED_NAME'].'</a>
		<br />';
		}
		$strCard .= '</div>
</div>';
	}

	$strPhoto = '<a href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'" class="bx-ui-tooltip-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon bx-icon-show">
		<a href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arQuote['PATH_TO_QUOTE_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
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
while (@ob_end_clean());

Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

echo CUtil::PhpToJsObject(array('RESULT' => $arResult));
die();

?>
