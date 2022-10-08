<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */
/** @var array $arResult */

Extension::load([
	'ui.alerts',
	'seo.ads.login',
]);

$this->addExternalCss('/bitrix/components/bitrix/imconnector.facebook/templates/.default/facebook.catalog.connector/index.css');
$this->addExternalJs('/bitrix/components/bitrix/imconnector.facebook/templates/.default/facebook.catalog.connector/index.js');

//todo: change the article code and the phrase
$helpDeskLinkStart = "<a href=\"javascript:void(0)\" class=\"imconnector-field-box-link\"";
$helpDeskLinkStart .= "onclick='top.BX.Helper.show(\"redirect=detail&code=10443976\");event.preventDefault();'>";
$helpDeskLinkEnd = '</a>';

$helpDeskLinkCatalog = str_replace(
	['#A_START#', '#A_END#'],
	[$helpDeskLinkStart, $helpDeskLinkEnd],
	Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_PERMISSION_TEXT', [
		'#BUTTON#' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CONNECT'),
	])
);
$openLineReloginUrl = htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['URI_RELOGIN']));
$isConnectorReady = $arResult['ACTIVE_STATUS'] && $arResult['STATUS'] && $arResult['FORM']['PAGE'];
$hasOLCatalogAccess = isset($arResult['DATA_STATUS']['CATALOG']) && $arResult['DATA_STATUS']['CATALOG'] === 'Y';

$hasAccessToSeoCatalog = $isConnectorReady && $hasOLCatalogAccess;
$hasSeoCatalogConnection = !empty($arResult['CATALOG_AUTH']['HAS_AUTH']);
$hasDifferentCatalogsConnected = false;//$arResult['CATALOG_AUTH']['PAGE_ID'] !== $arResult['FORM']['PAGE']['ID'];
$seoCatalogLoginUrl = htmlspecialcharsbx(CUtil::JSEscape($arResult['CATALOG_AUTH']['AUTH_URL'] ?? ''));
?>
<div class="imconnector-field-section imconnector-facebook-field-section">
	<div class="imconnector-facebook-field-main-title">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CATALOG_TAB_TITLE')?>
	</div>
	<div class="imconnector-field-content-box">
		<div class="imconnector-field-box-entity-text-bold">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_PERMISSION_TITLE')?>
		</div>
		<?php if (!$isConnectorReady): ?>
			<div class="ui-alert ui-alert-warning">
			<span class="ui-alert-message">
				<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CATALOG_TAB_SETUP_FIRST_WARNING') ?>
			</span>
			</div>
		<?php endif ?>
		<?php if (isset($arResult['WRONG_USER']) && $arResult['WRONG_USER'] === 'y'): ?>
			<div class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_USER_RELOGIN_WRONG_USER') ?>
				</span>
			</div>
		<?php endif ?>
		<div class="imconnector-field-user-box box-rights">
			<?=$helpDeskLinkCatalog?>
		</div>
		<?php if ($hasOLCatalogAccess && $isConnectorReady): ?>
			<div class="imconnector-field-user-box box-rights">
				<div class="alert-success">
					<span class="ui-alert-message"><?=
						Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_USER_RELOGIN_SUCCESS')
						?></span>
				</div>
			</div>
		<?php else: ?>
			<div class="imconnector-field-user-box box-rights">
				<button
					<?=(!$isConnectorReady ? 'disabled' : '')?>
					class="ui-btn ui-btn-primary ui-btn-sm"
					onclick="BX.util.popup('<?=$openLineReloginUrl?>', 700, 700)"
				>
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_USER_RELOGIN')?>
				</button>
			</div>
		<?php endif ?>
	</div>
	<div class="imconnector-field-content-box">
		<div class="imconnector-field-box-entity-text-bold"><?=
			Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_TITLE')
		?></div>
		<?php if ($hasSeoCatalogConnection): ?>
			<div class="imconnector-field-user-box box-rights"><?=
				Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DESCRIPTION')
			?></div>
			<?php if ($hasDifferentCatalogsConnected): ?>
				<div class="ui-alert ui-alert-danger" style="margin-top: 13px;">
					<span class="ui-alert-message"><?=
						Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DIFFERENT_IDS')
					?></span>
				</div>
			<?php endif; ?>
			<div class="imconnector-field-user-box box-rights">
				<div class="alert-success">
					<span class="ui-alert-message"><?=
						Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_SUCCESS')
						?></span>
				</div>
			</div>
			<div class="imconnector-field-user-box box-rights">
				<button class="ui-btn ui-btn-light-border ui-btn-sm" id="catalog-logout-button"><?=
					Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DISCONNECT')
				?></button>
			</div>
		<?php else: ?>
			<div class="imconnector-field-user-box box-rights"><?php
				echo Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CONNECT_HELP', [
					'#BUTTON#' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CONNECT'),
				]);
				echo ' ' . Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DESCRIPTION');
			?></div>
			<?php if (!$hasAccessToSeoCatalog): ?>
				<div class="ui-alert ui-alert-warning">
						<span class="ui-alert-message"><?=
							Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_ALERT')
						?></span>
				</div>
			<?php endif; ?>
			<div class="imconnector-field-user-box box-rights">
				<button
						class="ui-btn ui-btn-primary ui-btn-sm"
						id="catalog-login-button"
						onclick="BX.Seo.Ads.LoginFactory.getLoginObject({
							'TYPE': 'facebook',
							'ENGINE_CODE': 'catalog.facebook'
						}).login();"
				><?=
					Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CONNECT')
				?></button>
			</div>
		<?php endif; ?>
	</div>
</div>
<script>
	BX.ready(function() {
		new BX.ImConnector.FacebookCatalogConnector();
	});
</script>
