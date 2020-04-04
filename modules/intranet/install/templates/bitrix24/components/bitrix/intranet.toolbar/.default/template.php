<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
$i = 0;
if (!$arParams["AJAX_MODE"]):
	?>
	<div class="sidebar-buttons" id="bx_intranet_toolbar">
	<?
else:
	?>
	<div class="sidebar-buttons" id="bx_intranet_toolbar_tmp" style="display: none;">
	<?
endif;

if (!empty($arParams['OBJECT']->arButtons) && is_array($arParams['OBJECT']->arButtons)) :
foreach ($arParams["OBJECT"]->arButtons as $arButton):
	$arAttributes = array();
	if ($arButton['HREF'])
		$arAttributes[] = 'href="'.htmlspecialcharsbx($arButton['HREF']).'"';
	else
		$arAttributes[] = 'href="javascript:void(0)"';
		
	if ($arButton['ONCLICK'])
		$arAttributes[] = 'onclick="'.htmlspecialcharsbx($arButton['ONCLICK']).'"';

	if ($arButton['ID'])
		$arAttributes[] = 'id="'.htmlspecialcharsbx($arButton['ID']).'"';
		
	if ($arButton['ICON'] == 'add')
		$arButton['ICON'] = 'create';

	if ($arButton['ICON'] == 'import-users')
		$arButton['ICON'] = 'import';
			
	if ($arButton['TITLE'])
		$arAttributes[] = 'title="'.htmlspecialcharsbx($arButton['TITLE']).'"';

	$className =
		in_array($arButton["ICON"], array("add", "create")) ?
			" webform-small-button-blue bx24-top-toolbar-add" :
			""
	?>
		<a
			<?echo implode(' ', $arAttributes)?>
			class="webform-small-button bx24-top-toolbar-button<?=$className?>">
			<span class="webform-small-button-icon"></span>
			<span class="webform-small-button-text"><?=htmlspecialcharsbx($arButton['TEXT'])?></span>
		</a>
	<?
endforeach;
endif;
?>
</div>
<?
if ($arParams["AJAX_MODE"]):
	?>
	<script type="text/javascript">
	setTimeout(function() {
		var obToolbar = document.getElementById('bx_intranet_toolbar');
		var obToolbarTmp = document.getElementById('bx_intranet_toolbar_tmp');

		if (null == obToolbar)
		{
			obToolbarTmp.id = 'bx_intranet_toolbar';
			obToolbarTmp.style.display = 'block';
		}
		else
		{
			obToolbar.innerHTML = obToolbarTmp.innerHTML;
			obToolbarTmp.parentNode.removeChild(obToolbarTmp);
			obToolbarTmp = null;
		}
	}, 200);
	</script>
	<?
endif;
?>