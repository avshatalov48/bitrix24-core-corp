<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load(array("ui.graph.circle", "ui.icons.b24", "ui.fonts.opensans", "ui.hint"));
CJSCore::Init("finder");

use Bitrix\Main\Localization\Loc;

$this->SetViewTarget("sidebar", 10);
$frame = $this->createFrame()->begin();

?>
<div class="intranet-ustat-online-box js-intranet-ustat-online-container">
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

		BX.Intranet.UstatOnline = new BX.Intranet.UstatOnline({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			users: <?=$arResult['USERS']?>,
			currentUserId: <?=$USER->GetID()?>,
			limitOnlineSeconds: <?=$arResult["LIMIT_ONLINE_SECONDS"]?>,
			maxOnlineUserCountToday: '<?=$arResult["MAX_ONLINE_USER_COUNT_TODAY"]?>',
			allOnlineUserIdToday: <?=CUtil::PhpToJSObject($arResult["ALL_ONLINE_USER_ID_TODAY"])?>,
			ustatOnlineContainerNode: document.querySelector('.js-intranet-ustat-online-container'),
			userBlockNode: document.querySelector('.intranet-ustat-online-icon-box'),
			userInnerBlockNode: document.querySelector('.intranet-ustat-online-icon-inner'),
			circleNode: document.querySelector('.ui-graph-circle'),
			timemanNode: document.querySelector('.intranet-ustat-online-info'),
			isTimemanAvailable: '<?=$arResult["IS_FEATURE_TIMEMAN_AVAILABLE"] ? "Y" : "N"?>'
		});
	});
</script>

<?$frame->beginStub();?>
<div class="intranet-ustat-online-box js-intranet-ustat-online-container">
	<div class="intranet-ustat-online-badge">live</div>
	<div class="intranet-ustat-online-counter">
		<div class="ui-graph-circle ui-graph-circle-waves-blue ui-graph-circle-bar-progress-green">
			<div class="ui-graph-circle-wrapper ui-graph-circle-wrapper-animate ui-graph-circle-counter">
				<svg class="ui-graph-circle-bar ui-graph-circle-bar-animate ui-graph-circle-bar-without-animate" viewport="0 0 34 34" width="68" height="68">
					<circle r="24" cx="34" cy="34" class="ui-graph-circle-bar-bg"></circle><circle r="24" cx="34" cy="34" stroke-dasharray="150.72" stroke-dashoffset="0" class="ui-graph-circle-bar-progress"></circle>
				</svg>
				<div class="ui-graph-circle-waves-wrapper">
					<div class="ui-graph-circle-waves" style="transform: translateY(-70%);"></div>
				</div>
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
<?$frame->end(); ?>
