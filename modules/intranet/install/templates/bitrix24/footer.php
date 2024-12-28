<?
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
\Bitrix\Main\UI\Extension::load([
	'ui.icon-set.main',
	'ui.banner-dispatcher',
]);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
if (!isset($isBitrix24Cloud))
{
	$isBitrix24Cloud = Loader::includeModule('bitrix24');
}
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/templates/' . SITE_TEMPLATE_ID . '/footer.php');
$isCompositeMode = defined('USE_HTML_STATIC_CACHE');
$isCollaber = \Bitrix\Main\Loader::includeModule('extranet')
	&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById(\Bitrix\Intranet\CurrentUser::get()->getId());
$isIndexPage = $APPLICATION->GetCurPage(true) == SITE_DIR . 'stream/index.php';

											?></div>
										</div>
									</div>
								</td>
							</tr>
						</table><?

if ($isCompositeMode)
{
	$dynamicArea = \Bitrix\Main\Page\FrameStatic::getCurrentDynamicArea();
	if ($dynamicArea !== null)
	{
		$dynamicArea->finishDynamicArea();
	}
}

					?></td>
				</tr>
				<tr>
					<td class="bx-layout-inner-left" id="layout-left-column-bottom"></td>
					<td class="bx-layout-inner-center">
						<div id="footer">
							<span id="copyright">
								<? if ($isBitrix24Cloud):?>
									<a id="bitrix24-logo" target="_blank" class="bitrix24-logo-<?=(LANGUAGE_ID == "ua") ? LANGUAGE_ID : Loc::getDefaultLang(LANGUAGE_ID)?>" href="<?=GetMessage("BITRIX24_URL")?>"></a>
									<?
									$b24Languages = [];
									include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/languages.php");
									if (!\Bitrix\Main\Loader::includeModule('bitrix24') && false)
									{
										$cultures = Bitrix\Main\Localization\LanguageTable::getList([
											'select' => [
												'NAME',
												'CULTURE_CODE' => 'CULTURE.CODE',
											],
											'filter' => [
												'=ACTIVE' => 'Y'
											]
										]);
										$languages = [];
										while ($culture = $cultures->fetch())
										{
											if (in_array($culture['LID'], array_keys($b24Languages)))
											{
												$languages[$culture['LID']] = [
													"NAME" => $b24Languages[$culture['LID']]['NAME'] ?? $culture['NAME'],
													"IS_BETA" => false
												];
											}
										}
										$b24Languages = $languages;
										unset($languages);
									}
									?>
									<span class="bx-lang-btn <?=LANGUAGE_ID?>" id="bx-lang-btn" onclick="B24.openLanguagePopup(this)">
										<span class="bx-lang-btn-icon"><?=$b24Languages[LANGUAGE_ID]["NAME"]?></span>
									</span><?

									$numLanguages = count($b24Languages);
									$numRowItems = ceil($numLanguages/3);
									?>
									<div style="display: none" id="b24LangPopupContent">
										<?php if (!\Bitrix\Main\Loader::includeModule('bitrix24')): ?>
										<div class="bx-lang-help-btn" onclick="BX.Helper.show('redirect=detail&code=17526250');">
											<div class="ui-icon-set --help" style="--ui-icon-set__icon-size: 20px;"></div>
										</div>
										<?php endif; ?>
										<table>
											<?for ($i=1; $i<=$numRowItems; $i++): ?>
											<tr>
												<?for ($j=1; $j<=3; $j++): ?>
													<td class="bx-lang-popup-item" onclick="B24.changeLanguage('<?=key($b24Languages)?>');">
														<span>
															<?
															$lang = array_shift($b24Languages);
															echo $lang["NAME"].($lang["IS_BETA"] ? ", beta" : "");
															?>
														</span>
													</td>
													<?
													if (empty($b24Languages))
														break 2;
													?>
												<?endfor?>
											</tr>
											<?endfor?>
										</table>
									</div>
								<?
								endif;
								?><span class="bitrix24-copyright"><?=GetMessage("BITRIX24_COPYRIGHT2", array("#CURRENT_YEAR#" => date("Y")))?></span><?
								if (Bitrix\Main\Loader::includeModule('bitrix24'))
								{
									$licensePrefix = CBitrix24::getLicensePrefix();
									$licenseType = CBitrix24::getLicenseType();
								}

								if ($isBitrix24Cloud && $partnerID = COption::GetOptionString("bitrix24", "partner_id", ""))
								{
									if ($partnerID != "9409443") //sber
									{
										$arParamsPartner = [];
										$arParamsPartner["MESS"] = [
											"BX24_PARTNER_TITLE" => GetMessage("BITRIX24_PARTNER_POPUP_TITLE"),
											"BX24_BUTTON_SEND" => GetMessage("BITRIX24_PARTNER_POPUP_BUTTON"),
										];
										?><a href="javascript:void(0)" onclick="showPartnerForm(<?= CUtil::PhpToJSObject($arParamsPartner) ?>); return false;" class="footer-link"><?=GetMessage("BITRIX24_PARTNER_CONNECT")?></a><?php
									}
								}
								elseif (!$isCollaber && Bitrix\Main\Loader::includeModule('bitrix24'))
								{
									$orderParams = \CBitrix24::getPartnerOrderFormParams();
									?><a class="b24-web-form-popup-btn-57 footer-link" onclick="B24.showPartnerOrderForm(<?=CUtil::PhpToJSObject($orderParams)?>);"><?=GetMessage("BITRIX24_PARTNER_ORDER")?></a><?
								}
								else
								{
									?><a href="javascript:void(0)" onclick="BX.Helper.show();"
									   class="footer-link"><?=GetMessage("BITRIX24_MENU_CLOUDMAN")?></a><?
								}

								?><span
									class="footer-link"
									onclick="BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog()"
								><?=GetMessage("BITRIX24_THEME")?></span><?

								?><span
									class="footer-link"
									onclick="window.scroll(0, 0); setTimeout(function() {window.print()}, 0)"
								><?=GetMessage("BITRIX24_PRINT")?></span><?

							?></span>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table><?

