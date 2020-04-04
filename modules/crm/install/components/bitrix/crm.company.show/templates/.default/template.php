<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Crm\Integration\StorageType;

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

//Preliminary registration of disk api.
if(CCrmActivity::GetDefaultStorageTypeID() === StorageType::Disk)
{
	CJSCore::Init(array('uploader', 'file_dialog'));
}

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_COMPANY_SHOW_TITLE',
	array(
		'#ID#' => $arResult['ELEMENT']['ID'],
		'#TITLE#' => $arResult['ELEMENT']['TITLE']
	)
);

$isMyCompanyMode = (isset($arResult['MYCOMPANY_MODE']) && $arResult['MYCOMPANY_MODE'] === 'Y');
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$bizprocDispatcherID = strtolower($arResult['FORM_ID']).'_bp_disp';
$treeDispatcherID = strtolower($arResult['FORM_ID']).'_tree_disp';

$arTabs = array();
$arTabsExt = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1'],
	'display' => false
);
$arTabs[] = array(
	'id' => 'tab_details',
	'name' => GetMessage('CRM_TAB_DETAILS'),
	'title' => GetMessage('CRM_TAB_DETAILS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_details'],
	'display' => false
);

$liveFeedTab = null;
if (!empty($arResult['FIELDS']['tab_live_feed']))
{
	$liveFeedTab = array(
		'id' => 'tab_live_feed',
		'name' => GetMessage('CRM_TAB_LIVE_FEED'),
		'title' => GetMessage('CRM_TAB_LIVE_FEED_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_live_feed']
	);
	$arTabs[] = $liveFeedTab;
}
if (!empty($arResult['FIELDS']['tab_activity']))
	$arTabs[] = array(
		'id' => 'tab_activity',
		'name' => GetMessage('CRM_TAB_6'),
		'title' => GetMessage('CRM_TAB_6_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_activity']
	);
if (!empty($arResult['FIELDS']['tab_deal'])):
	$arTabs[] = array(
		'id' => 'tab_deal',
		'name' => GetMessage('CRM_TAB_3'),
		'title' => GetMessage('CRM_TAB_3_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_deal']
	);
	//$arTabsExt['tab_deal'] = array('SUFFIX' => " ({$arResult['DEAL_COUNT']})");
endif;

if (!empty($arResult['FIELDS']['tab_quote']))
	$arTabs[] = array(
		'id' => 'tab_quote',
		'name' => GetMessage('CRM_TAB_9'),
		'title' => GetMessage('CRM_TAB_9_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_quote']
	);
if (!empty($arResult['FIELDS']['tab_requisite']))
	$arTabs[] = array(
		'id' => 'tab_requisite',
		'name' => GetMessage('CRM_TAB_REQUISITE'),
		'title' => GetMessage('CRM_TAB_REQUISITE_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_requisite']
	);
if (!empty($arResult['FIELDS']['tab_invoice']))
{
	//$invoiceCount = intval($arResult['INVOICE_COUNT']);
	$arTabs[] = array(
		'id' => 'tab_invoice',
		//'name' => GetMessage('CRM_TAB_8')." ($invoiceCount)",
		'name' => GetMessage('CRM_TAB_8'),
		'title' => GetMessage('CRM_TAB_8_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_invoice']
	);
}

if (!empty($arResult['FIELDS']['tab_contact'])):
	$arTabs[] = array(
		'id' => 'tab_contact',
		'name' => GetMessage('CRM_TAB_2'),
		'title' => GetMessage('CRM_TAB_2_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_contact']
	);
	//$arTabsExt['tab_contact'] = array('SUFFIX' => " ({$arResult['CONTACT_COUNT']})");
endif;
if (isset($arResult['BIZPROC']) && $arResult['BIZPROC'] === 'Y' && !empty($arResult['FIELDS']['tab_bizproc']))
{
	$arTabs[] = array(
		'id' => 'tab_bizproc',
		'name' => GetMessage('CRM_TAB_7'),
		'title' => GetMessage('CRM_TAB_7_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_bizproc']
	);
}
if (!empty($arResult['FIELDS']['tab_tree']))
{
	$arTabs[] = array(
		'id' => 'tab_tree',
		'name' => GetMessage('CRM_TAB_TREE'),
		'title' => GetMessage('CRM_TAB_TREE_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_tree']
	);
}
if (!empty($arResult['FIELDS']['tab_event']))
{
	$arTabs[] = array(
		'id' => 'tab_event',
		'name' => GetMessage('CRM_TAB_HISTORY'),
		'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_event']
	);
}
if(!empty($arResult['LISTS']))
{
	foreach($arResult['LIST_IBLOCK'] as $iblockId => $iblockName)
	{
		if (!empty($arResult['FIELDS']['tab_lists_'.$iblockId]))
		{
			$arTabs[] = array(
				'id' => 'tab_lists_'.$iblockId,
				'name' => $iblockName,
				'title' => $iblockName,
				'icon' => '',
				'fields' => $arResult['FIELDS']['tab_lists_'.$iblockId]
			);
		}
	}
}

