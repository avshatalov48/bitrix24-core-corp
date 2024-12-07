<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
?>

<div class="imconnector-field-section imconnector-field-section-control">
	<div class="imconnector-field-box">
		<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
			  id="form_save_<?=$arResult['CONNECTOR']?>"
			  method="post"
			  class="imconnector-field-control-box-border"
		>
			<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
			<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active">
			<?=bitrix_sessid_post()?>

			<div class="imconnector-step-text">
				<label for="imconnector-whatsappbyedna-api-key">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_API_KEY_MSGVER_1')?><span data-hint="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_API_KEY_TIP')?>"></span>
				</label>
			</div>
			<input
				type="text"
				class="imconnector-field-control-input"
				id="imconnector-whatsappbyedna-api-key"
				name="api_key"
				<?php if($arResult['API_SAVED']): ?>
					placeholder="<?=htmlspecialcharsbx($arResult['placeholder']['api_key'])?>"
					<?= empty($arResult['API_SAVED']) ?: 'disabled' ?>
				<?php else: ?>
					value="<?=htmlspecialcharsbx($arResult['FORM']['api_key'])?>"
				<?php endif; ?>
			>
			<div class="imconnector-step-text">
				<label for="imconnector-whatsappbyedna-sender-id">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SENDER_ID_MSGVER_1')?><span data-hint="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SENDER_ID_TIP' . $arResult['LOC_REGION_POSTFIX'])?>" data-hint-html></span>
				</label>
			</div>
			<input
				type="hidden"
				class="imconnector-field-control-input"
				id="imconnector-whatsappbyedna-sender-id"
				name="sender_id"
				value="<?=htmlspecialcharsbx($arResult['FORM']['sender_id'])?>"
				readonly
			>

			<?php if(count($arResult['SUBJECT_TITLES']) > 0): ?>
				<?php $busyLine = false; ?>
				<?php $busyTitle = ''; ?>
				<?php foreach ($arResult['SUBJECT_TITLES'] as $subjectId => $subjectTitle): ?>
					<?php if($subjectTitle['lineId'] == $arParams['LINE']): ?>
						<?php $currentLine = true; ?>
						<?php $busyLine = $subjectId; ?>
						<?php $busyTitle = $subjectTitle['title']; ?>
						<?php continue; ?>
					<?php else: ?>
						<?php $currentLine = false; ?>
					<?php endif; ?>
					<input
						type="text"
						class="imconnector-field-control-input im-connector-edna-subject-id"
						id="imconnector-whatsappbyedna-sender-id-<?= $subjectId; ?>"
						value="<?= $subjectTitle['title'] ?>"
						<?php if($subjectTitle['hint']): ?>
							data-hint="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_DISABLED_SENDER_ID_HINT')?>"
						<?php endif; ?>
						disabled
					>
				<?php endforeach; ?>
			<?php endif; ?>

			<input
				type="text"
				class="imconnector-field-control-input im-connector-edna-subject-id"
				id="imconnector-whatsappbyedna-sender-id-0"
				name="sender_id_0"
				onfocus="if (!this.dataset.focused) { this.value='<?= $busyLine ?>'; this.dataset.focused = true; }"
				oninput="this.value = this.value.replace(/[^0-9]/g, '')"
				placeholder="<?= $busyTitle ?: Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_NEW_SENDER_ID') ?>"
				data-original-value="<?= $busyLine ?>"
			>

			<div class="imconnector-step-text">
				<button class="ui-btn ui-btn-success"
					id="webform-small-button-have"
					name="<?=$arResult['CONNECTOR']?>_save"
					value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>"
					disabled
					onclick="
						let shouldPreventDefault = false;
						(function() {
							const anotherSubjectIds = <?= json_encode($arResult['ANOTHER_SUBJECT_IDS']) ?>;
							const senderInput = document.getElementById('imconnector-whatsappbyedna-sender-id-0');
							const inputValue = parseInt(senderInput.value);
							const form = senderInput.closest('form');

							if (anotherSubjectIds.includes(inputValue)) {
								popupConfirmSaveShow();
								shouldPreventDefault = true;
							} else if (form) {
								const hiddenInput = document.createElement('input');
								hiddenInput.type = 'hidden';
								hiddenInput.name = '<?=$arResult['CONNECTOR']?>_save';
								hiddenInput.value = '<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>';
								form.appendChild(hiddenInput);
								form.submit();
							}
						})();
						return !shouldPreventDefault;
					"
				>
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
				</button>
				<?php if(count($arResult['SUBJECT_TITLES']) > 0 && $busyLine): ?>
					<button class="ui-btn ui-btn-light-border"
							name="<?=$arResult['CONNECTOR']?>_del"
							value="1"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>);return false;"
					>
						<?=Loc::getMessage((count($arResult['SUBJECT_TITLES']) > 1) ? 'IMCONNECTOR_COMPONENT_SETTINGS_DISABLE_LINE' : 'IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_DISABLE_CONNECTOR')?>
					</button>
				<?php endif; ?>
			</div>
		</form>
	</div>
	<?php if (empty($arResult['STATUS']) && empty($arResult['CONNECTOR_LINES'])): ?>
		<?php include 'connection-help.php'; ?>
	<?php endif; ?>
</div>