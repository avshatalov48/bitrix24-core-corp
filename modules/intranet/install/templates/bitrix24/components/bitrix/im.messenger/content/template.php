<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?
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
							<a href="http://dl.bitrix24.com/b24/bitrix24_desktop.exe" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-windows" target="_blank"></a>
							<span class="bx-im-fullscreen-apps-buttons-delimiter"></span>
							<a href="http://dl.bitrix24.com/b24/bitrix24_desktop.dmg" class="bx-im-fullscreen-app-icon bx-im-fullscreen-app-osx" target="_blank"></a>
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
<?$frame = $this->createFrame("im")->begin("");
	$arResult['EXTERNAL_RECENT_LIST'] = "bx-im-external-recent-list";
?>
<script>
	<?=CIMMessenger::GetTemplateJS(Array(), $arResult)?>
	bxFullscreenInit();
</script>
<?$frame->end()?>