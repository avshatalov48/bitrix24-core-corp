<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Restriction\RestrictionManager;

if (!empty($arResult['ERROR_MESSAGE']))
{
	ShowError($arResult['ERROR_MESSAGE']);
}

global $APPLICATION;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
$titleName = $arParams['IS_RECURRING'] === "Y" ? 'CRM_INVOICE_RECUR_SHOW_TITLE' : 'CRM_INVOICE_SHOW_TITLE';
$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	$titleName,
	array(
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($arResult['ELEMENT']['ACCOUNT_NUMBER']),
		'#ORDER_TOPIC#' => htmlspecialcharsbx($arResult['ELEMENT']['ORDER_TOPIC'])
	)
);

CJSCore::Init(array('clipboard'));

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_1'],
	'display' => false
);
$arTabs[] = array(
	'id' => 'tab_product_rows',
	'name' => GetMessage('CRM_TAB_PRODUCT_ROWS'),
	'title' => GetMessage('CRM_TAB_PRODUCT_ROWS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_product_rows']
);
$arTabs[] = array(
	'id' => 'tab_tree',
	'name' => GetMessage('CRM_TAB_TREE'),
	'title' => GetMessage('CRM_TAB_TREE_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_tree']
);
if(!empty($arResult['FIELDS']['tab_event']))
{
	$eventCount = intval($arResult['EVENT_COUNT']);
	$tabEventParams = [
		'id' => 'tab_event',
		'name' => GetMessage('CRM_TAB_HISTORY') . ($eventCount > 0 ? " ($eventCount)" : ''),
		'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_event']
	];
	if (isset($arResult['TAB_EVENT_TARIFF_LOCK']) && $arResult['TAB_EVENT_TARIFF_LOCK'] === 'Y')
	{
		$tabEventParams['tariffLock']  = RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript();
	}
	$arTabs[] = $tabEventParams;
	unset($tabEventParams);
}

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = mb_strtolower($arResult['FORM_ID']).'_editor';
$treeDispatcherID = mb_strtolower($arResult['FORM_ID']).'_tree_disp';

$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;
if($element)
{
	$arInvoiceStatusInfoValues[$element['ID']] = array(
		'PAY_VOUCHER_DATE' => ($element['PAY_VOUCHER_DATE'] != '') ? FormatDate('SHORT', MakeTimeStamp($element['PAY_VOUCHER_DATE'])) : '',
		'PAY_VOUCHER_NUM' => ($element['PAY_VOUCHER_NUM'] != '') ? $element['PAY_VOUCHER_NUM'] : '',
		'DATE_MARKED' => ($element['DATE_MARKED'] != '') ? FormatDate('SHORT', MakeTimeStamp($element['DATE_MARKED'])) : '',
		'REASON_MARKED' => ($element['REASON_MARKED_SUCCESS'] != '') ?
			$element['REASON_MARKED_SUCCESS'] : (($element['REASON_MARKED'] != '') ? $element['REASON_MARKED'] : '')
	);
}

if ($arParams['IS_RECURRING'] === "Y")
{
	unset($element['DATE_PAY_BEFORE']);
	$element['RECURRING_ACTIVE'] = $arResult['RECURRING_DATA']['ACTIVE'];
	$element['RECURRING_NEXT_EXECUTION'] = $arResult['RECURRING_DATA']['NEXT_EXECUTION'];
	$element['RECURRING_LAST_EXECUTION'] = $arResult['RECURRING_DATA']['LAST_EXECUTION'];
	$element['RECURRING_COUNTER_REPEAT'] = $arResult['RECURRING_DATA']['COUNTER_REPEAT'];
}


