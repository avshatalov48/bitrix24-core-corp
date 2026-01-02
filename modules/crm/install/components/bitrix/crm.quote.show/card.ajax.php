<?php

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

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
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL|QUOTE)_/iu', '', $_GET['USER_ID']);
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

	$strName = ($iVersion >= 2 ? '<a href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'" target="_blank">'.htmlspecialcharsbx(empty($arQuote['TITLE']) ? $arQuote['QUOTE_NUMBER'] : $arQuote['QUOTE_NUMBER'].' - '.$arQuote['TITLE']).'</a>' : '');
	$arProductRows = CCrmQuote::LoadProductRows($arQuote['ID']);

	if ($iVersion >= 2)
	{
		$fields = '';

		if (!empty($arQuote['STATUS_ID']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_STATUS_ID_MSGVER_2').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.$arResult['STATUS_LIST'][$arQuote['STATUS_ID']].'</span></span>
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

		if (($arQuote['COMPANY_ID'] ?? 0) > 0)
		{
			if (Container::getInstance()->getUserPermissions()->item()->canRead(CCrmOwnerType::Company, $arQuote['COMPANY_ID']))
			{
				$fields .= '<span class="bx-ui-tooltip-field-row">
					<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_COLUMN_COMPANY_TITLE').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arQuote['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arQuote['COMPANY_TITLE']).'</a></span>
				</span>';
			}
			else
			{
				$fields .= '<span class="bx-ui-tooltip-field-row">
					<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_COLUMN_COMPANY_TITLE').'</span>: <span class="bx-ui-tooltip-field-value">'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Company)).'</span>
				</span>';
			}
		}

		if (($arQuote['CONTACT_ID'] ?? 0) > 0)
		{
			if (Container::getInstance()->getUserPermissions()->item()->canRead(CCrmOwnerType::Contact, $arQuote['CONTACT_ID']))
			{
				$formattedName = CCrmContact::PrepareFormattedName([
					'HONORIFIC' => $arQuote['CONTACT_HONORIFIC'] ?? '',
					'NAME' => $arQuote['CONTACT_NAME'] ?? '',
					'LAST_NAME' => $arQuote['CONTACT_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arQuote['CONTACT_SECOND_NAME'] ?? '',
				]);

				if (!empty($formattedName))
				{
					$fields .= '<span class="bx-ui-tooltip-field-row">
						<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_COLUMN_CONTACT_FULL_NAME').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$arQuote['PATH_TO_CONTACT_SHOW'].'" target="_blank">'.htmlspecialcharsbx($formattedName).'</a></span>
					</span>';
				}
			}
			else
			{
				$fields .= '<span class="bx-ui-tooltip-field-row">
					<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_COLUMN_CONTACT_FULL_NAME').'</span>: <span class="bx-ui-tooltip-field-value">'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Contact)).'</span>
				</span>';
			}
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
			$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STATUS_ID_MSGVER_2').'</span>:
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

		if (($arQuote['COMPANY_ID'] ?? 0) > 0)
		{
			if (Container::getInstance()->getUserPermissions()->item()->canRead(CCrmOwnerType::Company, $arQuote['COMPANY_ID']))
			{
				$strCard .= '<span class="field-name">'.htmlspecialcharsbx(Loc::getMessage('CRM_COLUMN_COMPANY_TITLE')).'</span>:
					<a href="'.$arQuote['PATH_TO_COMPANY_SHOW'].'">'.htmlspecialcharsbx($arQuote['COMPANY_TITLE']).'</a>
				<br />';
			}
			else
			{
				$strCard .= '<span class="field-name">'.htmlspecialcharsbx(Loc::getMessage('CRM_COLUMN_COMPANY_TITLE')).'</span>:
					'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Company)).'
				<br />';
			}
		}

		if (($arQuote['CONTACT_ID'] ?? 0) > 0)
		{
			if (Container::getInstance()->getUserPermissions()->item()->canRead(CCrmOwnerType::Contact, $arQuote['CONTACT_ID']))
			{
				$formattedName = CCrmContact::PrepareFormattedName([
					'HONORIFIC' => $arQuote['CONTACT_HONORIFIC'] ?? '',
					'NAME' => $arQuote['CONTACT_NAME'] ?? '',
					'LAST_NAME' => $arQuote['CONTACT_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arQuote['CONTACT_SECOND_NAME'] ?? '',
				]);

				if (!empty($formattedName))
				{
					$strCard .= '<span class="field-name">'.htmlspecialcharsbx(Loc::getMessage('CRM_COLUMN_CONTACT_FULL_NAME')).'</span>:
						<a href="'.$arQuote['PATH_TO_CONTACT_SHOW'].'">'.htmlspecialcharsbx($formattedName).'</a>
					<br />';
				}
			}
			else
			{
				$strCard .= '<span class="field-name">'.htmlspecialcharsbx(Loc::getMessage('CRM_COLUMN_CONTACT_FULL_NAME')).'</span>:
					'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Contact)).'
				<br />';
			}
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
