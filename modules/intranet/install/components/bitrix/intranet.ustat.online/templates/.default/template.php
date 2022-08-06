<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load([
	"ui.graph.circle",
	"ui.icons.b24",
	"ui.fonts.opensans",
	"ui.hint",
	"ui.design-tokens",
	"ui.fonts.opensans",
]);
CJSCore::Init("finder");

use Bitrix\Main\Localization\Loc;

if ($arResult['DISPLAY_MODE'] === 'sidebar')
{
	$this->SetViewTarget("sidebar", 10);
	$frame = $this->createFrame()->begin();
}

$containerDataId = 'intranet-ustat-online-container-' . rand();
?>
<div class="intranet-ustat-online-box" data-id="<?=$containerDataId?>">
	<div class="intranet-ustat-online-badge">live</div>
	<div id="intranet-ustat-online-hint" class="intranet-ustat-online-hint" onclick="BX.Helper.show('redirect=detail&code=11584442');">
		<div class="intranet-ustat-online-hint-item">
			<div class="intranet-ustat-online-hint-icon"></div>
		</div>
	</div>
	<div class="intranet-ustat-online-counter" data-hint="<?=Loc::getMessage("INTRANET_USTAT_ONLINE_HINT")?>" data-hint-no-icon>
		<div class="ui-graph-circle ui-graph-circle-waves-blue ui-graph-circle-bar-progress-green"></div>
	</div>
	<div class="intranet-ustat-online-main">
		<div class="intranet-ustat-online-icon-box">
			<div class="intranet-ustat-online-icon-inner"></div>
		</div>
		<?if ($arResult["IS_TIMEMAN_INSTALLED"]):?>
			<div class="intranet-ustat-online-info">
				<div class="intranet-ustat-online-info-item js-ustat-online-timeman-opened-block">
					<span class="intranet-ustat-online-text js-ustat-online-timeman-text"><?=Loc::getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY")?></span>
					<?if (!$arResult["IS_FEATURE_TIMEMAN_AVAILABLE"]):?>
						<span class="tariff-lock" onclick="BX.UI.InfoHelper.show('limit_office_worktime');"></span>
					<?else:?>
						<span class="intranet-ustat-online-value js-ustat-online-timeman-opened"><?=$arResult["OPENED_DAY_COUNT"]?></span>
					<?endif?>
				</div>
				<div class="intranet-ustat-online-info-item js-ustat-online-timeman-closed-block">
					<span class="intranet-ustat-online-text js-ustat-online-timeman-text"><?=Loc::getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY")?></span>
					<?if (!$arResult["IS_FEATURE_TIMEMAN_AVAILABLE"]):?>
						<span class="tariff-lock" onclick="BX.UI.InfoHelper.show('limit_office_worktime');"></span>
					<?else:?>
						<span class="intranet-ustat-online-value js-ustat-online-timeman-closed"><?=$arResult["CLOSED_DAY_COUNT"]?></span>
					<?endif?>
				</div>
			</div>
		<?endif?>
	</div>
</div>
<!--
<div class="intranet-ustat-online-helper-wrap">
	<div style="background-color: #0a51ae; transform: scale(.25);" class="ui-graph-circle-helper ui-graph-circle-waves-pale ui-graph-circle-bar-progress-white"></div>
	<div class="intranet-ustat-online-helper">
		<span data-hint="hint"></span>
	</div>
</div>-->

<script>
	BX.ready(function () {
		BX.message({
			"INTRANET_USTAT_ONLINE_EMPTY" : "<?=CUtil::JSEscape(Loc::getMessage("INTRANET_USTAT_ONLINE_EMPTY"))?>",
			"INTRANET_USTAT_ONLINE_USERS" : "<?=CUtil::JSEscape(Loc::getMessage("INTRANET_USTAT_ONLINE_USERS"))?>",
			"INTRANET_USTAT_ONLINE_STARTED_DAY" : "<?=CUtil::JSEscape(Loc::getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY"))?>",
			"INTRANET_USTAT_ONLINE_FINISHED_DAY" : "<?=CUtil::JSEscape(Loc::getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY"))?>"
		});

		new BX.Intranet.UstatOnline({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			users: <?=$arResult['USERS']?>,
			currentUserId: <?=intval($USER->GetID())?>,
			limitOnlineSeconds: <?=$arResult["LIMIT_ONLINE_SECONDS"]?>,
			maxOnlineUserCountToday: '<?=$arResult["MAX_ONLINE_USER_COUNT_TODAY"]?>',
			maxUserToShow: <?=$arResult["MAX_USER_TO_SHOW"]?>,
			allOnlineUserIdToday: <?=CUtil::PhpToJSObject($arResult["ALL_ONLINE_USER_ID_TODAY"])?>,
			ustatOnlineContainerNode: document.querySelector("[data-id='<?=$containerDataId?>']"),
			isTimemanAvailable: '<?=$arResult["IS_FEATURE_TIMEMAN_AVAILABLE"] ? "Y" : "N"?>',
			isFullAnimationMode: '<?=$arResult["IS_FULL_ANIMATION_MODE"] ? "Y" : "N"?>'
		});
	});
</script>

<?
if ($arResult['DISPLAY_MODE'] === 'sidebar')
{
	$frame->beginStub();
?>
	<div class="intranet-ustat-online-box js-intranet-ustat-online-container">
		<div class="intranet-ustat-online-badge">live</div>
		<div class="intranet-ustat-online-counter">
			<div class="ui-graph-circle ui-graph-circle-waves-blue ui-graph-circle-bar-progress-green">
				<div class="ui-graph-circle-wrapper ui-graph-circle-wrapper-animate ui-graph-circle-counter">
					<canvas class="ui-graph-circle-canvas" height="200" width="200"></canvas>
				</div>
			</div>
		</div>
		<div class="intranet-ustat-online-main">
			<div class="intranet-ustat-online-icon-box"></div>
			<?if ($arResult["IS_TIMEMAN_INSTALLED"]):?>
				<div class="intranet-ustat-online-info">
					<div class="intranet-ustat-online-info-item js-ustat-online-timeman-opened-block">
						<span class="intranet-ustat-online-text js-ustat-online-timeman-text"><?=Loc::getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY")?></span>
					</div>
					<div class="intranet-ustat-online-info-item js-ustat-online-timeman-closed-block">
						<span class="intranet-ustat-online-text js-ustat-online-timeman-text"><?=Loc::getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY")?></span>
					</div>
				</div>
			<?endif?>
		</div>
	</div>
<?
	$frame->end();
}
?>
