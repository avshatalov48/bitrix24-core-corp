<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CJSCore::Init(array("popup"));

/** @global APPLICATION CMain */
global $APPLICATION;

if(isset($arResult['CONVERSION_LEGEND'])):
	?><div class="crm-view-message"><?=$arResult['CONVERSION_LEGEND']?></div><?
endif;

$elementID = (isset($arResult['ELEMENT']) && isset($arResult['ELEMENT']['ID'])) ? intval($arResult['ELEMENT']['ID']) : 0;
$titleName = $arParams['IS_RECURRING'] === "Y" ? 'CRM_INVOICE_RECUR_SHOW_TITLE' : 'CRM_INVOICE_SHOW_TITLE';
$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	($elementID > 0) ? $titleName : 'CRM_INVOICE_SHOW_NEW_TITLE',
	array(
		'#ACCOUNT_NUMBER#' => $arResult['ELEMENT']['ACCOUNT_NUMBER'],
		'#ORDER_TOPIC#' => $arResult['ELEMENT']['ORDER_TOPIC']
	)
);

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_invoice_spec'):
		$productFieldset['NAME'] = $field['name'];
		$productFieldset['REQUIRED'] = $field['required'] === true;
		unset($arTabs[0]['fields'][$k]);
	endif;

	if($field['id'] === 'INVOICE_PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;

endforeach;
unset($field);

$standardButtonsTitles = array();
if (!empty($arResult['INVOICE_REFERER']))
	$standardButtonsTitles['saveAndView']['title'] = GetMessage('CRM_INVOICE_CUSTOM_SAVE_BUTTON_TITLE');

$arFormButtons = array(
	'back_url' => $arResult['BACK_URL'],
	'standard_buttons_titles' => $standardButtonsTitles
);

if(isset($arResult['DEAL_ID']) && $arResult['DEAL_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] = '<input type="hidden" name="invoice_id" value="'.$elementID.'"/><input type="hidden" name="deal_id" value="'.$arResult['DEAL_ID'].'"/>';
}
elseif(isset($arResult['QUOTE_ID']) && $arResult['QUOTE_ID'] > 0)
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['wizard_buttons'] = true;
	$arFormButtons['custom_html'] = '<input type="hidden" name="invoice_id" value="'.$elementID.'"/><input type="hidden" name="quote_id" value="'.$arResult['QUOTE_ID'].'"/>';
}
elseif(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
{
	$arFormButtons['standard_buttons'] = false;
	$arFormButtons['dialog_buttons'] = true;
	$arFormButtons['wizard_buttons'] = false;
	$arFormButtons['custom_html'] .= '<input type="hidden" name="external_context" value="'.htmlspecialcharsbx($arResult['EXTERNAL_CONTEXT']).'"/>';
}
else
{
	$arFormButtons['standard_buttons'] = true;
	$arFormButtons['wizard_buttons'] = false;
	$arFormButtons['custom_html'] = '<input type="hidden" name="invoice_id" value="'.$elementID.'"/>';
}

if($arResult['CALL_LIST_ID'] > 0)
{
	$arFormButtons['custom_html'] .= '<input type="hidden" name="call_list_id" value="'.(int)$arResult['CALL_LIST_ID'].'"/>';
	$arFormButtons['custom_html'] .= '<input type="hidden" name="call_list_element" value="'.(int)$arResult['CALL_LIST_ELEMENT'].'"/>';
}

$arFormButtons['custom_html'] .= $arResult['FORM_CUSTOM_HTML'];

?><div class="bx-crm-edit-form-wrapper"><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'FIELD_SETS' => array($productFieldset),
		'BUTTONS' => $arFormButtons,
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmInvoice::$sUFEntityID,
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'ENABLE_USER_FIELD_CREATION' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
?>
</div>
<script type="text/javascript">

	window.CrmProductRowSetLocation = function(){ BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']); };

	function <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'])?>(params)
	{
		var ob = null;
		if (params) ob = params;
		if (ob)
		{
			var invoiceForm = BX('form_' + '<?=CUtil::JSEscape($arResult['FORM_ID'])?>');
			if (invoiceForm)
			{
				var inpAjaxFlag = BX.create('input', {'props': {'type': 'hidden', 'value': 'Y', 'name': 'invoiceSubmitAjax'}});
				if (inpAjaxFlag)
				{
					invoiceForm.appendChild(inpAjaxFlag);
					window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response_object')?> = ob;
					BX.ajax.submit(invoiceForm, <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'].'_response')?>);
				}
			}
		}
	}
	function <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'].'_response')?>()
	{
		var invoiceForm = BX(<?=CUtil::JSEscape('form_'.$arResult['FORM_ID'])?>);
		var fAjaxSubmit = null, ob = null, info = null, paySystems = null;
		if (invoiceForm)
		{
			// clear invoiceSubmitAjax flags
			fAjaxSubmit = BX.findChild(invoiceForm, {'tag': 'input', 'attr': {'name': 'invoiceSubmitAjax'}});
			if (fAjaxSubmit)
			{
				var fAjaxSubmitSibl;
				while (fAjaxSubmitSibl = BX.findNextSibling(fAjaxSubmit, {'tag': 'input', 'attr': {'name': 'invoiceSubmitAjax'}}))
					invoiceForm.removeChild(fAjaxSubmitSibl);
				invoiceForm.removeChild(fAjaxSubmit);

				// remove target attribute after ajax submit
				invoiceForm.removeAttribute('target');
			}
		}

		ob = window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response_object')?>;
		info = window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response')?>;
		if (ob && info)
			BX.onCustomEvent('InvoiceAjaxSubmitResponse', [{'ob': ob, 'info': info}]);
		if (info)
		{
			if (invoiceForm)
			{
				var paySystem = BX.findChild(invoiceForm, { 'tag':'select', 'attr':{ 'name': '<?=CUtil::JSEscape($arResult['PAY_SYSTEMS_LIST_ID'])?>' } }, true, false);
				if (paySystem)
				{
					if (typeof(info['PAY_SYSTEMS_LIST']) !== 'undefined')
						fRewriteSelectFromArray(paySystem, info['PAY_SYSTEMS_LIST']['items'], info['PAY_SYSTEMS_LIST']['value']);
				}
			}
		}
	}
	function fRewriteSelectFromArray(select, data, value)
	{
		var opt, el, i, j;
		var setSelected = false;
		var bMultiple;

		if (!(value instanceof Array)) value = new Array(value);
		if (select)
		{
			bMultiple = !!(select.getAttribute('multiple'));
			while (opt = select.lastChild) select.removeChild(opt);
			for (i in data)
			{
				el = document.createElement("option")
				el.value = data[i]['value'];
				el.innerHTML = data[i]['text'];
				try
				{
					// for IE earlier than version 8
					select.add(el,select.options[null]);
				}
				catch (e)
				{
					el = document.createElement("option")
					el.text = data[i]['text'];
					select.add(el,null);
				}
				if (!setSelected || bMultiple)
				{
					for (j in value)
					{
						if (data[i]['value'] == value[j])
						{
							el.selected = true;
							if (!setSelected)
							{
								setSelected = true;
								select.selectedIndex = i;
							}
							break;
						}
					}
				}
			}
		}
	}
	BX.ready(function () {
		var formObj = bxForm_<?=$arResult["FORM_ID"]?>;
		if (formObj && typeof(formObj) === "object")
		{
			BX.addCustomEvent(formObj, "OnSubmitConditionsCheck",
				function (sender) {
				    if (formObj === sender)
					{
						var form = formObj.GetForm();
						var result = true;
						if (BX.type.isDomNode(form)
							&& form.querySelector("input[type=hidden][name=invoiceSubmitAjax][value=Y]"))
						{
							result = false;
						}
						sender.SetSubmitConditionsFlag(result);
					}
				}
			);
		}

		var el = BX('LOC_CITY_val');
		if (el)
			BX.addClass(el, 'bx-crm-edit-input');

		BX.addCustomEvent('InvoiceSumTotalChange', <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'])?>);

		BX.addCustomEvent("CrmClientSelectorChange",
			function (sender, eventArgs)
			{
				var target = eventArgs["target"];

				BX.onCustomEvent("RecurringInvoiceClientDataList", [sender ,eventArgs.data]);

				if(target !== "primaryEntity")
				{
					return;
				}

				var selectorId = null;
				if (BX.type.isPlainObject(eventArgs["selectorInfo"])
					&& BX.type.isNotEmptyString(eventArgs["selectorInfo"]["id"]))
				{
					selectorId = eventArgs["selectorInfo"]["id"];
				}
				if (selectorId !== "<?=CUtil::JSEscape($arResult['CLIENT_SELECTOR_ID'])?>")
				{
					return
				}

				var form = BX(<?=CUtil::JSEscape('form_'.$arResult['FORM_ID'])?>);
				if (!form)
				{
					return;
				}

				var paySystemSelector = BX.findChild(
					form,
					{
						tag: "select",
						attr: { name: "<?=CUtil::JSEscape($arResult['PAY_SYSTEMS_LIST_ID'])?>" }
					},
					true,
					false
				);

				if (paySystemSelector)
				{
					fRewriteSelectFromArray(paySystemSelector, [], []);
				}

				BX.onCustomEvent("InitiateInvoiceSumTotalChange");

				var data = BX.type.isPlainObject(eventArgs["data"]) ? eventArgs["data"] : {};
				var entityInfo = data["primaryEntityInfo"];
				if(entityInfo instanceof BX.CrmEntityInfo)
				{
					var entityTypeName = data["primaryEntityTypeName"];
					if(entityTypeName === BX.CrmEntityType.names.company)
					{
						form["crmInvoiceCompanyId"] = entityInfo.getId();
						form["crmInvoiceContactId"] = 0;
					}
					else if(entityTypeName === BX.CrmEntityType.names.contact)
					{
						form["crmInvoiceCompanyId"] = 0;
						form["crmInvoiceContactId"] = entityInfo.getId();
					}
				}
				else
				{
					form["crmInvoiceCompanyId"] = 0;
					form["crmInvoiceContactId"] = 0;
				}

				var personTypeName = "UNKNOWN";
				if (form["crmInvoiceCompanyId"] > 0)
				{
					personTypeName = "COMPANY";
				}
				else if (form["crmInvoiceContactId"] > 0)
				{
					personTypeName = "CONTACT";
				}

				BX.onCustomEvent("CrmInvoiceChangePersonType", [ personTypeName ]);

				if (paySystemSelector)
				{
					if (form["crmInvoiceCompanyId"] > 0 || form["crmInvoiceContactId"] > 0)
					{
						paySystemSelector.removeAttribute("disabled");
						paySystemSelector.removeAttribute("title");
					}
					else
					{
						paySystemSelector.setAttribute("disabled", "true");
					}
				}
			}
		);

		<?if(CCrmPaySystem::isUserMustFillPSProps()):?>

			(function crmInvoicePSPropsDialog()
			{
				var d =
					new BX.CDialog(
						{
							title: "<?=GetMessage('CRM_INVOICE_PS_PROPS_TITLE')?>",
							head: "",
							content: "<?=GetMessage('CRM_INVOICE_PS_PROPS_CONTENT')?>",
							resizable: false,
							draggable: true,
							height: 70,
							width: 350
						}
					);

				var _BTN = [

					{
						title: "<?=GetMessage('CRM_INVOICE_PS_PROPS_GOTO')?>",
						id: "crmPSPropsCreate",
						"action": function()
						{
							window.location.href = "/crm/configs/ps/";
							BX.WindowManager.Get().Close();
						}
					},

					BX.CDialog.btnCancel
				];
				d.ClearButtons();
				d.SetButtons(_BTN);
				d.Show();
			})();

			<?CCrmPaySystem::markPSFillPropsDialogAsViewed();
		endif;?>

	});

	<?php
	if (isset($arResult['PRODUCT_ROWS']) && count($arResult['PRODUCT_ROWS']) > 0)
	{
		echo PHP_EOL.
			"\t".'BX.ready(function () {'.PHP_EOL.
			"\t\t".'BX.onCustomEvent(\'InitiateInvoiceSumTotalChange\');'.PHP_EOL.
			"\t".'})';
	}
	?>

</script><?php
// ---------------------- processing of status change ---------------------->
?>
<script type="text/javascript">

	function onCrmInvoiceEditStatusChange()
	{
		var statusSort = <?= CUtil::PhpToJSObject($arResult['STATUS_SORT']) ?>;
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		if (form)
		{
			var payVoucherNum = BX.findChild(form, {"tag": "input", "attr": {"type": "text", "name": "PAY_VOUCHER_NUM"}}, true, false);
			var statusSelect = BX.findChild(form, {"tag": "select", "attr": {"name": "STATUS_ID"}}, true, false);
			var payVoucherDate = BX("PAY_VOUCHER_DATE");
			var reasonMarkedSuccess = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED_SUCCESS"}}, true, false);
			var dateMarked = BX("DATE_MARKED");
			var reasonMarked = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED"}}, true, false);
			var statusId = null, isSuccess = false, isFailed = false, block = null;
			if (statusSelect &&
				payVoucherDate && payVoucherNum && reasonMarkedSuccess &&
				dateMarked && reasonMarked)
			{
				statusId = statusSelect.value;
				if (typeof(statusId) === "string" && statusId.length > 0)
				{
					isSuccess = (statusId === "P");
					if (isSuccess)
						isFailed = false;
					else
						isFailed = (statusSort[statusId] >= statusSort["D"]);

					var successElements = [payVoucherDate, payVoucherNum, reasonMarkedSuccess];
					var failedElements = [dateMarked, reasonMarked];
					for (var i in successElements)
					{
						block =  BX.findParent(successElements[i], {"tag": "tr", "attr": {"class": "crm-offer-row"}})
						if (block)
							block.style.display = isSuccess ? "" : "none";
					}
					for (var i in failedElements)
					{
						block =  BX.findParent(failedElements[i], {"tag": "tr", "attr": {"class": "crm-offer-row"}})
						if (block)
							block.style.display = isFailed ? "" : "none";
					}
				}
			}
		}
	}

	BX.ready(function () {
		onCrmInvoiceEditStatusChange();
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		if (form)
		{
			var statusSelect = BX.findChild(form, {"tag": "select", "attr": {"name": "STATUS_ID"}}, true, false);
			if (statusSelect)
			{
				BX.bind(statusSelect, "change", onCrmInvoiceEditStatusChange)
			}
		}
	});

</script>
<?php
// <---------------------- processing of status change ----------------------
// ---------------------- processing of pay system select ---------------------->
?><script type="text/javascript">
	BX.ready(function () {
		var companyId = <?= CUtil::JSEscape(intval($arResult['ELEMENT']['UF_COMPANY_ID'])) ?>;
		var contactId = <?= CUtil::JSEscape(intval($arResult['ELEMENT']['UF_CONTACT_ID'])) ?>;
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		if (form)
		{
			form["crmInvoiceCompanyId"] = companyId;
			form["crmInvoiceContactId"] = contactId;

			var contactPersonContainer = BX('<?=CUtil::JSEscape($arResult['CONTACT_PERSON_CONTAINER_ID'])?>');
			var contactPersonBlock = null;
			if (contactPersonContainer)
			{
				contactPersonBlock =  BX.findParent(contactPersonContainer, {"tag": "tr", "attr": {"class": "crm-offer-row"}});
				if (contactPersonBlock && companyId <= 0)
					contactPersonBlock.style.display = "none";
			}


			var paySystemSelect = BX("PAY_SYSTEM_SELECT");
			if (paySystemSelect)
			{
				if (companyId <= 0 && contactId <= 0)
				{
					paySystemSelect.setAttribute("disabled", "true");
				}
				var paySystemHint = BX.create("DIV",
					{
						"attrs": {"class": "bx-crm-edit-content-location-description"},
						"children":
						[
							BX.create("SPAN",
							{
								"attrs": {"class": "bx-crm-edit-content-location-description"},
								"text": "<?= CUtil::JSEscape($arResult['PAY_SYSTEM_ID_TITLE']) ?>"
							})
						]
					}
				);
				var paySystemContainer = BX.findParent(paySystemSelect, {"tag": "td", "class": "crm-offer-info-right"});
				if (paySystemContainer)
					paySystemContainer.appendChild(paySystemHint);
			}
		}
	});
</script><?php
// <---------------------- processing of pay system select ----------------------
?><?php
