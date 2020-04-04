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
$attachmentType = isset($map['AttachmentType']) ? $map['AttachmentType'] : null;
$attachment = isset($map['Attachment']) ? $map['Attachment'] : null;
$from = isset($map['MessageFrom']) ? $map['MessageFrom'] : null;
$fromValue = $from ? $dialog->getCurrentValue($from['FieldName'], $dialog->getCurrentValue('from','')) : null;

$runtimeData = $dialog->getRuntimeData();
$mailboxes = $runtimeData['mailboxes'];

if ($from && $mailboxes):?>
<div style="display:none;">
<?
	$APPLICATION->IncludeComponent('bitrix:main.mail.confirm', '');
?>
</div>
<div class="crm-automation-popup-settings crm-automation-popup-settings-text">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($from['Name'])?>:</span>
	<input type="hidden" name="<?=htmlspecialcharsbx($from['FieldName'])?>" value="<?=htmlspecialcharsbx($fromValue)?>" data-role="mailbox-selector-value">
	<a class="crm-automation-popup-settings-link" data-role="mailbox-selector"></a>
</div>
<?
endif;
?>
<div class="crm-automation-popup-settings">
	<input name="<?=htmlspecialcharsbx($subject['FieldName'])?>" type="text" class="crm-automation-popup-input"
		value="<?=htmlspecialcharsbx($dialog->getCurrentValue($subject['FieldName']))?>"
		placeholder="<?=htmlspecialcharsbx($subject['Name'])?>"
		data-role="inline-selector-target"
	>
</div>
<div class="crm-automation-popup-settings" data-role="inline-selector-html">
	<div class="crm-automation-popup-select"><?php
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
	));
	?></div>
</div>
<input type="hidden" name="<?=htmlspecialcharsbx($map['MessageTextType']['FieldName'])?>"
	value="<?=htmlspecialcharsbx(\CBPCrmSendEmailActivity::TEXT_TYPE_HTML)?>">

<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($emailType['Name'])?>:</span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($emailType['FieldName'])?>">
		<?foreach ($emailType['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $emailTypeValue) ? ' selected' : ''?>
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
<div class="crm-automation-popup-settings" data-role="file-selector" data-config="<?=$configAttributeValue?>"></div>
<?if ($from && $mailboxes):?>
<script>

	BX.ready(function ()
	{
		var dialog = BX.Bizproc.Automation.Designer.getRobotSettingsDialog();
		if (!dialog)
		{
			return;
		}

		var mailboxes = <?=\Bitrix\Main\Web\Json::encode($mailboxes);?>;

		var mailboxSelector = dialog.form.querySelector('[data-role="mailbox-selector"]');
		var mailboxSelectorValue = dialog.form.querySelector('[data-role="mailbox-selector-value"]');

		var setMailbox = function(value)
		{
			mailboxSelector.textContent = value ? value : '<?=GetMessageJS('CRM_SEMA_RPD_FROM_AUTO')?>';
			mailboxSelectorValue.value = value;
		};

		var getMenuItems = function()
		{
			var i, menuItems = [{
				text: '<?=GetMessageJS('CRM_SEMA_RPD_FROM_AUTO')?>',
				onclick: function(e, item)
				{
					this.popupWindow.close();
					setMailbox('');
				}
			}];

			for (i = 0; i < mailboxes.length; ++i)
			{
				var mailbox = mailboxes[i];
				var mailboxName = mailbox['name'].length > 0
					? mailbox['name'] + ' <' + mailbox['email'] + '>'
					: mailbox['email'];

				menuItems.push({
					text: BX.util.htmlspecialchars(mailboxName),
					value: mailboxName,
					onclick: function(e, item)
					{
						this.popupWindow.close();
						setMailbox(item.value);
					}
				});
			}

			if (window.BXMainMailConfirm)
			{
				menuItems.push({delimiter: true}, {
					text: '<?=GetMessageJS('CRM_SEMA_RPD_FROM_ADD')?>',
					onclick: function(e, item)
					{
						this.popupWindow.close();
						window.BXMainMailConfirm.showForm(function(mailbox)
						{
							mailboxes.push(mailbox);
							setMailbox(mailbox['name'].length > 0
								? mailbox['name'] + ' <' + mailbox['email'] + '>'
								: mailbox['email']);
						});
					}
				});
			}

			return menuItems;
		};

		BX.bind(mailboxSelector, 'click', function(e)
			{
				var menuId = 'crm-sma-mailboxes' + Math.random();
				BX.PopupMenu.show(
					menuId,
					this,
					getMenuItems(),
					{
						autoHide: true,
						offsetLeft: (BX.pos(this)['width'] / 2),
						angle: { position: 'top', offset: 0 },
						zIndex: 200,
						events:
						{
							onPopupClose: function()
							{
								this.destroy();
							}
						}
					},
				);
			}
		);

		//init
		setMailbox(mailboxSelectorValue.value);
	});
</script>
<?endif;