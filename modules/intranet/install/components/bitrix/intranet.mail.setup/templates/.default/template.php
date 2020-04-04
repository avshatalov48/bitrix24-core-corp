<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

?>

<div class="post-dialog-wrap<? if (IsIE() == 8) { ?> bx-ie bx-ie8<? } ?>">

<? if ($arResult['STEP'] == 'choose') { ?>

<table class="post-dialog-title">
	<tr>
		<td class="post-dialog-title-text align-center">
			<?=GetMessage('INTR_MAIL_CHOOSE'); ?>
		</td>
		<td class="post-dialog-title-img"></td>
	</tr>
</table>
<? $k = 0;
foreach ($arParams['SERVICES'] as $id => $settings)
{
	$k++;
	?><a href="?STEP=setup&SERVICE=<?=$id; ?>" class="post-dialog-service-item post-dialog-service-name"<? if (strlen($settings['name']) > 15) { ?> style="font-size: 18px; "<? } ?>>
		<span class="post-dialog-serv-item-align"></span>
		<? if ($settings['icon']) { ?>
		<img src="<?=$settings['icon']; ?>" alt="<?=$settings['name']; ?>"/>
		<? } else {?>
		&nbsp;<?=$settings['name']; ?>&nbsp;
		<? } ?>
	</a><? if ($k%2 == 0) { ?><div class="post-dialog-border"></div><? }
} ?>
<? if ($k%2 != 0) { ?><div class="post-dialog-border"></div><? } ?><br><br>

<? } else if ($arResult['STEP'] == 'setup') { ?>

<table class="post-dialog-title">
	<tr>
		<td class="post-dialog-title-text">
			<? if (!$arParams['SERVICES'][$arResult['SERVICE']]['icon']) { ?>
			<span class="post-dialog-inp-label"><?=$arParams['SERVICES'][$arResult['SERVICE']]['name']; ?></span>
			<? } ?>
			<?=GetMessage('INTR_MAIL_SETUP'); ?>
		</td>
		<td class="post-dialog-title-img">
			<? if ($arParams['SERVICES'][$arResult['SERVICE']]['icon']) { ?>
			<img src="<?=$arParams['SERVICES'][$arResult['SERVICE']]['icon']; ?>" alt="<?=$arParams['SERVICES'][$arResult['SERVICE']]['name']; ?>"/>
			<? } ?>
		</td>
	</tr>
</table>

<? if (!empty($arResult['ID'])) { ?>
<? $lastMailCheck = CUserOptions::GetOption('global', 'last_mail_check_'.SITE_ID, null); ?>
<? $lastMailCheckSuccess = CUserOptions::GetOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>
<div id="post-dialog-status" class="post-dialog-status post-status-<?=(isset($lastMailCheckSuccess) ? ($lastMailCheckSuccess ? 'successful' : 'error') : 'na'); ?>">
	<div class="post-dialog-stat-left">
		<span class="post-dialog-stat-label"><?=GetMessage('INTR_MAIL_STATUS'); ?>:</span>
		<span id="post-dialog-stat-text" class="post-dialog-stat-text">
		<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0) { ?>
		<?=str_replace('#DATE#', FormatDate(
			array("s" => "sago", "i" => "iago", "H" => "Hago", "d" => "dago", "m" => "mago", "Y" => "Yago"),
			intval($lastMailCheck)
		), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>
		<? } else { ?>
		<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
		<? } ?>
		</span>
		<span id="post-dialog-stat-alert" class="post-dialog-stat-alert">
		<? if (isset($lastMailCheckSuccess)) { ?>
			<? if ($lastMailCheckSuccess) { ?>
			<?=GetMessage('INTR_MAIL_CHECK_SUCCESS'); ?>
			<? } else { ?>
			<?=GetMessage('INTR_MAIL_CHECK_ERROR'); ?>
			<? } ?>
		<? } else { ?>
			<?=GetMessage('INTR_MAIL_CHECK_NA'); ?>
		<? } ?>
		</span>
		<span id="post-dialog-stat-info" class="post-dialog-stat-info" style="display: none; "></span>
	</div>
	<a id="settings_check" class="post-dialog-btn" href="#">
		<span class="post-dialog-btn-text"><?=GetMessage('INTR_MAIL_CHECK'); ?></span>
	</a>
</div>
<? } ?>

<? if (!empty($arResult['ERRORS'])) { ?>
<div class="post-dialog-alert">
	<span class="post-dialog-alert-align"></span>
	<span class="post-dialog-alert-icon"></span>
	<span class="post-dialog-alert-text">
	<? foreach ($arResult['ERRORS'] as $k => $error) { if ($k > 0) { ?><br><? } echo $error; } ?>
	</span>
</div>
<? } ?>

<form id="settings_form" name="settings_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
	<input type="hidden" name="STEP" value="setup">
	<input type="hidden" name="SERVICE" value="<?=$arResult['SERVICE']; ?>">
	<?=bitrix_sessid_post(); ?>
	<? foreach ($arParams['OPTIONS'] as $option) { ?>
	<? if (empty($arParams['SERVICES'][$arResult['SERVICE']][$option])) { ?>
	<? switch ($option) {
		case 'server': ?>
			<div class="post-dialog-inp-item post-dialog-inp-serv">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_SERVER'); ?></span>
				<input id="server" name="server" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['server'])) { ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['server']); ?>"<? } ?>>
			</div>
			<? break;
		case 'port': ?>
			<div class="post-dialog-inp-item post-dialog-inp-post">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PORT'); ?></span>
				<input id="port" name="port" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['port'])) { ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['port']); ?>"<? } ?>>
			</div>
			<? break;
		case 'encryption': ?>
			<div class="post-dialog-inp-item">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_ENCRYPT'); ?></span>
				<span class="post-dialog-inp-select-wrap">
					<select name="encryption" class="post-dialog-inp-select">
						<option value="Y"<? if (empty($arResult['SETTINGS']['encryption']) || $arResult['SETTINGS']['encryption'] != 'N') { ?> selected="selected"<? } ?>><?=GetMessage('INTR_MAIL_INP_ENCRYPT_YES'); ?></option>
						<option value="N"<? if (isset($arResult['SETTINGS']['encryption']) && $arResult['SETTINGS']['encryption'] == 'N') { ?> selected="selected"<? } ?>><?=GetMessage('INTR_MAIL_INP_ENCRYPT_NO'); ?></option>
					</select>
				</span>
			</div>
			<? break;
		case 'link': ?>
			<div class="post-dialog-inp-item">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LINK'); ?></span>
				<input id="link" name="link" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['link'])) { ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['link']); ?>"<? } ?>/>
			</div>
			<? break;
		case 'login': ?>
			<div class="post-dialog-inp-item">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LOGIN'); ?></span>
				<input name="login" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['login'])) { ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['login']); ?>"<? } ?>>
			</div>
			<? break;
		case 'password': ?>
			<div class="post-dialog-inp-item">
				<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PASS'); ?></span>
				<input name="password" type="password" class="post-dialog-inp"/>
			</div>
			<? break;
	} ?>
	<? } ?>
	<? } ?>
	<input type="submit" style="visibility: hidden; ">
