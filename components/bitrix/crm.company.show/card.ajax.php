<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAuthorized() && CCrmCompany::CheckReadPermission(0, $CCrmPerms)))
	return;

$arResult = array();
$entityId = $_GET['USER_ID'];
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iCompanyId = (int) $_GET['USER_ID'];
$iVersion = (!empty($_GET["version"]) ? intval($_GET["version"]) : 1);

if ($iCompanyId > 0)
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');

	$obRes = CCrmCompany::GetList(array(), array('ID' => $iCompanyId));
	$arCompany = $obRes->Fetch();
	if ($arCompany == false)
		return ;

	$arCompany['PATH_TO_COMPANY_SHOW'] = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $iCompanyId, false);
	$arCompany['PATH_TO_COMPANY_EDIT'] = \CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Company, $iCompanyId, false);

	//region Multifields
	$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
	$multiFieldHtml = array();

	$sipConfig =  array(
		'ENABLE_SIP' => true,
		'SIP_PARAMS' => array(
			'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName,
			'ENTITY_ID' => $iCompanyId)
	);

	$dbRes = CCrmFieldMulti::GetListEx(
		array(),
		array('ENTITY_ID' => CCrmOwnerType::CompanyName, 'ELEMENT_ID' => $iCompanyId, '@TYPE_ID' => array('PHONE', 'EMAIL', 'WEB')),
		false,
		false,
		array('TYPE_ID', 'VALUE_TYPE', 'VALUE')
	);

	while($multiField = $dbRes->Fetch())
	{
		$typeID = isset($multiField['TYPE_ID']) ? $multiField['TYPE_ID'] : '';

		if(isset($multiFieldHtml[$typeID]))
		{
			continue;
		}

		$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
		$valueType = isset($multiField['VALUE_TYPE']) ? $multiField['VALUE_TYPE'] : '';

		$entityType = $arEntityTypes[$typeID];
		$valueTypeInfo = isset($entityType[$valueType]) ? $entityType[$valueType] : null;

		$params = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType, 'VALUE_TYPE' => $valueTypeInfo);
		$item = CCrmViewHelper::PrepareMultiFieldValueItemData($typeID, $params, $sipConfig);
		if(isset($item['value']) && $item['value'] !== '')
		{
			$multiFieldHtml[$typeID] = $item['value'];
		}
	}
	//endregion

	$strName = ($iVersion >= 2 ? '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arCompany['TITLE']).'</a>' : '');

	if ($iVersion >= 2)
	{
		$fields = '';

		if (!empty($arCompany['COMPANY_TYPE']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_COMPANY_TYPE').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.$arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']].'</span></span>
			</span>';
		}
		if (!empty($arCompany['EMPLOYEES']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_EMPLOYEES').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.$arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']].'</span></span>
			</span>';
		}
		$fields .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arCompany['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span></span>
		</span>';

//		$fields .= '<span class="bx-ui-tooltip-field-row">'.GetMessage('CRM_SECTION_COMPANY_INFO').'</span>';

		if (isset($multiFieldHtml['PHONE']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>: <span class="bx-ui-tooltip-field-value">'.$multiFieldHtml['PHONE'].'</span>
			</span>';
		}
		if (isset($multiFieldHtml['EMAIL']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>: <span class="bx-ui-tooltip-field-value">'.$multiFieldHtml['EMAIL'].'</span>
			</span>';
		}
		if (isset($multiFieldHtml['WEB']))
		{
			$fields .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_COLUMN_WEB').'</span>: <span class="bx-ui-tooltip-field-value">'.$multiFieldHtml['WEB'].'</span>
			</span>';
		}

		$strCard = '<div class="bx-ui-tooltip-info-data-cont" id="bx_user_info_data_cont_'.htmlspecialcharsbx($entityId).'"><div class="bx-ui-tooltip-info-data-info crm-tooltip-info">'.$fields.'</div></div>';
	}
	else
	{
		$strCard = '
<div class="bx-user-info-data-cont-video  bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name ">
		<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.htmlspecialcharsbx($arCompany['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
		if (!empty($arCompany['COMPANY_TYPE']))
		{
			$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_COMPANY_TYPE').'</span>:
		<span class="fields enumeration">'.$arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']].'</span>
		<br />';
		}
		if (!empty($arCompany['EMPLOYEES']))
		{
			$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_EMPLOYEES').'</span>:
		<span class="fields enumeration">'.$arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']].'</span>
		<br />';
		}
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arCompany['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />
	</div>
	<div class="bx-user-info-data-name bx-user-info-seporator">
		<nobr>'.GetMessage('CRM_SECTION_COMPANY_INFO').'</nobr>
	</div>
	<div class="bx-user-info-data-info">';
		if (isset($multiFieldHtml['PHONE']))
		{
			$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>:
		<span class="crm-client-contacts-block-text crm-client-contacts-block-handset">'.$multiFieldHtml['PHONE'].'</span>
		<br />';
		}
		if (isset($multiFieldHtml['EMAIL']))
		{
			$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>:
		<span class="crm-client-contacts-block-text">'.$multiFieldHtml['EMAIL'].'</span>
		<br />';
		}
		if (isset($multiFieldHtml['WEB']))
		{
			$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_WEB').'</span>:
		<span class="crm-client-contacts-block-text">'.$multiFieldHtml['WEB'].'</span>
		<br />';
		}
		$strCard .= '</div>
</div>';
	}

	if (!empty($arCompany['LOGO']))
	{
		$imageFile = CFile::GetFileArray($arCompany['LOGO']);
		if ($imageFile !== false)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array('width' => 102, 'height' => 104),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$imageImg = CFile::ShowImage($arFileTmp['src'], 102, 104, "border='0'", '');
		}
		if ($imageImg <> '')
			$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-ui-tooltip-info-data-photo" target="_blank">'.$imageImg.'</a>';
		else
			$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-ui-tooltip-info-data-photo no-photo" target="_blank"></a>';
	}
	else
		$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-ui-tooltip-info-data-photo no-photo" target="_blank"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon bx-icon-show">
		<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" target="_blank">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arCompany['PATH_TO_COMPANY_EDIT'].'" target="_blank">'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$script = '
		var params = 
		{
			serviceUrls: 
			{ 
				"CRM_'.CUtil::JSEscape(CCrmOwnerType::CompanyName).'" : 
					"/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get().'"
			},
			messages: 
			{
				"unknownRecipient": "'.GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT').'",
				"makeCall": "'.GetMessageJS('CRM_SIP_MGR_MAKE_CALL').'"
			}
		};
		
		if(typeof(BX.CrmSipManager) === "undefined")
		{
			BX.loadScript(
				"/bitrix/js/crm/common.js", 
				function() { BX.CrmSipManager.ensureInitialized(params); }
			);
		}
		else
		{
			BX.CrmSipManager.ensureInitialized(params);
		}';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Name' => $strName,
		'Card' => $strCard,
		'Photo' => $strPhoto,
		'Scripts' => array($script)
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