$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.quickpanelview',
	'',
	array(
		'GUID' => mb_strtolower($arResult['FORM_ID']).'_qpv',
		'FORM_ID' => $arResult['TACTILE_FORM_ID'],
		'ENTITY_TYPE_NAME' => CCrmOwnerType::InvoiceName,
		'ENTITY_ID' => $arResult['ELEMENT_ID'],
		'ENTITY_FIELDS' => $element,
		'ENABLE_INSTANT_EDIT' => $arResult['ENABLE_INSTANT_EDIT'],
		'INSTANT_EDITOR_ID' => $instantEditorID,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.invoice.show/ajax.php?'.bitrix_sessid_get(),
		'CONVERSION_SCHEME' => null,
		'SHOW_SETTINGS' => 'Y',
		'SHOW_STATUS_ACTION' => $arParams['IS_RECURRING'] === 'Y' ?  'N' :'Y'
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
		'DATA' => $element,
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');

$arResult['PREFIX'] = isset($arResult['PREFIX']) ? strval($arResult['PREFIX']) : 'crm_invoice_edit';
$gridEditorID = $arResult['PREFIX'].'_send_email';

$arAEParams = array(
	'CONTAINER_ID' => '',
	'EDITOR_ID' => $gridEditorID,
	'PREFIX' => $arResult['PREFIX'],
	'ENABLE_UI' => false,
	'ENABLE_EMAIL_ADD' => true,
	'ENABLE_TOOLBAR' => true,
	'TOOLBAR_ID' => 'crm_invoice_toolbar',
	'OWNER_TYPE' => \CCrmOwnerType::InvoiceName,
	'OWNER_ID' => $arResult['ELEMENT_ID'],
	'SKIP_VISUAL_COMPONENTS' => 'Y'
);

if(isset($arResult['ELEMENT']['UF_DEAL_ID']) && intval($arResult['ELEMENT']['UF_DEAL_ID']) > 0)
{
	$dealEmailList = array(
		'id' => (int)($arResult['ELEMENT']['UF_DEAL_ID']),
		'place' => \CCrmOwnerType::DealName,
		'title' => htmlspecialcharsbx($arResult['ELEMENT']['UF_DEAL_TITLE']),
		'type' => \CCrmOwnerType::DealName,
		'url' => htmlspecialcharsbx($arResult['ELEMENT']['UF_DEAL_SHOW_URL'])
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$arAEParams,
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
				entityTypeName: '<?= CCrmOwnerType::InvoiceName?>',
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
<script type="text/javascript">
<?if($arResult['ENABLE_INSTANT_EDIT']):?>
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
					containerID: ['<?=CUtil::JSEscape($summaryContainerID)?>'],
					ownerType: 'I',
					ownerID: <?=$arResult['ELEMENT_ID']?>,
					url: '/bitrix/components/bitrix/crm.invoice.show/ajax.php?<?=bitrix_sessid_get()?>',
					callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>
				}
			);

			instantEditor.setFieldReadOnly('PRICE', true)

			var prodEditor = typeof(BX.CrmProductEditor) !== 'undefined' ? BX.CrmProductEditor.getDefault() : null;

			function handleProductRowChange()
			{
				if(prodEditor)
				{
					instantEditor.setFieldReadOnly('OPPORTUNITY', prodEditor.getProductCount() > 0);
				}
			}

			if(prodEditor)
			{
				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						instantEditor.setFieldValue('OPPORTUNITY', ttl);
					}
				);

				handleProductRowChange();

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					handleProductRowChange
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					handleProductRowChange
				);
			}
		}
	);

<?endif;?>

function onCrmInvoiceSendEmailButtClick()
{
	getInvoicePdfContent();
	return false;
}

function generateExternalLink(event)
{
	var data = {
		'INVOICE_ID': '<?=CUtil::JSEscape($arResult['ELEMENT_ID'])?>',
		'MODE': 'GENERATE_LINK',
		'sessid': BX.bitrix_sessid()
	};

	BX.showWait();
	BX.ajax({
		data: data,
		method: 'POST',
		dataType: 'json',
		url: "<?=$componentPath.'/ajax.php'?>",
		onsuccess: BX.delegate(function(result) {
			BX.closeWait();
			if(result)
			{
				if(!result.ERROR)
				{
					BX.PopupMenu.show(
						'externale-link-<?=CUtil::JSEscape($arResult['ELEMENT_ID'])?>',
						event,
						[
							{
								html : '<input type="text" value="' + result.LINK + '" style="margin-top: 6px; ' +
									'width : 100%" id="generated-link"><span class="crm-invoice-edit-url-link-icon" ' +
									'title="<?=GetMessage('CRM_INVOICE_PUBLIC_LINK_COPY');?>" ' +
									'id="clipboard-copy"></span>'
							}
						],
						{angle : {offset : 80, position : 'top'}}
					);

					var clipboard = BX('clipboard-copy');
					if (clipboard)
						BX.clipboard.bindCopyClick(clipboard, {text : result.LINK});

					var link = BX('generated-link');
					if (link)
					{
						link.select();
						var parent = BX.findParent(link, {className : 'menu-popup'});
						parent.style.width = '430px';
						link.parentNode.style.width = '390px';
					}
				}
				else
				{
					BX.debug(result.ERROR);
				}
			}
		}, this
		),
		onfailure: function() {BX.debug('onfailure: generateExternalLink');}
	});
}