</form>

<form id="remove_form" name="remove_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
	<input type="hidden" name="STEP" value="remove">
	<?=bitrix_sessid_post(); ?>
</form>

<div class="post-dialog-footer">
	<? if ($arResult['SERVICE']) { ?>
	<a id="settings_save" href="#" class="webform-button webform-button-create">
		<span class="webform-button-left"></span>
		<span class="webform-button-text"><?=GetMessage(empty($arResult['ID']) ? 'INTR_MAIL_CREATE' : 'INTR_MAIL_UPDATE'); ?></span>
		<span class="webform-button-right"></span>
	</a>
	<? } ?>
	<? if (!empty($arResult['ID'])) { ?>
	<a id="settings_remove" href="#" class="webform-button">
		<span class="webform-button-left"></span>
		<span class="webform-button-text"><?=GetMessage('INTR_MAIL_REMOVE'); ?></span>
		<span class="webform-button-right"></span>
	</a>
	<? } ?>
</div>

<script type="text/javascript">

	BX.bind(BX('settings_save'), 'click', function(e) {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		var formElements = BX('settings_form').elements;
		for (var i = 0; i < formElements.length; i++)
		{
			if (formElements[i].value == formElements[i].getAttribute('data-placeholder'))
				formElements[i].value = '';
		}
		BX('settings_form').submit();
		return false;
	});
	BX.bind(BX('settings_remove'), 'click', function(e) {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		if (confirm('<?=GetMessage('INTR_MAIL_REMOVE_CONFIRM'); ?>'))
			BX('remove_form').submit();
		return false;
	});
	BX.bind(BX('settings_check'), 'click', function(e) {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		var btn = this;
		BX.addClass(btn, 'post-dialog-btn-wait');
		BX.ajax({
			url: location.pathname+'?STEP=check',
			dataType: 'json',
			onsuccess: function(json)
			{
				BX.removeClass(btn, 'post-dialog-btn-wait');
				BX('post-dialog-stat-text').innerHTML = '<?=str_replace(
					'#DATE#',
					GetMessage('INTR_MAIL_CHECK_JUST_NOW'),
					GetMessage('INTR_MAIL_CHECK_TEXT')
				); ?>';

				if (json.result == 'ok')
				{
					BX.removeClass(BX('post-dialog-status'), 'post-status-error');
					BX.addClass(BX('post-dialog-status'), 'post-status-successful');
					BX.adjust(BX('post-dialog-stat-alert'), {text: '<?=GetMessage('INTR_MAIL_CHECK_SUCCESS'); ?>'});
					BX.adjust(BX('post-dialog-stat-info'), {style: {display: 'none'}});
				}
				else
				{
					BX.removeClass(BX('post-dialog-status'), 'post-status-successful');
					BX.addClass(BX('post-dialog-status'), 'post-status-error');
					BX.adjust(BX('post-dialog-stat-alert'), {text: '<?=GetMessage('INTR_MAIL_CHECK_ERROR'); ?>'});
					BX.adjust(BX('post-dialog-stat-info'), {
						props: {title: json.error},
						style: {display: 'inline-block'}
					});
				}
			},
			onfailure: function()
			{
				BX.removeClass(btn, 'post-dialog-btn-wait');
			}
		});
		return false;
	});

	var prompt = function(input, text, isFake)
	{
		if (!input || !input.nodeName || input.nodeName.toUpperCase() != 'INPUT')
			return;

		isFake = isFake == false ? false : true;

		if (isFake)
			BX.adjust(input, {attrs: {'data-placeholder': text}});

		if (input.value == '')
		{
			input.style.color = '#a9a9a9';
			input.value = text;
		}

		BX.bind(input, 'focus', function() {
			if (input.value == text)
			{
				input.style.color = '';
				input.value = '';
			}
		});
		BX.bind(input, 'blur', function() {
			if (input.value == '')
			{
				input.style.color = '#a9a9a9';
				input.value = text;
			}
		});
	};

	prompt(document.getElementById('link'), 'http://mail.example.com');
	prompt(document.getElementById('server'), 'imap.example.com');
	prompt(document.getElementById('port'), '993', false);

