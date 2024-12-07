<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load(
	[
		'crm.field.color-selector',
		'crm_common',
		'bizproc.automation',
		'ui.design-tokens',
		'ui.icon-set.api.core',
		'ui.icon-set.main',
	]
);
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcreatetodoactivity/script.js'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field): ?>
	<?php if ($field['FieldName'] === 'attachment_type'):?>
		<tr>
			<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
			<td width="60%">
				<select name="<?=htmlspecialcharsbx($field['FieldName'])?>" id="BPMA-attachment-type">
					<?php
					$currentType = $dialog->getCurrentValue($field['FieldName']) ?: 'disk';
					foreach ($field['Options'] as $key => $value):?>
					<option value="<?=htmlspecialcharsbx($key)?>"<?= $currentType === $key ? " selected" : "" ?>>
						<?=htmlspecialcharsbx($value)?>
					</option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
	<?php elseif ($field['FieldName'] === 'attachment'):?>
		<tr>
			<td align="right" width="40%"><span><?= htmlspecialcharsbx($field['Name']) ?>:</span></td>
			<td width="60%">
				<?php
				$attachmentValues = $dialog->getCurrentValue($field['FieldName']);
				?>
				<div id="BPMA-disk-control" <?=($currentType !== 'disk') ? 'hidden' : ''?>>
					<div id="BPMA-disk-control-items">
						<?php
						foreach ($attachmentValues as $fileId)
						{
							if ($currentType !== 'disk')
							{
								break;
							}
							$object = \Bitrix\Disk\File::loadById($fileId);
							if ($object)
							{
								$objectId = $object->getId();
								$objectName = $object->getName();
								?>
								<div>
									<input type="hidden" name="<?=htmlspecialcharsbx($field['Fiel	dName'])?>[]" value="<?=$objectId?>"/>
									<span style="color: grey">
										<?=htmlspecialcharsbx($objectName)?>
									</span class='BPMA-disk-delete-file-button'>
										<a onclick="BX.Dom.remove(this.parentNode); return false" style="color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
									</div>
								<?php
							}
						}
						?>
					</div>
					<a id="BPMA-show-disk-file-dialog-button" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?= 'Выбрать'?></a>
				</div>
				<div id="BPMA-file-control" <?=($currentType !== 'file') ? 'hidden':''?>>
					<?php
					$field['Type'] = 'string';
					$filedType = $dialog->getFieldTypeObject($field);
					echo $filedType->renderControl(
						[
							'Form' => $dialog->getFormName(),
							'Field' => $field['FieldName']
						],
						$currentType === 'file' ? $attachmentValues : [],
						true,
						\Bitrix\Bizproc\FieldType::RENDER_MODE_DESIGNER);
					?>
				</div>
			</td>
		</tr>
	<?php else:?>
		<tr>
			<td align="right" width="40%">
				<?php if (!empty($field['Required'])): ?><span class="adm-required-field"><?php endif; ?>
				<?= htmlspecialcharsbx($field['Name']) ?>:
			<?php if (!empty($field['Required'])): ?></span><?php endif; ?>
			</td>
			<td width="60%">
				<?php
				$filedType = $dialog->getFieldTypeObject($field);

				echo $filedType->renderControl([
					'Form' => $dialog->getFormName(),
					'Field' => $field['FieldName'],
				], $dialog->getCurrentValue($field['FieldName']), true, 0);
				?>
			</td>
		</tr>
	<?php endif?>
<?php endforeach;?>
<script>
	BX.Event.ready(() => {
		if (BX.Crm.Activity.CrmCreateTodoActivity)
		{
			const createTodoActivity = new BX.Crm.Activity.CrmCreateTodoActivity({
				isRobot: false,
				documentType: <?= Json::encode($dialog->getDocumentType()) ?>,
				formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
				attachmentType: document.getElementById('BPMA-attachment-type'),
				fileControl: document.getElementById('BPMA-file-control'),
				diskControl: document.getElementById('BPMA-disk-control'),
				diskControlItems: document.getElementById('BPMA-disk-control-items'),
				showDiskFileDialogButton: document.getElementById('BPMA-show-disk-file-dialog-button'),
			});
		}
	});
</script>
