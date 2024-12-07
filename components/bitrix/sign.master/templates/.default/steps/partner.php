<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Document;

/** @var array $arParams */
/** @var array $arResult */
/** @var SignMasterComponent $component */
/** @var Document $document */

$document = $arResult['DOCUMENT'] ?? null;
if (!$document)
{
	return;
}

$blank = $document->getBlank();
if (!$blank)
{
	return;
}

$documentEditorUrl = str_replace('#doc_id#', $document->getId(), $arParams['PAGE_URL_EDIT']);
$contactsCount = $blank->getPartCount();

$link = $component->getRequestedPage([
	$arParams['VAR_STEP_ID'] => 'loadFile'
]);
$openEditorOnLoad = $component->getRequest($arParams['VAR_STEP_ID'] . '_editor') === 'open';
\Bitrix\Main\UI\Extension::load([
	'sign.backend',
	'crm.entity-editor',
	'ui.alerts',
	'ui.hint'
]);
?>
<style>
#sign-master__partner--requisites .ui-entity-editor-header-title-text {
	text-transform: none;
	color: #2fc6f6;
	font-size: 20px;
	font-weight: normal;
}

#sign-master__partner--requisites .ui-entity-editor-section-header {
	border-bottom: none;
}

#sign-master__partner--requisites .ui-entity-editor-block-title-text {
	display: none;
}
#sign-master__partner--requisites .crm-entity-widget-content-block-inner .ui-entity-editor-block-title-text {
	display: block;
}
</style>
<input type="hidden" name="actionName" value="assignMembers" />

<div class="ui-alert ui-alert-danger" style="display: none">
	<span class="ui-alert-message" data-role="sign-error-container"></span>
</div>

<div class="sign-master__content-responsible" id="sign-master__content-responsible">
	<div class="sign-master__content-responsible_title"><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_LABEL_WHOIS_RESPONSIBLE')?>
		<span class="sign-master__content-responsible_title-hint"  data-hint="<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_LABEL_WHOIS_RESPONSIBLE_HELP')?>" data-hint-no-icon><i></i></span>
	</div>
	<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
		<input class="ui-ctl-element" type="text" name="initiatorName" value="<?php echo \htmlspecialcharsbx($arResult['RESPONSIBLE_NAME'])?>" />
	</div>
</div>

<div class="sign-master__partner--requisites" id="sign-master__partner--requisites"></div>

<div class="sign-master__content-bottom">
	<a href="<?php echo htmlspecialcharsbx($link)?>" class="ui-btn ui-btn-lg ui-btn-light-border ui-btn-round" data-master-prev-step-button><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_BACK')?></a>
	<button title="<?= Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_PARTNER_LOCK_BTN_WHILE_LOADING_DOCUMENT_PREVIEW') ?>" type="button" data-action="changePartner" class="ui-btn ui-btn-lg ui-btn-primary ui-btn-round" data-master-next-step-button><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_CONTINUE')?></button>
</div>

<script>
	BX.ready(function()
	{
		BX.message(<?php echo \Bitrix\Main\Web\Json::encode([
			'SIGN_CMP_MASTER_TPL_ERROR_WRONG_CONTACTS_NUMBER' => Loc::getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_CONTACTS_NUMBER'),
			'SIGN_CMP_MASTER_TPL_ERROR_WRONG_INITIATOR' => Loc::getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_INITIATOR'),
		])?>);

		BX.Sign.Component.Master.loadCrmEntityEditor({
			id: <?php echo $document->getEntityId()?>,
			documentId: <?php echo $document->getId()?>,
			entityTypeId: '<?php echo $arParams['CRM_ENTITY_TYPE_ID']?>',
			guid: 'sign_new_document',
			stageId: '<?php echo $arResult['STAGE_ID']?>',
			categoryId: <?php echo (int)$arParams['CATEGORY_ID']?>,
			container: BX('sign-master__partner--requisites'),
			documentEditorUrl: '<?php echo \CUtil::jsEscape($documentEditorUrl)?>',
			contactsCount: <?php echo $contactsCount?>
		});

		BX.UI.Hint.init(BX('sign-master__content-responsible'));

		<?php if ($openEditorOnLoad):?>
		BX.Sign.Component.Master.openEditor(
			'<?php echo \CUtil::jsEscape($documentEditorUrl)?>',
			BX('sign-master__partner--requisites').closest('form')
		);
		<?php endif?>
	});
</script>