</script>

<? } else if ($arResult['STEP'] == 'confirm') { ?>

<? if (!empty($arResult['SERVICE'])) { ?>
<table class="post-dialog-title post-dialog-success-compl">
	<tr>
		<td class="post-dialog-title-text align-center">
			<? if ($arParams['SERVICES'][$arResult['SERVICE']]['icon']) { ?>
			<img src="<?=$arParams['SERVICES'][$arResult['SERVICE']]['icon']; ?>" alt="<?=$arParams['SERVICES'][$arResult['SERVICE']]['name']; ?>"/>
			<? } else {?>
			<span class="post-dialog-inp-label"><?=$arParams['SERVICES'][$arResult['SERVICE']]['name']; ?></span>
			<? } ?>
		</td>
		<td class="post-dialog-title-img"></td>
	</tr>
</table>
<? } ?>
<table class="post-dialog-success-table">
	<tr>
		<td class="post-dialog-success-cell">
			<div class="post-dialog-success-caption"><?=GetMessage($arResult['ACT'] == 'remove' ? 'INTR_MAIL_REMOVE_COMPLETE' : 'INTR_MAIL_COMPLETE'); ?></div>
			<?=GetMessage('INTR_MAIL_SETUP_HINT'); ?>
			<br><br><br>
			<a class="post-dialog-success-link" href="<?=$APPLICATION->GetCurPage(); ?>" <? if ($arResult['ACT'] != 'remove') { ?> target="_blank"<? } ?>>
				<?=GetMessage($arResult['ACT'] == 'remove' ? 'INTR_MAIL_SETUP_LINK' : 'INTR_MAIL_LINK'); ?>
			</a>
		</td>
	</tr>
</table>

<? } ?>

</div>
