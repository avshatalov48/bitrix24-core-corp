<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;

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
	"sign.v2.document-send",
]);

$APPLICATION->SetTitle(htmlspecialcharsbx($arResult['title']));

if (\Bitrix\Main\Loader::includeModule('ui'))
{
	Toolbar::deleteFavoriteStar();

	Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([
			'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
			'icon' => \Bitrix\UI\Buttons\Icon::PRINT,
		]))
			->setUniqId('crm-document-print')
		,
	);

	Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([
			'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
			'icon' =>  \Bitrix\UI\Buttons\Icon::DOWNLOAD,
			'text' => Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD'),
		]))
			->setUniqId('crm-document-download')
		,
	);
}

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
<div class="crm__document-view--wrap">
	<div class="crm__document-view--inner crm__document-view--inner-slider">
		<div class="crm__document-view--img">
			<div class="crm__document-view--pdf" id="crm-document-pdf" data-role="pdf-viewer" data-viewer-type="document"></div>
		</div>
	</div>
	<div class="crm__document-view--sidebar-wrapper">
		<?php if ($arResult['documentResendEnabled']): ?>
		<div class="crm__document-view--sidebar crm__document-view--sidebar-channels">
			<div class="crm__document-view--channels">
				<div class="crm__document-view--resend_widget"></div>
			</div>
		</div>
		<?php endif; ?>

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
	BX.message(<?=\CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function(){
		const params = <?=\CUtil::PhpToJSObject($arResult, false, false, true);?>;
		params.pdfNode = document.querySelector('[data-role="pdf-viewer"]');

		new BX.Crm.Component.SignDocumentView(params);

		const documentSendClass = BX && BX.Sign && BX.Sign.V2 && BX.Sign.V2.DocumentSend
		if (params.documentResendEnabled && typeof documentSendClass !== 'undefined')
		{
			const widgetPlaceholders = document.querySelectorAll('.crm__document-view--resend_widget');
			widgetPlaceholders.forEach((widgetPlaceholder) => {
				new BX.Crm.Component.SignDocumentViewSendWidget({
					memberIds: params?.documentResendMembers ?? [],
				}).renderTo(widgetPlaceholder);
			});
		}
	});
</script>
