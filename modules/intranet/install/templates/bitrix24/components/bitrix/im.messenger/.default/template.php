<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (defined('BX_IM_FULLSCREEN'))
{
	return;
}
use Bitrix\Main\Localization\Loc;

$v2MessengerEnabled = isset($arResult['MESSENGER_V2']) && $arResult['MESSENGER_V2'] === true;
$copilotAvailable = $v2MessengerEnabled && isset($arResult['COPILOT_AVAILABLE']) && $arResult['COPILOT_AVAILABLE'] === true;
$copilotAvailableTab = $v2MessengerEnabled && isset($arResult['COPILOT_AVAILABLE_TAB']) && $arResult['COPILOT_AVAILABLE_TAB'] === true;
$desktopDownloadLinks = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks();
$isCollaber = \Bitrix\Main\Loader::includeModule('extranet')
	&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById(\Bitrix\Intranet\CurrentUser::get()->getId());

if ($v2MessengerEnabled)
{
	\Bitrix\Main\UI\Extension::load("im.v2.application.quick-access");
}
\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'im.public']);
$this->SetViewTarget("im-fullscreen");
?>
<div class="bx-desktop bx-im-fullscreen-popup" id="im-workarea-popup">
	<table class="bx-im-fullscreen-popup-table">
		<tr>
			<td class="bx-im-fullscreen-popup-td bx-im-fullscreen-popup-td1">
				<div class="bx-im-fullscreen-popup-logo">
					<?if (IsModuleInstalled("bitrix24")):?>
						<span><?
							$clientLogo = COption::GetOptionInt("bitrix24", "client_logo", "");?>
							<?if ($clientLogo):?>
								<img src="<?if ($clientLogo) echo CFile::GetPath($clientLogo)?>"/>
							<?else:?>
								<?=htmlspecialcharsbx(COption::GetOptionString("bitrix24", "site_title", ""))?> <?if(COption::GetOptionString("bitrix24", "logo24show", "Y") !=="N"):?>24<?endif?>
							<?endif?>
						</span>
					<?else:
						$logoID = COption::GetOptionString("main", "wizard_site_logo", "", SITE_ID);
						?><span>
							<?if ($logoID):
								$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/company_name.php"), false);?>
							<?else:?>
								<?=htmlspecialcharsbx(COption::GetOptionString("main", "site_name", ""));?> 24
							<?endif?>
						</span>
					<?endif?>
				</div>
				<div class="bx-im-fullscreen-popup-back"><a href="/" onclick="bxFullscreenClose(); return false;" class="bx-im-fullscreen-popup-back-link"><?=GetMessage('IM_FULLSCREEN_BACK')?></a></div>
			</td>
		</tr>
		<tr>
			<td class="bx-im-fullscreen-popup-td bx-im-fullscreen-popup-td2" ><div class="bx-desktop-placeholder" id="im-workarea-content"></div></td>
		</tr>
		<tr>
			<td class="bx-im-fullscreen-popup-td bx-im-fullscreen-popup-td3">
				<span class="bx-im-fullscreen-apps">
					<span class="bx-im-fullscreen-apps-title"><?=GetMessage('IM_FULLSCREEN_APPS')?>:</span>
					<span class="bx-im-fullscreen-apps-buttons" id="im-workarea-apps">
						<span class="bx-im-fullscreen-apps-buttons-group">
							<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['windows']) ?>" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-windows" target="_blank"></a>
							<span class="bx-im-fullscreen-apps-buttons-delimiter"></span>
							<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['macos']) ?>" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-osx" target="_blank"></a>
							<span class="bx-im-fullscreen-apps-buttons-delimiter"></span>
							<a href="https://github.com/buglloc/brick/" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-linux" target="_blank"></a>
						</span>
						<span class="bx-im-fullscreen-apps-buttons-group">
							<a href="https://play.google.com/store/apps/details?id=com.bitrix24.android" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-googleplay" target="_blank"></a>
							<span class="bx-im-fullscreen-apps-buttons-delimiter"></span>
							<a href="<?=GetMessage('IM_FULLSCREEN_DOWN_ITS');?>" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-appstore" target="_blank"></a>
						</span>
					</span>
				</span>
				<span class="bx-im-fullscreen-bg">
					<span class="bx-im-fulsscrenn-bg-title"><?=GetMessage('IM_FULLSCREEN_BG_TITLE')?>:</span>
					<span class="bx-im-fulsscrenn-bg-wrap">
						<span id="im-workarea-backgound-selector-title" class="bx-im-fulsscrenn-bg-wrap-title">--------</span>
						<select id="im-workarea-backgound-selector" class="bx-im-fullscreen-bg-selector">
							<option value="transparent"><?=GetMessage('IM_FULLSCREEN_BG_TRANSPARENT')?></option>
							<option value="0"><?=GetMessage('IM_FULLSCREEN_BG_0')?></option>
							<option value="1"><?=GetMessage('IM_FULLSCREEN_BG_1')?></option>
							<option value="2"><?=GetMessage('IM_FULLSCREEN_BG_2')?></option>
							<option value="3"><?=GetMessage('IM_FULLSCREEN_BG_3')?></option>
							<option value="4"><?=GetMessage('IM_FULLSCREEN_BG_4')?></option>
							<option value="5"><?=GetMessage('IM_FULLSCREEN_BG_5')?></option>
							<option value="6"><?=GetMessage('IM_FULLSCREEN_BG_6')?></option>
							<option value="7"><?=GetMessage('IM_FULLSCREEN_BG_7')?></option>
							<option value="8"><?=GetMessage('IM_FULLSCREEN_BG_8')?></option>
							<option value="9"><?=GetMessage('IM_FULLSCREEN_BG_9')?></option>
							<option value="10"><?=GetMessage('IM_FULLSCREEN_BG_10')?></option>
							<option value="11"><?=GetMessage('IM_FULLSCREEN_BG_11')?></option>
							<option value="12"><?=GetMessage('IM_FULLSCREEN_BG_12')?></option>
						</select>
					</span>
				</span>
			</td>
		</tr>
	</table>
