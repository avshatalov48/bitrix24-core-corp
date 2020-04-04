<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<?$this->SetViewTarget("bx-mobile-back-btn", 100);
?>
<a href="<?=SITE_DIR?>m/company/" class="ui-btn-left" data-iconpos="notext" data-role="button" data-icon="back" data-direction="reverse"><?=GetMessage("BM_TO_USER_LIST")?></a>
<?
$this->EndViewTarget();
?>
<div id="m-users">
<?
if(!empty($arResult["users"]))
{
	foreach($arResult["users"] as $id => $item)
	{
		?>
		<table width="100%">
			<tr>
				<td valign="top"><div class="bx-m-user-image-big"><img src="<?=($item["PERSONAL_PHOTO_B"]? $item["PERSONAL_PHOTO_B"]["src"]: "/bitrix/images/intranet/employees/nopic_user_50_noborder.gif")?>"/></div></td>
				<td style="padding-left: 10px; vertical-align:top;" width="100%">
				<b style="font-size:1.2em;"<?if($item["IS_ONLINE"]):?> class="bx-user-online"<?endif;?>><?=$item["LAST_NAME"].' '.$item['NAME']?></b><br />
				<b><?=$item["WORK_POSITION"]?></b><br />
				<?if ($item["CHAT_URL"]):?>
					<a href="<?= $item["CHAT_URL"] ?>" data-ajax="false" class="bx-icon-message" style="float:left;"><?=GetMessage("BM_WRITE")?></a>
				<?endif;?>
				</td>
			</tr>
		</table>
		<table cellpadding="1" style="padding-top: 10px; font-family: Verdana; color: #777777;">
		<?
		if(!empty($item["UF_DEPARTMENT"]))
		{
			?>
			<tr>
				<td><?=GetMessage("BM_DEPARTMENT")?></td>
				<td><a href="<?=$arResult["deps"][$item["UF_DEPARTMENT"][0]]["URL"];?>"><?=$arResult["deps"][$item["UF_DEPARTMENT"][0]]["NAME"];?></a></td>
			</tr>
			<?
		}
		if(!empty($arResult['MANAGERS']))
		{
			?>
			<tr>
				<td><?=GetMessage("BM_DIRECTOR")?>:</td>
				<td><?
			foreach($arResult['MANAGERS'] as $manager)
			{
				?>
				<table cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td><div class="bx-m-user-info-thumbnail"><a href="<?=$manager["URL"]?>"><img src="<?=$manager["PHOTO"]["src"]?>" border='0' alt=""/></a></div></td>
					<td valign="top"><a href="<?=$manager["URL"]?>"><?=$manager["NAME"]?></a></td>
				</tr>
				</table>
				<?
			}
			?></td>
			</tr>
			<?
		}
		if(!empty($arResult['DEPARTMENTS']))
		{
			?>
			<tr>
				<td><?=GetMessage("BM_DIRECTOR_OF")?>:</td>
				<td><?
			foreach($arResult['DEPARTMENTS'] as $dep)
			{
				?><a href="<?=$dep["URL"]?>"><?=$dep["NAME"]?></a><?if($dep['EMPLOYEE_COUNT'] > 0):?><span title="<?=GetMessage("BM_USR_CNT")?>"> (<?=$dep['EMPLOYEE_COUNT']?>)<span><?endif?><?
			}
			?></td>
			</tr><?
		}
		if($item['EMAIL'])
		{
			?><tr>
				<td>E-mail:</td>
				<td><a href="mailto:<?=$item["EMAIL"];?>"><?=$item["EMAIL"];?></a></td>
			</tr><?
		}
		if($item['WORK_PHONE'])
		{
			?><tr>
				<td><?=GetMessage("BM_PHONE")?>:</td>
				<td><a href="tel:<?=$item['WORK_PHONE']?>"><?=$item['WORK_PHONE']?></a></td>
			</tr><?
		}
		if($item['PERSONAL_MOBILE'])
		{
			?><tr>
				<td><?=GetMessage("BM_PHONE_MOB")?>:</td>
				<td><a href="tel:<?=$item['PERSONAL_MOBILE']?>"><?=$item['PERSONAL_MOBILE']?></a></td>
			</tr><?
		}
		if($item['UF_PHONE_INNER'])
		{
			?><tr>
				<td><?=GetMessage("BM_PHONE_INT")?>:</td>
				<td><a href="tel:<?=$item['UF_PHONE_INNER']?>"><?=$item['UF_PHONE_INNER']?></a></td>
			</tr><?
		}
		if($item['PERSONAL_BIRTHDAY'])
		{
			?><tr>
				<td><?=GetMessage("BM_BIRTHDAY")?>:</td>
				<td><?=$item['PERSONAL_BIRTHDAY']?></td>
			</tr><?
		}
		?></table><?
	}
}
else
	ShowError(GetMessage("BM_NO_USERS"));
?>
</div>

<script>
function showDetail(id)
{
	$.mobile.changePage('index.php?id='+id);
}
</script>