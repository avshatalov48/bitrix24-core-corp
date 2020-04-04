<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
$i = 0;
if (!$arParams["AJAX_MODE"]):
	?>
	<div class="bx-intranet-buttons-container" id="bx_intranet_toolbar">
	<?
else:
	?>
	<div class="bx-intranet-buttons-container" id="bx_intranet_toolbar_tmp" style="display: none;">
	<?
endif;

if (!empty($arParams['OBJECT']->arButtons) && is_array($arParams['OBJECT']->arButtons)) :
foreach ($arParams["OBJECT"]->arButtons as $arButton):
	?>
	<div class="bx-intranet-button">
		<table>
			<tr>
				<td class="bx-intranet-button-delimiter"></td>
				<?
				$arAttributes = array();
				if ($arButton['HREF'])
					$arAttributes[] = 'href="'.htmlspecialcharsbx($arButton['HREF']).'"';
				else
					$arAttributes[] = 'href="javascript:void(0)"';
					
				if ($arButton['ONCLICK'])
					$arAttributes[] = 'onclick="'.htmlspecialcharsbx($arButton['ONCLICK']).'"';
						
				if ($arButton['TITLE'])
					$arAttributes[] = 'title="'.htmlspecialcharsbx($arButton['TITLE']).'"';
				?>
				<td class="bx-intranet-button-container bx-intranet-<?echo htmlspecialcharsbx($arButton['ICON'])?>">
					<a <?echo implode(' ', $arAttributes)?>><?echo htmlspecialcharsbx($arButton['TEXT'])?></a>
				</td>
				<?
				if (++$i == $cnt):
					?>
					<td class="bx-intranet-button-delimiter"></td>
					<?
				endif;
				?>
			</tr>
		</table>
	</div>
	<?
endforeach;
endif;
?>
</div>
<div style="clear: both;"></div>
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