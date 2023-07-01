<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var \CAllMain $APPLICATION */

use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
	"ui.alerts",
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.progressround",
	"ui.viewer",
	"ui.notification",
	"ui.info-helper",
	"loader",
	"popup",
	"sidepanel",
	"documentpreview",
	"ui.icons.disk",
]);
Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');

$downloadButtonOptions = \CUserOptions::GetOption('crm.document.view', 'download_button', []);
$defaultDownloadFormat = in_array(mb_strtolower($downloadButtonOptions['format']), ['doc', 'pdf'], true)
	? mb_strtolower($downloadButtonOptions['format'])
	: 'pdf';

$isSigningEnabledInCurrentTariff = (bool)($arResult['isSigningEnabledInCurrentTariff'] ?? false);
$APPLICATION->IncludeComponent("bitrix:bitrix24.limit.lock", "", array());

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

$this->IncludeLangFile();
if ($arParams['IS_SLIDER']):
	$APPLICATION->RestartBuffer();
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
			<div class="pagetitle crm__document-view--pagetitle">
				<span id="pagetitle" class="pagetitle-item crm__document-view--pagetitle-item"><?=htmlspecialcharsbx($arResult['title']);?></span>
			</div>
			<div class="crm__document-view--buttons">
				<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-print" id="crm-document-print"></button>
				<?php if (Bitrix24Manager::isEnabled()):
					?><button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.DocumentGenerator.Feedback.open('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['PROVIDER']));?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['TEMPLATE_NAME']));?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['TEMPLATE_CODE']));?>');"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_FEEDBACK');?></button>
				<?php endif;?>
				<div
					class="ui-btn-split ui-btn-light-border crm__document-view--btn-icon-<?=mb_strtolower($defaultDownloadFormat)?>"
					id="crm-document-download"
				>
					<button class="ui-btn-main">
						<span class="ui-btn-text"><?=Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD');?></span>
					</button>
					<button class="ui-btn-menu"></button>
				</div>
			</div>
		</div>
	</div>
<?php
else:
	$APPLICATION->SetTitle($arResult['title']);
