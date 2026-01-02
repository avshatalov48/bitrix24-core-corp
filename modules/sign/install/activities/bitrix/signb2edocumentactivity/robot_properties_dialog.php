<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use \Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('sign'))
{
	die();
}

Asset::getInstance()->addJs(getLocalPath('activities/bitrix/signb2edocumentactivity/script.js'));
Asset::getInstance()->addCss(getLocalPath('activities/bitrix/signb2edocumentactivity/style.css'));

/**
 * @var PropertiesDialog $dialog
 */
$templateId = (string)$dialog->getCurrentValue(CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID);

Extension::load([
	'ui.forms',
	'ui.hint',
	'ui.buttons',
	'sign.v2.blank-selector',
]);

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$isSubscriptionEnabled = (string)$dialog->getCurrentValue(CBPSignB2EDocumentActivity::PARAM_IS_SUBSCRIPTION_ENABLED) === 'Y';
?>

<?php foreach ($dialog->getMap() as $fieldId => $field): ?>
	<?php if ($fieldId === CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID): ?>
		<div class="bizproc-automation-popup-section">
	<?php endif; ?>
	<div class="bizproc-automation-popup-settings">
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
			<div class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
				<?= htmlspecialcharsbx((string)($field['Name'] ?? null)) ?>:
				<?php if ($field['Hint'] ?? null): ?>
					<span data-hint="<?= htmlspecialcharsbx((string)($field['Hint'] ?? null)) ?>"></span>
				<?php endif; ?>
			</div>
			<?= $dialog->renderFieldControl($field) ?>
		<?php endif; ?>
	</div>
	<?php if ($fieldId === CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID): ?>
		<button id="bizproc-automation-popup-btn-create-template" type="button" class="ui-btn ui-btn-light-border"><?=Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_CREATE_TEMPLATE')?></button>
	<?php endif; ?>
	<?php if ($fieldId === CBPSignB2EDocumentActivity::PARAM_TEMPLATE_ID): ?>
		</div>
	<?php endif; ?>
</div>
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
		BX.UI.Hint.init(BX('.bizproc-automation-popup-section'));
	});
</script>