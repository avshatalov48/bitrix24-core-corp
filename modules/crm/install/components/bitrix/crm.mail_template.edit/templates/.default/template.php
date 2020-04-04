<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

$formId = 'crm_mail_template_edit_form_'.intval($arResult['ELEMENT']['ID']);

if (!empty($arResult['ELEMENT']['BODY']) && \CCrmContentType::Html != $arResult['ELEMENT']['BODY_TYPE'])
{
	$bbcodeParser = new \CTextParser();
	$arResult['ELEMENT']['BODY'] = $bbcodeParser->convertText($arResult['ELEMENT']['BODY']);
}

if ('Y' != $arResult['ELEMENT']['IS_ACTIVE'])
	$arResult['ELEMENT']['IS_ACTIVE'] = 'N';

if (\CCrmMailTemplateScope::Common != $arResult['ELEMENT']['SCOPE'])
	$arResult['ELEMENT']['SCOPE'] = \CCrmMailTemplateScope::Personal;

if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
{
	$APPLICATION->restartBuffer();

	?><!DOCTYPE html>
	<html>
		<head><? $APPLICATION->showHead(); ?></head>
		<body style="background: #eef2f4 !important; ">
			<div style="padding: 0 20px 20px 20px; ">
				<div class="pagetitle-wrap">
					<div class="pagetitle-inner-container">
						<div class="pagetitle-menu" id="pagetitle-menu"><?
							$APPLICATION->showViewContent('pagetitle');
							$APPLICATION->showViewContent('inside_pagetitle');
						?></div>
						<div class="pagetitle">
							<span id="pagetitle" class="pagetitle-item"><? $APPLICATION->showTitle() ?></span>
						</div>
					</div>
				</div>
	<?

	if (!empty($arResult['ERRORS']))
		showError(implode("\n", $arResult['ERRORS']));
}
else
{
	$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
	$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'pagetitle-toolbar-field-view')));
}

?>