endif;
?>
<div class="crm__document-view--wrap">
	<div class="crm__document-view--inner crm__document-view--inner-slider">
		<div class="ui-alert ui-alert-danger ui-alert-icon-danger ui-alert-text-center" id="crm-document-view-error"<?php
		if ($arResult['ERRORS'] || $arResult['transformationErrorMessage']):
			?> style="display: block;"
		<?php endif;?>>
			<span class="ui-alert-message" id="crm-document-view-error-message">
			<?php if (is_array($arResult['ERRORS'])):
				foreach($arResult['ERRORS'] as $error):
					echo htmlspecialcharsbx($error);
					echo '<br />';
				endforeach;
			elseif ($arResult['transformationErrorMessage']):
				echo htmlspecialcharsbx($arResult['transformationErrorMessage']);
			endif; ?></span>
			<span class="ui-alert-close-btn" onclick="BX.hide(BX('crm-document-view-error'));"></span>
		</div>
		<?php if (!$arResult['ERRORS']):?>
			<div class="crm__document-view--img">
				<div class="crm__document-view--error" id="crm__document-view--transform-error"<?php if ($arResult['isTransformationError']):?> style="display: block;"<?php endif;?>>
					<div class="crm__document-view--error-message">
						<span class="crm__document-view--error-message-text"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRANSFORM_ERROR');?></span>
						<span class="crm__document-view--error-message-text"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRY_LATER');?></span>
						<span><a onclick="location.reload();" class="crm__document-view--link-pointer"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRY_AGAIN');?></a></span>
					</div>
					<div class="crm__document-view--error-img"></div>
				</div>
				<div class="crm__document-view--upload crm__document-view--upload-img" id="crm__document-view--node"<?php if (!$arResult['isTransformationError'] && !$arResult['pdfUrl']):?> style="display: block;"<?php endif;?>>
					<div class="crm__document-view--upload-message">
						<span class="crm__document-view--upload-message-text" id="crm__document-view--node-message"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PREVIEW_MESSAGE_PREPARE');?></span>
						<div class="crm__document-view--upload-progress" id="docs-progress-bar"></div>
						<span class="crm__document-view--upload-detail" id="crm__document-view--node-detail"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PREVIEW_MESSAGE_READY');?></span>
					</div>
				</div>
				<div class="crm__document-view--pdf" id="crm-document-pdf" data-viewer-type="document"></div>
			</div>
			<script>
				BX.ready(function()
				{
					<?php
					$messages = array_merge(Loc::loadLanguageFile(__FILE__), [
						'CRM_DOCUMENT_VIEW_COMPONENT_PROCESSED_NO_PDF_ERROR' => Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_PROCESSED_NO_PDF_ERROR'),
					]);
					echo 'BX.message('.\CUtil::PhpToJSObject($messages).');'?>
					BX.Crm.DocumentView.init(<?=CUtil::PhpToJSObject($arResult);?>);
				});
			</script>
		<?php endif;?>
	</div>
	<?php if (!$arResult['ERRORS']):?>
		<div class="crm__document-view--sidebar-wrapper">
			<?php if (!empty($arResult['channelSelectorParameters'])):?>
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
			<?php endif;?>
			<div class="crm__document-view--sidebar --company-information">
				<div class="crm__document-view--sidebar-section">
					<div
						class="crm__document-view--sidebar-control
							<?php
							if (!$arResult['editTemplateUrl'] || !$arResult['changeQrCodeEnabled']):
								?> --disabled<?php
							endif;?>
							">
						<label class="crm__document-view--label" for="crm-document-qr"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_QR');?></label>
						<input class="crm__document-view--checkbox" type="checkbox" id="crm-document-qr"<?php
						if ($arResult['qrCodeEnabled']):
							?> checked<?php
						endif;
						if (!$arResult['editTemplateUrl'] || !$arResult['changeQrCodeEnabled']):
							?> disabled<?php
						endif;?>>
					</div>
				</div>
				<?php /* // payments temporary disabled
				<div class="crm__document-view--sidebar-section">
					<div class="crm__document-view--sidebar-control">
						<label class="crm__document-view--label --label-icon --icon-paymen crm__document-view--sidebar-control-paymen">
							<?=Loc::getMessage('CRM_DOCUMENT_VIEW_PAYMENT_BUTTON');?>
						</label>
						<span class="crm__document-view--arrow"> </span>
					</div>
				</div>
 				*/?>
				<div class="crm__document-view--sidebar-section">
				<?php
					$renderRequisiteSection(
						Loc::getMessage('CRM_DOCUMENT_VIEW_REQUISITES_MY_COMPANY_TITLE'),
						$arResult['myCompanyRequisites'],
					);
				?>
					<?php if (Driver::getInstance()->getUserPermissions()->canModifyDocuments()):?>
						<div class="crm__document-view--sidebar-control --in-details">
							<label class="crm__document-view--label" type="text" for="crm-document-stamp"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_SIGN_AND_STAMP');?></label>
							<input class="crm__document-view--checkbox" type="checkbox" id="crm-document-stamp"<?php
							if ($arResult['stampsEnabled'] && $arResult['changeStampsEnabled']):
								?> checked<?php
							endif;
							if (!$arResult['editTemplateUrl'] || !$arResult['changeStampsEnabled']):
								?> disabled<?php
							endif;?>>
						</div>
					<?php endif;?>
				</div>
				<div class="crm__document-view--sidebar-section">
				<?php
					$renderRequisiteSection(
						Loc::getMessage('CRM_COMMON_CLIENT'),
						$arResult['clientRequisites'],
					);
				?></div>
				<?php if ($arResult['isSigningEnabled'] ?? false):
					echo \Bitrix\Crm\Tour\Sign\SignDocumentFromSlider::getInstance()->build();
					?>
					<div class="crm__document-view--sidebar-section">
						<div class="crm__document-view--sidebar-control" id="crm-document-sign">
							<label class="crm__document-view--label --label-icon --icon-sign crm__document-view--sidebar-control-sign">
								<?= Loc::getMessage('CRM_DOCUMENT_VIEW_SIGN_BUTTON') ?>
							</label>
							<span class="<?=
							$isSigningEnabledInCurrentTariff
								? "crm__document-view--arrow"
								: "tariff-lock"
							?>"></span>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php if ($arResult['editDocumentUrl']):?>
				<div class="crm__document-view--link-inner">
					<div class="crm__document-view--link-block">
						<span class="crm__document-view--link-text" id="crm-document-edit-document"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_EDIT_DOCUMENT');?></span>
					</div>
				</div>
			<?php endif;
			if (!is_bool($arResult['editTemplateUrl'])):?>
			<div class="crm__document-view--link-inner">
				<div class="crm__document-view--link-block">
					<span class="crm__document-view--link-text" id="crm-document-edit-template"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_EDIT_TEMPLATE');?></span>
				</div>
			</div>
			<?php endif;
			if (isset($arResult['publicUrlView']['time'])):?>
				<div class="crm__document-view--sidebar crm__document-view--sidebar-details">
					<div class="crm__document-view--public-view-info">
						<?=Loc::getMessage('CRM_DOCUMENT_VIEW_PUBLIC_URL_VIEWED_TIME', [
							'#TIME#' => $arResult['publicUrlView']['time'],
						]);?>
					</div>
				</div>
			<?php endif;?>
		</div>
	<?php endif;?>
</div>
<?php if ($arParams['IS_SLIDER']):?>
</body>
	</html>
<?php
	\CMain::FinalActions();
endif;