</div>
<?$this->EndViewTarget()?>
<?
$this->SetViewTarget("im", 100);

$copilotClass = $copilotAvailableTab ? 'bx-im-bar-with-copilot' : '';
$openLinesAvailableTab = !$isCollaber;
$openLinesClass = $openLinesAvailableTab ? 'bx-im-bar-with-ol' : '';
?>
<div class="bx-im-bar <?=$openLinesClass?> <?=$copilotClass?>" id="bx-im-bar">
	<div class="help-block bx-im-border-b" id="bx-help-block" title="<?=GetMessage("AUTH_HELP")?>">
		<div class="help-icon-border"></div>
		<div class="help-block-icon"></div>
		<div class="help-block-counter-wrap" id="bx-help-notify">
		</div>
	</div>
	<?
	if ($arResult["SHOW_HELP_SPOTLIGHT"])
	{
		$APPLICATION->includeComponent("bitrix:spotlight", "", array(
			"ID" => "help-spotlight2",
			"USER_TYPE" => "ALL",
			"JS_OPTIONS" => array(
				"targetElement" => "#bx-help-block",
				"content"       => Loc::getMessage("IM_HELP_SPOTLIGHT"),
				"targetVertex"  => "middle-center",
				"left" => -10,
				"lightMode"     => true
			)
		));
	}
	?>
	<div class="bx-im-helper-block bx-im-border-b">
		<?php if ($copilotAvailableTab): ?>
			<div id="bx-im-bar-copilot" class="bx-im-informer bx-im-informer-copilot">
				<div class="bx-im-informer-copilot-icon" title="<?=GetMessage('IM_MESSENGER_OPEN_COPILOT');?>"></div>
			</div>
		<?php endif; ?>
		<div id="bx-im-bar-notify" class="bx-im-informer">
			<div class="bx-im-informer-icon" title="<?=GetMessage('IM_MESSENGER_OPEN_NOTIFY');?>">
				<div class="bx-im-informer-num"></div>
			</div>
		</div>
		<?php if ($openLinesAvailableTab): ?>
		<div id="bx-im-bar-ol" class="bx-im-informer bx-im-informer-ol">
			<div class="bx-im-informer-ol-icon" title="<?=GetMessage('IM_MESSENGER_OPEN_OL');?>">
				<div class="bx-im-informer-num"></div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<div id="bx-im-bar-search" class="bx-im-search bx-im-border-b" title="<?=GetMessage('IM_MESSENGER_OPEN_SEARCH');?>">
		<div class="bx-im-informer-num"></div>
	</div>
	<div class="bx-im-users-wrap">
		<div class="bx-im-scroll-wrap" id="bx-im-external-recent-list"></div>
	</div>

	<div class="bx-im-bottom-block" id="bx-im-bottom-block"></div>
	<svg width="0" height="0" style="display: block">
		<defs>
			<clipPath id="clip-avatar">
				<path d="M31.342 20.557a7.5 7.5 0 0 0-9.524 10.352A15.96 15.96 0 0 1 16 32C7.163 32 0 24.837 0 16S7.163 0 16 0s16 7.163 16 16c0 1.583-.23 3.113-.658 4.557z" fill="#D8D8D8" fill-rule="evenodd"/>
			</clipPath>
		</defs>
	</svg>
</div>

<script>
	BX.Intranet.Bitrix24.ImBar.redraw();
</script>
<?$this->EndViewTarget()?>

<?$frame = $this->createFrame("im")->begin("");
	$arResult['EXTERNAL_RECENT_LIST'] = "bx-im-external-recent-list";
?>
<script>
	BX.ready(function() {
		BX.Intranet.Bitrix24.ImBar.init({
			copilotAvailable: <?= \Bitrix\Main\Web\Json::encode($copilotAvailable) ?>,
		});
	});
	<?php
		if ($v2MessengerEnabled)
		{
			echo CIMMessenger::GetV2TemplateJS($arResult);
		}
		else
		{
			echo CIMMessenger::GetTemplateJS([], $arResult);
		}
	?>
</script>
<?$frame->end()?>
