<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
?>
<li class="feed-add-post-destination-block">
	<div class="feed-add-post-destination-title"><?=GetMessage("CRM_SL_MPF_DESTINATION_WHERE")?></div>
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.user.selector",
		"",
		[
			"ID" => "CRM_FEED_WHERE",
			"LIST" => (
					!empty($arResult['FEED_WHERE']['SELECTED'])
						? $arResult['FEED_WHERE']['SELECTED']
						: []
			),
			"LAZYLOAD" => "Y",
			"INPUT_NAME" => 'DEST_CODES[]',
			"USE_SYMBOLIC_ID" => true,
			"BUTTON_SELECT_CAPTION" => Loc::getMessage("CRM_SL_EVENT_EDIT_MPF_WHERE_1"),
			"API_VERSION" => 3,
			"SELECTOR_OPTIONS" => array(
				'lazyLoad' => 'Y',
				'context' => 'CRM_POST',
				'contextCode' => '',
				'enableSonetgroups' => 'N',
				'enableUsers' => 'N',
				'useClientDatabase' => 'N',
				'enableAll' => 'N',
				'enableDepartments' => 'N',
				'enableCrm' => 'Y',
				'enableCrmContacts' => 'Y',
				'enableCrmCompanies' => 'Y',
				'enableCrmLeads' => 'Y',
				'enableCrmDeals' => 'Y',
//				'addTabCrmContacts' => 'Y',
//				'addTabCrmCompanies' => 'Y',
//				'addTabCrmLeads' => 'Y',
//				'addTabCrmDeals' => 'Y'
			)
		]
	);
?>
</li>
<?