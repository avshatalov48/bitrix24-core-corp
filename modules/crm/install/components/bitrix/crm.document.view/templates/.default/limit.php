<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 */

\CJSCore::Init("sidepanel");

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_DOCUMENT_LIMIT_TITLE'));
if (\Bitrix\Main\Loader::includeModule('ui'))
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
}

?>
<script>
	BX.SidePanel.Instance.getTopSlider().setWidth(735);
	BX.SidePanel.Instance.getTopSlider().adjustLayout();
</script>
<div class="document-limit-container">
	<div class="document-limit-inner">
		<div class="document-limit-desc">
			<div class="document-limit-img">
				<div class="document-limit-img-lock"></div>
			</div>
			<div class="document-limit-desc-text">
				<?= \Bitrix\Main\Localization\Loc::getMessage(
					'CRM_DOCUMENT_LIMIT_TEXT_EXTENDED',
					['#MAX#' => \Bitrix\DocumentGenerator\Integration\Bitrix24Manager::getDocumentsLimit()],
				);?>
			</div>
		</div>
		<div class="document-limit-buttons">
			<?php \Bitrix\DocumentGenerator\Integration\Bitrix24Manager::showTariffRestrictionButtons(); ?>
		</div>
	</div>
</div>
