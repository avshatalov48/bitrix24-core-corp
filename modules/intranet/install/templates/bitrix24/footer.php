<?

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".SITE_TEMPLATE_ID."/footer.php");
$isCompositeMode = defined("USE_HTML_STATIC_CACHE");
$isIndexPage = $APPLICATION->GetCurPage(true) == SITE_DIR."stream/index.php";
?>
											</div>
										</div>
									</div>
								</td>
							</tr>
						</table>
<?
if ($isCompositeMode && !$isIndexPage)
{
	$dynamicArea = \Bitrix\Main\Page\FrameStatic::getCurrentDynamicArea();
	if ($dynamicArea !== null)
	{
		$dynamicArea->finishDynamicArea();
	}
}
?>
					</td>
				</tr>
				<tr>
					<td class="bx-layout-inner-left" id="layout-left-column-bottom"></td>
					<td class="bx-layout-inner-center">
						<div id="footer">
							<span id="copyright">
								<?if ($isBitrix24Cloud):?>
									<a id="bitrix24-logo" target="_blank" class="bitrix24-logo-<?=(LANGUAGE_ID == "ua") ? LANGUAGE_ID : Loc::getDefaultLang(LANGUAGE_ID)?>" href="<?=GetMessage("BITRIX24_URL")?>"></a>
									<?
									$b24Languages = [];
									include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/languages.php");
									if (!\Bitrix\Main\Application::getInstance()->isUtfMode())
									{
										array_walk($b24Languages, function(&$lang) {
											$lang["NAME"] = mb_convert_encoding($lang["NAME"], "HTML-ENTITIES", "UTF-8");
										});
									}
									?>
									<span class="bx-lang-btn <?=LANGUAGE_ID?>" id="bx-lang-btn" onclick="B24.openLanguagePopup(this)">
										<span class="bx-lang-btn-icon"><?=$b24Languages[LANGUAGE_ID]["NAME"]?></span>
									</span>
									<?
									$numLanguages = count($b24Languages);
									$numRowItems = ceil($numLanguages/3);
									?>
									<div style="display: none" id="b24LangPopupContent">
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
								<?endif?>
								<span class="bitrix24-copyright"><?=GetMessage("BITRIX24_COPYRIGHT2", array("#CURRENT_YEAR#" => date("Y")))?></span>
								<?
								if (CModule::IncludeModule("bitrix24"))
								{
									$licensePrefix = CBitrix24::getLicensePrefix();
									$licenseType = CBitrix24::getLicenseType();
								}
								?>
								<?
								if ($isBitrix24Cloud && $partnerID = COption::GetOptionString("bitrix24", "partner_id", ""))
								{
									if ($partnerID != "9409443") //sber
									{
										$arParamsPartner = array();
										$arParamsPartner["MESS"] = array(
											"BX24_PARTNER_TITLE" => GetMessage("BX24_SITE_PARTNER"),
											"BX24_CLOSE_BUTTON"  => GetMessage("BX24_CLOSE_BUTTON"),
											"BX24_LOADING"       => GetMessage("BX24_LOADING"),
										);
										?>
										<a href="javascript:void(0)" onclick="showPartnerForm(<?echo CUtil::PhpToJSObject($arParamsPartner)?>); return false;" class="footer-discuss-link"><?=GetMessage("BITRIX24_PARTNER_CONNECT")?></a>
										<?
									}
								}
								elseif (CModule::IncludeModule("bitrix24"))
								{
									$orderParams = \CBitrix24::getPartnerOrderFormParams();
								?>
									<a class="b24-web-form-popup-btn-57 footer-discuss-link" onclick="B24.showPartnerOrderForm(<?=CUtil::PhpToJSObject($orderParams)?>);"><?=GetMessage("BITRIX24_PARTNER_ORDER")?></a>
								<?
								}
								else
								{
								?>
									<a href="javascript:void(0)" onclick="BX.Helper.show();"
									   class="footer-discuss-link"><?=GetMessage("BITRIX24_MENU_CLOUDMAN")?></a>
								<?
								}
								?>

								<span
									class="footer-link"
									onclick="BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog()"
								><?=GetMessage("BITRIX24_THEME")?></span>

								<span
									class="footer-link"
									onclick="window.scroll(0, 0); setTimeout(function() {window.print()}, 0)"
								><?=GetMessage("BITRIX24_PRINT")?></span>

							</span>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?
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
	$APPLICATION->IncludeComponent("bitrix:bitrix24.notify.panel", "", array());
	//$APPLICATION->IncludeComponent("bitrix:bitrix24.broadcast", "", array());
}
?>

<?
if (
	preg_match("/(MSIE|Trident)/i", $_SERVER["HTTP_USER_AGENT"]) &&
	CUserOptions::getOption("intranet", "ie11_warning_2", "N") === "N"
):
?>
<script type="text/javascript">
	BX.ready(function() {
			setTimeout(function() {
				BX.UI.Notification.Center.notify({
					width: 750,
					content: "<?=GetMessage("BITRIX24_IE_SUPPORT")?>",
					position: "top-center",
					autoHide: false,
					actions: [{
						title: "<?=GetMessage("BX24_CLOSE_BUTTON")?>",
						events: {
							click: function(event, balloon) {
								balloon.close();
							}
						}
					}],
					events: {
						onClose: function() {
							BX.userOptions.save("intranet", "ie11_warning_2", null, "Y");
							BX.userOptions.send(null);
						}
					}
				});
			}, 5000);
	});
</script>
<? endif ?>

<script>
	BX.message({
		"BITRIX24_CS_ONLINE" : "<?=GetMessageJS("BITRIX24_CS_ONLINE")?>",
		"BITRIX24_CS_OFFLINE" : "<?=GetMessageJS("BITRIX24_CS_OFFLINE")?>",
		"BITRIX24_CS_CONNECTING" : "<?=GetMessageJS("BITRIX24_CS_CONNECTING")?>",
		"BITRIX24_CS_RELOAD" : "<?=GetMessageJS("BITRIX24_CS_RELOAD")?>",
		"BITRIX24_SEARCHTITLE_ALL" : "<?=GetMessageJS("BITRIX24_SEARCHTITLE_ALL")?>"
	});
</script>
<script type="text/javascript">BX.onCustomEvent(window, "onScriptsLoaded");</script>
</body>
</html>