<form id="<?=htmlspecialcharsbx($formId) ?>" action="<?=POST_FORM_ACTION_URI ?>" method="POST">
	<?=bitrix_sessid_post() ?>
	<? $saveAction = isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] != '' ? 'save' : 'apply'; ?>
	<input type="hidden" name="<?=$saveAction ?>" value="<?=$saveAction ?>">
	<input type="hidden" name="element_id" value="<?=intval($arResult['ELEMENT']['ID']) ?>">
	<input type="hidden" name="BODY_TYPE" value="<?=\CCrmContentType::Html ?>">
	
	<? ob_start(); ?>
	<span class="crm-mail-template-edit-form-switches-wrapper">
		<span class="crm-mail-template-edit-form-switch">
			<input class="crm-mail-template-edit-form-switch-checkbox" form="<?=htmlspecialcharsbx($formId) ?>"
				id="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_active"
				name="IS_ACTIVE" value="Y" type="checkbox"
				<? if ('Y' == $arResult['ELEMENT']['IS_ACTIVE']): ?> checked<? endif ?>
				onchange="BX(this.id+'_alt').value = this.checked ? this.value : '';">
			<label class="crm-mail-template-edit-form-switch-label"
				for="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_active"><?=getMessage('CRM_MAIL_TEMPLATE_IS_ACTIVE') ?></label>
		</span>
		<? if (\CCrmPerms::isAdmin() || \CCrmMailTemplateScope::Common == $arResult['ELEMENT']['SCOPE']): ?>
			<span class="crm-mail-template-edit-form-switch">
				<input type="hidden" name="SCOPE" value="<?=\CCrmMailTemplateScope::Personal ?>">
				<input class="crm-mail-template-edit-form-switch-checkbox" form="<?=htmlspecialcharsbx($formId) ?>"
					id="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_public"
					name="SCOPE" value="<?=\CCrmMailTemplateScope::Common ?>" type="checkbox"
					<? if (\CCrmMailTemplateScope::Common == $arResult['ELEMENT']['SCOPE']): ?> checked<? endif ?>
					onchange="BX(this.id+'_alt').value = this.checked ? this.value : <?=\CCrmMailTemplateScope::Personal ?>;"
					<? if (!\CCrmPerms::isAdmin()): ?> disabled<? endif ?>>
				<label class="crm-mail-template-edit-form-switch-label"
					for="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_public"><?=getMessage('CRM_MAIL_TEMPLATE_SCOPE_PUBLIC') ?></label>
			</span>
		<? endif ?>
		<? if (false && $arResult['ELEMENT']['ID'] > 0 && (\CCrmPerms::isAdmin() || $arResult['USER_ID'] == $arResult['ELEMENT']['OWNER_ID'])): ?>
			<? $deleteHref = \CHTTP::urlAddParams(
				\CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'],
					array('element_id' => $arResult['ELEMENT']['ID'])
				),
				array('delete' => '', 'sessid' => bitrix_sessid())
			); ?>
			<span class="crm-mail-template-edit-form-switch">
				<label class="crm-mail-template-edit-form-switch-label">
					<a href="#" onclick="confirm('<?=\CUtil::jsEscape(getMessage('CRM_MAIL_TEMPLATE_DELETE_DLG_MESSAGE')) ?>') && (window.location = '<?=\CUtil::jsEscape($deleteHref); ?>'); return false; "><?=getMessage('CRM_MAIL_TEMPLATE_DELETE_BTN') ?></a>
				</label>
			</span>
		<? endif ?>
	</span>
	<?

	$controls = ob_get_clean();

	if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
	{
		$this->setViewTarget('inside_pagetitle');
		echo $controls;
		$this->endViewTarget();
	}
	else if (SITE_TEMPLATE_ID == 'bitrix24')
	{
		$this->setViewTarget('inside_pagetitle');
		?><div class="pagetitle-container pagetitle-flexible-space"></div>
		<span class="pagetitle-container pagetitle-align-right-container"><?=$controls ?></span><?
		$this->endViewTarget();
	}
	else
	{
		echo $controls;
	}

	?>

	<input id="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_active_alt"
		type="hidden" name="IS_ACTIVE" value="<?=htmlspecialcharsbx($arResult['ELEMENT']['IS_ACTIVE']) ?>">
	<input id="crm_mail_template_<?=intval($arResult['ELEMENT']['ID']) ?>_public_alt"
		type="hidden" name="SCOPE" value="<?=htmlspecialcharsbx($arResult['ELEMENT']['SCOPE']) ?>">

	<? $APPLICATION->includeComponent(
		'bitrix:main.mail.form', '',
		array(
			'FORM_ID' => $formId,
			'LAYOUT_ONLY' => true,
			'EDITOR_TOOLBAR' => true,
			'FIELDS' => array(
				array(
					'name'        => 'TITLE',
					'title'       => getMessage('CRM_MAIL_TEMPLATE_TITLE'),
					'placeholder' => getMessage('CRM_MAIL_TEMPLATE_TITLE_HINT'),
					'required'    => true,
					'short'       => true,
					'value'       => $arResult['ELEMENT']['TITLE'],
				),
				array(
					'name'        => 'ENTITY_TYPE_ID',
					'title'       => getMessage('CRM_MAIL_ENTITY_TYPE2'),
					'type'        => 'list',
					'placeholder' => getMessage('CRM_MAIL_ENTITY_TYPE_UNI'),
					'value'       => $arResult['ELEMENT']['ENTITY_TYPE_ID'],
					'list'        => $arResult['OWNER_TYPES'],
				),
				array(
					'type' => 'separator',
				),
				array(
					'name'  => 'EMAIL_FROM',
					'title' => getMessage('CRM_MAIL_TEMPLATE_EMAIL_FROM'),
					'type'  => 'from',
					'value' => $arResult['ELEMENT']['EMAIL_FROM'],
				),
				array(
					'name'        => 'SUBJECT',
					'title'       => getMessage('CRM_MAIL_TEMPLATE_SUBJECT'),
					'placeholder' => getMessage('CRM_MAIL_TEMPLATE_SUBJECT_HINT'),
					'value'       => $arResult['ELEMENT']['SUBJECT'],
					'menu'        => true,
				),
				array(
					'name'  => 'BODY',
					'type'  => 'editor',
					'value' => $arResult['ELEMENT']['BODY'],
					'menu'  => true,
				),
				array(
					'name'  => 'FILES',
					'type'  => 'files',
					'value' => $arResult['ELEMENT']['FILES'],
				),
			),
			'BUTTONS' => array(
				'submit' => array(
					'title' => getMessage('CRM_MAIL_TEMPLATE_SAVE_BTN'),
				),
				'cancel' => array(
					'title' => getMessage('CRM_MAIL_TEMPLATE_CANCEL_BTN'),
				),
			),
		)
	); ?>
