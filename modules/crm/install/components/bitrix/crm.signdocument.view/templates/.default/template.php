<?php

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
	"ui.alerts",
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.progressround",
	"ui.viewer",
	"ui.notification",
	"loader",
	"popup",
	"sidepanel",
	"documentpreview",
	"ui.icons.disk",
]);

$renderRequisiteSection = function(?string $entityName, array $data): void {
	?>
	<div class="crm__document-view--sidebar-requisite-field">
		<?=htmlspecialcharsbx($entityName);?>
	</div>
	<div class="crm__document-view--sidebar-requisite-title">
		<?php if (!empty($data['link'])):?>
			<a href="<?=htmlspecialcharsbx($data['link']);?>"><?=htmlspecialcharsbx($data['title'] ?? '');?></a>
		<?php else:?>
			<?=htmlspecialcharsbx($data['title'] ?? '');?>
		<?php endif;?>
	</div>
	<?if (!empty($data['subTitle'])):?>
		<div class="crm__document-view--sidebar-requisite-subtitle">
			<?=htmlspecialcharsbx($data['subTitle']);?>
		</div>
	<?php endif;?>
<?php
};
?>
<!DOCTYPE html>
<html lang="<?=LANGUAGE_ID;?>">
<head>
	<script data-skip-moving="true">
		// Prevent loading page without header and footer
		if (window === window.top)
		{
			window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
		}
	</script>
	<?php $APPLICATION->ShowHead(); ?>
</head>
<body class="crm__document-view--slider-wrap">
<div class="crm__document-view--title">
	<div class="crm__document-view--container-docs-preview">
		<div class="pagetitle">
			<span id="pagetitle" class="pagetitle-item crm__document-view--pagetitle-item"><?=htmlspecialcharsbx($arResult['title']);?></span>
		</div>
		<div class="crm__document-view--buttons">
			<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-print" data-btn-uniqid="crm-document-print"></button>

			<div
				class="ui-btn-split ui-btn-light-border crm__document-view--btn-icon-pdf"
				data-btn-uniqid="crm-document-download"
			>
				<button class="ui-btn-main">
					<span class="ui-btn-text"><?=Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD');?></span>
				</button>
				<button class="ui-btn-menu"></button>
			</div>
		</div>
	</div>
</div>
<div class="crm__document-view--wrap">
	<div class="crm__document-view--inner crm__document-view--inner-slider">
		<div class="crm__document-view--img">
			<div class="crm__document-view--pdf" id="crm-document-pdf" data-role="pdf-viewer" data-viewer-type="document"></div>
		</div>
	</div>
	<div class="crm__document-view--sidebar-wrapper">
		<div class="crm__document-view--sidebar crm__document-view--sidebar-channels">
			<div class="crm__document-view--channels">
				<?php $APPLICATION->includeComponent(
					'bitrix:crm.channel.selector',
					'',
					$arResult['channelSelectorParameters'],
					$this->getComponent()
				);?>
			</div>
		</div>

		<div class="crm__document-view--sidebar --company-information">
			<div class="crm__document-view--sidebar-section">
				<?php
				$renderRequisiteSection(
					Loc::getMessage('CRM_DOCUMENT_VIEW_REQUISITES_MY_COMPANY_TITLE'),
					$arResult['myCompanyRequisites'],
				);
				?>
			</div>
			<div class="crm__document-view--sidebar-section">
				<?php
				$renderRequisiteSection(
					Loc::getMessage('CRM_COMMON_CLIENT'),
					$arResult['clientRequisites'],
				);
				?></div>
			<?php
			if ($arResult['isSigningEnabled']) {?>
				<div class="crm__document-view--sidebar-section">
					<div class="crm__document-view--sidebar-control" id="crm-document-sign" >
						<label class="crm__document-view--label --label-icon --icon-sign crm__document-view--sidebar-control-sign">
							<?=Loc::getMessage('CRM_DOCUMENT_VIEW_SIGN_BUTTON');?>
						</label>
						<span class="crm__document-view--arrow"> </span>
					</div>
				</div>
			<?}?>
		</div>
	</div>
</div>
<script>
	BX.ready(function(){
		const params = <?=\CUtil::PhpToJSObject($arResult, false, false, true);?>;
		params.pdfNode = document.querySelector('[data-role="pdf-viewer"]');

		new BX.Crm.Component.SignDocumentView(params);
	});
</script>
</body>
</html>
<?php
\CMain::FinalActions();