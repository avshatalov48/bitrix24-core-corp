<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
 */

$blankIdValue = $dialog->getCurrentValue('blankId');

$storage = \Bitrix\Sign\Config\Storage::instance();

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

<tr>
	<td align="right" width="40%" valign="top">
		<span class="adm-required-field">
			<?= htmlspecialcharsbx($dialog->getMap()['initiatorName']['Name']) ?>
		</span>:
	</td>
	<td width="60%">
		<?= $dialog->renderFieldControl($dialog->getMap()['initiatorName'], null, true, 0) ?>
	</td>
</tr>

<tr>
	<td align="right" width="40%" valign="top">
		<?= htmlspecialcharsbx($dialog->getMap()['blankId']['Name']) ?>
	</td>
	<td width="60%">
		<div class="bizproc-automation-popup-blank-selector">
			<input type="hidden" name="blankId" value="<?= htmlspecialcharsbx($blankIdValue) ?>">
		</div>
	</td>
</tr>
