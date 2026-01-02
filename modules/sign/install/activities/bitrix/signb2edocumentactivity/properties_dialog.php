<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use \Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('sign'))
{
	die;
}

Asset::getInstance()->addJs(getLocalPath('activities/bitrix/signb2edocumentactivity/script.js'));
Asset::getInstance()->addCss(getLocalPath('activities/bitrix/signb2edocumentactivity/style.css'));

/**
 * @var PropertiesDialog $dialog
 */
$templateId = (string)$dialog->getCurrentValue(CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID);
$isSubscriptionEnabled = (string)$dialog->getCurrentValue(CBPSignB2EDocumentActivity::PARAM_IS_SUBSCRIPTION_ENABLED) === 'Y';

Extension::load([
	'ui.forms',
	'ui.hint',
	'ui.buttons',
	'sign.v2.blank-selector',
]);

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
foreach ($dialog->getMap() as $fieldId => $field): ?>
	<tr class="sign-bp-properties-dialog-tr">
		<td align="right" width="40%">
			<?php if ($fieldId !== CBPSignB2EDocumentActivity::PARAM_IS_SUBSCRIPTION_ENABLED): ?>
				<span class="adm-required-field"><?= htmlspecialcharsbx((string)($field['Name'] ?? null)) ?>:
					<?php if ($field['Hint'] ?? null): ?>
						<span data-hint="<?= htmlspecialcharsbx((string)($field['Hint'] ?? null)) ?>"></span>
					<?php endif; ?>
			<?php endif; ?>
		</td>
		<td width="60%" class="sign-bp-properties-dialog-td">
			<?php if ($fieldId === CBPSignB2EDocumentActivity::PARAM_IS_SUBSCRIPTION_ENABLED): ?>
			<label>
				<input
					type="checkbox"
					value="Y"
					name="<?= CBPSignB2EDocumentActivity::PARAM_IS_SUBSCRIPTION_ENABLED ?>"
					<?php if ($isSubscriptionEnabled) echo 'checked' ?>
				/>
				<?= htmlspecialcharsbx((string)($field['Name'] ?? null)) ?>
			</label>
			<?php else: ?>
				<?= $dialog->renderFieldControl(field: $field, renderMode: ($fieldId === CBPSignB2EDocumentActivity::PARAM_EMPLOYEE) ? FieldType::RENDER_MODE_DESIGNER : FieldType::RENDER_MODE_PUBLIC) ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php if ($fieldId === CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID): ?>
		<tr>
			<td align="right" width="40%">
			</td>
			<td width="60%">
				<button id="bizproc-automation-popup-btn-create-template" type="button">
					<?=Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_CREATE_TEMPLATE')?>
				</button>
			</td>
		</tr>
	<?php endif; ?>
<?php endforeach; ?>

<script>
	BX.ready(() => {
		new BX.Sign.SignB2EDocumentActivity({
			select: document.querySelector('select[name="<?= (string)CUtil::JSEscape(CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID) ?>"]'),
			documentType: <?= (string)Json::encode($dialog->getDocumentType() ?? []) ?>,
			formName: '<?= (string)CUtil::JSEscape($dialog->getFormName()) ?>',
			buttonNode: document.getElementById('bizproc-automation-popup-btn-create-template'),
			templateId:'<?= (string)CUtil::JSEscape($templateId) ?>',
		}).init();
		BX.UI.Hint.init(BX('.sign-bp-properties-dialog-tr'));
	});
</script>

<style>
	.sign-bp-properties-dialog-td > [name=<?=CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID?>] {
		width: 100% !important;
		max-width: 100% !important;
	}

	.sign-bp-properties-dialog-td .ui-tag-selector-text-box {
		display: none !important;
	}

	.sign-bp-properties-dialog-tr .ui-hint {
		width: 8px;
		position: relative;
		top: 3px;
	}
</style>