$APPLICATION->ShowBodyScripts();

//$APPLICATION->IncludeComponent("bitrix:pull.request", "", Array(), false, Array("HIDE_ICONS" => "Y"));
$APPLICATION->IncludeComponent("bitrix:intranet.mail.check", "", array(), false, array("HIDE_ICONS" => "Y"));

$dynamicArea = new \Bitrix\Main\Page\FrameStatic("otp-info");
$dynamicArea->setAssetMode(\Bitrix\Main\Page\AssetMode::STANDARD);
$dynamicArea->startDynamicArea();
$APPLICATION->IncludeComponent("bitrix:intranet.otp.info", "", array("PATH_TO_PROFILE_SECURITY" => $profileLink."/user/#user_id#/security/",), false, array("HIDE_ICONS" => "Y"));
$dynamicArea->finishDynamicArea();

if ($isBitrix24Cloud)
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.notify.panel', '');
}
else
{
	$APPLICATION->IncludeComponent('bitrix:intranet.notify.panel', '');
}

$APPLICATION->IncludeComponent("bitrix:intranet.placement", "", array());
$APPLICATION->IncludeComponent('bitrix:bizproc.debugger', '', []);
$APPLICATION->IncludeComponent('bitrix:intranet.bitrix24.release', '', []); // remove after December 15 2023

$imBarExists =
	Loader::includeModule('im')
	&& CBXFeatures::IsFeatureEnabled('WebMessenger')
	&& !defined('BX_IM_FULLSCREEN')
;

$APPLICATION->IncludeComponent(
	'bitrix:main.sidepanel.toolbar',
	'',
	[
		'CONTEXT' => SITE_ID . '_' . SITE_TEMPLATE_ID,
		'POSITION' => $imBarExists ? ['right' => '90px', 'bottom' => '20px'] : ['right' => '25px', 'bottom' => '20px'],
		'SHIFTED_POSITION' => $imBarExists ? ['right' => '7px', 'bottom' => '20px'] : ['right' => '25px', 'bottom' => '20px'],
	]
);
?><script>
	BX.message({
		"BITRIX24_CS_ONLINE" : "<?=GetMessageJS("BITRIX24_CS_ONLINE")?>",
		"BITRIX24_CS_OFFLINE" : "<?=GetMessageJS("BITRIX24_CS_OFFLINE")?>",
		"BITRIX24_CS_CONNECTING" : "<?=GetMessageJS("BITRIX24_CS_CONNECTING")?>",
		"BITRIX24_CS_RELOAD" : "<?=GetMessageJS("BITRIX24_CS_RELOAD")?>",
		"BITRIX24_SEARCHTITLE_ALL" : "<?=GetMessageJS("BITRIX24_SEARCHTITLE_ALL")?>"
	});
	<?php
		if ($isBitrix24Cloud
			&& defined('FORBID_ADVERTISE')
			&& FORBID_ADVERTISE === true
		):
	?>
		BX.UI.BannerDispatcher.only([
			BX.UI.AutoLaunch.LaunchPriority.HIGH,
			BX.UI.AutoLaunch.LaunchPriority.CRITICAL,
		]);
	<?php
		endif;
	?>

	if (document.referrer.length > 0 && document.referrer.startsWith(location.origin) === false)
	{
		BX.Runtime.loadExtension('intranet.recognize-links');
	}
	BX.onCustomEvent(window, "onScriptsLoaded");
</script>
</body></html>
