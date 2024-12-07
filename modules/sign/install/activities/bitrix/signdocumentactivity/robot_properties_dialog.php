<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Sign\Config\Storage;

/**
 * @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
 */
$blankIdValue = $dialog->getCurrentValue('blankId');
$storage = Storage::instance();
$regionCode = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

$blankSelectorConfig = (new \Bitrix\Sign\Config\Ui\BlankSelector())->create(
	\Bitrix\Sign\Type\BlankScenario::B2B,
	isEdoRegion: false,
);

\Bitrix\Main\UI\Extension::load([
	'ui.forms',
	'sign.v2.blank-selector',
]);
?>

<script>
	BX.ready(() => {
		const blankSelectorOptions = <?= CUtil::PhpToJSObject($blankSelectorConfig) ?>;
		const blankFieldContainer = document.querySelector('.bizproc-automation-popup-blank-selector');
		const blankIdInputElement = document.querySelector('input[name="blankId"]');

		const setBlankId = (blankId) => {
			if (BX.Type.isDomNode(blankIdInputElement))
			{
				blankIdInputElement.value = BX.Text.encode(blankId);
			}
		};
		const selectedBlankId = blankIdInputElement.value ? Number(blankIdInputElement.value) : null;

		const blankField = new BX.Sign.V2.BlankField({
			selectorOptions: {
				canUploadNewBlank: false,
				type: 'b2b',
				...blankSelectorOptions,
			},
			events: {
				onSelect: (event) => {
					const { id } = event.getData();
					setBlankId(id);
				},
			},
			data: {
				blankId: selectedBlankId,
			},
		});

		blankField.renderTo(blankFieldContainer);
	});
</script>

<div class="bizproc-automation-popup-settings">
	<div class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?= htmlspecialcharsbx($dialog->getMap()['initiatorName']['Name']) ?>:
	</div>
	<?= $dialog->renderFieldControl($dialog->getMap()['initiatorName']) ?>
</div>

<div class="bizproc-automation-popup-settings">
	<div class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?= htmlspecialcharsbx($dialog->getMap()['blankId']['Name']) ?>:
	</div>
	<div class="bizproc-automation-popup-blank-selector">
		<input type="hidden" name="blankId" value="<?= htmlspecialcharsbx($blankIdValue) ?>">
	</div>
</div>