$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.quickpanelview',
	'',
	array(
		'GUID' => strtolower($arResult['FORM_ID']).'_qpv',
		'FORM_ID' => $arResult['TACTILE_FORM_ID'],
		'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
		'ENTITY_ID' => $arResult['ELEMENT_ID'],
		'ENTITY_FIELDS' => $element,
		'ENABLE_INSTANT_EDIT' => $arResult['ENABLE_INSTANT_EDIT'],
		'INSTANT_EDITOR_ID' => $instantEditorID,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
		'SHOW_SETTINGS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TACTILE_FORM_ID' => $arResult['TACTILE_FORM_ID'],
		'TABS' => $arTabs,
		'TABS_EXT' => $arTabsExt,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

?>
<script type="text/javascript">
	BX.ready(function(){
		var treeContainerId = '<?=$arResult['TREE_CONTAINER_ID']?>';
		if (!BX(treeContainerId))
		{
			return;
		}

		BX.CrmEntityTreeDispatcher.create(
			'dispatcher<?= CUtil::JSEscape($treeDispatcherID)?>',
			{
				containerID: treeContainerId,
				entityTypeName: '<?= CCrmOwnerType::CompanyName?>',
				entityID: <?=$arResult['ELEMENT_ID']?>,
				serviceUrl: '/bitrix/components/bitrix/crm.entity.tree/ajax.php?<?=bitrix_sessid_get()?>',
				formID: '<?= CUtil::JSEscape($arResult['FORM_ID'])?>',
				selected: <?= $arResult['TAB_TREE_OPEN'] ? 'true' : 'false'?>,
				pathToLeadShow: '<?= CUtil::JSEscape($arParams['PATH_TO_LEAD_SHOW'])?>',
				pathToContactShow: '<?= CUtil::JSEscape($arParams['PATH_TO_CONTACT_SHOW'])?>',
				pathToCompanyShow: '<?= CUtil::JSEscape($arParams['PATH_TO_COMPANY_SHOW'])?>',
				pathToDealShow: '<?= CUtil::JSEscape($arParams['PATH_TO_DEAL_SHOW'])?>',
				pathToQuoteShow: '<?= CUtil::JSEscape($arParams['PATH_TO_QUOTE_SHOW'])?>',
				pathToInvoiceShow: '<?= CUtil::JSEscape($arParams['PATH_TO_INVOICE_SHOW'])?>',
				pathToUserProfile: '<?=CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>'
			}
		);
	});
</script>

<?


if($arResult['ENABLE_INSTANT_EDIT']):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmInstantEditorMessages =
								{
					editButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_EDIT_BTN_TTL'))?>',
					lockButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_LOCK_BTN_TTL'))?>'
				};

				var instantEditor = BX.CrmInstantEditor.create(
						'<?=CUtil::JSEscape($instantEditorID)?>',
						{
							containerID: [],
							ownerType: 'CO',
							ownerID: <?=$arResult['ELEMENT_ID']?>,
							url: '/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>',
							callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>
						}
				);

				<?if(isset($arResult['ENABLE_BIZPROC_LAZY_LOADING']) && $arResult['ENABLE_BIZPROC_LAZY_LOADING'] === true):?>
				var bpContainerId = "<?=$arResult['BIZPROC_CONTAINER_ID']?>";
				if(BX(bpContainerId))
				{
					BX.CrmBizprocDispatcher.create(
						"<?=CUtil::JSEscape($bizprocDispatcherID)?>",
						{
							containerID: bpContainerId,
							entityTypeName: "<?=CCrmOwnerType::CompanyName?>",
							entityID: <?=$arResult['ELEMENT_ID']?>,
							serviceUrl: "/bitrix/components/bitrix/crm.company.show/bizproc.php?company_id=<?=$arResult['ELEMENT_ID']?>&post_form_uri=<?=urlencode($arResult['POST_FORM_URI'])?>&<?=bitrix_sessid_get()?>",
							formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
							pathToEntityShow: "<?=CUtil::JSEscape($arResult['PATH_TO_COMPANY_SHOW'])?>"
						}
					);
				}
				<?endif;?>
			}
	);
</script>
<?endif;?>

<?if(isset($arResult['ENABLE_LIVE_FEED_LAZY_LOAD']) && $arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] === true):?>
<script type="text/javascript">
	(function()
	{
		var liveFeedContainerId = "<?=CUtil::JSEscape($arResult['LIVE_FEED_CONTAINER_ID'])?>";
		if(!BX(liveFeedContainerId))
		{
			return;
		}

		var params =
		{
			"ENTITY_TYPE_NAME" : "<?=CCrmOwnerType::CompanyName?>",
			"ENTITY_ID": <?=$arResult['ELEMENT_ID']?>,
			"POST_FORM_URI": "<?=CUtil::JSEscape($arResult['POST_FORM_URI'])?>",
			"ACTION_URI": "<?=CUtil::JSEscape($arResult['ACTION_URI'])?>",
			"PATH_TO_USER_PROFILE": "<?=CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>"
		};

		BX.addCustomEvent(
			window,
			"SonetLogBeforeGetNextPage",
			function(data)
				{
					if(!BX.type.isNotEmptyString(data["url"]))
					{
						return;
					}

					var request = {};
					for(var key in params)
					{
						if(params.hasOwnProperty(key))
						{
							request["PARAMS[" + key + "]"] = params[key];
						}
					}
					data["url"] = BX.util.add_url_param(data["url"], request);
				}
		);

		BX.CrmFormTabLazyLoader.create(
			"<?=CUtil::JSEscape(strtolower($arResult['FORM_ID'])).'_livefeed'?>",
			{
				containerID: liveFeedContainerId,
				serviceUrl: "/bitrix/components/bitrix/crm.entity.livefeed/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
				tabID: "tab_live_feed",
				params: params
			}
		);
	})();
</script>
<?endif;?>
