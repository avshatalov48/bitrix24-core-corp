<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$crDomainSetup = !empty($arResult['SETTINGS']) && $arResult['SETTINGS']['type'] == 'crdomain'
	&& !empty($arResult['STATUS']['stage']) && in_array($arResult['STATUS']['stage'], array('owner-check', 'mx-check'));

$registeredDomain = (boolean) ($arResult['SETTINGS']['flags'] & CMail::F_DOMAIN_REG);

?>

<? if (empty($arResult['ERRORS']) && $crDomainSetup): ?>

	<div id="crdomain">

		<div class="mail-white-block mail-white-block-token">
			<div class="mail-white-block-title"><?=GetMessage('INTR_MAIL_DOMAIN_TITLE3'); ?></div>
			<div class="mail-set-item-block ">
				<div class="mail-set-item-block-r">
					<span id="domain_remove" class="webform-button webform-button-decline">
						<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_REMOVE'); ?></span><span class="webform-button-right"></span>
					</span>
				</div>
				<div class="mail-set-item-block-l">
					<span class="post-dialog-stat-text"><strong>@<?=$arResult['SETTINGS']['domain']; ?></strong></span>
					<span id="check-domain-status" class="post-dialog-stat-alert-wait">
						<? if ($arResult['STATUS']['stage'] == 'owner-check'): ?>
							<?=GetMessage('INTR_MAIL_DOMAIN_WAITCONFIRM'); ?>
						<? elseif ($arResult['STATUS']['stage'] == 'mx-check'): ?>
							<?=GetMessage('INTR_MAIL_DOMAIN_WAITMX'); ?>
						<? endif ?>
					</span>
				</div>
			</div>
			<form id="remove_domain_form" name="remove_domain_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
				<input type="hidden" name="page" value="domain">
				<input type="hidden" name="act" value="remove">
				<?=bitrix_sessid_post(); ?>
			</form>
		</div>

		<? if (!$registeredDomain): ?>
			<div id="crdomain-instr">
				<div class="mail-white-block">
					<div class="mail-white-block-inner">
						<div class="mail-white-block-title"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_TITLE'); ?></div>
						<div class="mail-white-block-title2"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1'); ?></div>
						<? if ($arResult['STATUS']['stage'] == 'owner-check'): ?>
							<div class="mail-white-block-text"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_PROMPT'); ?></div>
							<ul class="mail-white-block-list">
								<li class="mail-white-block-list-li">
									<?=str_replace(array('#SECRET_N#', '#SECRET_C#'), array($arResult['STATUS']['secrets']['name'], $arResult['STATUS']['secrets']['content']), GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_A')); ?>
								</li>
								<li class="mail-white-block-list-li">
									<span class="mail-white-block-list-or"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_OR'); ?></span>
									<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B'); ?>
									<div class="mail-info-message">
										<div class="mail-info-message-red"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_PROMPT'); ?></div>
										<div class="mail-info-message-text">
											<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_TYPE'); ?> <b>CNAME</b>
										</div>
										<div class="mail-info-message-text">
											<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAME'); ?>
											<?=str_replace(array('#SECRET_N#', '#DOMAIN#'), array($arResult['STATUS']['secrets']['name'], $arResult['SETTINGS']['domain']), GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAMEV')); ?>
										</div>
										<div class="mail-info-message-text">
											<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUE'); ?> <?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUEV'); ?>
										</div>
									</div>
								</li>
								<li class="mail-white-block-list-li">
									<span class="mail-white-block-list-or"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_OR'); ?></span>
									<?=str_replace(array('#SECRET_N#', '#DOMAIN#'), array($arResult['STATUS']['secrets']['name'], $arResult['SETTINGS']['domain']), GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_C')); ?><br><br>
									<?=str_replace(array('#SECRET_N#', '#DOMAIN#'), array($arResult['STATUS']['secrets']['name'], $arResult['SETTINGS']['domain']), GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_C_HINT')); ?>
								</li>
							</ul>
						<? endif ?>
						<div class="mail-domain-status">
							<? if ($arResult['STATUS']['stage'] == 'mx-check'): ?>
								<div class="mail-domain-status-title" style="font-weight: normal; "><?=GetMessage('INTR_MAIL_DOMAIN_STATUS_TITLE2'); ?></div>
							<? else: ?>
								<div class="mail-domain-status-title"><?=GetMessage('INTR_MAIL_DOMAIN_STATUS_TITLE'); ?></div>
							<? endif ?>
							<div class="mail-set-item-block-wrap">
								<div id="check-confirm-form" class="mail-set-item-block<? if ($arResult['STATUS']['stage'] == 'owner-check'): ?> post-status-error<? endif ?>">
									<div class="mail-set-item-right-btns">
										<span id="check-confirm-status" class="post-dialog-stat-alert"><?=GetMessage($arResult['STATUS']['stage'] == 'owner-check' ? 'INTR_MAIL_DOMAIN_STATUS_NOCONFIRM' : 'INTR_MAIL_DOMAIN_STATUS_CONFIRM'); ?></span>
										<span id="check-confirm" class="webform-button">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_CHECK'); ?></span><span class="webform-button-right"></span>
										</span>
									</div>
									<? $lastDomainCheck = isset($arResult['STATUS']['last_check']) ? strtotime($arResult['STATUS']['last_check']) : 0; ?>
									<? $nextDomainCheck = isset($arResult['STATUS']['next_check']) ? strtotime($arResult['STATUS']['next_check']) : 0; ?>
									<? $nextDomainCheck = $nextDomainCheck > time() ? $nextDomainCheck : 0; ?>
									<div id="check-confirm-text" class="mail-set-item-text<? if (!$lastDomainCheck || !$nextDomainCheck): ?> onerow<? endif ?>">
										<? if (!$lastDomainCheck && !$nextDomainCheck): ?>
											<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
										<? endif ?>
										<? if ($lastDomainCheck > 0): ?>
											<?=str_replace('#DATE#', FormatDate(
												array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
												$lastDomainCheck
											), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>
										<? endif ?>
										<? if ($nextDomainCheck > 0): ?>
											<? if ($lastDomainCheck > 0): ?><br><small><? endif ?>
											<?=str_replace('#DATE#', FormatDate(
												array('s' => 'sdiff', 'i' => 'idiff', 'H' => 'Hdiff', 'd' => 'ddiff', 'm' => 'mdiff', 'Y' => 'Ydiff'),
												time() - ($nextDomainCheck - time())
											), GetMessage('INTR_MAIL_CHECK_TEXT_NEXT')); ?>
											<? if ($lastDomainCheck > 0): ?></small><? endif ?>
										<? endif ?>
									</div>
								</div>
							</div>
						</div>
						<? if ($arResult['STATUS']['stage'] == 'owner-check'): ?>
							<div class="mail-white-block-text"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP1_HINT'); ?></div>
						<? endif ?>
					</div>
				</div>
				<div class="mail-white-block mail-white-block-lspace">
					<div class="mail-white-block-inner">
						<div class="mail-white-block-title2"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2'); ?></div>
						<div class="mail-white-block-text"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_PROMPT'); ?></div>
						<div class="mail-domain-status-title"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_TITLE'); ?></div>
						<div class="mail-info-message">
							<div class="mail-info-message-red"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_MXPROMPT'); ?></div>
							<div class="mail-info-message-text">
								<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_TYPE'); ?> <b>MX</b>
							</div>
							<div class="mail-info-message-text">
								<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_NAME'); ?>
								<?=str_replace('#DOMAIN#', $arResult['SETTINGS']['domain'], GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_NAMEV')); ?>
							</div>
							<div class="mail-info-message-text">
								<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_VALUE'); ?> <?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_VALUEV'); ?>
							</div>
							<div class="mail-info-message-text">
								<?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_PRIORITY'); ?> <b>10</b>
							</div>
						</div>
						<div class="mail-white-block-text mail-white-block-text-mtop"><?=GetMessage('INTR_MAIL_DOMAIN_INSTR_STEP2_HINT'); ?></div>
						<div class="mail-domain-status">
							<div class="mail-domain-status-title"><?=GetMessage('INTR_MAIL_DOMAIN_STATUS_TITLE'); ?></div>
							<div class="mail-set-item-block-wrap">
								<div id="check-mx-form" class="mail-set-item-block post-status-error">
									<div class="mail-set-item-right-btns">
										<span id="check-mx-status" class="post-dialog-stat-alert"><?=GetMessage($arResult['STATUS']['stage'] == 'owner-check' ? 'INTR_MAIL_DOMAIN_STATUS_NOCONFIRM' : 'INTR_MAIL_DOMAIN_STATUS_NOMX'); ?></span>
										<span id="check-mx" class="webform-button">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_CHECK'); ?></span><span class="webform-button-right"></span>
										</span>
									</div>
									<? $lastDomainCheck = isset($arResult['STATUS']['last_check']) ? strtotime($arResult['STATUS']['last_check']) : 0; ?>
									<? $nextDomainCheck = isset($arResult['STATUS']['next_check']) ? strtotime($arResult['STATUS']['next_check']) : 0; ?>
									<? $nextDomainCheck = $nextDomainCheck > time() ? $nextDomainCheck : 0; ?>
									<div id="check-mx-text" class="mail-set-item-text<? if (!$lastDomainCheck || !$nextDomainCheck): ?> onerow<? endif ?>">
										<? if (!$lastDomainCheck && !$nextDomainCheck): ?>
											<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
										<? endif ?>
										<? if ($lastDomainCheck > 0): ?>
											<?=str_replace('#DATE#', FormatDate(
												array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
												$lastDomainCheck
											), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>
										<? endif ?>
										<? if ($nextDomainCheck > 0): ?>
											<? if ($lastDomainCheck > 0): ?><br><small><? endif ?>
											<?=str_replace('#DATE#', FormatDate(
												array('s' => 'sdiff', 'i' => 'idiff', 'H' => 'Hdiff', 'd' => 'ddiff', 'm' => 'mdiff', 'Y' => 'Ydiff'),
												time() - ($nextDomainCheck - time())
											), GetMessage('INTR_MAIL_CHECK_TEXT_NEXT')); ?>
											<? if ($lastDomainCheck > 0): ?></small><? endif ?>
										<? endif ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<? else: ?>
				<div id="mail-info-message" class="mail-info-message"><?=GetMessage('INTR_MAIL_DOMAIN_SETUP_HINT'); ?></div>
			<? endif ?>
		</div>
	</div>

	<script type="text/javascript">

		BX.bind(BX('check-confirm'), 'click', function()
		{
			BX.addClass(BX('check-confirm'), 'webform-button-active webform-button-wait');

			BX.ajax({
				method: 'POST',
				url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=check',
				data: '<?=bitrix_sessid_get(); ?>',
				dataType: 'json',
				onsuccess: function(json)
				{
					BX.removeClass(BX('check-confirm'), 'webform-button-active webform-button-wait');

					if (json.result == 'ok')
					{
						if (!json.last_check || !json.next_check)
							BX.addClass(BX('check-confirm-text'), 'onerow');
						else
							BX.removeClass(BX('check-confirm-text'), 'onerow');

						var checkText = '';
						if (!json.last_check && !json.next_check)
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT_NA')); ?>';
						if (json.last_check)
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT')); ?>'.replace('#DATE#', json.last_check);
						if (json.next_check)
						{
							if (json.last_check)
								checkText += '<br><small>';
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT_NEXT')); ?>'.replace('#DATE#', json.next_check);
							if (json.last_check)
								checkText += '</small>';
						}

						BX('check-confirm-text').innerHTML = checkText;
						BX('check-confirm-status').innerHTML = BX.util.in_array(json.stage, ['mx-check', 'added'])
							? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_STATUS_CONFIRM')); ?>'
							: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_STATUS_NOCONFIRM')); ?>';

						if (BX.util.in_array(json.stage, ['mx-check', 'added']))
							BX.removeClass(BX('check-confirm-form'), 'post-status-error');
						else
							BX.addClass(BX('check-confirm-form'), 'post-status-error');
					}
				},
				onfailure: function()
				{
					BX.removeClass(BX('check-confirm'), 'webform-button-active webform-button-wait');
				}
			});
		});

		BX.bind(BX('check-mx'), 'click', function()
		{
			BX.addClass(BX('check-mx'), 'webform-button-active webform-button-wait');

			BX.ajax({
				method: 'POST',
				url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=check',
				data: '<?=bitrix_sessid_get(); ?>',
				dataType: 'json',
				onsuccess: function(json)
				{
					if (json.result == 'ok')
					{
						if (!json.last_check || !json.next_check)
							BX.addClass(BX('check-mx-text'), 'onerow');
						else
							BX.removeClass(BX('check-mx-text'), 'onerow');

						var checkText = '';
						if (!json.last_check && !json.next_check)
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT_NA')); ?>';
						if (json.last_check)
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT')); ?>'.replace('#DATE#', json.last_check);
						if (json.next_check)
						{
							if (json.last_check)
								checkText += '<br><small>';
							checkText += '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT_NEXT')); ?>'.replace('#DATE#', json.next_check);
							if (json.last_check)
								checkText += '</small>';
						}

						BX('check-mx-text').innerHTML = checkText;

						if (json.stage == 'added')
						{
							BX.removeClass(BX('check-mx-form'), 'post-status-error');
							BX('check-mx-status').innerHTML = '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_STATUS_NOMX')); ?>';

							window.location = '<?=$arParams['PATH_TO_MAIL_CFG_MANAGE'] ?>';
						}
						else
						{
							BX.removeClass(BX('check-mx'), 'webform-button-active webform-button-wait');

							BX.addClass(BX('check-mx-form'), 'post-status-error');
							BX('check-mx-status').innerHTML = json.stage == 'mx-check'
								? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_STATUS_NOMX')); ?>'
								: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_STATUS_NOCONFIRM')); ?>';
						}
					}
					else
					{
						BX.removeClass(BX('check-mx'), 'webform-button-active webform-button-wait');
					}
				},
				onfailure: function()
				{
					BX.removeClass(BX('check-mx'), 'webform-button-active webform-button-wait');
				}
			});
		});

	</script>
		
<? else: ?>

	<div id="domain" class="mail-set-wrap<? if (IsIE() == 8): ?> bx-ie bx-ie8<? endif ?>"<? if (empty($arResult['SETTINGS'])): ?> style="display: none; "<? endif ?>>

		<div class="post-dialog-domain mail-white-block-token mail-white-small">

			<? if ($arResult['SERVICE']): ?>
				<div class="mail-white-block-text"><?=GetMessage('INTR_MAIL_DOMAIN_TITLE2'); ?></div>
			<? else: ?>
				<div class="mail-white-block-title2"><?=GetMessage('INTR_MAIL_DOMAIN_TITLE'); ?></div>
			<? endif ?>

			<? if (!empty($arResult['ERRORS'])): ?>
				<div class="post-dialog-alert">
					<span class="post-dialog-alert-align"></span>
					<span class="post-dialog-alert-icon"></span>
					<span class="post-dialog-alert-text">
					<? foreach ($arResult['ERRORS'] as $k => $error): ?>
						<? if ($k > 0): ?><br><? endif ?>
						<?=$error ?>
					<? endforeach ?>
					</span>
				</div>
			<? elseif ($arResult['SERVICE'] && $arResult['STATUS']['stage'] != 'added'): ?>
				<div class="post-dialog-alert post-dialog-warning">
					<span class="post-dialog-alert-align post-dialog-warning-align"></span>
					<span class="post-dialog-alert-text post-dialog-warning-text">
					<? if ($arResult['STATUS']['stage'] == 'owner-check'): ?>
					<?=GetMessage('INTR_MAIL_DOMAIN_NOCONFIRM'); ?>
					<? elseif ($arResult['STATUS']['stage'] == 'mx-check'): ?>
					<?=GetMessage('INTR_MAIL_DOMAIN_NOMX'); ?>
					<? endif ?>
					</span>
				</div>
			<? endif ?>

			<form id="domain_form" name="domain_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
				<input type="hidden" name="page" value="domain">
				<input type="hidden" name="act" value="save">
				<input type="hidden" name="type" value="<?=($arResult['SERVICE'] && $arResult['SETTINGS']['type'] == 'crdomain' ? 'delegate' : 'connect'); ?>">
				<?=bitrix_sessid_post(); ?>
				<? if ($arResult['SERVICE']): ?>
					<div class="mail-set-item-block">
						<div class="mail-set-item-block-r">
							<span id="domain_remove" class="webform-button webform-button-decline">
								<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_REMOVE'); ?></span><span class="webform-button-right"></span>
							</span>
						</div>
						<div class="mail-set-item-block-l">
							<span class="post-dialog-stat-text"><strong>@<?=$arResult['SETTINGS']['domain']; ?></strong></span>
							<? if (!empty($arResult['STATUS']['stage']) && $arResult['STATUS']['stage'] == 'added'): ?>
								<a href="<?=$arParams['PATH_TO_MAIL_CFG_MANAGE'] ?>" class="mail-set-item-block-set-link"><strong><?=GetMessage('INTR_MAIL_MANAGE'); ?></strong></a>
							<? endif ?>
						</div>
					</div>
				<? else: ?>
					<div class="post-dialog-inp-item">
						<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_DOMAIN'); ?></span>
						<input id="domain-inp" name="domain" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['domain'])): ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['domain']); ?>"<? endif ?>>
					</div>
				<? endif ?>
				<? if (!$arResult['SERVICE'] || $arResult['SETTINGS']['type'] == 'domain'): ?>
					<div class="post-dialog-inp-item">
						<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_TOKEN'); ?>
							<span id="token-container"<? if (empty($arResult['SETTINGS']['domain'])): ?> style="display: none; "<? endif ?>>
								(<a id="token-link" onclick="window.open(this.href+'&'+(new Date()).getTime(), '_blank', 'height=480,width=720,top='+parseInt(screen.height/2-240)+',left='+parseInt(screen.width/2-360)); return false; " href="https://pddimp.yandex.ru/api2/admin/get_token?domain=<? if (!empty($arResult['SETTINGS']['domain'])) echo htmlspecialcharsbx($arResult['SETTINGS']['domain']); ?>" target="_blank"><?=GetMessage('INTR_MAIL_GET_TOKEN'); ?></a>)
							</span>
						</span>
						<input id="token" name="token" type="text" class="post-dialog-inp"<? if (!empty($arResult['SETTINGS']['token'])): ?> value="<?=htmlspecialcharsbx($arResult['SETTINGS']['token']); ?>"<? endif ?>>
					</div>
				<? endif ?>
				<div class="post-dialog-inp-item">
					<label>
						<input id="public" name="public" type="checkbox" value="Y" style="margin: 5px 3px 5px 0px; "<? if (empty($arResult['SETTINGS']['public']) || $arResult['SETTINGS']['public'] == 'Y'): ?> checked<? endif ?> />
						<?=GetMessage('INTR_MAIL_INP_PUBLIC_DOMAIN'); ?>
					</label>
				</div>
				<input type="submit" style="position: absolute; visibility: hidden; ">
			</form>

			<form id="remove_domain_form" name="remove_domain_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
				<input type="hidden" name="page" value="domain">
				<input type="hidden" name="act" value="remove">
				<?=bitrix_sessid_post(); ?>
			</form>

			<? if (!$arResult['SERVICE'] || $arResult['SETTINGS']['type'] == 'domain'): ?>
				<? if (empty($arResult['STATUS']['stage']) || $arResult['STATUS']['stage'] != 'added'): ?>
					<div class="mail-set-ifo-box mail-set-ifo-box-light mail-set-ifo-box-no-corner">
						<?=GetMessage('INTR_MAIL_DOMAIN_HELP'); ?>
					</div>
				<? endif ?>
			<? endif ?>

			<div class="post-dialog-footer">
				<a id="domain_save" href="#" class="webform-button webform-button-accept">
					<span class="webform-button-left"></span>
					<span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_SAVE'); ?></span>
					<span class="webform-button-right"></span>
				</a>
			</div>

		</div>

	</div>

	<script type="text/javascript">

		BX.bind(BX('domain_form'), 'submit', function(e)
		{
			BX.addClass(BX('domain_save'), 'webform-button-accept-active webform-button-wait');
		});

		BX.bind(BX('domain_save'), 'click', function(e)
		{
			e.preventDefault ? e.preventDefault() : e.returnValue = false;
			BX.addClass(BX('domain_save'), 'webform-button-accept-active webform-button-wait');
			BX('domain_form').submit();
			return false;
		});

		var handleDomain = function()
		{
			if (BX('domain-inp').value)
			{
				BX.adjust(BX('token-link'), {attrs: {
					href: 'https://pddimp.yandex.ru/api2/admin/get_token?domain='+BX('domain-inp').value
				}});
				BX.show(BX('token-container'), 'inline');
			}
			else
			{
				BX.hide(BX('token-container'), 'inline');
			}
		};

		BX.bind(BX('domain-inp'), 'blur', handleDomain);
		BX.bind(BX('domain-inp'), 'keyup', handleDomain);

	</script>

	<? if (empty($arResult['SETTINGS'])): ?>

		<div id="new-domain" class="mail-set-wrap<? if (IsIE() == 8): ?> bx-ie bx-ie8<? endif ?>" style="display: none; ">
			<div id="reg-block" class="post-dialog-domain mail-white-block-domain mail-white-small">
				<div class="mail-white-block-title"><?=GetMessage('INTR_MAIL_DOMAIN_CHOOSE_TITLE'); ?></div>
				<form id="reg_domain_form" name="domain_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
					<input type="hidden" name="page" value="domain">
					<input type="hidden" name="act" value="get">
					<?=bitrix_sessid_post(); ?>
					<div class="mail-set-item">
						<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_DOMAIN_CHOOSE_HINT'); ?></div>
						<input id="reg-domain-inp" name="domain" type="text" class="mail-set-inp">
						<div id="reg-domain-inp-hint" class="mail-inp-description"></div>
						<span id="reg_domain_whois" class="webform-button">
							<span class="webform-button-left"></span>
							<span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_WHOIS'); ?></span>
							<span class="webform-button-right"></span>
						</span>
					</div>
					<div style="position: relative; ">
						<div id="bad-dname-hint" style="z-index: 1000; position: absolute; display: none; left: 45px; ">
							<table class="popup-window popup-window-light" cellspacing="0">
								<tr class="popup-window-top-row">
									<td class="popup-window-left-column"><div class="popup-window-left-spacer"></div></td>
									<td class="popup-window-center-column"></td>
									<td class="popup-window-right-column"><div class="popup-window-right-spacer"></div></td>
								</tr>
								<tr class="popup-window-content-row">
									<td class="popup-window-left-column"></td>
									<td class="popup-window-center-column">
										<div class="popup-window-content" id="popup-window-content-input-alert-popup">
											<div id="mail-alert-popup-cont" class="mail-alert-popup-cont" style="display: block;">
												<div class="mail-alert-popup-text"><?=GetMessage('INTR_MAIL_DOMAIN_BAD_NAME_HINT'); ?></div>
											</div>
										</div>
									</td>
									<td class="popup-window-right-column"></td>
								</tr>
								<tr class="popup-window-bottom-row">
									<td class="popup-window-left-column"></td>
									<td class="popup-window-center-column"></td>
									<td class="popup-window-right-column"></td>
								</tr>
							</table>
							<div class="popup-window-light-angly popup-window-light-angly-top" style="left: 30px; margin-left: auto;"></div>
						</div>
					</div>
					<div id="domain_suggestions" class="mail-set-domain-offer-block" style="display: none; ">
						<div class="mail-set-domain-offer-title"><?=GetMessage('INTR_MAIL_DOMAIN_SUGGEST_TITLE'); ?>:</div>
						<ul id="domain_suggestions_list" class="mail-set-domain-offer-list"></ul>
						<a id="more_suggestions" href="#" onclick="RegDomain.suggest.more(this); return false; " class="webform-small-button" style="display: none; ">
							<span class="webform-small-button-left"></span>
							<span class="webform-small-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_SUGGEST_MORE'); ?></span>
							<span class="webform-small-button-right"></span>
						</a>
					</div>
					<div class="mail-white-block-domain-footer">
						<div>
							<input id="reg-public" name="public" type="checkbox" value="Y" class="mail-set-domain-checkbox" checked>
							<label for="reg-public" class="mail-set-domain-description"><?=GetMessage('INTR_MAIL_INP_PUBLIC_DOMAIN'); ?></label>
						</div>
						<div>
							<input id="reg-confirm-eula" name="eula" type="checkbox" value="Y" class="mail-set-domain-checkbox" onclick="BX('reg-block').classList.toggle('mail-white-block-domain-agree');">
							<label for="reg-confirm-eula" class="mail-set-domain-description"><?=GetMessage('INTR_MAIL_DOMAIN_EULA_CONFIRM'); ?></label>
						</div>
					</div>
					<div class="post-dialog-footer" style="border: none; padding-top: 10px; ">
						<div class="mail-white-block-btn-block">
							<span id="reg_domain_save"  class="webform-button webform-button-accept">
								<span class="webform-button-left"></span>
								<span class="webform-button-text"><?=GetMessage('INTR_MAIL_DOMAIN_SAVE2'); ?></span>
								<span class="webform-button-right"></span>
							</span>
							<span class="mail-white-block-disable-btn"></span>
						</div>
					</div>
				</form>
			</div>
		</div>

		<script type="text/javascript">

			var lastCheckedDomain = false;
			var getPopup = false;

			var RegDomain = {
				checkResults: {},
				check: function (e, callback)
				{
					var dname = BX('reg-domain-inp').value.trim();

					if (typeof e == 'object' && e.type != 'click' && lastCheckedDomain == dname)
						return;

					BX.removeClass(BX('reg-domain-inp').parentNode, 'mail-set-error mail-set-ok');
					BX.hide(BX('bad-dname-hint'), 'block');

					if (typeof e == 'object' && e.type != 'click')
						return;

					lastCheckedDomain = dname;

					BX.hide(BX('domain_suggestions'), 'block');
					BX.cleanNode(BX('domain_suggestions_list'), false);
					BX.hide(BX('more_suggestions'), 'inline-block');

					if (dname.length == 0)
					{
						BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_EMPTY_NAME')); ?>'});
						BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');

						return;
					}

					if (dname.match(/[a-z0-9]([a-z0-9-]*[a-z0-9])?\.ru$/i) && !dname.match(/^..--/i))
					{
						if (dname.length < 5)
						{
							BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_SHORT_NAME')); ?>'});
							BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');

							return;
						}
						else if (dname.length > 66)
						{
							BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_LONG_NAME')); ?>'});
							BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');

							return;
						}
					}

					if (!dname.match(/^[a-z0-9][a-z0-9-]*[a-z0-9]\.ru$/i) || dname.match(/^..--/i))
					{
						BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_BAD_NAME')); ?>'});
						BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');
						BX.show(BX('bad-dname-hint'), 'block');

						return;
					}

					var handleCheckResult = function (json)
					{
						if (typeof json == 'undefined')
							return;

						if (json.result == 'ok')
						{
							if (json.occupied)
							{
								BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_NAME_OCCUPIED')); ?>'});
								BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');
								RegDomain.suggest.request();
							}
							else
							{
								BX.adjust(BX('reg-domain-inp-hint'), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_NAME_FREE')); ?>'});
								BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-ok');

								if (callback && callback.call)
									callback.call();
							}
						}
						else
						{
							BX.adjust(BX('reg-domain-inp-hint'), {text: json.error});
							BX.addClass(BX('reg-domain-inp').parentNode, 'mail-set-error');
						}

						if (typeof RegDomain.checkResults[dname] == 'undefined')
							RegDomain.checkResults[dname] = json;
					};

					if (typeof RegDomain.checkResults[dname] == 'undefined')
					{
						BX.addClass(BX('reg_domain_whois'), 'webform-button-active webform-button-wait');

						BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=whois&domain='+dname,
							dataType: 'json',
							onsuccess: function (json)
							{
								BX.removeClass(BX('reg_domain_whois'), 'webform-button-active webform-button-wait');

								handleCheckResult(json);
							},
							onfailure: function()
							{
								BX.removeClass(BX('reg_domain_whois'), 'webform-button-active webform-button-wait');
							}
						});
					}
					else
					{
						handleCheckResult(RegDomain.checkResults[dname]);
					}
				},
				create: function (e)
				{
					var form = BX('reg_domain_form');

					var data = {};
					for (var i = 0; i < form.elements.length; i++)
					{
						if (form.elements[i].name)
							data[form.elements[i].name] = form.elements[form.elements[i].name].value;
					}

					if (!data['sdomain'] && !BX.hasClass(BX('reg-domain-inp').parentNode, 'mail-set-ok'))
					{
						RegDomain.check(e, RegDomain.create);

						return;
					}

					if (getPopup === false)
					{
						getPopup = new BX.PopupWindow('get-domain', null, {
							closeIcon: false,
							closeByEsc: false,
							overlay: true,
							lightShadow: true,
							buttons: [
								new BX.PopupWindowButton({
									className: 'popup-window-button-accept',
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_SAVE2')); ?>',
									events: {
										click: function()
										{
											this.popupWindow.close();

											BX.addClass(BX('reg_domain_save'), 'webform-button-accept-active webform-button-wait');

											BX.ajax({
												method: 'POST',
												url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=get',
												data: this.popupWindow.formData,
												dataType: 'json',
												onsuccess: function(json)
												{
													if (json.result == 'ok')
													{
														window.location.href = '#delegate';
														window.location.reload();
													}
													else
													{
														BX.removeClass(BX('reg_domain_save'), 'webform-button-accept-active webform-button-wait');
													}
												},
												onfailure: function()
												{
													BX.removeClass(BX('reg_domain_save'), 'webform-button-accept-active webform-button-wait');
												}
											});
										}
									}
								}),
								new BX.PopupWindowButtonLink({
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_CANCEL')); ?>',
									events: {
										click: function()
										{
											this.popupWindow.close();
										}
									}
								})
							]
						});
					}

					getPopup.formData = data;
					getPopup.setContent(BX.create('div', {
						attrs: {className: 'mail-confirm-domain-popup'},
						html: '<div class="mail-confirm-domain-popup-title"><?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_REG_CONFIRM_TITLE')); ?></div>\
							<div class="mail-confirm-domain-popup-text"><?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_REG_CONFIRM_TEXT')); ?></div>\
							<div class="mail-white-block-domain-footer"></div>'.replace('#DOMAIN#', data['sdomain'] || data['domain'])
					}));
					getPopup.show();
				},
				suggestResults: {},
				suggest: {
					request: function ()
					{
						BX.show(BX('domain_suggestions'), 'block');
						BX('domain_suggestions_list').innerHTML = '<li><?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_SUGGEST_WAIT')); ?><li>';

						var dname = BX('reg-domain-inp').value;

						var handleSuggestResult = function (json)
						{
							BX.cleanNode(BX('domain_suggestions_list'), false);

							if (typeof json == 'undefined')
								return;

							if (json.result == 'ok')
							{
								if (json.suggestions.length > 0)
								{
									var suggestions = '';
									for (i in json.suggestions)
									{
										suggestions += '<li' + (i >= 9 ? ' style="display: none; "' : '') + '><label>';
										suggestions += '<input type="radio" name="sdomain" value="' + json.suggestions[i] + '"> ';
										suggestions += json.suggestions[i] + '</label></li>';
									}

									BX('domain_suggestions_list').innerHTML = suggestions;
									BX.show(BX('more_suggestions'), 'inline-block');
								}
								else
								{
									BX.hide(BX('domain_suggestions'), 'block');
								}
							}

							if (typeof RegDomain.suggestResults[dname] == 'undefined')
								RegDomain.suggestResults[dname] = json;
						};

						if (typeof RegDomain.suggestResults[dname] == 'undefined')
						{
							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=suggest&domain='+dname,
								dataType: 'json',
								onsuccess: handleSuggestResult,
								onfailure: function()
								{
									BX.cleanNode(BX('domain_suggestions_list'), false);
								}
							});
						}
						else
						{
							handleSuggestResult(RegDomain.suggestResults[dname]);
						}
					},
					more: function (link)
					{
						var variants = BX.findChildren(BX('domain_suggestions_list'), {tag: 'li'}, false);
						var k = 0;

						for (var i = 0; i < variants.length && k < 9; i++)
						{
							if (variants[i].style.display == 'none')
							{
								variants[i].style.display = 'inline-block';
								k++;
							}
						};

						if (variants[variants.length-1].style.display != 'none')
							BX.hide(link, 'inline-block');
					}
				}
			};


			BX.bind(BX('reg-domain-inp'), 'keyup', RegDomain.check);
			BX.bind(BX('reg-domain-inp'), 'focus', RegDomain.check);
			BX.bind(BX('reg-domain-inp'), 'blur', RegDomain.check);

			BX.bind(BX('reg_domain_whois'), 'click', RegDomain.check);

			BX.bind(BX('reg_domain_form'), 'submit', function(e)
			{
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				RegDomain.check();
				return false;
			});

			BX.bind(BX('reg_domain_save'), 'click', RegDomain.create);

			if (window.location.hash.substr(1) == 'get')
			{
				BX.show(BX('new-domain'), 'block');

				BX.ajax({
					method: 'GET',
					url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=initget',
					dataType: 'json',
					onsuccess: function() {},
					onfailure: function() {}
				});
			}
			else
			{
				BX.show(BX('domain'), 'block');
			}

		</script>

	<? endif ?>

