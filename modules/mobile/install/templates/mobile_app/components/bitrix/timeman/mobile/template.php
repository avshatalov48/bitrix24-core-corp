<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CMain $APPLICATION
 */
$duration = 0;
$statusClass = $arResult["statusClass"];
$stateTimer = 0;
$pauseTimer = 0;

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();

\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v1.3");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("LanguageId", LANGUAGE_ID);
\Bitrix\Main\Data\AppCacheManifest::getInstance()->setExcludeImagePatterns(
	array(
		"fontawesome",
		"images/newpost",
		"images/files",
		"/crm",
		"images/im",
		"images/post",
		"images/notification",
		"images/messages",
		"images/lenta",
		"images/bizproc",
		"images/calendar",
		"images\\/sprite.png", "images\\/tri_"
	)
);



if ($request->getQuery("report") == "Y")
{
	if ($arResult["START_INFO"]["ID"] > 0)
	{
		$APPLICATION->SetPageProperty('BodyClass', 'mobile-timeman-report');
		\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "timeman.report");

		$feedFrame = $this->createFrame()->begin();
?>
<script>
	BX.MTimeManReport(BX("mobile-timeman"), <?=CUtil::PhpToJSObject($arResult["START_INFO"]);?>);
</script><?
		$feedFrame->beginStub();
?><div class="mobile-timeman-loader"></div><?
		$feedFrame->end();
?><div class="mobile-timeman mobile-timeman-report" id="mobile-timeman">
	<div class="mobile-timeman-completion">
		<textarea class="mobile-timeman-completion-textarea" data-bx-timeman="report" data-bx-timeman-report-req="" placeholder="<?=($arResult["START_INFO"]['REPORT_REQ'] == "Y" ? GetMessage("TM_PLACEHODER0") : GetMessage("TM_PLACEHODER1"))?>"></textarea>
		<div class="mobile-timeman-control">
			<input type="submit" class="mobile-button mobile-button-create" data-bx-timeman="save-button" value="<?=GetMessage("TM_SAVE")?>" />
		</div>
	</div>
</div>
<script>
	BX.message({
		PAGE_TITLE : '<?=GetMessageJS("PAGE_TITLE1")?>',
		PULLDOWN_PULL : '<?=GetMessageJS('PULLDOWN_PULL')?>',
		PULLDOWN_DOWN : '<?=GetMessageJS('PULLDOWN_DOWN')?>',
		PULLDOWN_LOADING : '<?=GetMessageJS('PULLDOWN_LOADING')?>',
		TM_MENU_SAVE : '<?=GetMessageJS('TM_MENU_SAVE')?>',
		TM_MENU_CANCEL : '<?=GetMessageJS('TM_MENU_CANCEL')?>'
	});
</script>
<?
	}
	else
	{
		?><?=GetMessage("TM_ERROR1")?><?
	}
}
else if ($request->getQuery("edit") == "Y")
{
	\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "timeman.edit");
	$disabled = ($arResult["START_INFO"]["CAN_EDIT"] === "Y" ? "" : "disabled");
	$APPLICATION->SetPageProperty('BodyClass', 'mobile-timeman-edit');

	$feedFrame = $this->createFrame()->begin();
?><script>
BX.MTimeManEdit(
	BX("mobile-timeman"),
	<?=CUtil::PhpToJSObject($arResult["START_INFO"]);?>,
	{time : '<?=$arParams["TIME_FORMAT"]?>'}
);
</script><?
	$feedFrame->beginStub();
?><div class="mobile-timeman-loader"></div><?
	$feedFrame->end();
?>
<div class="mobile-timeman" id="mobile-timeman">
	<div class="mobile-timeman-completion">
		<div class="mobile-timeman-control-timing">
			<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_BEGINNING")?></div>
			<div class="mobile-timeman-control-timing-time"></div><?
			?><input type="hidden" data-bx-timeman="start-timestamp" value="0" />
		</div>
		<div class="mobile-timeman-control-timing">
			<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_END")?></div>
			<div class="mobile-timeman-control-timing-time"></div><?
			?><input type="hidden" data-bx-timeman="finish-timestamp" value="0" />
		</div>
		<div class="mobile-timeman-control-timing">
			<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_PAUSE")?></div>
			<div class="mobile-timeman-control-timing-time"></div><?
			?><input type="hidden" data-bx-timeman="pause-timestamp" value="0" />
		</div>
		<div class="mobile-timeman-control-timing">
			<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_DURATION")?></div>
			<div class="mobile-timeman-control-timing-time"></div><?
			?><input type="hidden" data-bx-timeman="duration-timestamp" value="0" />
		</div>
		<textarea <?=$disabled?> class="mobile-timeman-completion-textarea" data-bx-timeman="edit-reason" placeholder="<?=GetMessage("TM_REASON2")?>"></textarea>
		<div class="mobile-timeman-control">
			<input type="submit" <?=$disabled?> class="mobile-button mobile-button-create" data-bx-timeman="save-button" value="<?=GetMessage("TM_SAVE")?>" />
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.message({
		PAGE_TITLE : '<?=GetMEssageJS("PAGE_TITLE2")?>',
		PULLDOWN_PULL : '<?=GetMessageJS('PULLDOWN_PULL')?>',
		PULLDOWN_DOWN : '<?=GetMessageJS('PULLDOWN_DOWN')?>',
		PULLDOWN_LOADING : '<?=GetMessageJS('PULLDOWN_LOADING')?>'
	});
