<?
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Intranet\Integration\Templates\Air\AirTemplate;
use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Composite\StaticArea;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Page\AssetMode;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load([
	'aiassistant.marta',
	'pull.client'
]);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

// Performance optimization for sliders
if (isset($_GET['IFRAME']) && $_GET['IFRAME'] === 'Y' && !isset($_GET['SONET']))
{
	return;
}

$isBitrix24Cloud = ModuleManager::isModuleInstalled('bitrix24');
$isCompositeMode = defined('USE_HTML_STATIC_CACHE');

$isCollaber = (
	Loader::includeModule('extranet')
	&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById(CurrentUser::get()->getId())
);

// If a page doesn't have a top menu then show one item with a page title or a property 'title'
AirTemplate::tryApplyDefaultTopMenu();

					?></main>
					</div>
				</div><?
				$dynamicArea = StaticArea::getCurrentDynamicArea();
				if ($dynamicArea !== null)
				{
					$dynamicArea->finishDynamicArea();
				}
			?>
			</div>
			<footer class="app__footer" id="air-footer">
			<div class="air-footer">
				<? if ($isBitrix24Cloud): ?>
					<div class="air-footer__buttons">
						<div class="air-footer__button">
							<a
								target="_blank"
								href="<?=Loc::getMessage('BITRIX24_AIR_URL')?>"
								class="air-footer-button --logo-clock"
							>
								<span class="air-footer-button-logo">
									<span class="
									air-footer-button-logo__text"><?=Loc::getMessage('MENU_HEADER_LOGO_TEXT')?></span>
									<span class="air-footer-button-logo__number">24</span>
								</span>
							</a>
						</div>
						<?php
							$b24Languages = [];
							include($_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/languages.php');
						?>
						<div class="air-footer__button">
							<button
								onclick='BX.Intranet.Bitrix24.languageSwitcher.showLanguageListPopup(this, <?= Json::encode($b24Languages) ?>)'
								class="air-footer-button --dropdown"
							><?=$b24Languages[LANGUAGE_ID]['NAME']?></button>
						</div>
					</div>
				<? endif ?>
				<div class="air-footer__copyright">
					<?=Loc::getMessage('BITRIX24_AIR_COPYRIGHT', array('#CURRENT_YEAR#' => date('Y')))?>
				</div>
				<div class="air-footer__links">
					<?
						$partnerID = \COption::getOptionString('bitrix24', 'partner_id', '');
						if ($isBitrix24Cloud && $partnerID && Loader::includeModule('bitrix24'))
						{
							$formParams = \CBitrix24::getPartnerFormParams();
							$formParams['messages']['BX24_PARTNER_TITLE'] = Loc::getMessage('BITRIX24_AIR_PARTNER_POPUP_TITLE');
							$formParams['messages']['BX24_BUTTON_SEND'] = Loc::getMessage('BITRIX24_AIR_PARTNER_POPUP_BUTTON');

							?><button
								onclick='BX.Intranet.Bitrix24.PartnerForm.showConnectForm(<?= Json::encode($formParams) ?>);'
								class="air-footer__link"
								><?=Loc::getMessage('BITRIX24_AIR_PARTNER_CONNECT')?>
							</button><?php
						}
						elseif (!$isCollaber && $isBitrix24Cloud && Loader::includeModule('bitrix24'))
						{
							$orderParams = \CBitrix24::getPartnerOrderFormParams();
							?><button
								class="air-footer__link"
								onclick='BX.Intranet.Bitrix24.PartnerForm.showIntegrationOrderForm(<?= Json::encode($orderParams) ?>);'
							><?=Loc::getMessage("BITRIX24_AIR_PARTNER_ORDER")?>
							</button><?
						}
						else
						{
							?><button
								onclick="BX.Intranet.Bitrix24.PartnerForm.showHelper();"
								class="air-footer__link"
							><?=Loc::getMessage('BITRIX24_AIR_MENU_CLOUDMAN')?>
							</button><?
						}
					?>
					<button
						class="air-footer__link"
						onclick="BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog()"
					><?=Loc::getMessage('BITRIX24_AIR_THEME')?></button>
					<button
						class="air-footer__link"
						onclick="window.scroll(0, 0); setTimeout(function() {window.print()}, 0)"
					><?=Loc::getMessage('BITRIX24_AIR_PRINT')?>
					</button>
				</div>
			</div>
		</footer>
		</div>
	</div>
</div>

<?
$APPLICATION->showBodyScripts();

$imEnabled = ModuleManager::isModuleInstalled('im') && CBXFeatures::isFeatureEnabled('WebMessenger');
$imEmbedded = defined('BX_IM_FULLSCREEN') && BX_IM_FULLSCREEN;
if (!$imEnabled || !$imEmbedded)
{
	$APPLICATION->includeComponent(
		'bitrix:main.sidepanel.toolbar',
		'',
		[
			'CONTEXT' => SITE_ID . '_' . SITE_TEMPLATE_ID,
			'POSITION' => ['right' => '70px', 'bottom' => '15px'],
			'SHIFTED_POSITION' => ['right' => '10px', 'bottom' => '15px'],
		]
	);
}

$APPLICATION->includeComponent('bitrix:intranet.connection.status', '', ['rootContainer' => '#connection-status']);

$dynamicArea = new StaticArea('footer-components');
$dynamicArea->setAssetMode(AssetMode::STANDARD);
$dynamicArea->startDynamicArea();

$APPLICATION->includeComponent('bitrix:intranet.invitation.notification', '', []);
$APPLICATION->includeComponent('bitrix:intranet.mobile.popup', '', []);
$APPLICATION->includeComponent('bitrix:intranet.placement', '', []);
$APPLICATION->includeComponent('bitrix:bizproc.debugger', '', []);
$APPLICATION->includeComponent('bitrix:timeman.report.status', '', []);
$APPLICATION->includeComponent($isBitrix24Cloud ? 'bitrix:bitrix24.notify.panel' : 'bitrix:intranet.notify.panel', '');
$APPLICATION->includeComponent('bitrix:intranet.mail.check', '', []);
$APPLICATION->includeComponent('bitrix:intranet.bitrix24.release', '', []);
$APPLICATION->includeComponent('bitrix:intranet.otp.info', '', []);

$dynamicArea->finishDynamicArea();
?>
</body>
</html><?
