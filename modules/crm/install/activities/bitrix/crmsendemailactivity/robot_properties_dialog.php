<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
/** @global \CMain $APPLICATION $map */
global $APPLICATION;

$map = $dialog->getMap();
$messageText = $map['MessageText'];
$subject = $map['Subject'];
$messageType = $dialog->getCurrentValue(
		$map['MessageTextType']['FieldName'],
		\CBPCrmSendEmailActivity::TEXT_TYPE_BBCODE
);
$emailType = $map['EmailType'];
$emailTypeValue = (string)$dialog->getCurrentValue($emailType['FieldName'], '');
$emailSelectRule = $map['EmailSelectRule'];
$emailSelectRuleValue = (string)$dialog->getCurrentValue($emailSelectRule['FieldName']);

$attachmentType = isset($map['AttachmentType']) ? $map['AttachmentType'] : null;
$attachment = isset($map['Attachment']) ? $map['Attachment'] : null;
$from = isset($map['MessageFrom']) ? $map['MessageFrom'] : null;

if ($from): ?>
	<div style="display:none;">
	<?php
		$APPLICATION->IncludeComponent('bitrix:main.mail.confirm', '');
	?>
	</div>
	<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top"><?=htmlspecialcharsbx($from['Name'])?>:</span>
		<?= $dialog->renderFieldControl($from)?>
	</div>
<?php endif; ?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($subject)?>
</div>
<div class="bizproc-automation-popup-settings" data-role="inline-selector-html">
	<div class="bizproc-automation-popup-select"><?php
	$emailEditor = new CHTMLEditor;

	$content = $dialog->getCurrentValue($messageText['FieldName'], '');
	if ($dialog->getCurrentValue('message_text_encoded'))
	{
		$content = \CBPCrmSendEmailActivity::decodeMessageText($content);
		$content = \Bitrix\Crm\Automation\Helper::convertExpressions($content, $dialog->getDocumentType());
	}

	if ($messageType !== \CBPCrmSendEmailActivity::TEXT_TYPE_HTML)
	{
		$parser = new CTextParser();
		$content = $parser->convertText($content);
	}

	$emailEditor->show(array(
		'name'                => $messageText['FieldName'],
		'content'			  => $content,
		'siteId'              => SITE_ID,
		'width'               => '100%',
		'minBodyWidth'        => 630,
		'normalBodyWidth'     => 630,
		'height'              => 198,
		'minBodyHeight'       => 198,
		'showTaskbars'        => false,
		'showNodeNavi'        => false,
		'autoResize'          => true,
		'autoResizeOffset'    => 40,
		'bbCode'              => false,
		'saveOnBlur'          => false,
		'bAllowPhp'           => false,
		'limitPhpAccess'      => false,
		'setFocusAfterShow'   => false,
		'askBeforeUnloadPage' => true,
		'useFileDialogs' => false,
		'controlsMap'         => array(
			array('id' => 'Bold',  'compact' => true, 'sort' => 10),
			array('id' => 'Italic',  'compact' => true, 'sort' => 20),
			array('id' => 'Underline',  'compact' => true, 'sort' => 30),
			array('id' => 'Strikeout',  'compact' => true, 'sort' => 40),
			array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 50),
			array('id' => 'Color',  'compact' => true, 'sort' => 60),
			array('id' => 'FontSelector',  'compact' => false, 'sort' => 70),
			array('id' => 'FontSize',  'compact' => false, 'sort' => 80),
			array('separator' => true, 'compact' => false, 'sort' => 90),
			array('id' => 'OrderedList',  'compact' => true, 'sort' => 100),
			array('id' => 'UnorderedList',  'compact' => true, 'sort' => 110),
			array('id' => 'AlignList', 'compact' => false, 'sort' => 120),
			array('separator' => true, 'compact' => false, 'sort' => 130),
			array('id' => 'InsertLink',  'compact' => true, 'sort' => 140),
			array('id' => 'InsertImage',  'compact' => false, 'sort' => 150),
			array('id' => 'InsertTable',  'compact' => false, 'sort' => 170),
			array('id' => 'Code',  'compact' => true, 'sort' => 180),
			array('id' => 'Quote',  'compact' => true, 'sort' => 190),
			array('separator' => true, 'compact' => false, 'sort' => 200),
			array('id' => 'Fullscreen',  'compact' => false, 'sort' => 210),
			array('id' => 'ChangeView',  'compact' => true, 'sort' => 220),
			array('id' => 'More',  'compact' => true, 'sort' => 400)
		),
		'isCopilotEnabled' => false,
	));
	?></div>
</div>
<input type="hidden" name="<?=htmlspecialcharsbx($map['MessageTextType']['FieldName'])?>"
	value="<?=htmlspecialcharsbx(\CBPCrmSendEmailActivity::TEXT_TYPE_HTML)?>">

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($emailType['Name'])?>:</span>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($emailType['FieldName'])?>">
		<?foreach ($emailType['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $emailTypeValue) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($emailSelectRule['Name'])?>:</span>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($emailSelectRule['FieldName'])?>">
		<?php foreach ($emailSelectRule['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?= ($value === $emailSelectRuleValue) ? ' selected' : '' ?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<?
	$config = array(
		'type' => $dialog->getCurrentValue($attachmentType['FieldName']),
		'typeInputName' => $attachmentType['FieldName'],
		'valueInputName' => $attachment['FieldName'],
		'multiple' => $attachment['Multiple'],
		'required' => !empty($attachment['Required']),
		'useDisk' => CModule::IncludeModule('disk'),
		'label' => $attachment['Name'],
		'labelFile' => $attachmentType['Options']['file'],
		'labelDisk' => $attachmentType['Options']['disk']
	);

	if ($dialog->getCurrentValue($attachmentType['FieldName']) === 'disk')
	{
		$config['selected'] = \Bitrix\Crm\Automation\Helper::prepareDiskAttachments(
			$dialog->getCurrentValue($attachment['FieldName'])
		);
	}
	else
	{
		$config['selected'] = \Bitrix\Crm\Automation\Helper::prepareFileAttachments(
			$dialog->getDocumentType(),
			$dialog->getCurrentValue($attachment['FieldName'])
		);
	}
	$configAttributeValue = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($config));
?>
<div class="bizproc-automation-popup-settings" data-role="file-selector" data-config="<?=$configAttributeValue?>"></div>

<div class="bizproc-automation-popup-checkbox">
	<div class="bizproc-automation-popup-checkbox-item">
		<label class="bizproc-automation-popup-chk-label">
			<input type="checkbox" name="<?=htmlspecialcharsbx($map['UseLinkTracker']['FieldName'])?>" value="Y" class="bizproc-automation-popup-chk" <?=$dialog->getCurrentValue($map['UseLinkTracker']) === 'Y' ? 'checked' : ''?>>
			<?=htmlspecialcharsbx($map['UseLinkTracker']['Name'])?>
		</label>
	</div>
</div>