<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["FatalError"] <> '')
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if ($arResult["ErrorMessage"] <> '')
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>

	<form method="post" action="<?= POST_FORM_ACTION_URI ?>" enctype="multipart/form-data" name="meeting_add">
	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<tbody>
			<tr>
				<td align="right"><span class="red_star">*</span><?= GetMessage("INTASK_C87_NAME") ?>:</td>
				<td><input type="text" name="name" value="<?= $arResult["Item"]["NAME"] ?>" size="50"></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?= GetMessage("INTASK_C87_DESCR") ?>:</td>
				<td><textarea name="description" rows="5" cols="50"><?=$arResult['Item']['DESCRIPTION'] ?></textarea></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C87_FLOOR") ?>:</td>
				<td><input type="text" name="uf_floor" value="<?= $arResult["Item"]["UF_FLOOR"] ?>" size="5"></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C87_PLACE") ?>:</td>
				<td><input type="text" name="uf_place" value="<?= $arResult["Item"]["UF_PLACE"] ?>" size="5"></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C87_PHONE") ?>:</td>
				<td><input type="text" name="uf_phone" value="<?= $arResult["Item"]["UF_PHONE"] ?>" size="20"></td>
			</tr>
		</tbody>
	</table>
	<br>
	<input type="submit" name="save" value="<?= GetMessage("INTASK_C87_SAVE") ?>">
	<?=bitrix_sessid_post()?>
	</form>
	<?
}
?>