</script>
<?
}
else
{
	$APPLICATION->SetPageProperty('BodyClass', 'mobile-timeman-view');
	\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "timeman.view");
	$feedFrame = $this->createFrame()->begin();
?>
<script>
BX.MTimeMan(BX("mobile-timeman"), <?=CUtil::PhpToJSObject($arResult["START_INFO"]);?>, {
	datetime : '<?=$arParams["DATE_TIME_FORMAT"]?>',
	date : '<?=$arParams["DATE_FORMAT"]?>',
	time : '<?=$arParams["TIME_FORMAT"]?>'
});
</script><?
$feedFrame->beginStub();
?><div class="mobile-timeman-loader"></div><?
$feedFrame->end();

	$stateTimer = 0;
	$pauseTimer = 0;
?>
<div class="mobile-timeman" data-bx-timeman-status="<?=$statusClass?>" data-bx-timeman-pause="<?=$pauseTimer?>" data-bx-timeman-start-state="normal" id="mobile-timeman">
	<div class="mobile-timeman-timing">
		<div class="mobile-timeman-regular-block" data-bx-timeman="regular-block">
			<div class="mobile-timeman-timing-title"><?=GetMessage("TM_DURATION2")?></div>
			<div class="mobile-timeman-time" data-bx-timeman="state-timer">
				<span><?=floor($stateTimer/3600)?></span>:<span><?=str_pad(floor($stateTimer/60) % 60, 2, "0", STR_PAD_LEFT)?></span>:<span><?=str_pad($stateTimer % 60, 2, "0", STR_PAD_LEFT)?></span>
			</div>
			<div class="mobile-timeman-control-timing" data-bx-timeman="pause-block">
				<div class="mobile-timeman-control-timing-title"><span><?=GetMessage("TM_PAUSE")?></span></div>
				<div class="mobile-timeman-control-timing-time" data-bx-timeman="pause-timer">
					<span><?=floor($pauseTimer/3600)?></span>:<span><?=str_pad(floor($pauseTimer/60) % 60, 2, "0", STR_PAD_LEFT)?></span>:<span><?=str_pad($pauseTimer % 60, 2, "0", STR_PAD_LEFT)?></span>
				</div>
			</div>
		</div>
		<div class="mobile-timeman-start-block" data-bx-timeman="start-block">
			<div class="mobile-timeman-control-timing">
				<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_BEGINNING")?></div>
				<div class="mobile-timeman-control-timing-time">8:00</div><?
				?><input type="hidden" data-bx-timeman="start-timestamp" value="<?=28800?>" />
			</div>
			<textarea class="mobile-timeman-completion-textarea" data-bx-timeman="start-reason" placeholder="<?=GetMessage("TM_REASON_START")?>"></textarea>
		</div>
		<div class="mobile-timeman-expired-block" data-bx-timeman="expired-block">
			<div class="mobile-timeman-completion-title"><?=GetMessage("TM_ERROR2")?></div>
		</div>
		<div class="mobile-timeman-stop-block" data-bx-timeman="stop-block">
			<div class="mobile-timeman-control-timing">
				<div class="mobile-timeman-control-timing-title"><?=GetMessage("TM_END")?></div>
				<div class="mobile-timeman-control-timing-time">18:00</div><?
				?><input type="hidden" data-bx-timeman="stop-timestamp" value="64800" />
			</div>
			<textarea class="mobile-timeman-completion-textarea" data-bx-timeman="stop-reason" placeholder="<?=GetMessage("TM_REASON_END")?>"></textarea>
		</div>
		<div class="mobile-timeman-control">
			<input type="submit" class="mobile-button mobile-button-create" data-bx-timeman="start-button" value="<?=GetMessage("TM_START")?>" />
			<input type="submit" class="mobile-button mobile-button-create" data-bx-timeman="resume-button" value="<?=GetMessage("TM_RESUME")?>" />
			<input type="submit" class="mobile-button" data-bx-timeman="pause-button" value="<?=GetMessage("TM_PAUSE")?>" />
			<input type="submit" class="mobile-button mobile-button-decline" data-bx-timeman="stop-button" value="<?=GetMessage("TM_FINISH")?>" />
		</div>
	</div>
<script type="text/javascript">
	BX.message({
		PAGE_TITLE : '<?=GetMessageJS("PAGE_TITLE2")?>',
		TM_MENU_START : '<?=GetMessageJS("TM_MENU_START")?>',
		TM_MENU_START1 : '<?=GetMessageJS("TM_MENU_START1")?>',
		TM_MENU_STOP : '<?=GetMessageJS("TM_MENU_STOP")?>',
		TM_MENU_STOP1 : '<?=GetMessageJS("TM_MENU_STOP1")?>',
		TM_MENU_EDIT : '<?=GetMessageJS("TM_MENU_EDIT")?>',
		TM_MENU_REPORT : '<?=GetMessageJS("TM_MENU_REPORT")?>',
		PULLDOWN_PULL : '<?=GetMessageJS('PULLDOWN_PULL')?>',
		PULLDOWN_DOWN : '<?=GetMessageJS('PULLDOWN_DOWN')?>',
		PULLDOWN_LOADING : '<?=GetMessageJS('PULLDOWN_LOADING')?>'
	});
</script>
</div>
<?
}