<? endif ?>

<script type="text/javascript">

	var deletePopup = false;
	BX.bind(BX('domain_remove'), 'click', function(e)
	{
		e.preventDefault ? e.preventDefault() : e.returnValue = false;

		if (deletePopup === false)
		{
			deletePopup = new BX.PopupWindow('delete-domain', null, {
				closeIcon: {'margin-right': '3px', 'margin-top': '13px'},
				closeByEsc: true,
				overlay: true,
				lightShadow: true,
				titleBar: {content: BX.create('span', {
					attrs: {className: 'mail-alert-top-popup-title'},
					text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAINREMOVE_CONFIRM')); ?>'
				})},
				content: BX.create('div', {
					attrs: {className: 'mail-alert-popup-del-box mail-alert-popup-del-text'},
					html: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAINREMOVE_CONFIRM_TEXT')); ?>'
				}),
				buttons: [
					new BX.PopupWindowButton({
						className: 'popup-window-button-decline',
						text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_REMOVE')); ?>',
						events: {
							click: function()
							{
								this.popupWindow.close();

								BX.addClass(BX('domain_remove'), 'webform-button-decline-active webform-button-wait');
								BX('remove_domain_form').submit();
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_CANCEL')); ?>',
						className: 'popup-window-button-link-cancel',
						events: {
							click: function()
							{
								this.popupWindow.close();
							}
						}
					})
				]
			});
		}

		deletePopup.show();

		return false;
	});

</script>
