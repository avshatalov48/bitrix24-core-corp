<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.alerts', 'ui.design-tokens']);
$APPLICATION->SetPageProperty("BodyClass", "no-paddings no-hidden no-background");

if (empty($arResult["EMAIL_FORWARD_TO"]))
{
	return;
}
?>
<div class="intranet-socnet-email" id="intranet-socnet-email">

	<div class="intranet-socnet-email-intro"><?=Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_DESC")?></div>

	<?
	if (!empty($arResult["EMAIL_FORWARD_TO"]['BLOG_POST']))
	{
	?><div class="intranet-socnet-email-tr">
		<div class="intranet-socnet-email-td"><?=GetMessage("INTRANET_SOCNET_EMAIL_SETTINGS_STREAM2").":"?></div>
		<div class="intranet-socnet-email-td">
			<input
				type="text"
				class="ui-ctl-element"
				data-input=""
				data-role="copy-blog-input"
				value="<?=$arResult["EMAIL_FORWARD_TO"]['BLOG_POST']?>"
			>
		</div>
		<div class="intranet-socnet-email-td">
			<span class="intranet-socnet-email-link-copy" data-role="copyBlog">
				<?=Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_COPY")?>
			</span>
		</div>
	</div>

	<?
	}
	if (!empty($arResult["EMAIL_FORWARD_TO"]['TASKS_TASK']))
	{
	?><div class="intranet-socnet-email-tr">
		<div class="intranet-socnet-email-td"><?=Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_TASK").":"?></div>
		<div class="intranet-socnet-email-td">
			<input
				type="text"
				class="ui-ctl-element"
				data-input=""
				data-role="copy-task-input"
				value="<?=$arResult["EMAIL_FORWARD_TO"]['TASKS_TASK']?>"
			>
		</div>
		<div class="intranet-socnet-email-td">
			<span class="intranet-socnet-email-link-copy" data-role="copyTask">
				<?=Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_COPY")?>
			</span>
		</div>
	</div><?
	}?>

	<div class="intranet-socnet-email-warning">
		<div class="ui-alert ui-alert-md ui-alert-warning">
			<span class="ui-alert-message">
				<?=Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_HELP", array(
					"#LINK_START#" => "<a href=\"javascript:void(0)\" onclick='top.BX.Helper.show(\"redirect=detail&code=9324261\");'>",
					"#LINK_END#" => "</a>"
				))?>
			</span>
		</div>
	</div>

</div>

<script>
	BX.message({
		"INTRANET_SOCNET_EMAIL_SETTINGS_COPY_SUCCESS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_SOCNET_EMAIL_SETTINGS_COPY_SUCCESS"))?>"
	});

	BX.ready(function () {
		new BX.Intranet.SocnetEmailSettings({});
	});
</script>