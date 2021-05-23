<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CUtil::InitJSCore(array('ajax', 'popup'));

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');

$fieldName = $arParams["arUserField"]["~FIELD_NAME"];
$formPressetName = $arParams["form_name"];
$formPressetChoiseName = str_replace('filter_', 'filters_', $formPressetName);
?>


<div id="crm-<?=$fieldName?>-box">
	<div  class="crm-button-open">
		<a id="crm-<?=$fieldName?>-open" href="#open" onclick="return CRM_set<?=$fieldName?>(this, true)"><?=GetMessage('CRM_FF_CHOISE');?></a>
		<input id="crm-<?=$fieldName?>-input-temp" type="text" name="<?=$fieldName?>"  style="display:none" onchange="return CRM_set<?=$fieldName?>(BX.findPreviousSibling(this, { 'tagName':'A' }), false)" />
	</div>
</div>

<script type="text/javascript">
	function CRM_set<?=$fieldName?>(el, bOpen)
	{
		var subIdName = '';
		if (document.forms['<?=CUtil::JSEscape($formPressetChoiseName)?>'])
		{
			subIdName = document.forms['<?=CUtil::JSEscape($formPressetChoiseName)?>'].filters_list.value;
			if (!subIdName)
				subIdName = 'add'+Math.round(Math.random()*1000000);

			BX.addCustomEvent('onWindowClose', function(ev, dd) {
				var opt = document.forms['<?=CUtil::JSEscape($formPressetChoiseName)?>'].filters_list;
				if (!opt)
					return ;

				for(var i = 0; i < opt.options.length; i++)
				{
					for (var j in obCrm)
					{
						if (j.indexOf(opt.options[i].value) != -1)
							obCrm[j].Clear();
					}
				}
			});
		}

		var crmID = CRM.Set(
			el,
			'<?=CUtil::JSEscape($fieldName)?>',
			subIdName,
			<?echo CUtil::PhpToJsObject($arResult['ELEMENT']);?>,
			<?=($arResult["PREFIX"]=='Y'? 'true': 'false')?>,
			false,
			<?echo CUtil::PhpToJsObject($arResult['ENTITY_TYPE']);?>,
			{
				'lead': '<?=CUtil::JSEscape(GetMessage('CRM_FF_LEAD'))?>',
				'contact': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CONTACT'))?>',
				'company': '<?=CUtil::JSEscape(GetMessage('CRM_FF_COMPANY'))?>',
				'deal': '<?=CUtil::JSEscape(GetMessage('CRM_FF_DEAL'))?>',
				'quote': '<?=CUtil::JSEscape(GetMessage('CRM_FF_QUOTE'))?>',
				'ok': '<?=CUtil::JSEscape(GetMessage('CRM_FF_OK'))?>',
				'cancel': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CANCEL'))?>',
				'close': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CLOSE'))?>',
				'wait': '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
				'noresult': '<?=CUtil::JSEscape(GetMessage('CRM_FF_NO_RESULT'))?>',
				'add' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHOISE'))?>',
				'edit' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHANGE'))?>',
				'search' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
				'last' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_LAST'))?>'
			});

		// temporary input, need for correct job presets
		if (el.nextElementSibling)
		{
			var tmpInput = el.nextElementSibling;
			var tmpInputValue = '';
			tmpInputValue = tmpInput.value;
			//tmpInput.parentNode.removeChild(tmpInput);
			if (tmpInputValue != '')
				obCrm[crmID].PopupSetItem(tmpInputValue);
		}

		if (bOpen && obCrm[crmID])
			obCrm[crmID].Open();

		return false;
	}

	// through "ready" necessary because the presets are initialized so
	BX.ready(function() {
		if (document.forms['<?=$formPressetName?>'])
		{
			var el_a = BX.findChild(document.forms['<?=$formPressetName?>'], {attr : {id : "crm-<?=$fieldName?>-open"}}, true, false);
			if (el_a)
				CRM_set<?=$fieldName?>(el_a, false);
		}
	})

</script>