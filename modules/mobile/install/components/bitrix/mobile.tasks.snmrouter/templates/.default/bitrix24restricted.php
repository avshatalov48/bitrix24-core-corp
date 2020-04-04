<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 * @var CUser $USER
 */
if (false && $USER->CanDoOperation('bitrix24_config'))
{
?>
<div class="mobile-grid mobile-grid-empty" >
	<div class="mobile-grid-stub">
		<div class="mobile-grid-stub-text2"><?=GetMessage("TASK_RESTRICTED_ADMIN1")?></div>
		<a href="/settings/business_tools.php" target="_blank" class="webform-small-button webform-small-button-blue"><?=GetMessage("TASK_RESTRICTED_ADMIN2")?></a>
	</div>
</div>
<?
}
else
{
?>
<div class="mobile-grid mobile-grid-empty" >
	<div class="mobile-grid-stub">
		<div class="mobile-grid-stub-text2"><?=GetMessage("TASK_RESTRICTED_USER1")?></div>
		<a href="#" class="webform-small-button webform-small-button-blue" id="TASK_RESTRICTED_USER2"><?=GetMessage("TASK_RESTRICTED_USER2")?></a>
	</div>
</div>
<script>
BX.ready(function(){
	BX.bind(BX("TASK_RESTRICTED_USER2"), "click", function(e) {
		var href = BX("TASK_RESTRICTED_USER2"),
			f = function(data){
				href.removeAttribute("bx-busy");
				BX.removeClass(href.parentNode, "mobile-grid-load");
				if (data && data["success"] == "Y")
				{
					BX.addClass(href.parentNode, "mobile-grid-sent");
					href.innerHTML = '<?=GetMessageJS("TASK_RESTRICTED_USER3")?>';
					href.setAttribute("bx-sent", "Y");
				}
			};
		if (!href.hasAttribute("bx-sent") && !href.hasAttribute("bx-busy"))
		{
			href.setAttribute("bx-busy", "Y");
			BX.addClass(href.parentNode, "mobile-grid-load");
			BX.ajax({
				method : "POST",
				dataType : "json",
				url : "<?=SITE_DIR?>mobile/?mobile_action=bitrix24_ajax",
				data : {
					sessid : BX.bitrix_sessid(),
					action : "tool_request"
				},
				onsuccess : f,
				onfailure : f
			});
		}
		return BX.PreventDefault(e);
	});
});
</script><?
}
