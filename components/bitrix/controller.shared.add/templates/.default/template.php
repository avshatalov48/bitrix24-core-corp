<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?if($arResult["NEW_URL"]):?>
	<p>
	<?echo GetMessage("CT_BCSA_SITE_CREATED")?>:
	<a href="http://<?=$arResult["NEW_URL"]?>/">http://<?=$arResult["NEW_URL"]?>/</a>.
	</p>
<?endif?>
<?ShowError($arResult["ERROR_MESSAGE"]);?>
<div class="post-form">
<form method="POST">
	<table class="controller-form">
	<tr>
		<th colspan="2"><?echo GetMessage("CT_BCSA_SITE_NAME")?>:</th>
	</tr>
	<tr>
		<td>http://<input type="text" name="domain_name" value="<?=htmlspecialcharsbx($_REQUEST["domain_name"])?>">.<?=htmlspecialcharsbx($arParams['URL_SUBDOMAIN'])?></td>
		<td><input type="submit" name="create" value="<?echo GetMessage("CT_BCSA_CREATE_BUTTON")?>"></td>
	</tr>
	</table>
</form>
</div>

<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
