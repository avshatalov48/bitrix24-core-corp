<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];
?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($messageText)?>
</div>
<div class="bizproc-automation-popup-checkbox">
	<div class="bizproc-automation-popup-checkbox-item" data-role="help">
		<label class="bizproc-automation-popup-chk-label">
			<input type="hidden" name="<?=htmlspecialcharsbx($map['IsSystem']['FieldName'])?>" value="N">
			<input type="checkbox" name="<?=htmlspecialcharsbx($map['IsSystem']['FieldName'])?>" value="Y" class="bizproc-automation-popup-chk" <?=$dialog->getCurrentValue($map['IsSystem']['FieldName']) === 'Y' ? 'checked' : ''?>>
			<?=htmlspecialcharsbx($map['IsSystem']['Name'])?>
		</label>
		<?if (!empty($map['IsSystem']['Description'])):?>
		<span class="bizproc-automation-status-help" data-hint="<?=htmlspecialcharsbx($map['IsSystem']['Description'])?>"></span>
		<?endif?>
	</div>
</div>
<?
$attachmentType = isset($map['AttachmentType']) ? $map['AttachmentType'] : null;
$attachment = isset($map['Attachment']) ? $map['Attachment'] : null;
$config = array(
	'type' => $dialog->getCurrentValue($attachmentType['FieldName']),
	'typeInputName' => $attachmentType['FieldName'],
	'valueInputName' => $attachment['FieldName'],
	'multiple' => $attachment['Multiple'],
	'required' => !empty($attachment['Required']),
	'useDisk' => \Bitrix\Main\Loader::includeModule('disk'),
	'label' => $attachment['Name'],
	'labelFile' => $attachmentType['Options']['file'],
	'labelDisk' => $attachmentType['Options']['disk']
);

if ($dialog->getCurrentValue($attachmentType['FieldName']) === 'disk')
{
	$config['selected'] = \Bitrix\Bizproc\Automation\Helper::prepareDiskAttachments(
		$dialog->getCurrentValue($attachment['FieldName'])
	);
}
else
{
	$config['selected'] = \Bitrix\Bizproc\Automation\Helper::prepareFileAttachments(
		$dialog->getDocumentType(),
		$dialog->getCurrentValue($attachment['FieldName'])
	);
}
$configAttributeValue = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($config));
?>
<div class="bizproc-automation-popup-settings" data-role="file-selector" data-config="<?=$configAttributeValue?>"></div>
