<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\CJSCore::init(["loader", "popup", "sidepanel", "documentpreview"]);
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');

$this->IncludeLangFile();
if($arParams['IS_SLIDER'])
{
	$APPLICATION->RestartBuffer();
	?>
	<!DOCTYPE html>
	<html>
<head>
	<script data-skip-moving="true">
		// Prevent loading page without header and footer
		if (window === window.top)
		{
			window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
		}
	</script>
	<?$APPLICATION->ShowHead(); ?>
</head>
<body class="docs-preview-slider-wrap">
<div class="docs-preview-title">
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container pagetitle-inner-container-docs-preview">
			<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item docs-preview-pagetitle-item"><?=htmlspecialcharsbx($arResult['title']);?>
				</span>
			</div>
			<div class="docs-preview-buttons">
				<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-print" id="crm-document-print"></button>
				<?if(Bitrix24Manager::isEnabled())
				{
					?><button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.DocumentGenerator.Feedback.open('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['PROVIDER']));?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['TEMPLATE_NAME']));?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['TEMPLATE_CODE']));?>');"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_FEEDBACK');?></button>
					<?
				}?>
				<button class="ui-btn ui-btn-md ui-btn-primary ui-btn-dropdown" id="crm-document-send"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_SEND');?></button>
			</div>
		</div>
	</div>
</div>
<?}
else
{
	$APPLICATION->SetTitle($arResult['title']);
}
$activityEditorParams = array(
	'CONTAINER_ID' => '',
	'EDITOR_ID' => 'document-send-email',
	'PREFIX' => '',
	'ENABLE_UI' => false,
	'ENABLE_EMAIL_ADD' => true,
	'ENABLE_TOOLBAR' => false,
	'TOOLBAR_ID' => '',
	'OWNER_TYPE' => \CCrmOwnerType::ResolveName($this->getComponent()->getCrmOwnerType()),
	'OWNER_ID' => $this->getComponent()->getValue(),
	'SKIP_VISUAL_COMPONENTS' => 'Y',
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$activityEditorParams,
	$this->getComponent(),
	array('HIDE_ICONS' => 'Y')
);
?>
<div class="docs-preview-wrap">
	<div class="docs-preview-inner docs-preview-inner-slider">
		<div class="ui-alert ui-alert-danger ui-alert-icon-danger ui-alert-text-center" id="crm-document-view-error"<?
		if($arResult['ERRORS'] || $arResult['transformationErrorMessage'])
		{
			?> style="display: block;"
		<?}?>>
			<span class="ui-alert-message" id="crm-document-view-error-message"><?if(is_array($arResult['ERRORS']))
			{
				foreach($arResult['ERRORS'] as $error)
				{
					echo htmlspecialcharsbx($error);
					echo '<br />';
				}
			}
			elseif($arResult['transformationErrorMessage'])
			{
				echo htmlspecialcharsbx($arResult['transformationErrorMessage']);
			}?></span>
			<span class="ui-alert-close-btn" onclick="BX.hide(BX('crm-document-view-error'));"></span>
		</div>
		<?if(!$arResult['ERRORS'])
		{?>
		<div class="docs-preview-img" id="crm-document-image">
			<div class="docs-preview-error" id="docs-preview-transform-error"<?if($arResult['isTransformationError']){?> style="display: block;"<?}?>>
				<div class="docs-preview-error-message">
					<span class="docs-preview-error-message-text"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRANSFORM_ERROR');?></span>
					<span class="docs-preview-error-message-text"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRY_LATER');?></span>
					<span><a onclick="location.reload();" class="docs-preview-link-pointer"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_TRY_AGAIN');?></a></span>
				</div>
				<div class="docs-preview-error-img"></div>
			</div>
			<div class="docs-preview-upload" id="docs-preview-node"<?if(!$arResult['isTransformationError'] && !$arResult['imageUrl'] && !$arResult['pdfUrl']){?> style="display: block;"<?}?>>
				<div class="docs-preview-upload-message">
					<span class="docs-preview-upload-message-text" id="docs-preview-node-message"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PREVIEW_TIME_MESSAGE');?></span>
					<div class="docs-preview-upload-progress">
						<div class="docs-preview-upload-progress-inner">
							<div class="docs-preview-upload-progress-value" id="docs-progress-bar"></div>
						</div>
					</div>
					<span class="docs-preview-upload-detail" id="docs-preview-node-detail"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PREVIEW_READY_MESSAGE');?></span>
				</div>
				<div class="docs-preview-upload-img"></div>
			</div>
		</div>
		<div class="docs-preview-pdf" id="crm-document-pdf">
		</div>
		<span class="docs-preview-more-btn" id="crm-document-show-pdf"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_SHOW_FULL_DOCUMENT');?></span>
		<script type="text/javascript">
			BX.ready(function()
			{
				<?='BX.message('.\CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)).');'?>
				BX.Crm.DocumentView.init(<?=CUtil::PhpToJSObject($arResult);?>);
			});
		</script>
		<?}?>
	</div>
	<?if(!$arResult['ERRORS'])
	{?>
	<div class="docs-preview-sidebar-wrapper">
		<div class="docs-preview-sidebar">
		<div class="docs-preview-download">
			<div class="docs-preview-format-inner">
				<div class="docs-preview-format-text"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_DOWNLOAD_IN');?></div>
				<div class="docs-preview-format-icons">
					<div class="docs-preview-icon-inner docs-preview-link-pointer" id="crm-document-download-pdf">
						<i class="docs-preview-icon docs-preview-icon-pdf"></i>
					</div>
					<div class="docs-preview-icon-inner docs-preview-link-pointer" id="crm-document-download-file">
						<i class="docs-preview-icon docs-preview-icon-doc"></i>
					</div>
				</div>
			</div>
			<?if(\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyDocuments())
			{?>
			<div class="docs-preview-block">
				<input class="docs-preview-checkbox" type="checkbox" id="crm-document-stamp"<?if($arResult['stampsEnabled']){
					?> checked<?
				}
				if(!$arResult['editTemplateUrl'])
					{?> disabled<?
				}
                ?>>
				<label class="docs-preview-checkbox-label docs-preview-link-pointer" type="text" for="crm-document-stamp"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_SIGNED');?></label>
			</div>
			<?}?>
		</div>
		<div class="docs-preview-link-inner docs-preview-link-inner-public">
			<div class="docs-preview-public-link">
				<div class="docs-preview-public-link-block docs-preview-public-link-block-show">
					<div class="docs-preview-public-link-block-wrapper">
						<div class="docs-preview-public-link-copy">
							<span class="docs-preview-public-link-title" for="crm-document-public-url"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PUBLIC_LINK');?></span>
							<div class="docs-preview-switcher-inner">
								<div class="docs-preview-switcher docs-preview-switcher-on<?if(!$arResult['publicUrl']){?> docs-preview-switcher-off<?}?>" id="docs-preview-switcher">
									<span class="docs-preview-switcher-label"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_PUBLIC_ON');?></span>
									<div class="docs-preview-switcher-point"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="crm-document-public-value-container"<?if(!$arResult['publicUrl']){?> style="height: 0;"<?}?>>
				<div class="docs-preview-link-value">
					<div class="docs-preview-public-link-input-copy" id="crm-document-copy-public-url"></div>
					<input class="docs-preview-public-link-input" value="<?=htmlspecialcharsbx($arResult['publicUrl']);?>" type="text" id="crm-document-public-url-container" readonly>
				</div>
			</div>
		</div>
		<?if(!is_bool($arResult['editTemplateUrl']))
		{?>
		<div class="docs-preview-link-inner">
			<div class="docs-preview-link-block">
				<span class="docs-preview-link-text" id="crm-document-edit-template"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_EDIT_TEMPLATE');?></span>
			</div>
		</div>
		<?}
		if($arResult['editDocumentUrl'])
		{?>
		<div class="docs-preview-link-inner">
			<div class="docs-preview-link-block">
				<span class="docs-preview-link-text" id="crm-document-edit-document"><?=Loc::getMessage('CRM_DOCUMENT_VIEW_EDIT_DOCUMENT');?></span>
			</div>
		</div>
		<?}?>
	</div>
		<?if($arResult['editMyCompanyRequisitesUrl'])
		{
		?><div class="docs-preview-sidebar docs-preview-sidebar-details">
			<div class="docs-preview-details-inner">
				<?=Loc::getMessage('CRM_DOCUMENT_VIEW_CREATE_OR_EDIT_MY_COMPANY_REQUISITES', ['#URL#' => $arResult['editMyCompanyRequisitesUrl']]);?>
			</div>
		</div>
		<?}?>
		<?php
        if(isset($arResult['publicUrlView']))
		{
			?><div class="docs-preview-sidebar docs-preview-sidebar-details">
			    <div class="docs-preview-public-view-info">
                    <?=Loc::getMessage('CRM_DOCUMENT_VIEW_PUBLIC_URL_VIEWED_TIME', [
                        '#TIME#' => $arResult['publicUrlView']['time'],
                    ]);?>
                </div>
            </div>
		<?}?>
	</div>
	<?}?>
</div>
<?if($arParams['IS_SLIDER'])
{
	?>
</body>
	</html>
<?
	\Bitrix\Main\Application::getInstance()->terminate();
}