</form>
<script type="text/javascript">
BX.ready(function()
{
	var rawFieldsMap = <?=\CUtil::phpToJsObject(\CCrmTemplateManager::getAllMaps()) ?>;
	var fieldsMap = {};

	for (var i = 0, item; i < rawFieldsMap.length; i++)
	{
		item = rawFieldsMap[i];

		fieldsMap[item.typeId] = item;
		fieldsMap[item.typeName] = item;
	}

	var menuItems = function(prefix, typeId, handler, level)
	{
		if (typeof typeId == 'undefined' || !BX.type.isFunction(handler))
			return [];

		var items = [];
		level = level > 1 ? level : 1;

		if (typeof fieldsMap[typeId] != 'undefined')
		{
			var map = fieldsMap[typeId];
			prefix = prefix ? [prefix, map.typeName].join('.') : map.typeName;

			for (var i = 0, code; i < map.fields.length; i++)
			{
				code = map.fields[i].id;
				if (code.match(/^UF_/))
					code += '('+map.fields[i].name+')';

				items.push({
					text: map.fields[i].name,
					value: '#'+[prefix, code].join('.')+'#',
					items: level < 2 ? menuItems(prefix, map.fields[i].id, handler, level+1) : [],
					onclick: handler
				});
			}
		}

		return items;
	};

	var formNode = BX('<?=\CUtil::jsEscape($formId) ?>');
	var mailForm = BXMainMailForm.getForm('<?=\CUtil::jsEscape($formId) ?>');

	BX.addCustomEvent(mailForm, 'MailForm:field:setMenuExt', function(form, field)
	{
		var typeId = formNode.elements['ENTITY_TYPE_ID'].value;

		var handler = function (event, item)
		{
			if (item.options.items && item.options.items.length > 0)
				return;

			field.insert(item.options.value);
			item.menuWindow.close();
		};

		field.setMenuExt(
			[
				{
					text: '<?=\CUtil::jsEscape(getMessage('CRM_MAIL_TEMPLATE_SENDER_MENU')) ?>',
					value: '#SENDER#',
					items: menuItems('', <?=\CCrmOwnerType::System ?>, handler),
					onclick: handler
				}
			]
			.concat(menuItems('', typeId, handler))
		);
	});

	<? if ($arResult['ELEMENT']['ID'] > 0 && empty($arResult['CAN_EDIT'])): ?>

	var submitButton = BX.findChildByClassName(formNode, 'main-mail-form-submit-button', true);
	if (submitButton)
		submitButton.style.opacity = 0.4;

	BX.addCustomEvent(mailForm, 'MailForm:submit', function (form, event)
	{
		return BX.PreventDefault(event);
	});

	<? endif ?>

	BX.addCustomEvent(mailForm, 'MailForm:footer:buttonClick', function(form, button)
	{
		if (BX.hasClass(button, 'main-mail-form-cancel-button'))
		{
			<? if (isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] != ''): ?>
			BX.localStorage.set(
				'onCrmMailTemplateCreate',
				{
					context: '<?=\CUtil::jsEscape($arResult['EXTERNAL_CONTEXT']) ?>'
				},
				10
			);
			<? endif ?>

			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if (slider && slider.close)
			{
				slider.close();
				return;
			}

			window.location = '<?=\CUtil::jsEscape(
				\CComponentEngine::makePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'])
			) ?>';
		}
	});

	mailForm.init();
});
</script>

<?

if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
{
	?>
			</div>
		</body>
	</html><?

	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die;
}