function crmInvoiceOpenEmailDialog(arParams)
{
	var mailSett = {};

	<?if(isset($arResult['COMMUNICATION'])):?>
		mailSett['communications'] = [<?=CUtil::PhpToJSObject($arResult['COMMUNICATION'])?>];
	<?endif;?>

	if(arParams)
	{
		if(arParams['webdavelement'])
		{
			mailSett['webdavelements'] = [arParams['webdavelement']];
			mailSett['storageTypeID'] = BX.CrmActivityStorageType.webdav;
		}
		else if(arParams["diskfile"])
		{
			mailSett["diskfiles"] = [arParams["diskfile"]];
			mailSett["storageTypeID"] = BX.CrmActivityStorageType.disk;
		}
		else if(arParams['file'])
		{
			arParams['file']['fileURL'] = arParams['file']['src'];
			mailSett['files'] = [arParams['file']];
			mailSett['storageTypeID'] = BX.CrmActivityStorageType.file;
		}
	}

	mailSett['subject'] = "<?=CUtil::JSEscape(GetMessage('CRM_INVOICE_TITLE').' '.$arResult['ELEMENT']['ACCOUNT_NUMBER'])?>";
	BX.CrmActivityEditor.items["<?=$gridEditorID?>"].setSetting('mailTemplateData', <?=CUtil::PhpToJSObject($arResult['MAIL_TEMPLATE_DATA'])?>);
	activityEmail = BX.CrmActivityEditor.items["<?=$gridEditorID?>"].addEmail(mailSett);
	<?
	if (!empty($dealEmailList) && is_array($dealEmailList))
	{
		?>
		activityEmail._setupOwner(<?=CUtil::PhpToJSObject($dealEmailList)?>);
		<?
	}
	?>
}

function getInvoicePdfContent()
{
	if (top.BX.Bitrix24 && top.BX.Bitrix24.Slider)
	{
		crmInvoiceOpenEmailDialog();
		return;
	}

	data = {
		'INVOICE_ID': '<?=CUtil::JSEscape($arResult['ELEMENT_ID'])?>',
		'INVOICE_NUM': '<?=CUtil::JSEscape($arResult['ELEMENT']['ACCOUNT_NUMBER'])?>',
		'MODE': 'SAVE_PDF',
		'pdf': 1,
		'GET_CONTENT': 'Y',
		'sessid': BX.bitrix_sessid()
	};

	BX.showWait();
	BX.ajax({
		data: data,
		method: 'POST',
		dataType: 'json',
		url: "<?=$componentPath.'/ajax.php'?>",
		onsuccess: BX.delegate(function(result) {
									BX.closeWait();
									if(result)
									{
										if(!result.ERROR)
											crmInvoiceOpenEmailDialog(result);
										else
											BX.debug(result.ERROR);
									}
								}, this
					),
		onfailure: function() {BX.debug('onfailure: getPdfContent');}
	});
}
</script><?php
// -------------------- status info processing ------------------->
?><script type="text/javascript">
	BX.ready(function(){
		if (typeof(BX.CrmInvoiceStatusManager) === 'function')
		{
			BX.CrmInvoiceStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arInvoiceStatusInfoValues) ?>;
		}

		BX.addCustomEvent("CrmProgressControlAfterSaveSucces", function (progressControl, data) {
			var settings = <?= CUtil::PhpToJSObject($statusInfoSettings) ?>;
			if (typeof(settings) === "object" && typeof(settings["items"]) === 'object' && typeof(data) === "object")
			{
				var items = settings["items"];
				if (typeof(data["STATE_SUCCESS"]) === "string" && typeof(data["STATE_FAILED"]) === "string")
				{
					for (var i in items)
					{
						if (typeof(items[i]) === "object")
						{
							var elBlock = BX("INVOICE_STATUS_INFO_" + i + "_block");
							if (elBlock)
							{
								var displayStyle = "none";
								if (
									data[i] != false && (
										(data["STATE_SUCCESS"] === 'Y' && (items[i]['status'] === 'success' || items[i]['status'] === 'all')) ||
										(data["STATE_FAILED"] === 'Y' && (items[i]['status'] === 'failed' || items[i]['status'] === 'all'))
									)
								)
								{
									displayStyle = "";
								}
								elBlock.style.display = displayStyle;
								if (displayStyle === "" && data[i] != false)
								{
									var elValue = BX("INVOICE_STATUS_INFO_" + i + "_value");
									if (elValue)
									{
										elValue.innerHTML = BX.util.htmlspecialchars(data[i]);
									}
								}
							}
						}
					}
				}
			}
		});
	});
</script><?php
// <-------------------- status info processing -------------------
