<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

function get_plural_messages($prefix)
{
	global $MESS;

	$result = array();

	$k = 0;
	while ($form = getMessage($prefix.'PLURAL_'.++$k))
		$result[] = $form;

	return $result;
}

// http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html
function plural_form($n, $forms)
{
	switch (LANGUAGE_ID)
	{
		case 'ru':
		case 'ua':
			$p = $n%10 == 1 && $n%100 != 11 ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
			break;
		case 'en':
		case 'de':
		case 'es':
			$p = $n == 1 ? 0 : 1;
			break;
	}

	return isset($forms[$p]) ? $forms[$p] : end($forms);
}

$limitedLicense = false;
if (\CModule::includeModule('bitrix24'))
{
	if ($limitedLicense = !in_array(CBitrix24::getLicenseType(), array('company', 'nfr', 'edu', 'demo')))
		\CBitrix24::initLicenseInfoPopupJS();
}

$socservAvailable = \CModule::includeModule('socialservices');
$domainAdded = false;
foreach ($arParams['SERVICES'] as $id => $settings)
{
	if ($settings['type'] == 'imap')
	{
		if ($socservAvailable)
		{
			if ($settings['name'] == 'gmail' && $settings['server'] == 'imap.gmail.com')
			{
				$oauthClient = new \CSocServGoogleOAuth();
				if ($oauthClient->checkSettings())
				{
					$arParams['SERVICES'][$id]['oauth'] = $oauthClient;
					$arParams['SERVICES'][$id]['oauth_scope'] = array(
						'email', 'https://mail.google.com/',
					);
				}
			}

			if ($settings['name'] == 'outlook.com' && $settings['server'] == 'imap-mail.outlook.com')
			{
				$oauthClient = new \CSocServLiveIDOAuth();
				if ($oauthClient->checkSettings())
				{
					$arParams['SERVICES'][$id]['oauth'] = $oauthClient;
					$arParams['SERVICES'][$id]['oauth_scope'] = array(
						'wl.emails', 'wl.imap', 'wl.offline_access',
					);
				}
			}
		}
	}

	if ($settings['type'] != 'imap')
	{
		if ($settings['type'] == 'controller' && $settings['name'] == 'bitrix24')
		{
			$b24Settings = $settings;
		}
		else if ($settings['type'] == 'domain' || $settings['type'] == 'crdomain')
		{
			$domainSettings = $settings;
			$domainStatus = isset($arParams['DOMAIN_STATUS']['stage']) ? $arParams['DOMAIN_STATUS']['stage'] : false;
			$domainAdded  = strtolower($domainStatus) == 'added';
		}
	}
}

if (!empty($arParams['MAILBOX']))
{
	if (!is_array($arParams['MAILBOX']['OPTIONS']))
		$arParams['MAILBOX']['OPTIONS'] = array();
	if (!is_array($arParams['MAILBOX']['OPTIONS']['flags']))
		$arParams['MAILBOX']['OPTIONS']['flags'] = array();
	if (!is_array($arParams['MAILBOX']['OPTIONS']['imap']))
		$arParams['MAILBOX']['OPTIONS']['imap'] = array();
	if (!is_array($arParams['MAILBOX']['OPTIONS']['imap']['income']))
		$arParams['MAILBOX']['OPTIONS']['imap']['income'] = array();
	if (!is_array($arParams['MAILBOX']['OPTIONS']['imap']['outcome']))
		$arParams['MAILBOX']['OPTIONS']['imap']['outcome'] = array();

	if (strpos($arParams['MAILBOX']['NAME'], '@') > 0)
		$emailAddress = $arParams['MAILBOX']['NAME'];
	elseif (strpos($arParams['MAILBOX']['LOGIN'], '@') > 0)
		$emailAddress = $arParams['MAILBOX']['LOGIN'];

	$isOauthMailbox = false;
	$settings = $arParams['SERVICES'][$arParams['MAILBOX']['SERVICE_ID']];
	if (!empty($settings['oauth']))
	{
		$provider = $settings['name'] == 'gmail' ? 'google'
			: ($settings['name'] == 'outlook.com' ? 'liveid' : false);

		if ($provider)
		{
			$isOauthMailbox = preg_match(
				sprintf('/^\x00oauth\x00%s\x00(\d+)$/', preg_quote($provider, '/')),
				$arParams['MAILBOX']['PASSWORD']
			);
		}
	}
}

?>

<? $isUserAdmin = $USER->isAdmin() || $USER->canDoOperation('bitrix24_config'); ?>

<? $showB24Block = IsModuleInstalled('bitrix24') && !empty($b24Settings) && !empty($arParams['CR_DOMAINS']); ?>
<? $showDomainBlock = IsModuleInstalled('bitrix24') || !empty($domainSettings) || in_array(LANGUAGE_ID, array('ru', 'ua')); ?>

<? $b24Mailbox = !empty($b24Settings) && !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVICE_ID'] == $b24Settings['id']; ?>
<? $domainMailbox = !empty($domainSettings) && !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVICE_ID'] == $domainSettings['id']; ?>
<? $imapMailbox = !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVER_TYPE'] == 'imap'; ?>

<?

if ($isUserAdmin && !empty($domainSettings) && !$domainAdded)
{
	$defaultBlock = 'domain';
}
else
{
	if ($imapMailbox || !$b24Mailbox && !$domainAdded)
		$defaultBlock = 'imap';
	if ($b24Mailbox)
		$defaultBlock = 'bitrix24';
	if (!$imapMailbox && !$b24Mailbox && $domainAdded)
		$defaultBlock = 'domain';
}

?>

<div class="mail-set-wrap<? if ($showB24Block xor $showDomainBlock): ?> mail-set-wrap-small<? endif ?>"
	<? if (!$showB24Block && !$showDomainBlock): ?> style="max-width: 1100px; "<? endif ?>>
	<div class="mail-top-title-block"<? if (!$showB24Block && !$showDomainBlock): ?> style="margin-bottom: 20px; "<? endif ?>>
		<div class="mail-top-title-icon"></div>
		<? if ($showB24Block): ?>
			<?=getMessage('INTR_MAIL_DESCR_B24_CRM', array('#DOMAIN#' => htmlspecialcharsbx(reset($arParams['CR_DOMAINS'])))) ?>
		<? elseif ($showDomainBlock): ?>
			<?=getMessage('INTR_MAIL_DESCR_BOX_CRM') ?>
		<? else: ?>
			<?=getMessage(isModuleInstalled('bitrix24') ? 'INTR_MAIL_HINT_B24_CRM' : 'INTR_MAIL_HINT_BOX_CRM') ?>
		<? endif ?>
	</div>
	<table class="mail-block-table" id="mail-block-table"<? if (!$showB24Block && !$showDomainBlock): ?> style="display: none; "<? endif ?>>
		<tr class="mail-block-top">
			<td class="mail-block mail-block-imap<? if ($defaultBlock == 'imap'): ?> mail-block-active<? endif ?>" data-block="mail-set-third">
				<div class="mail-block-title">
					<span class="mail-block-title-icon"></span>
					<span class="mail-block-title-text"><?=getMessage('INTR_MAIL_IMAP_TITLE') ?></span>
				</div>
				<? if ($imapMailbox): ?>
					<div id="imap_block_descr_mailbox" class="mail-block-text">
						<? if (empty($emailAddress)): ?>
							<?=getMessage('INTR_MAIL_IMAP_DESCR_MAILBOX_UN') ?>
						<? else: ?>
							<?=getMessage('INTR_MAIL_IMAP_DESCR_MAILBOX', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
						<? endif ?>
						<? if (in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags'])): ?>
							<div class="mail-block-crm mail-block-crm-success">CRM</div>
						<? elseif ($arParams['CRM_AVAILABLE']): ?>
							<div class="mail-block-crm mail-block-crm-disconnect">CRM</div>
						<? endif ?>
					</div>
				<? endif ?>
				<div id="imap_block_descr_nomailbox" class="mail-block-text" style="<? if ($imapMailbox): ?>display: none; <? endif ?><? if (!$showB24Block && !$showDomainBlock): ?>min-height: 0; <? endif ?>">
					<?=getMessage('INTR_MAIL_IMAP_DESCR_CRM') ?>
					<? if ($showB24Block || $showDomainBlock): ?>
						<img class="mail-block-icon-list" src="/bitrix/components/bitrix/intranet.mail.setup/templates/.default/images/<?=getMessage('INTR_MAIL_IMAP_DESCR_ICONS') ?>"/>
						<img class="mail-block-icon-list-colorless" src="/bitrix/components/bitrix/intranet.mail.setup/templates/.default/images/<?=getMessage('INTR_MAIL_IMAP_DESCR_ICONS_CL') ?>"/>
					<? endif ?>
				</div>
			</td>
			<? if ($showB24Block): ?>
				<td class="mail-block-space"></td>
				<td class="mail-block mail-block-b24<? if ($defaultBlock == 'bitrix24'): ?> mail-block-active<? endif ?>" data-block="mail-set-first">
					<div class="mail-block-title">
						<span class="mail-block-title-at">@</span>
						<span class="mail-block-title-text"><?=htmlspecialcharsbx(reset($arParams['CR_DOMAINS'])) ?></span>
					</div>
					<? if ($b24Mailbox): ?>
						<div id="b24_block_descr_mailbox" class="mail-block-text">
							<?=getMessage('INTR_MAIL_B24_DESCR_MAILBOX', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
							<? if (in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags'])): ?>
								<div class="mail-block-crm mail-block-crm-success">CRM</div>
							<? elseif ($arParams['CRM_AVAILABLE']): ?>
								<div class="mail-block-crm mail-block-crm-disconnect">CRM</div>
							<? endif ?>
						</div>
					<? endif ?>
					<div id="b24_block_descr_nomailbox" class="mail-block-text"<? if ($b24Mailbox): ?> style="display: none; "<? endif ?>>
						<?=getMessage('INTR_MAIL_B24_DESCR_CRM', array('#DOMAIN#' => htmlspecialcharsbx(reset($arParams['CR_DOMAINS'])))) ?>
					</div>
				</td>
			<? endif ?>
			<? if ($showDomainBlock): ?>
				<td class="mail-block-space"></td>
				<td class="mail-block mail-block-own<? if ($defaultBlock == 'domain'): ?> mail-block-active<? endif ?>" data-block="mail-set-second">
					<div class="mail-block-title">
						<? if (empty($domainSettings) || !$isUserAdmin && !$domainAdded): ?>
							<span class="mail-block-title-icon"></span>
							<span class="mail-block-title-text"><?=getMessage('INTR_MAIL_DOMAIN_TITLE') ?></span>
						<? else: ?>
							<span class="mail-block-title-at">@</span>
							<span class="mail-block-title-text"><?=htmlspecialcharsbx($domainSettings['server']) ?></span>
						<? endif ?>
					</div>
					<? if (empty($domainSettings) || !$isUserAdmin && !$domainAdded): ?>
						<div class="mail-block-text">
							<?=getMessage(isModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24' : 'INTR_MAIL_DOMAIN_DESCR_BOX') ?>
						</div>
					<? else: ?>
						<? if ($domainMailbox && $domainAdded): ?>
							<div id="domain_block_descr_mailbox" class="mail-block-text">
								<?=getMessage(isModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24_DOMAIN' : 'INTR_MAIL_DOMAIN_DESCR_BOX_DOMAIN', array('#DOMAIN#' => htmlspecialcharsbx($domainSettings['server']))) ?>
								<br><br>
								<?=getMessage('INTR_MAIL_DOMAIN_DESCR_MAILBOX', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
								<? if (in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags'])): ?>
									<div class="mail-block-crm mail-block-crm-success">CRM</div>
								<? elseif ($arParams['CRM_AVAILABLE']): ?>
									<div class="mail-block-crm mail-block-crm-disconnect">CRM</div>
								<? endif ?>
							</div>
						<? endif ?>
						<div id="domain_block_descr_nomailbox" class="mail-block-text"<? if ($domainMailbox && $domainAdded): ?> style="display: none; "<? endif ?>>
							<?=getMessage(
								isModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24_DOMAIN' : 'INTR_MAIL_DOMAIN_DESCR_BOX_DOMAIN',
								array('#DOMAIN#' => htmlspecialcharsbx($domainSettings['server']))
							) ?><br><br>
							<?=getMessage($domainAdded ? 'INTR_MAIL_DOMAIN_DESCR_NOMAILBOX_CRM' : 'INTR_MAIL_DOMAIN_DESCR_WAIT') ?>
						</div>
					<? endif ?>
				</td>
			<? endif ?>
		</tr>
		<tr class="mail-block-bottom">
			<td class="mail-block<? if ($defaultBlock == 'imap'): ?> mail-block-active<? endif ?>" data-block="mail-set-third">
				<div class="mail-block-footer">
					<span class="mail-block-btn" id="mail-set-third-btn"<? if (!$showB24Block && !$showDomainBlock): ?> style="position: absolute; visibility: hidden; "<? endif ?>>
						<?=getMessage($imapMailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE') ?></span>
				</div>
			</td>
			<? if ($showB24Block): ?>
				<td class="mail-block-space"></td>
				<td class="mail-block<? if ($defaultBlock == 'bitrix24'): ?> mail-block-active<? endif ?>"  data-block="mail-set-first">
					<div class="mail-block-footer">
						<span class="mail-block-btn" id="mail-set-first-btn"><?=getMessage($b24Mailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE') ?></span>
					</div>
				</td>
			<? endif ?>
			<? if ($showDomainBlock): ?>
				<td class="mail-block-space"></td>
				<td class="mail-block<? if ($defaultBlock == 'domain'): ?> mail-block-active<? endif ?>" data-block="mail-set-second">
					<div class="mail-block-footer">
						<span class="mail-block-btn" id="mail-set-second-btn"><?=getMessage($domainMailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE') ?></span>
					</div>
				</td>
			<? endif ?>
		</tr>
	</table>
	<div class="mail-set-block-wrap" id="mail-set-block-wrap"<? if (!$showB24Block && !$showDomainBlock): ?> style="margin-top: 20px; "<? endif ?>>
		<div class="mail-set-block mail-set-block-active" id="mail-set-block">
			<? if ($showB24Block): ?>
				<div id="mail-set-first" class="mail-set-first-wrap"<? if ($defaultBlock == 'bitrix24'): ?> style="display: block;"<? endif ?>>
					<div class="mail-set-first">

						<? if ($b24Mailbox): ?>
							<div id="b24_setup_form">

								<? $lastMailCheck = CUserOptions::getOption('global', 'last_mail_check_'.SITE_ID, null); ?>
								<? $lastMailCheckSuccess = CUserOptions::getOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

								<div class="mail-set-title">
									<?=getMessage('INTR_MAIL_MAILBOX_MANAGE', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
								</div>
								<? if ($arParams['CRM_AVAILABLE'] && !empty($arParams['CRM_PRECONNECT'])): ?>
								<div name="post-dialog-alert" class="post-dialog-alert">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text">
										<?=getMessage('INTR_MAIL_CRM_PRECONNECT') ?>
										<? if (!empty($arParams['IMAP_ERROR'])): ?>
											&mdash; <?=htmlspecialcharsbx($arParams['IMAP_ERROR']) ?>
											<? if (!empty($arParams['IMAP_ERROR_EXT'])): ?>
												<span style="font-weight: normal; ">
													(<a href="#" onclick="this.style.display = 'none'; BX.findNextSibling(this, {class: 'post-dialog-alert-text-ext'}).style.display = ''; setPost.animCurrent(); return false; "><?=getMessage('INTR_MAIL_ERROR_EXT') ?></a><?
													?><span class="post-dialog-alert-text-ext" style="display: none; "><?=htmlspecialcharsbx($arParams['IMAP_ERROR_EXT']) ?></span>)</span>
											<? endif ?>
										<? endif ?>
									</span>
								</div>
								<? else: ?>
								<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
								</div>
								<? endif ?>
								<div class="mail-set-item-block-wrap">
									<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_STATUS') ?></div>
									<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess): ?> post-status-error<? endif ?>">
										<div class="mail-set-item-block-r">
											<span id="b24_delete_form" class="webform-button webform-button-decline">
												<?=getMessage('INTR_MAIL_MAILBOX_DELETE') ?>
											</span>&nbsp;
										</div>
										<div class="mail-set-item-block-l">
											<span name="status-text" class="post-dialog-stat-text">
												<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0): ?>
													<?=str_replace('#DATE#', FormatDate(
														array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
														intval($lastMailCheck)
													), getMessage('INTR_MAIL_CHECK_TEXT')) ?>:
												<? else: ?>
													<?=getMessage('INTR_MAIL_CHECK_TEXT_NA') ?>
												<? endif ?>
											</span>
											<span name="status-alert" class="post-dialog-stat-alert">
												<? if (isset($lastMailCheckSuccess)): ?>
													<?=getMessage($lastMailCheckSuccess ? 'INTR_MAIL_CHECK_SUCCESS' : 'INTR_MAIL_CHECK_ERROR') ?>
												<? endif ?>
											</span>
											<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>
											<span id="b24_check_form" class="webform-button">
												<?=getMessage('INTR_MAIL_CHECK') ?>
											</span>
										</div>
										<? $isCrmConfig = in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags']); ?>
										<? if ($isCrmConfig || $arParams['CRM_AVAILABLE']): ?>
											<div class="mail-set-item-block-bottom">
												<div class="mail-set-item-block-l">
													<span class="post-dialog-stat-text"><?=getMessage('INTR_MAIL_CRM_CONNECT2') ?>:</span>
													<? if ($isCrmConfig): ?>
														<span class="post-dialog-stat-alert"><?=getMessage('INTR_MAIL_CRM_ENABLED') ?></span>
														<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('b24_setup_crm_options', BX('b24_setup_crm_options').offsetHeight == 0); ">
															<?=getMessage('INTR_MAIL_CRM_CONFIG') ?>
														</span>
														<span class="webform-button mail-set-nomargin" id="b24_disable_crm">
															<?=getMessage('INTR_MAIL_CRM_DISABLE') ?>
														</span>
													<? else: ?>
														<span class="post-status-error">
															<?=getMessage(empty($arParams['CRM_PRECONNECT']) ? 'INTR_MAIL_CRM_DISABLED' : 'INTR_MAIL_CRM_ALMOST') ?>
														</span>
														<? if (empty($arParams['CRM_PRECONNECT'])): ?>
															<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('b24_setup_crm_options', BX('b24_setup_crm_options').offsetHeight == 0); ">
																<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
															</span>
														<? else: ?>
															<span class="webform-button webform-button-disable mail-set-nomargin" name="enablecrm-button">
																<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
															</span>
														<? endif ?>
													<? endif ?>
												</div>
											</div>
											<div class="mail-set-item-block-crm" id="b24_setup_crm_options" style="display: none; margin-right: 25px; ">
												<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper" style="margin-top: 10px; ">
													<form id="b24_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm_form">
														<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>" >
														<?=bitrix_sessid_post() ?>
														<div class="mail-set-item-block-crm-wrapper-dec">
															<? if (empty($arParams['MAILBOX']['PASSWORD'])): ?>
																<span class="mail-set-crm-title" style="border: none; "><?=getMessage('INTR_MAIL_INP_PASS') ?></span>
																<div class="post-dialog-inp-item" style="display: inline-block; margin-bottom: 0px; ">
																	<input name="password" type="password" class="post-dialog-inp" style="width: 380px; ">
																	<div name="pass-hint" class="mail-inp-description"></div>
																</div>
															<? endif ?>
															<div class="mail-set-crm">
																<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_MAILBOX_OPTIONS') ?></div>
															</div>
															<? if ($isCrmConfig): ?>
																<? $imapDirsList = array_merge(
																	$arParams['MAILBOX']['OPTIONS']['imap']['income'],
																	$arParams['MAILBOX']['OPTIONS']['imap']['outcome']
																); ?>
																<div class="mail-set-crm-item">
																	<label class="mail-set-crm-check-label" style="display: block; margin-bottom: -1px; padding-bottom: 1px; max-width: 500px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; ">
																		<?=getMessage('INTR_MAIL_IMAP_DIRS_LIST') ?>:
																		<a href="#" style="margin-left: 3px; color: #303031; border-bottom: 1px dashed #303031; "
																			onclick="loadImapDirsPopup(this, 'edit_imap_dirs'); return false; "
																			title="<?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?>" ><?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?></a>
																	</label>
																	<div id="edit_imap_dirs">
																		<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['income'] as $item): ?>
																			<input type="hidden" name="imap_dirs[income][]" value="<?=htmlspecialcharsbx($item) ?>">
																		<? endforeach ?>
																		<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['outcome'] as $item): ?>
																			<input type="hidden" name="imap_dirs[outcome][]" value="<?=htmlspecialcharsbx($item) ?>">
																		<? endforeach ?>
																	</div>
																</div>
																<? $blacklist = array_map('htmlspecialcharsbx', $arParams['BLACKLIST'] ?: array()) ?>
																<div class="mail-set-crm-item">
																	<a href="#" class="mail-set-textarea-show <? if (!empty($blacklist)): ?>mail-set-textarea-show-open<? endif ?>"
																		onclick="toggleCrmBlacklist(this, 'edit_b24_crm_blacklist'); return false; "><?=getMessage('INTR_MAIL_CRM_BLACKLIST') ?></a>
																	<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_HINT')) ?>" style="margin-left: 15px; "></span>
																	<div class="mail-set-textarea-wrapper" id="edit_b24_crm_blacklist"
																		<? if (empty($blacklist)): ?> style="display: none; "<? endif ?>>
																		<div class="mail-set-textarea" id="mail-set-textarea">
																			<textarea class="mail-set-textarea-input" name="black_list"
																				placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_PROMPT')) ?>"><?
																				echo join(', ', $blacklist);
																			?></textarea>
																		</div>
																	</div>
																</div>
															<? else: ?>
																<div class="mail-set-crm-item">
																	<input class="mail-set-crm-check" id="b24_setup_sync_old" type="checkbox" name="sync_old" value="Y" checked>
																	<label class="mail-set-crm-check-label" for="b24_setup_sync_old"><?=getMessage('INTR_MAIL_CRM_SYNC_OLD') ?></label>
																	<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="b24_setup_max_age_3"
																		<? if ($limitedLicense): ?>onclick="showLicenseInfoPopup('age'); return false; "<? endif ?>>
																		<input type="radio" name="max_age" value="0">
																		<div class="mail-set-singleselect-wrapper">
																			<input type="radio" name="max_age" value="3" id="b24_setup_max_age_3" checked>
																			<label for="b24_setup_max_age_3"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_3') ?></label>
																			<input type="radio" name="max_age" value="-1" id="b24_setup_max_age_i">
																			<label for="b24_setup_max_age_i"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_I') ?></label>
																		</div>
																	</label>
																	<? if ($limitedLicense): ?>
																	<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('age'); "></span>
																	<? endif ?>
																</div>
															<? endif ?>
															<div class="mail-set-crm" style="margin-top: 20px; ">
																<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_OPTIONS') ?></div>
															</div>
															<div class="mail-set-crm-item">
																<? $crmNewLeadDenied = $isCrmConfig ? in_array('crm_deny_new_lead', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
																<input class="mail-set-crm-check" id="b24_setup_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y"
																	<? if (!$crmNewLeadDenied): ?> checked<? endif ?>
																	onclick="toggleSubordinateBlock('b24_setup_crm_new_lead_resp', this.checked); ">
																<label class="mail-set-crm-check-label" for="b24_setup_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
																<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																	<div class="mail-set-crm-check-ext" id="b24_setup_crm_new_lead_resp"
																		<? if ($crmNewLeadDenied): ?> style="display: none; "<? endif ?>>
																		<? $defaultLeadSource = $isCrmConfig ? $arParams['MAILBOX_LEAD_SOURCE'] : $arParams['DEFAULT_LEAD_SOURCE']; ?>
																		<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																		<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																		<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="b24_setup_lead_source_<?=htmlspecialcharsbx($defaultLeadSource) ?>">
																			<input type="radio" name="lead_source" value="0">
																			<div class="mail-set-singleselect-wrapper">
																				<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																					<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="b24_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"
																						<? if ($value == $defaultLeadSource): ?> checked<? endif ?>>
																					<label for="b24_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																				<? endforeach ?>
																			</div>
																		</label>
																	</div>
																<? endif ?>
															</div>
															<div class="mail-set-crm-item">
																<? $crmNewContactDenied = $isCrmConfig ? in_array('crm_deny_new_contact', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
																<input class="mail-set-crm-check" id="b24_setup_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y"
																	<? if (!$crmNewContactDenied): ?> checked<? endif ?>>
																<label class="mail-set-crm-check-label" for="b24_setup_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
															</div>
														</div>
														<div class="mail-set-item-block-crm-button">
															<span class="webform-small-button webform-button-accept" id="b24_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm"><?=getMessage($isCrmConfig ? 'INTR_MAIL_INP_EDIT_SAVE' : 'INTR_MAIL_CRM_CONNECT_BUTTON') ?></span>
															<span class="webform-small-button" onclick="toggleSubordinateBlock('b24_setup_crm_options', false); "><?=getMessage('INTR_MAIL_INP_CANCEL') ?></span>
														</div>
													</form>
												</div>
											</div>
										<? endif ?>
									</div>
								</div>
								<div class="mail-set-item-block-wrap mail-set-item-block-inp">
									<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD_MANAGE') ?></div>
									<div class="mail-set-item-block">
										<form id="b24_password_form">
											<? list($login, ) = explode('@', $emailAddress, 2); ?>
											<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>" />
											<input name="login" type="hidden" value="<?=htmlspecialcharsbx($login) ?>" />
											<?=bitrix_sessid_post() ?>
											<div class="mail-set-item-block-r">
												<span id="b24_password_save" name="password-save" class="webform-button webform-button-accept">
													<span class="webform-button-left"></span><span class="webform-button-text"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE') ?></span><span class="webform-button-right"></span>
												</span>&nbsp;
											</div>
											<div class="mail-set-item-block-l">
												<div class="mail-set-item">
													<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD') ?></div>
													<input name="password" class="mail-set-inp" type="password"/>
													<div name="pass-hint" class="mail-inp-description"><?=getMessage('INTR_MAIL_INP_PASS_SHORT') ?></div>
												</div>
												<div class="mail-set-item">
													<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD2') ?></div>
													<input name="password2" class="mail-set-inp" type="password"/>
													<div name="pass2-hint" class="mail-inp-description"></div>
												</div>
											</div>
										</form>
									</div>
								</div>

							</div>
						<? endif ?>

						<? if (!empty($arParams['MAILBOX']) && !$b24Mailbox): ?>
							<div id="b24_block_replace_warning">
								<div class="mail-set-item-block mail-set-item-icon">
									<span class="mail-set-item-text">
										<? if (empty($emailAddress)): ?>
											<?=getMessage('INTR_MAIL_REPLACE_WARNING_UN') ?>
										<? else: ?>
											<?=getMessage('INTR_MAIL_REPLACE_WARNING', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
										<? endif ?>
									</span>
								</div>
								<br/><br/>
							</div>
						<? endif ?>

						<form<? if ($b24Mailbox): ?> style="display: none; "<? endif ?> id="b24_create_form" name="settings_form" action="<?=POST_FORM_ACTION_URI ?>" method="POST">
							<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
								<span class="post-dialog-alert-align"></span>
								<span class="post-dialog-alert-icon"></span>
								<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
							</div>
							<input type="hidden" name="act" value="create">
							<input type="hidden" name="SERVICE" value="<?=$b24Settings['id'] ?>">
							<? if (!empty($arParams['MAILBOX'])): ?>
								<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID'] ?>">
							<? endif ?>
							<?=bitrix_sessid_post() ?>
							<div class="mail-set-cont">
								<div class="mail-set-cont-left">
									<div class="mail-set-item">
										<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_MB_NAME') ?></div>
										<input name="login" class="mail-set-inp" type="text" autocomplete="off" />
										<? if (count($arParams['CR_DOMAINS']) == 1): ?>
											<input type="hidden" name="domain" value="<?=htmlspecialcharsbx(reset($arParams['CR_DOMAINS'])) ?>">
											<span class="mail-set-address">@<?=htmlspecialcharsbx(reset($arParams['CR_DOMAINS'])) ?></span>
										<? else: ?>
											<select name="domain" class="mail-set-address mail-set-select">
												<? foreach ($arParams['CR_DOMAINS'] as $domain): ?>
													<option value="<?=htmlspecialcharsbx($domain) ?>">@<?=htmlspecialcharsbx($domain) ?></option>
												<? endforeach ?>
											</select>
										<? endif ?>
										<div name="login-hint" class="mail-inp-description"></div>
									</div>
									<div name="bad-login-hint" style="z-index: 1000; position: absolute; display: none; left: 60px; ">
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
														<div id="mail-alert-popup-cont" class="mail-alert-popup-cont" style="display: block; ">
															<div class="mail-alert-popup-text"><?=getMessage('INTR_MAIL_INP_NAME_BAD_HINT') ?></div>
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
									<div class="mail-set-item">
										<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_MB_PASS') ?></div>
										<input name="password" class="mail-set-inp" type="password" />
										<div name="pass-hint" class="mail-inp-description"><?=getMessage('INTR_MAIL_INP_PASS_SHORT') ?></div>
									</div>
									<div class="mail-set-item">
										<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_PASS2') ?></div>
										<input name="password2" class="mail-set-inp" type="password" />
										<div name="pass2-hint" class="mail-inp-description"></div>
									</div>
									<? if ($arParams['CRM_AVAILABLE']): ?>
										<div class="mail-set-item-block-crm">
											<div class="mail-set-item-block-crm-wrapper">
												<div class="mail-set-item-block-crm-wrapper-dec" style="margin-bottom: 0px; ">
													<div class="mail-set-crm">
														<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_CONNECT') ?></div>
													</div>
													<div class="mail-set-crm-item">
														<input class="mail-set-crm-check" id="create_b24_crm_connect" type="checkbox" name="crm_connect" value="Y" checked
															onclick="toggleSubordinateBlock('create_b24_crm_options', this.checked); ">
														<label class="mail-set-crm-check-label" for="create_b24_crm_connect"><?=getMessage('INTR_MAIL_INP_CRM') ?></label>
													</div>
													<div id="create_b24_crm_options">
														<div class="mail-set-crm-item">
															<input class="mail-set-crm-check" id="create_b24_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y" checked
																onclick="toggleSubordinateBlock('create_b24_crm_new_lead_resp', this.checked); ">
															<label class="mail-set-crm-check-label" for="create_b24_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
															<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																<div class="mail-set-crm-check-ext" id="create_b24_crm_new_lead_resp">
																	<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																	<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																	<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_b24_lead_source_<?=htmlspecialcharsbx($arParams['DEFAULT_LEAD_SOURCE']) ?>">
																		<input type="radio" name="lead_source" value="0">
																		<div class="mail-set-singleselect-wrapper">
																			<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																				<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="create_b24_lead_source_<?=htmlspecialcharsbx($value) ?>"
																					<? if ($value == $arParams['DEFAULT_LEAD_SOURCE']): ?> checked<? endif ?>>
																				<label for="create_b24_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																			<? endforeach ?>
																		</div>
																	</label>
																</div>
															<? endif ?>
														</div>
														<div class="mail-set-crm-item">
															<input class="mail-set-crm-check" id="create_b24_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y" checked>
															<label class="mail-set-crm-check-label" for="create_b24_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
														</div>
													</div>
												</div>
											</div>
										</div>
									<? endif ?>
								</div>
								<div class="mail-set-cont-right">
									<div class="mail-set-second-info"><?=getMessage('INTR_MAIL_B24_HELP_CRM') ?></div>
								</div>
								<div style="clear: both; "></div>
							</div>
							<div class="mail-set-footer">
								<a id="b24_create_save" name="create-save" class="webform-button webform-button-accept" href="#">
									<span class="webform-button-left"></span><span class="webform-button-text"><?=getMessage('INTR_MAIL_INP_SAVE') ?></span><span class="webform-button-right"></span>
								</a>
								<span id="b24_create_clear" class="mail-set-cancel-link"><?=getMessage('INTR_MAIL_INP_CLEAR') ?></span>
							</div>
							<input type="submit" style="position: absolute; visibility: hidden; ">
						</form>

					</div>
				</div>
			<? endif ?>
			<? if ($showDomainBlock): ?>
				<div id="mail-set-second" class="mail-set-second-wrap"<? if ($defaultBlock == 'domain'): ?> style="display: block; "<? endif ?>>
					<div class="mail-set-second">

						<? if (!$isUserAdmin && !$domainAdded): ?>
							<div style="text-align: center; "><?=getMessage('INTR_MAIL_NODOMAIN_USER_INFO') ?><br><br><br></div>
						<? endif ?>

						<? if ($isUserAdmin && empty($domainSettings)): ?>

							<? if (IsModuleInstalled('bitrix24')): ?>
								<form id="domain_form" name="domain_form" action="<?=POST_FORM_ACTION_URI ?>" method="POST">
									<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
										<span class="post-dialog-alert-align"></span>
										<span class="post-dialog-alert-icon"></span>
										<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
									</div>
									<input type="hidden" name="page" value="domain">
									<input type="hidden" name="act" value="create">
									<?=bitrix_sessid_post() ?>
									<? if (in_array(LANGUAGE_ID, array('ru', 'ua'))): ?>
										<div id="delegate-domain-block" class="mail-set-item-block-wrap">
											<div class="mail-set-item-block">
												<div class="mail-set-radio-item">
													<input type="radio" id="select-delegate" name="connect" value="0" class="mail-set-radio-inp" checked="checked">
													<label for="select-delegate"><?=getMessage('INTR_MAIL_DOMAIN_DELEGATE') ?></label>
												</div>
												<div class="mail-set-domain-block">
													<div class="mail-set-domain-text"><?=getMessage('INTR_MAIL_DOMAIN_INP_NAME') ?></div>
													<div class="mail-set-domain-inp-wrap">
														<span class="mail-set-domain-at">@</span>
														<input class="mail-set-inp" type="text" name="domain">
													</div>
													<div class="mail-set-domain-checkbox-wrap">
														<input class="mail-set-checkbox" type="checkbox" id="domain-public" name="public" checked="checked">
														<label for="domain-public" class="mail-set-label"><?=getMessage('INTR_MAIL_DOMAIN_PUBLIC') ?></label>
													</div>
												</div>
											</div>
										</div>
										<div id="connect-domain-block" class="mail-set-item-block-wrap mail-set-domain-disable">
											<div class="mail-set-item-block">
												<div class="mail-set-radio-item">
													<input type="radio" id="select-connect" name="connect" value="1" class="mail-set-radio-inp">
													<label for="select-connect"><?=getMessage('INTR_MAIL_DOMAIN_CONNECT') ?></label>
												</div>
											</div>
										</div>
										<div id="get-domain-block" class="mail-set-item-block-wrap mail-set-domain-disable">
											<div class="mail-set-item-block">
												<div class="mail-set-radio-item">
													<input type="radio" id="select-get" name="connect" value="-1" class="mail-set-radio-inp">
													<label for="select-get"><?=($arParams['REG_DOMAIN'] ? str_replace('#DOMAIN#', htmlspecialcharsbx($arParams['REG_DOMAIN']), getMessage('INTR_MAIL_DOMAIN_GET2')) : getMessage('INTR_MAIL_DOMAIN_GET')) ?></label>
												</div>
											</div>
										</div>
									<? else: ?>
										<div class="mail-set-item-block-wrap">
											<div class="mail-set-item-block">
												<div class="mail-set-domain-text"><?=getMessage('INTR_MAIL_DOMAIN_INP_NAME') ?></div>
												<div class="mail-set-domain-inp-wrap">
													<span class="mail-set-domain-at">@</span>
													<input class="mail-set-inp" type="text" name="domain">
												</div>
												<div class="mail-set-domain-checkbox-wrap">
													<input class="mail-set-checkbox" type="checkbox" id="domain-public" name="public" checked="checked">
													<label for="domain-public" class="mail-set-label"><?=getMessage('INTR_MAIL_DOMAIN_PUBLIC') ?></label>
												</div>
											</div>
										</div>
									<? endif ?>
									<div class="mail-set-footer">
										<a id="domain_create" class="webform-button webform-button-accept" href="?page=domain">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=getMessage('INTR_MAIL_INP_DOMAIN_ADD') ?></span><span class="webform-button-right"></span>
										</a>
									</div>
									<input type="submit" style="position: absolute; visibility: hidden; ">
								</form>
								<script type="text/javascript">

									BX.bind(BX('delegate-domain-block'), 'click', function() {
										BX('select-delegate').checked = true;
										BX.removeClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
										BX.addClass(BX('connect-domain-block'), 'mail-set-domain-disable');
										BX.addClass(BX('get-domain-block'), 'mail-set-domain-disable');
									});
									BX.bind(BX('connect-domain-block'), 'click', function() {
										BX('select-connect').checked = true;
										BX.addClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
										BX.removeClass(BX('connect-domain-block'), 'mail-set-domain-disable');
										BX.addClass(BX('get-domain-block'), 'mail-set-domain-disable');
									});
									BX.bind(BX('get-domain-block'), 'click', function() {
										BX('select-get').checked = true;
										BX.addClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
										BX.addClass(BX('connect-domain-block'), 'mail-set-domain-disable');
										BX.removeClass(BX('get-domain-block'), 'mail-set-domain-disable');
									});

									var handleDomainForm = function(e)
									{
										e.preventDefault ? e.preventDefault() : e.returnValue = false;

										if (<? if (in_array(LANGUAGE_ID, array('ru', 'ua'))): ?>BX('select-delegate').checked<? else: ?>true<? endif ?>)
										{
											var form = BX('domain_form');

											var formButton = BX('domain_create');
											var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

											if (form.elements['domain'].value.length > 0)
											{
												BX.hide(alert, 'block');
												setPost.animCurrent();

												BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

												var data = {};
												for (var i = 0; i < form.elements.length; i++)
												{
													if (form.elements[i].name)
														data[form.elements[i].name] = form.elements[i].value;
												}
												BX.ajax({
													method: 'POST',
													url: '<?=$this->__component->getPath() ?>/ajax.php?page=domain&act=create',
													data: data,
													dataType: 'json',
													onsuccess: function(json)
													{
														if (json.result != 'error')
														{
															window.location = '?page=domain#delegate';
														}
														else
														{
															BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

															BX.removeClass(alert, 'post-dialog-alert-ok');
															BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
															BX.show(alert, 'block');
															setPost.animCurrent();
														}
													},
													onfailure: function()
													{
														BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

														BX.removeClass(alert, 'post-dialog-alert-ok');
														BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_FORM_ERROR')) ?>'});
														BX.show(alert, 'block');
														setPost.animCurrent();
													}
												});
											}
											else
											{
												BX.removeClass(alert, 'post-dialog-alert-ok');
												BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_DOMAIN_INP_NAME_EMPTY')) ?>'});
												BX.show(alert, 'block');
												setPost.animCurrent();
											}
										}
										else if (BX('select-get').checked)
										{
											<? if ($arParams['REG_DOMAIN']): ?>
												var formButton = BX('domain_create');
												var alert = BX.findChild(BX('domain_form'), {attr: {name: 'post-dialog-alert'}}, true, false);

												BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

												BX.ajax({
													method: 'POST',
													url: '<?=$this->__component->getPath() ?>/ajax.php?page=domain&act=get&domain=<?=CUtil::jsEscape($arParams['REG_DOMAIN']) ?>',
													data: '<?=bitrix_sessid_get() ?>',
													dataType: 'json',
													onsuccess: function(json)
													{
														if (json.result == 'ok')
														{
															window.location = '?page=domain#delegate';
														}
														else
														{
															BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

															BX.removeClass(alert, 'post-dialog-alert-ok');
															BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
															BX.show(alert, 'block');
															setPost.animCurrent();
														}
													},
													onfailure: function()
													{
														BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

														BX.removeClass(alert, 'post-dialog-alert-ok');
														BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_FORM_ERROR')) ?>'});
														BX.show(alert, 'block');
														setPost.animCurrent();
													}
												});
											<? else: ?>
												window.location = '?page=domain#get';
											<? endif ?>
										}
										else if (BX('select-connect').checked)
										{
											window.location = '?page=domain#connect';
										}

										return false;
									};

									BX.bind(BX('domain_form'), 'submit', handleDomainForm);
									BX.bind(BX('domain_create'), 'click', handleDomainForm);

								</script>
							<? else: ?>
								<?=getMessage('INTR_MAIL_DOMAIN_HELP') ?>
								<br><br><br>
								<div class="mail-set-footer">
									<a class="webform-button webform-button-accept" href="?page=domain">
										<?=getMessage('INTR_MAIL_INP_DOMAIN_ADD') ?>
									</a>
								</div>
							<? endif ?>

						<? endif ?>

						<? if (!empty($domainSettings)): ?>

							<? if ($isUserAdmin): ?>
								<div class="mail-set-item-block">
									<span style="float: right; margin-right: 22px; font-size: 16px; ">
										<a style="text-decoration: underline; " href="?page=domain"><?=getMessage('INTR_MAIL_INP_DOMAIN_EDIT') ?></a>
										<? if ($domainAdded): ?>
											&nbsp;&nbsp;&nbsp;&nbsp;
											<a style="text-decoration: underline; " href="?page=manage"><?=getMessage('INTR_MAIL_INP_ADMIN_MANAGE') ?></a>
										<? endif ?>
									</span>
									<?=getMessage($domainAdded ? 'INTR_MAIL_ADMIN_DOMAIN' : 'INTR_MAIL_ADMIN_DOMAIN_WAIT', array('#DOMAIN#' => htmlspecialcharsbx($domainSettings['server']))) ?>
								</div>
								<br><br>
							<? endif ?>

							<? if ($domainMailbox): ?>
								<div id="domain_setup_form">

									<? $lastMailCheck = CUserOptions::getOption('global', 'last_mail_check_'.SITE_ID, null); ?>
									<? $lastMailCheckSuccess = CUserOptions::getOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

									<div class="mail-set-title">
										<?=getMessage('INTR_MAIL_MAILBOX_MANAGE', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
									</div>
									<? if ($arParams['CRM_AVAILABLE'] && !empty($arParams['CRM_PRECONNECT'])): ?>
										<div name="post-dialog-alert" class="post-dialog-alert">
											<span class="post-dialog-alert-align"></span>
											<span class="post-dialog-alert-icon"></span>
											<span name="post-dialog-alert-text" class="post-dialog-alert-text">
												<?=getMessage('INTR_MAIL_CRM_PRECONNECT') ?>
												<? if (!empty($arParams['IMAP_ERROR'])): ?>
													&mdash; <?=htmlspecialcharsbx($arParams['IMAP_ERROR']) ?>
													<? if (!empty($arParams['IMAP_ERROR_EXT'])): ?>
														<span style="font-weight: normal; ">
															(<a href="#" onclick="this.style.display = 'none'; BX.findNextSibling(this, {class: 'post-dialog-alert-text-ext'}).style.display = ''; setPost.animCurrent(); return false; "><?=getMessage('INTR_MAIL_ERROR_EXT') ?></a><?
															?><span class="post-dialog-alert-text-ext" style="display: none; "><?=htmlspecialcharsbx($arParams['IMAP_ERROR_EXT']) ?></span>)</span>
													<? endif ?>
												<? endif ?>
											</span>
										</div>
									<? else: ?>
										<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
											<span class="post-dialog-alert-align"></span>
											<span class="post-dialog-alert-icon"></span>
											<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
										</div>
									<? endif ?>
									<div class="mail-set-item-block-wrap">
										<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_STATUS') ?></div>
										<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess): ?> post-status-error<? endif ?>">
											<div class="mail-set-item-block-r">
												<span id="domain_delete_form" class="webform-button webform-button-decline">
													<?=getMessage('INTR_MAIL_MAILBOX_DELETE') ?>
												</span>&nbsp;
											</div>
											<div class="mail-set-item-block-l">
												<span name="status-text" class="post-dialog-stat-text">
													<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0): ?>
														<?=str_replace('#DATE#', FormatDate(
															array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
															intval($lastMailCheck)
														), getMessage('INTR_MAIL_CHECK_TEXT')) ?>:
													<? else: ?>
														<?=getMessage('INTR_MAIL_CHECK_TEXT_NA') ?>
													<? endif ?>
												</span>
												<span name="status-alert" class="post-dialog-stat-alert">
												<? if (isset($lastMailCheckSuccess)): ?>
													<?=getMessage($lastMailCheckSuccess ? 'INTR_MAIL_CHECK_SUCCESS' : 'INTR_MAIL_CHECK_ERROR') ?>
												<? endif ?>
												</span>
												<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>
												<span id="domain_check_form" class="webform-button">
													<?=getMessage('INTR_MAIL_CHECK') ?>
												</span>
											</div>
											<? $isCrmConfig = in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags']); ?>
											<? if ($isCrmConfig || $arParams['CRM_AVAILABLE']): ?>
												<div class="mail-set-item-block-bottom">
													<div class="mail-set-item-block-l">
														<span class="post-dialog-stat-text"><?=getMessage('INTR_MAIL_CRM_CONNECT2') ?>:</span>
														<? if ($isCrmConfig): ?>
															<span class="post-dialog-stat-alert"><?=getMessage('INTR_MAIL_CRM_ENABLED') ?></span>
															<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('domain_setup_crm_options', BX('domain_setup_crm_options').offsetHeight == 0); ">
																<?=getMessage('INTR_MAIL_CRM_CONFIG') ?>
															</span>
															<span class="webform-button mail-set-nomargin" id="domain_disable_crm">
																<?=getMessage('INTR_MAIL_CRM_DISABLE') ?>
															</span>
														<? else: ?>
															<span class="post-status-error">
																<?=getMessage(empty($arParams['CRM_PRECONNECT']) ? 'INTR_MAIL_CRM_DISABLED' : 'INTR_MAIL_CRM_ALMOST') ?>
															</span>
															<? if (empty($arParams['CRM_PRECONNECT'])): ?>
																<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('domain_setup_crm_options', BX('domain_setup_crm_options').offsetHeight == 0); ">
																	<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
																</span>
															<? else: ?>
																<span class="webform-button webform-button-disable mail-set-nomargin" name="enablecrm-button">
																	<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
																</span>
															<? endif ?>
														<? endif ?>
													</div>
												</div>
												<div class="mail-set-item-block-crm" id="domain_setup_crm_options" style="display: none; margin-right: 25px; ">
													<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper" style="margin-top: 10px; ">
														<form id="domain_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm_form">
															<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>" >
															<?=bitrix_sessid_post() ?>
															<div class="mail-set-item-block-crm-wrapper-dec">
																<? if (empty($arParams['MAILBOX']['PASSWORD'])): ?>
																	<span class="mail-set-crm-title" style="border: none; "><?=getMessage('INTR_MAIL_INP_PASS') ?></span>
																	<div class="post-dialog-inp-item" style="display: inline-block; margin-bottom: 0px; ">
																		<input name="password" type="password" class="post-dialog-inp" style="width: 380px; ">
																		<div name="pass-hint" class="mail-inp-description"></div>
																	</div>
																<? endif ?>
																<div class="mail-set-crm">
																	<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_MAILBOX_OPTIONS') ?></div>
																</div>
																<? if ($isCrmConfig): ?>
																	<? $imapDirsList = array_merge(
																		$arParams['MAILBOX']['OPTIONS']['imap']['income'],
																		$arParams['MAILBOX']['OPTIONS']['imap']['outcome']
																	); ?>
																	<div class="mail-set-crm-item">
																		<label class="mail-set-crm-check-label" style="display: block; margin-bottom: -1px; padding-bottom: 1px; max-width: 500px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; ">
																			<?=getMessage('INTR_MAIL_IMAP_DIRS_LIST') ?>:
																			<a href="#" style="margin-left: 3px; color: #303031; border-bottom: 1px dashed #303031; "
																				onclick="loadImapDirsPopup(this, 'edit_imap_dirs'); return false; "
																				title="<?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?>" ><?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?></a>
																		</label>
																		<div id="edit_imap_dirs">
																			<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['income'] as $item): ?>
																				<input type="hidden" name="imap_dirs[income][]" value="<?=htmlspecialcharsbx($item) ?>">
																			<? endforeach ?>
																			<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['outcome'] as $item): ?>
																				<input type="hidden" name="imap_dirs[outcome][]" value="<?=htmlspecialcharsbx($item) ?>">
																			<? endforeach ?>
																		</div>
																	</div>
																	<? $blacklist = array_map('htmlspecialcharsbx', $arParams['BLACKLIST'] ?: array()) ?>
																	<div class="mail-set-crm-item">
																		<a href="#" class="mail-set-textarea-show <? if (!empty($blacklist)): ?>mail-set-textarea-show-open<? endif ?>"
																			onclick="toggleCrmBlacklist(this, 'edit_domain_crm_blacklist'); return false; "><?=getMessage('INTR_MAIL_CRM_BLACKLIST') ?></a>
																		<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_HINT')) ?>" style="margin-left: 15px; "></span>
																		<div class="mail-set-textarea-wrapper" id="edit_domain_crm_blacklist"
																			<? if (empty($blacklist)): ?> style="display: none; "<? endif ?>>
																			<div class="mail-set-textarea" id="mail-set-textarea">
																				<textarea class="mail-set-textarea-input" name="black_list"
																					placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_PROMPT')) ?>"><?
																					echo join(', ', $blacklist);
																				?></textarea>
																			</div>
																		</div>
																	</div>
																<? else: ?>
																	<div class="mail-set-crm-item">
																		<input class="mail-set-crm-check" id="domain_setup_sync_old" type="checkbox" name="sync_old" value="Y" checked>
																		<label class="mail-set-crm-check-label" for="domain_setup_sync_old"><?=getMessage('INTR_MAIL_CRM_SYNC_OLD') ?></label>
																		<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="domain_setup_max_age_3"
																			<? if ($limitedLicense): ?>onclick="showLicenseInfoPopup('age'); return false; "<? endif ?>>
																			<input type="radio" name="max_age" value="0">
																			<div class="mail-set-singleselect-wrapper">
																				<input type="radio" name="max_age" value="3" id="domain_setup_max_age_3" checked>
																				<label for="domain_setup_max_age_3"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_3') ?></label>
																				<input type="radio" name="max_age" value="-1" id="domain_setup_max_age_i">
																				<label for="domain_setup_max_age_i"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_I') ?></label>
																			</div>
																		</label>
																		<? if ($limitedLicense): ?>
																			<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('age'); "></span>
																		<? endif ?>
																	</div>
																<? endif ?>
																<div class="mail-set-crm" style="margin-top: 20px; ">
																	<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_OPTIONS') ?></div>
																</div>
																<div class="mail-set-crm-item">
																	<? $crmNewLeadDenied = $isCrmConfig ? in_array('crm_deny_new_lead', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
																	<input class="mail-set-crm-check" id="domain_setup_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y"
																		<? if (!$crmNewLeadDenied): ?> checked<? endif ?>
																		onclick="toggleSubordinateBlock('domain_setup_crm_new_lead_resp', this.checked); ">
																	<label class="mail-set-crm-check-label" for="domain_setup_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
																	<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																		<div class="mail-set-crm-check-ext" id="domain_setup_crm_new_lead_resp"
																			<? if ($crmNewLeadDenied): ?> style="display: none; "<? endif ?>>
																			<? $defaultLeadSource = $isCrmConfig ? $arParams['MAILBOX_LEAD_SOURCE'] : $arParams['DEFAULT_LEAD_SOURCE']; ?>
																			<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																			<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																			<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="domain_setup_lead_source_<?=htmlspecialcharsbx($defaultLeadSource) ?>">
																				<input type="radio" name="lead_source" value="0">
																				<div class="mail-set-singleselect-wrapper">
																					<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																						<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="domain_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"
																							<? if ($value == $defaultLeadSource): ?> checked<? endif ?>>
																						<label for="domain_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																					<? endforeach ?>
																				</div>
																			</label>
																		</div>
																	<? endif ?>
																</div>
																<div class="mail-set-crm-item">
																	<? $crmNewContactDenied = $isCrmConfig ? in_array('crm_deny_new_contact', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
																	<input class="mail-set-crm-check" id="domain_setup_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y"
																		<? if (!$crmNewContactDenied): ?> checked<? endif ?>>
																	<label class="mail-set-crm-check-label" for="domain_setup_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
																</div>
															</div>
															<div class="mail-set-item-block-crm-button">
																<span class="webform-small-button webform-button-accept" id="domain_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm"><?=getMessage($isCrmConfig ? 'INTR_MAIL_INP_EDIT_SAVE' : 'INTR_MAIL_CRM_CONNECT_BUTTON') ?></span>
																<span class="webform-small-button" onclick="toggleSubordinateBlock('domain_setup_crm_options', false); "><?=getMessage('INTR_MAIL_INP_CANCEL') ?></span>
															</div>
														</form>
													</div>
												</div>
											<? endif ?>
										</div>
									</div>

									<div class="mail-set-item-block-wrap mail-set-item-block-inp">
										<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD_MANAGE') ?></div>
										<div class="mail-set-item-block">
											<form id="domain_password_form">
												<? list($login, ) = explode('@', $emailAddress, 2); ?>
												<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>">
												<input name="login" type="hidden" value="<?=htmlspecialcharsbx($login) ?>">
												<?=bitrix_sessid_post() ?>
												<div class="mail-set-item-block-r">
													<span id="domain_password_save" name="password-save" class="webform-button webform-button-accept">
														<span class="webform-button-left"></span><span class="webform-button-text"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE') ?></span><span class="webform-button-right"></span>
													</span>&nbsp;
												</div>
												<div class="mail-set-item-block-l">
													<div class="mail-set-item">
														<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD') ?></div>
														<input name="password" class="mail-set-inp" type="password">
														<div name="pass-hint" class="mail-inp-description"><?=getMessage('INTR_MAIL_INP_PASS_SHORT') ?></div>
													</div>
													<div class="mail-set-item">
														<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD2') ?></div>
														<input name="password2" class="mail-set-inp" type="password">
														<div name="pass2-hint" class="mail-inp-description"></div>
													</div>
												</div>
											</form>
										</div>
									</div>

								</div>
							<? endif ?>

							<? if ($domainAdded && ($isUserAdmin || $domainSettings['encryption'] == 'N')): ?>

								<? if (!empty($arParams['MAILBOX']) && !$domainMailbox): ?>
									<div id="domain_block_replace_warning">
										<div class="mail-set-item-block mail-set-item-icon">
											<span class="mail-set-item-text">
												<? if (empty($emailAddress)): ?>
													<?=getMessage('INTR_MAIL_REPLACE_WARNING_UN') ?>
												<? else: ?>
													<?=getMessage('INTR_MAIL_REPLACE_WARNING', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
												<? endif ?>
											</span>
										</div>
										<br/><br/>
									</div>
								<? endif ?>

								<form<? if ($domainMailbox): ?> style="display: none; "<? endif ?> id="domain_create_form" name="settings_form" action="<?=POST_FORM_ACTION_URI ?>" method="POST">
									<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
										<span class="post-dialog-alert-align"></span>
										<span class="post-dialog-alert-icon"></span>
										<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
									</div>
									<input type="hidden" name="act" value="create">
									<input type="hidden" name="SERVICE" value="<?=$domainSettings['id'] ?>">
									<? if (!empty($arParams['MAILBOX'])): ?>
										<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID'] ?>">
									<? endif ?>
									<?=bitrix_sessid_post() ?>
									<div class="mail-set-cont">
										<div class="mail-set-cont-left">
											<div class="mail-set-item">
												<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_MB_NAME') ?></div>
												<input name="login" class="mail-set-inp" type="text" autocomplete="off">
												<input type="hidden" name="domain" value="<?=htmlspecialcharsbx($domainSettings['server']) ?>">
												<span class="mail-set-address">@<?=htmlspecialcharsbx($domainSettings['server']) ?></span>
												<div name="login-hint" class="mail-inp-description"></div>
											</div>
											<div name="bad-login-hint" style="z-index: 1000; position: absolute; display: none; left: 60px; ">
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
																<div id="mail-alert-popup-cont" class="mail-alert-popup-cont" style="display: block; ">
																	<div class="mail-alert-popup-text"><?=getMessage('INTR_MAIL_INP_NAME_BAD_HINT') ?></div>
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
												<div class="popup-window-light-angly popup-window-light-angly-top" style="left: 30px; margin-left: auto; "></div>
											</div>
											<div class="mail-set-item">
												<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_MB_PASS') ?></div>
												<input name="password" class="mail-set-inp" type="password">
												<div name="pass-hint" class="mail-inp-description"><?=getMessage('INTR_MAIL_INP_PASS_SHORT') ?></div>
											</div>
											<div class="mail-set-item">
												<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_INP_PASS2') ?></div>
												<input name="password2" class="mail-set-inp" type="password" />
												<div name="pass2-hint" class="mail-inp-description"></div>
											</div>
											<? if ($arParams['CRM_AVAILABLE']): ?>
												<div class="mail-set-item-block-crm">
													<div class="mail-set-item-block-crm-wrapper">
														<div class="mail-set-item-block-crm-wrapper-dec" style="margin-bottom: 0px; ">
															<div class="mail-set-crm">
																<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_CONNECT') ?></div>
															</div>
															<div class="mail-set-crm-item">
																<input class="mail-set-crm-check" id="create_domain_crm_connect" type="checkbox" name="crm_connect" value="Y" checked
																	onclick="toggleSubordinateBlock('create_domain_crm_options', this.checked); ">
																<label class="mail-set-crm-check-label" for="create_domain_crm_connect"><?=getMessage('INTR_MAIL_INP_CRM') ?></label>
															</div>
															<div id="create_domain_crm_options">
																<div class="mail-set-crm-item">
																	<input class="mail-set-crm-check" id="create_domain_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y" checked
																		onclick="toggleSubordinateBlock('create_domain_crm_new_lead_resp', this.checked); ">
																	<label class="mail-set-crm-check-label" for="create_domain_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
																	<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																		<div class="mail-set-crm-check-ext" id="create_domain_crm_new_lead_resp">
																			<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																			<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																			<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_domain_lead_source_<?=htmlspecialcharsbx($arParams['DEFAULT_LEAD_SOURCE']) ?>">
																				<input type="radio" name="lead_source" value="0">
																				<div class="mail-set-singleselect-wrapper">
																					<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																						<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="create_domain_lead_source_<?=htmlspecialcharsbx($value) ?>"
																							<? if ($value == $arParams['DEFAULT_LEAD_SOURCE']): ?> checked<? endif ?>>
																						<label for="create_domain_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																					<? endforeach ?>
																				</div>
																			</label>
																		</div>
																	<? endif ?>
																</div>
																<div class="mail-set-crm-item">
																	<input class="mail-set-crm-check" id="create_domain_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y" checked>
																	<label class="mail-set-crm-check-label" for="create_domain_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
																</div>
															</div>
														</div>
													</div>
												</div>
											<? endif ?>
										</div>
										<div class="mail-set-cont-right">
											<div class="mail-set-second-info">
												<?=getMessage('INTR_MAIL_DOMAIN_HELP_CRM') ?>
											</div>
										</div>
										<div style="clear: both; "></div>
									</div>
									<div class="mail-set-footer">
										<a id="domain_create_save" name="create-save" class="webform-button webform-button-accept" href="#">
											<?=getMessage('INTR_MAIL_INP_SAVE') ?>
										</a>
										<span id="domain_create_clear" class="mail-set-cancel-link"><?=getMessage('INTR_MAIL_INP_CLEAR') ?></span>
									</div>
									<input type="submit" style="position: absolute; visibility: hidden; ">
								</form>

							<? endif ?>

							<? if ($domainAdded && !$isUserAdmin && $domainSettings['encryption'] != 'N'): ?>
								<div id="domain_create_form" style="text-align: center; <? if ($domainMailbox): ?>display: none; <? endif ?>">
									<?=getMessage('INTR_MAIL_DOMAIN_USER_INFO', array('#DOMAIN#' => htmlspecialcharsbx($domainSettings['server']))) ?>
									<br><br><br>
								</div>
							<? endif ?>

						<? endif ?>

					</div>
				</div>
			<? endif ?>
			<div id="mail-set-third" class="mail-set-third-wrap"<? if ($defaultBlock == 'imap'): ?> style="display: block;"<? endif ?>>
				<div class="mail-set-third">

					<? if ($imapMailbox): ?>
						<div id="imap_setup_form" class="mail-set-imap-setup">

							<? $lastMailCheck = CUserOptions::getOption('global', 'last_mail_check_'.SITE_ID, null); ?>
							<? $lastMailCheckSuccess = CUserOptions::getOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

							<div class="mail-set-title">
								<? if (empty($emailAddress)): ?>
									<?=getMessage('INTR_MAIL_MAILBOX_MANAGE_UN') ?>
								<? else: ?>
									<?=getMessage('INTR_MAIL_MAILBOX_MANAGE', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
								<? endif ?>
							</div>

							<? if ($arParams['CRM_AVAILABLE'] && !empty($arParams['CRM_PRECONNECT'])): ?>
								<div name="post-dialog-alert" class="post-dialog-alert">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text">
										<?=getMessage('INTR_MAIL_CRM_PRECONNECT') ?>
										<? if (!empty($arParams['IMAP_ERROR'])): ?>
											&mdash; <?=htmlspecialcharsbx($arParams['IMAP_ERROR']) ?>
											<? if (!empty($arParams['IMAP_ERROR_EXT'])): ?>
												<span style="font-weight: normal; ">
													(<a href="#" onclick="this.style.display = 'none'; BX.findNextSibling(this, {class: 'post-dialog-alert-text-ext'}).style.display = ''; setPost.animCurrent(); return false; "><?=getMessage('INTR_MAIL_ERROR_EXT') ?></a><?
													?><span class="post-dialog-alert-text-ext" style="display: none; "><?=htmlspecialcharsbx($arParams['IMAP_ERROR_EXT']) ?></span>)</span>
											<? endif ?>
										<? endif ?>
									</span>
								</div>
							<? else: ?>
								<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
								</div>
							<? endif ?>
							<div class="mail-set-item-block-wrap">
								<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_STATUS') ?></div>
								<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess): ?> post-status-error<? endif ?>">
									<div class="mail-set-item-block-r">
										<span id="imap_delete_form" class="webform-button webform-button-decline">
											<?=getMessage('INTR_MAIL_MAILBOX_DELETE') ?>
										</span>&nbsp;
									</div>
									<div class="mail-set-item-block-l">
										<span name="status-text" class="post-dialog-stat-text">
											<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0): ?>
												<?=str_replace('#DATE#', FormatDate(
													array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
													intval($lastMailCheck)
												), getMessage('INTR_MAIL_CHECK_TEXT')) ?>:
											<? else: ?>
												<?=getMessage('INTR_MAIL_CHECK_TEXT_NA') ?>
											<? endif ?>
										</span>

										<span name="status-alert" class="post-dialog-stat-alert">
											<? if (isset($lastMailCheckSuccess)): ?>
												<?=getMessage($lastMailCheckSuccess ? 'INTR_MAIL_CHECK_SUCCESS' : 'INTR_MAIL_CHECK_ERROR') ?>
											<? endif ?>
										</span>
										<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>

										<span id="imap_check_form" class="webform-button">
											<?=getMessage('INTR_MAIL_CHECK') ?>
										</span>
									</div>
									<? $isCrmConfig = in_array('crm_connect', $arParams['MAILBOX']['OPTIONS']['flags']); ?>
									<? if ($isCrmConfig || $arParams['CRM_AVAILABLE']): ?>
										<div class="mail-set-item-block-bottom">
											<div class="mail-set-item-block-l">
												<span class="post-dialog-stat-text"><?=getMessage('INTR_MAIL_CRM_CONNECT2') ?>:</span>
												<? if ($isCrmConfig): ?>
													<span class="post-dialog-stat-alert"><?=getMessage('INTR_MAIL_CRM_ENABLED') ?></span>
													<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('imap_setup_crm_options', BX('imap_setup_crm_options').offsetHeight == 0); ">
														<?=getMessage('INTR_MAIL_CRM_CONFIG') ?>
													</span>
													<span class="webform-button mail-set-nomargin" id="imap_disable_crm">
														<?=getMessage('INTR_MAIL_CRM_DISABLE') ?>
													</span>
												<? else: ?>
													<span class="post-status-error">
														<?=getMessage(empty($arParams['CRM_PRECONNECT']) ? 'INTR_MAIL_CRM_DISABLED' : 'INTR_MAIL_CRM_ALMOST') ?>
													</span>
													<? if (empty($arParams['CRM_PRECONNECT'])): ?>
														<span class="webform-button mail-set-nomargin" onclick="toggleSubordinateBlock('imap_setup_crm_options', BX('imap_setup_crm_options').offsetHeight == 0); ">
															<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
														</span>
													<? else: ?>
														<span class="webform-button webform-button-disable mail-set-nomargin" name="enablecrm-button">
															<?=getMessage('INTR_MAIL_CRM_ENABLE') ?>
														</span>
													<? endif ?>
												<? endif ?>
											</div>
										</div>
										<div class="mail-set-item-block-crm" id="imap_setup_crm_options" style="display: none; margin-right: 25px; ">
											<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper" style="margin-top: 10px; ">
												<form id="imap_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm_form">
													<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>" >
													<?=bitrix_sessid_post() ?>
													<div class="mail-set-item-block-crm-wrapper-dec">
														<div class="mail-set-crm">
															<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_MAILBOX_OPTIONS') ?></div>
														</div>
														<? if ($isCrmConfig): ?>
															<? $imapDirsList = array_merge(
																$arParams['MAILBOX']['OPTIONS']['imap']['income'],
																$arParams['MAILBOX']['OPTIONS']['imap']['outcome']
															); ?>
															<div class="mail-set-crm-item">
																<label class="mail-set-crm-check-label" style="display: block; margin-bottom: -1px; padding-bottom: 1px; max-width: 500px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; ">
																	<?=getMessage('INTR_MAIL_IMAP_DIRS_LIST') ?>:
																	<a href="#" style="margin-left: 3px; color: #303031; border-bottom: 1px dashed #303031; "
																		onclick="loadImapDirsPopup(this, 'edit_imap_dirs'); return false; "
																		title="<?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?>" ><?=htmlspecialcharsbx(join(', ', $imapDirsList)) ?></a>
																</label>
																<div id="edit_imap_dirs">
																	<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['income'] as $item): ?>
																		<input type="hidden" name="imap_dirs[income][]" value="<?=htmlspecialcharsbx($item) ?>">
																	<? endforeach ?>
																	<? foreach ($arParams['MAILBOX']['OPTIONS']['imap']['outcome'] as $item): ?>
																		<input type="hidden" name="imap_dirs[outcome][]" value="<?=htmlspecialcharsbx($item) ?>">
																	<? endforeach ?>
																</div>
															</div>
															<? $blacklist = array_map('htmlspecialcharsbx', $arParams['BLACKLIST'] ?: array()) ?>
															<div class="mail-set-crm-item">
																<a href="#" class="mail-set-textarea-show <? if (!empty($blacklist)): ?>mail-set-textarea-show-open<? endif ?>"
																	onclick="toggleCrmBlacklist(this, 'edit_imap_crm_blacklist'); return false; "><?=getMessage('INTR_MAIL_CRM_BLACKLIST') ?></a>
																<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_HINT')) ?>" style="margin-left: 15px; "></span>
																<div class="mail-set-textarea-wrapper" id="edit_imap_crm_blacklist"
																	<? if (empty($blacklist)): ?> style="display: none; "<? endif ?>>
																	<div class="mail-set-textarea" id="mail-set-textarea">
																		<textarea class="mail-set-textarea-input" name="black_list"
																			placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_PROMPT')) ?>"><?
																			echo join(', ', $blacklist);
																		?></textarea>
																	</div>
																</div>
															</div>
														<? else: ?>
															<div class="mail-set-crm-item">
																<input class="mail-set-crm-check" id="imap_setup_sync_old" type="checkbox" name="sync_old" value="Y" checked>
																<label class="mail-set-crm-check-label" for="imap_setup_sync_old"><?=getMessage('INTR_MAIL_CRM_SYNC_OLD') ?></label>
																<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="imap_setup_max_age_3"
																	<? if ($limitedLicense): ?>onclick="showLicenseInfoPopup('age'); return false; "<? endif ?>>
																	<input type="radio" name="max_age" value="0">
																	<div class="mail-set-singleselect-wrapper">
																		<input type="radio" name="max_age" value="3" id="imap_setup_max_age_3" checked>
																		<label for="imap_setup_max_age_3"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_3') ?></label>
																		<input type="radio" name="max_age" value="-1" id="imap_setup_max_age_i">
																		<label for="imap_setup_max_age_i"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_I') ?></label>
																	</div>
																</label>
																<? if ($limitedLicense): ?>
																<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('age'); "></span>
																<? endif ?>
															</div>
														<? endif ?>
														<div class="mail-set-crm" style="margin-top: 20px; ">
															<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_OPTIONS') ?></div>
														</div>
														<div class="mail-set-crm-item">
															<? $crmNewLeadDenied = $isCrmConfig ? in_array('crm_deny_new_lead', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
															<input class="mail-set-crm-check" id="imap_setup_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y"
																<? if (!$crmNewLeadDenied): ?> checked<? endif ?>
																onclick="toggleSubordinateBlock('imap_setup_crm_new_lead_resp', this.checked); ">
															<label class="mail-set-crm-check-label" for="imap_setup_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
															<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																<div class="mail-set-crm-check-ext" id="imap_setup_crm_new_lead_resp"
																	<? if ($crmNewLeadDenied): ?> style="display: none; "<? endif ?>>
																	<? $defaultLeadSource = $isCrmConfig ? $arParams['MAILBOX_LEAD_SOURCE'] : $arParams['DEFAULT_LEAD_SOURCE']; ?>
																	<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																	<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																	<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="imap_setup_lead_source_<?=htmlspecialcharsbx($defaultLeadSource) ?>">
																		<input type="radio" name="lead_source" value="0">
																		<div class="mail-set-singleselect-wrapper">
																			<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																				<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="imap_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"
																					<? if ($value == $defaultLeadSource): ?> checked<? endif ?>>
																				<label for="imap_setup_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																			<? endforeach ?>
																		</div>
																	</label>
																</div>
															<? endif ?>
														</div>
														<div class="mail-set-crm-item">
															<? $crmNewContactDenied = $isCrmConfig ? in_array('crm_deny_new_contact', $arParams['MAILBOX']['OPTIONS']['flags']) : false; ?>
															<input class="mail-set-crm-check" id="imap_setup_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y"
																<? if (!$crmNewContactDenied): ?> checked<? endif ?>>
															<label class="mail-set-crm-check-label" for="imap_setup_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
														</div>
													</div>
													<div class="mail-set-item-block-crm-button">
														<span class="webform-small-button webform-button-accept" id="imap_<?=($isCrmConfig ? 'config' : 'enable') ?>_crm"><?=getMessage($isCrmConfig ? 'INTR_MAIL_INP_EDIT_SAVE' : 'INTR_MAIL_CRM_CONNECT_BUTTON') ?></span>
														<span class="webform-small-button" onclick="toggleSubordinateBlock('imap_setup_crm_options', false); "><?=getMessage('INTR_MAIL_INP_CANCEL') ?></span>
													</div>
												</form>
											</div>
										</div>
									<? endif ?>
								</div>
							</div>

							<div id="imap_pass_block" class="mail-set-item-block-wrap" <? if ($isOauthMailbox): ?> style="display: none; "<? endif ?>>
								<div class="mail-set-item-block-name"><?=getMessage('INTR_MAIL_MAILBOX_SETTINGS_MANAGE') ?></div>
								<div class="mail-set-item-block">
									<form id="imap_password_form">
										<? list($login, ) = explode('@', $emailAddress, 2); ?>
										<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID'] ?>" />
										<input name="login" type="hidden" value="<?=htmlspecialcharsbx($login) ?>" />
										<?=bitrix_sessid_post() ?>
										<div class="mail-set-item-block-r mail-set-item-block-without-label">
											<span class="webform-button" onclick="BX.hide(BX('imap_pass_block'), 'block'); BX.show(BX('edit_imap'), 'block'); setPost.animCurrent(); ">
												<?=getMessage('INTR_MAIL_MAILBOX_SETTINGS_GO') ?>
											</span>&nbsp;
										</div>
										<div class="mail-set-item-block-inp">
											<div class="mail-set-item">
												<div class="mail-set-first-label"><?=getMessage('INTR_MAIL_MAILBOX_PASSWORD') ?></div>
												<input name="password" class="mail-set-inp" type="password"/>
												<div name="pass-hint" class="mail-inp-description"></div>
												<span id="imap_password_save" name="password-save" class="webform-button webform-button-accept" style="margin-left: 25px; ">
													<?=getMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE_IMAP') ?>
												</span>
											</div>
										</div>
									</form>
								</div>
							</div>

							<div id="edit_imap" name="edit-imap" class="post-dialog-wrap" style="display: none; ">
								<form>
									<? $settings = $arParams['SERVICES'][$arParams['MAILBOX']['SERVICE_ID']]; ?>
									<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
										<span class="post-dialog-alert-align"></span>
										<span class="post-dialog-alert-icon"></span>
										<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
									</div>
									<input type="hidden" name="act" value="edit">
									<input type="hidden" name="SERVICE" value="<?=$settings['id'] ?>">
									<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID'] ?>">
									<?=bitrix_sessid_post() ?>
									<? if (empty($settings['link'])): ?>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_LINK') ?></span>
											<input id="link" name="link" type="text" class="post-dialog-inp" value="<?=htmlspecialcharsbx($arParams['MAILBOX']['LINK']) ?>">
											<div name="link-hint" class="mail-inp-description"></div>
										</div>
									<? endif ?>
									<? if (empty($settings['server'])): ?>
										<div class="post-dialog-inp-item">
											<div class="post-dialog-inp-serv">
												<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_SERVER') ?></span>
												<input id="server" name="server" type="text" class="post-dialog-inp" value="<?=htmlspecialcharsbx($arParams['MAILBOX']['SERVER']) ?>">
												<div name="server-hint" class="mail-inp-description"></div>
											</div><div class="post-dialog-inp-post">
												<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PORT') ?></span>
												<input id="port" name="port" type="text" class="post-dialog-inp" value="<?=$arParams['MAILBOX']['PORT'] ?>">
											</div>
										</div>
									<? endif ?>
									<? if (empty($settings['encryption'])): ?>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_ENCRYPT') ?></span>
											<span class="post-dialog-inp-select-wrap">
												<select name="encryption" class="post-dialog-inp-select">
													<option value="Y"<? if ($arParams['MAILBOX']['USE_TLS'] == 'Y'): ?> selected="selected"<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_YES') ?></option>
													<? if (PHP_VERSION_ID >= 50600): ?>
														<option value="S"<? if ($arParams['MAILBOX']['USE_TLS'] == 'S'): ?> selected="selected"<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_SKIP') ?></option>
													<? endif ?>
													<option value="N"<? if (!in_array($arParams['MAILBOX']['USE_TLS'], array('Y', 'S'))): ?> selected="selected"<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_NO') ?></option>
												</select>
											</span>
										</div>
									<? endif ?>
									<div class="post-dialog-inp-item">
										<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_LOGIN') ?></span>
										<input disabled type="text" class="post-dialog-inp" value="<?=htmlspecialcharsbx($arParams['MAILBOX']['LOGIN']) ?>">
									</div>
									<div class="post-dialog-inp-item">
										<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PASS') ?></span>
										<input name="password" type="password" class="post-dialog-inp">
										<div name="pass-hint" class="mail-inp-description"></div>
									</div>
									<div class="post-dialog-footer">
										<a id="imap_edit_save" name="edit-save" href="#" class="webform-button webform-button-accept">
											<?=getMessage('INTR_MAIL_INP_EDIT_SAVE') ?>
										</a>
										<span id="imap_edit_cancel" class="webform-button" onclick="BX.hide(BX('edit_imap'), 'block'); BX.show(BX('imap_pass_block'), 'block'); setPost.animCurrent(); ">
											<?=getMessage('INTR_MAIL_INP_CANCEL') ?>
										</span>
									</div>
									<input type="submit" style="position: absolute; visibility: hidden; ">
								</form>
							</div>
						</div>
					<? endif ?>

					<? $hasImap = false; ?>
					<div id="imap_icons" class="mail-set-img-wrap"<? if ($imapMailbox): ?> style="display: none; "<? endif ?>>
						<? foreach ($arParams['SERVICES'] as $id => $settings): ?>
							<? if ($settings['type'] != 'imap') continue; ?>
							<? $hasImap = true; ?>
							<a class="mail-set-serv" id="imap-<?=$id ?>-link" href="#imap-<?=$id ?>" name="imap-link"
								<? if (strlen($settings['name']) > 15): ?> style="font-size: 18px; "<? endif ?>
								onclick="toggleImapForm(this, <?=$id ?>); return false; "><?
								if ($settings['icon']): ?><img src="<?=$settings['icon'] ?>" alt="<?=htmlspecialcharsbx($settings['name']) ?>"><? else: ?>&nbsp;<?=htmlspecialcharsbx($settings['name']) ?>&nbsp;<? endif
							?></a>
						<? endforeach ?>
					</div>

					<? if (!$hasImap): ?>
						<div style="text-align: center; ">
							<br><br>
							<?=getMessage('MAIL_SERVICES_NOT_FOUND') ?>
							<br><br><br>
						</div>
					<? endif ?>

					<div id="create_imap" class="mail-set-imap-cont-wrap" style="display: none; ">

						<? if (!empty($arParams['MAILBOX']) && !$imapMailbox): ?>
							<div id="imap_block_replace_warning">
								<br/><br/>
								<div class="mail-set-item-block mail-set-item-icon">
									<span class="mail-set-item-text">
										<? if (empty($emailAddress)): ?>
											<?=getMessage('INTR_MAIL_REPLACE_WARNING_UN') ?>
										<? else: ?>
											<?=getMessage('INTR_MAIL_REPLACE_WARNING', array('#EMAIL#' => htmlspecialcharsbx($emailAddress))) ?>
										<? endif ?>
									</span>
								</div>
							</div>
						<? endif ?>

						<? foreach ($arParams['SERVICES'] as $id => $settings): ?>
							<? if ($settings['type'] != 'imap') continue; ?>

							<div id="create_imap_<?=$id ?>" name="create-imap" class="post-dialog-wrap" style="display: none; ">
								<form>
									<div style="padding-bottom: 20px; height: 55px; max-width: 130px; font: bold 22px/50px Arial; color: #585858; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; "><?
										if ($settings['icon']): ?><img style="max-height: 80%; max-width: 100%; vertical-align: middle; " src="<?=$settings['icon'] ?>" alt="<?=htmlspecialcharsbx($settings['name']) ?>"><? else: ?><?=htmlspecialcharsbx($settings['name']) ?><?	endif
									?></div>
									<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
										<span class="post-dialog-alert-align"></span>
										<span class="post-dialog-alert-icon"></span>
										<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
									</div>
									<input type="hidden" name="act" value="create">
									<input type="hidden" name="SERVICE" value="<?=$id ?>">
									<? if (!empty($arParams['MAILBOX'])): ?>
										<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID'] ?>">
									<? endif ?>
									<?=bitrix_sessid_post() ?>
									<? if (empty($settings['link'])): ?>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_LINK') ?></span>
											<input id="link" name="link" type="text" class="post-dialog-inp">
											<div name="link-hint" class="mail-inp-description"></div>
										</div>
									<? endif ?>
									<? if (empty($settings['server'])): ?>
										<div class="post-dialog-inp-item">
											<div class="post-dialog-inp-serv">
												<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_SERVER') ?></span>
												<input id="server" name="server" type="text" class="post-dialog-inp">
												<div name="server-hint" class="mail-inp-description"></div>
											</div><div class="post-dialog-inp-post">
												<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PORT') ?></span>
												<input id="port" name="port" type="text" class="post-dialog-inp">
											</div>
										</div>
									<? endif ?>
									<? if (empty($settings['encryption'])): ?>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_ENCRYPT') ?></span>
											<span class="post-dialog-inp-select-wrap">
												<select name="encryption" class="post-dialog-inp-select">
													<option value="Y" selected="selected"><?=getMessage('INTR_MAIL_INP_ENCRYPT_YES') ?></option>
													<? if (PHP_VERSION_ID >= 50600): ?>
														<option value="S"><?=getMessage('INTR_MAIL_INP_ENCRYPT_SKIP') ?></option>
													<? endif ?>
													<option value="N"><?=getMessage('INTR_MAIL_INP_ENCRYPT_NO') ?></option>
												</select>
											</span>
										</div>
									<? endif ?>
									<? if (empty($settings['oauth'])): ?>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_EMAIL') ?></span>
											<input name="email" type="text" class="post-dialog-inp">
											<div name="email-hint" class="mail-inp-description"></div>
										</div>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_LOGIN') ?></span>
											<input name="login" type="text" class="post-dialog-inp">
											<div name="login-hint" class="mail-inp-description"></div>
										</div>
										<div class="post-dialog-inp-item">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PASS') ?></span>
											<input name="password" type="password" class="post-dialog-inp">
											<div name="pass-hint" class="mail-inp-description"></div>
										</div>
									<? else: ?>
										<input type="hidden" name="oauth" value="<?=htmlspecialcharsbx($settings['oauth']->getUrl('opener', $settings['oauth_scope'], array('BACKURL' => uniqid('#oauth')))) ?>">
									<? endif ?>
									<? if ($arParams['CRM_AVAILABLE']): ?>
										<div class="mail-set-item-block-crm" id="mail-set-item-block-crm">
											<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper">
												<div class="mail-set-item-block-crm-wrapper-dec">
													<div class="mail-set-crm">
														<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_CONNECT') ?></div>
													</div>
													<div class="mail-set-crm-item">
														<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_connect" type="checkbox" name="crm_connect" value="Y" checked
															onclick="toggleSubordinateBlock('create_imap_<?=$id ?>_crm_options', this.checked); ">
														<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_connect"><?=getMessage('INTR_MAIL_INP_CRM') ?></label>
													</div>
													<div id="create_imap_<?=$id ?>_crm_options">
														<div class="mail-set-crm-item">
															<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_sync_old" type="checkbox" name="sync_old" value="Y" checked>
															<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_sync_old"><?=getMessage('INTR_MAIL_CRM_SYNC_OLD') ?></label>
															<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_max_age_3"
																<? if ($limitedLicense): ?>onclick="showLicenseInfoPopup('age'); return false; "<? endif ?>>
																<input type="radio" name="max_age" value="0">
																<div class="mail-set-singleselect-wrapper">
																	<input type="radio" name="max_age" value="3" id="create_imap_<?=$id ?>_max_age_3" checked>
																	<label for="create_imap_<?=$id ?>_max_age_3"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_3') ?></label>
																	<input type="radio" name="max_age" value="-1" id="create_imap_<?=$id ?>_max_age_i">
																	<label for="create_imap_<?=$id ?>_max_age_i"><?=getMessage('INTR_MAIL_CRM_SYNC_AGE_I') ?></label>
																</div>
															</label>
															<? if ($limitedLicense): ?>
																<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('age'); "></span>
															<? endif ?>
														</div>
														<div class="mail-set-crm-item">
															<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_new_lead" type="checkbox" name="crm_new_lead" value="Y" checked
																onclick="toggleSubordinateBlock('create_imap_<?=$id ?>_crm_new_lead_resp', this.checked); ">
															<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_lead"><?=getMessage('INTR_MAIL_INP_CRM_NEW_LEAD') ?></label>
															<? if (!empty($arParams['LEAD_SOURCE_LIST'])): ?>
																<div class="mail-set-crm-check-ext" id="create_imap_<?=$id ?>_crm_new_lead_resp">
																	<input class="mail-set-crm-check" type="checkbox" style="visibility: hidden; ">
																	<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_LEAD_SOURCE') ?>:</label>
																	<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_lead_source_<?=htmlspecialcharsbx($arParams['DEFAULT_LEAD_SOURCE']) ?>">
																		<input type="radio" name="lead_source" value="0">
																		<div class="mail-set-singleselect-wrapper">
																			<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																				<input type="radio" name="lead_source" value="<?=htmlspecialcharsbx($value) ?>" id="create_imap_<?=$id ?>_lead_source_<?=htmlspecialcharsbx($value) ?>"
																					<? if ($value == $arParams['DEFAULT_LEAD_SOURCE']): ?> checked<? endif ?>>
																				<label for="create_imap_<?=$id ?>_lead_source_<?=htmlspecialcharsbx($value) ?>"><?=htmlspecialcharsbx($title) ?></label>
																			<? endforeach ?>
																		</div>
																	</label>
																</div>
															<? endif ?>
														</div>
														<div class="mail-set-crm-item">
															<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y" checked>
															<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
														</div>
													</div>
												</div>
											</div>
										</div>
									<? endif ?>
									<div class="post-dialog-footer">
										<a id="imap_<?=$id ?>_create_save" name="create-save" href="#" class="webform-button webform-button-accept">
											<?=getMessage('INTR_MAIL_INP_SAVE') ?>
										</a>
										<span id="imap_<?=$id ?>_create_cancel" class="webform-button" onclick="toggleImapForm(BX('imap-<?=$id ?>-link'), <?=$id ?>); return false; ">
											<?=getMessage('INTR_MAIL_INP_CANCEL') ?>
										</span>
									</div>
									<input type="submit" style="position: absolute; visibility: hidden; ">
								</form>
							</div>

						<? endforeach ?>

						<div class="mail-set-cont-right">
							<div class="mail-set-second-info" id="create_imap_info">
								<?=getMessage('INTR_MAIL_IMAP_HELP') ?>
							</div>
							<div class="mail-set-second-info" id="create_imap_info_oauth" style="display: none; ">
								<?=getMessage('INTR_MAIL_IMAP_HELP_OAUTH') ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="mail-set-corner" class="mail-set-corner"<? if (!$showB24Block && !$showDomainBlock): ?> style="display: none; " <? endif ?>></div>
		</div>
	</div>
	<? if ($showB24Block || $showDomainBlock): ?>
		<div id="mail-info-message" class="mail-info-message"<? if (!empty($arParams['MAILBOX'])): ?> style="display: none; "<? endif ?>>
			<?=getMessage(isModuleInstalled('bitrix24') ? 'INTR_MAIL_HINT_B24_CRM' : 'INTR_MAIL_HINT_BOX_CRM') ?>
		</div>
	<? endif ?>
</div>

<script type="text/javascript">

	function toggleSubordinateBlock(id, show)
	{
		var block = BX(id);

		block.style.transition = 'none';

		if (show)
		{
			if (block.offsetHeight == 0)
			{
				BX('mail-set-block').style.height = '';

				block.style.visibility = 'hidden';
				block.style.position   = 'absolute';
				block.style.overflow   = '';
				block.style.height     = '';
				block.style.display    = '';

				var hhh = block.offsetHeight;

				block.style.height     = '0px';
				block.style.overflow   = 'hidden';
				block.style.position   = '';
				block.style.visibility = '';
				block.style.transition = 'height .1s';

				block.offsetHeight; // o.O
				block.style.height = hhh+'px';

				setTimeout(function()
				{
					block.style.overflow = '';
					block.style.height   = '';
					BX('mail-set-block').style.height = BX('mail-set-block').offsetHeight+'px';
				}, 100);
			}
			else
			{
				block.style.overflow   = '';
				block.style.position   = '';
				block.style.visibility = '';
				block.style.height     = '';
				block.style.display    = '';
			}
		}
		else
		{
			if (block.offsetHeight > 0)
			{
				BX('mail-set-block').style.height = '';

				block.style.height     = block.offsetHeight+'px';
				block.style.overflow   = 'hidden';
				block.style.position   = '';
				block.style.visibility = '';
				block.style.transition = 'height .1s';

				block.offsetHeight; // o.O
				block.style.height = '0px';

				setTimeout(function()
				{
					BX('mail-set-block').style.height = BX('mail-set-block').offsetHeight+'px';
				}, 100);
			}
			else
			{
				block.style.display = '';
			}
		}
	}

	function toggleImapForm(link, id)
	{
		var createImap = BX('create_imap');
		var createImapCurrent = BX('create_imap_'+id);

		if (createImap.style.display == 'none')
		{
			BX.addClass(link, 'mail-set-serv-active');

			createImap.style.display = 'block';
			createImapCurrent.style.display = 'block';
		}
		else
		{
			if (createImapCurrent.style.display == 'none')
			{
				var links = BX.findChildren(BX('imap_icons'), {attr: {name: 'imap-link'}}, true);
				for (var i in links)
					BX.removeClass(links[i], 'mail-set-serv-active');

				var forms = BX.findChildren(createImap, {attr: {name: 'create-imap'}}, true);
				for (var i in forms)
				{
					BX.onCustomEvent(forms[i], 'HideImapForm');
					forms[i].style.display = 'none';
				}

				BX.addClass(link, 'mail-set-serv-active');
				createImapCurrent.style.display = 'block';
			}
			else
			{
				BX.removeClass(link, 'mail-set-serv-active');

				BX.onCustomEvent(createImapCurrent, 'HideImapForm');
				createImap.style.display = 'none';
				createImapCurrent.style.display = 'none';
			}
		}

		if (BX.findChild(createImapCurrent, {tag: 'input', attr: {name: 'oauth'}}, true))
		{
			BX.hide(BX('create_imap_info'), 'block');
			BX.show(BX('create_imap_info_oauth'), 'block');
		}
		else
		{
			BX.hide(BX('create_imap_info_oauth'), 'block');
			BX.show(BX('create_imap_info'), 'block');
		}

		setPost.anim('mail-set-third', BX('mail-set-third-btn'));
	}

	function inputPlaceholder(input, text, isFake)
	{
		var isFake = isFake == false ? false : true;

		BX.adjust(input, {attrs: {'data-placeholder': text}});

		if (input.value == '')
		{
			if (isFake)
				BX.addClass(input, 'post-dialog-inp-placeholder');
			input.value = text;
		}

		BX.bind(input, 'focus', function() {
			if (!isFake)
			{
				setTimeout(function() {
					input.select();
				}, 0);
			}
			else
			{
				if (input.value == text && BX.hasClass(input, 'post-dialog-inp-placeholder'))
					input.value = '';
			}
			BX.removeClass(input, 'post-dialog-inp-placeholder');
		});
		BX.bind(input, 'blur', function() {
			if (input.value == '')
			{
				BX.addClass(input, 'post-dialog-inp-placeholder');
				input.value = text;
			}
		});
	}

	function toggleCrmBlacklist(link, blacklistId)
	{
		var blacklist = BX(blacklistId);
		var openClass = BX.hasClass(link, 'post-dialog-stat-link')
			? 'post-dialog-stat-link-open'
			: 'mail-set-textarea-show-open';

		if (blacklist.offsetHeight == 0)
		{
			BX.addClass(link, openClass);
			toggleSubordinateBlock(blacklistId, true);
		}
		else
		{
			BX.removeClass(link, openClass);
			toggleSubordinateBlock(blacklistId, false);
		}
	}

	function showLicenseInfoPopup(id)
	{
		B24.licenseInfoPopup.show(
			'mail_setup_'+id,
			'<?=CUtil::jsEscape(getMessage('MAIL_MAIL_CRM_LICENSE_TITLE')) ?>',
			'<?=CUtil::jsEscape(getMessage('MAIL_MAIL_CRM_LICENSE_DESCR_AGE')) ?>'
		);
	}

	var linkInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'link'}}, true);
	for (var i in linkInputs)
		inputPlaceholder(linkInputs[i], 'http://mail.example.com', true);

	var serverInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'server'}}, true);
	for (var i in serverInputs)
		inputPlaceholder(serverInputs[i], 'imap.example.com', true);

	var portInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'port'}}, true);
	for (var i in portInputs)
		inputPlaceholder(portInputs[i], '993', false);

	var emailInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'email'}}, true);
	for (var i in emailInputs)
		inputPlaceholder(emailInputs[i], 'info@example.com', true);

	var passInputs = BX.findChildren(BX('edit_imap'), {tag: 'input', attr: {name: 'password'}}, true);
	for (var i in passInputs)
		inputPlaceholder(passInputs[i], '********', true);


	var singleselect = function(input)
	{
		var options = BX.findChildren(input, {tag: 'input', attr: {type: 'radio'}}, true);
		for (var i in options)
		{
			BX.bind(options[i], 'change', function()
			{
				if (this.checked && this.value != 0)
					input.setAttribute('data-checked', this.id);
			});
		}

		BX.bind(input, 'click', function(event)
		{
			event = event || window.event;
			event.skip_singleselect = input;
		});

		BX.bind(document, 'click', function(event)
		{
			event = event || window.event;
			if (event.skip_singleselect !== input)
				BX(input.getAttribute('data-checked')).checked = true;
		});
	};

	var selectInputs = BX.findChildrenByClassName(BX('mail-set-block'), 'mail-set-singleselect', true);
	for (var i in selectInputs)
		singleselect(selectInputs[i]);

	var setPost = {
		corner : BX('mail-set-corner'),
		anim_block : null,
		btn : null,
		wrap_block : BX('mail-set-block'),
		block_list : null,
		table : BX('mail-block-table'),
		active_cell_num : null,
		over_cell_num : null,

		show : function(ev)
		{
			var event = ev || window.event;
			var target = event.target || event.srcElement;
			var active_cell,
				btn;

			while(target != this)
			{
				if (target.tagName == 'TD') {
					active_cell = target;
					break;
				}
				target = target.parentNode;
			}

			if(!active_cell.hasAttribute('data-block')) return;

			if(event.type == 'mouseover'){
				setPost.block_hover(active_cell);
			}
			else if (event.type == 'mouseout'){
				setPost.block_out();
			}
			else if(event.type == 'click')
			{
				var blockID = active_cell.getAttribute('data-block');

				if(blockID == 'mail-set-first'){
					btn = BX('mail-set-first-btn')
				}else if(blockID == 'mail-set-second'){
					btn = BX('mail-set-second-btn')
				}else if(blockID == 'mail-set-third'){
					btn = BX('mail-set-third-btn')
				}
				setPost.anim(blockID, btn)
			}
		},

		animCurrent: function()
		{
			var activeCell = BX.findChild(BX('mail-block-table'), {'class': 'mail-block-active'}, true, false);
			var blockID = activeCell.getAttribute('data-block');

			switch (blockID)
			{
				case 'mail-set-first':
					btn = BX('mail-set-first-btn');
					break;
				case 'mail-set-second':
					btn = BX('mail-set-second-btn');
					break;
				case 'mail-set-third':
					btn = BX('mail-set-third-btn');
					break;
			}

			if (blockID && btn)
				setPost.anim(blockID, btn);
		},

		anim : function(blockID, btn)
		{
			this.block_list = this.wrap_block.childNodes;

			this.anim_block = BX(blockID);
			this.btn = btn;

			this.wrap_block.style.height = this.wrap_block.offsetHeight + 'px';

			for(var i = this.block_list.length-1; i>=0; i--){
				if(this.block_list[i].tagName == 'DIV' && this.block_list[i] != this.corner){
					this.block_list[i].style.display = 'none';
				}
			}

			this.anim_block.style.display = 'block';

			var corner_offset =  ((this.btn.offsetWidth/2) + BX.pos(this.btn).left) - ((this.corner.offsetWidth/2) + BX.pos(this.corner).left);
			this.corner.style.left = parseInt(BX.style(this.corner, 'left')) + corner_offset + 'px';

			this.wrap_block.style.height = this.anim_block.offsetHeight +'px';

			for(var i = this.table.rows.length-1; i >=0; i--){
				for(var b = this.table.rows[i].cells.length-1; b>=0; b--)
				{
					BX.removeClass(this.table.rows[i].cells[b], 'mail-block-active');

					if(this.btn.parentNode.parentNode == this.table.rows[i].cells[b]){
						this.active_cell_num = b;
					}
				}
			}

			BX.addClass(this.table.rows[0].cells[this.active_cell_num], 'mail-block-active');
			BX.addClass(this.table.rows[1].cells[this.active_cell_num], 'mail-block-active')
		},

		block_hover : function(cell)
		{
			var tr;
			tr = cell.parentNode;

			for(var i = tr.cells.length-1; i>=0; i--){
				if(tr.cells[i] == cell){
					this.over_cell_num = i;
				}
			}

			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.addClass(this.table.rows[i].cells[this.over_cell_num] ,'mail-block-hover')
			}
		},

		block_out : function()
		{
			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.removeClass( this.table.rows[i].cells[this.over_cell_num] ,'mail-block-hover')
			}
		}
	};

	BX.bind(BX('mail-block-table'), 'mouseover', setPost.show);
	BX.bind(BX('mail-block-table'), 'mouseout', setPost.show);
	BX.bind(BX('mail-block-table'), 'click', setPost.show);

	<? if ($defaultBlock == 'domain'): ?>
		setTimeout(function () {
			setPost.anim('mail-set-second', BX('mail-set-second-btn'));
		}, 10);
	<? endif ?>
	<? if ($defaultBlock == 'imap'): ?>
		setTimeout(function () {
			setPost.anim('mail-set-third', BX('mail-set-third-btn'));
		}, 10);
	<? endif ?>

</script>

<script type="text/javascript">

	var imapDirsPopup = new BX.PopupWindow('imap_dirs_popup', null, {
		titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_TITLE')) ?>',
		closeIcon: true,
		overlay: true,
		lightShadow: true
	});

	function loadImapDirsPopup(link, form)
	{
		imapDirsPopup.setContent('<div style="width: 500px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_LOADER')) ?></div>');
		imapDirsPopup.setButtons([]);

		imapDirsPopup.resizeOverlay();
		imapDirsPopup.show();

		var form = BX(form);
		var callback = function(params)
		{
			var items = [];

			while (form.childNodes.length > 0)
				form.removeChild(form.childNodes[0]);

			for (var i in params)
			{
				if ('subact' == i)
					continue;

				items.push(params[i]);

				var input = document.createElement('INPUT');

				input.type  = 'hidden';
				input.name  = i;
				input.value = params[i];

				form.appendChild(input);
			}

			var text = items.join(', ');
			BX.adjust(link, {attrs: {title: text}, text: text});
		};

		var data = {
			sessid: '<?=bitrix_sessid() ?>'
		};
		for (var i = 0; i < form.childNodes.length; i++)
		{
			var item = form.childNodes[i];

			if (item.name && item.value)
			{
				if (item.name.match(/\[\]$/))
				{
					var pname = item.name.substr(0, item.name.length-2);

					if (typeof data[pname] == 'undefined')
						data[pname] = [];

					data[pname].push(item.value);
				}
				else
				{
					data[item.name] = item.value;
				}
			}
		}

		BX.ajax({
			method: 'POST',
			url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=imapdirs',
			data: data,
			dataType: 'json',
			onsuccess: function(json)
			{
				if (json.result != 'error')
					showImapDirsPopup(json.imap_dirs, callback);
				else
					imapDirsPopup.setContent('<div style="width: 500px; ">'+json.error+'</div>');
			},
			onfailure: function()
			{
				imapDirsPopup.setContent('<div style="width: 500px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?></div>');
			}
		});
	}

	function showImapDirsPopup(dirs, callback, fallback)
	{
		var html = '<form name="imap-dirs-form" style="min-width: 500px; ">';

		html += '<div class="mail-set-popup-text" style="max-width: 500px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_DESCR_2')) ?></div>';

		html += '<div name="post-dialog-alert" class="post-dialog-alert imap-dirs-popup-error" style="display: none; ">';
		html += '<span class="post-dialog-alert-align"></span>&nbsp;<span class="post-dialog-alert-icon"></span>&nbsp;';
		html += '<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>';
		html += '</div>';

		html += '<div class="mail-set-popup-text-title"><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_IN')) ?>:</div>';
		html += '<div class="mail-set-param">';

		var count = dirs.length;

		for (var i = 0, p = []; i < count; i++)
		{
			dirs[i].i = i;
			dirs[i].p = p[dirs[i].level-1];

			dirs[i].incomep  = dirs[i].income && !dirs[i].disabled ? dirs[i].level : -1;
			dirs[i].outcomep = dirs[i].outcome && !dirs[i].disabled ? dirs[i].level : -1;

			p[dirs[i].level] = dirs[i];
		}

		for (var i = count-1; i >= 0; i--)
		{
			if (dirs[i].level > 0) // dirs[i].p
			{
				if (dirs[i].incomep >= 0 && (dirs[i].p.incomep < 0 || dirs[i].incomep < dirs[i].p.incomep))
					dirs[i].p.incomep = dirs[i].incomep;

				if (dirs[i].outcomep >= 0 && (dirs[i].p.outcomep < 0 || dirs[i].outcomep < dirs[i].p.outcomep))
					dirs[i].p.outcomep = dirs[i].outcomep;
			}
		}

		var compf = function(a, b)
		{
			if (a.level > b.level)
				return a.p.i == b.i ? a.i-b.i : compf(a.p, b);

			if (a.level < b.level)
				return a.i == b.p.i ? a.i-b.i : compf(a, b.p);

			if (a.incomep != b.incomep)
				return a.incomep < 0 || b.incomep >= 0 && b.incomep < a.incomep ? 1 : -1;

			if (a.outcomep != b.outcomep)
				return a.outcomep < 0 || b.outcomep >= 0 && b.outcomep < a.outcomep ? 1 : -1;

			return a.i-b.i;
		};

		dirs.sort(compf);

		var incomeLimit  = 5;
		var outcomeLimit = 5;
		for (var i = 5; i < count; i++)
		{
			if (dirs[i].income && !dirs[i].disabled)
				incomeLimit = (i+1);
			if (dirs[i].outcome && !dirs[i].disabled)
				outcomeLimit = (i+1);
		}

		if (count-incomeLimit <= 2)
			incomeLimit = 0;
		if (count-outcomeLimit <= 2)
			outcomeLimit = 0;

		for (var i = 0; i < count; i++)
		{
			var flag = dirs[i].disabled ? 'disabled' : (dirs[i].income ? 'checked' : '');

			if (incomeLimit > 0 && i == incomeLimit)
			{
				var onclick = "BX('imap_dirs_income_more').style.display = ''; this.style.display = 'none'; imapDirsPopup.resizeOverlay(); return false;";

				html += '<a href="#" onclick="'+onclick+'"><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_MORE')) ?></a>';
				html += '<div id="imap_dirs_income_more" style="display: none; ">';
			}

			html += '<div class="mail-set-param-item" style="padding-left: '+(20*dirs[i].level)+'px">';
			html += '<input id="imap-dir-income-n'+i+'" type="checkbox" name="imap_dirs[income]['+i+']" value="'+dirs[i].path+'" '+flag+'>';
			html += '<label for="imap-dir-income-n'+i+'" '+(dirs[i].disabled ? 'style="color: #a0a0a0;"' : '')+'>'+dirs[i].name+'</label>';
			html += '</div>';
		}

		if (incomeLimit > 0)
			html += '</div>';

		html += '</div>';

		html += '<div class="mail-set-popup-text-title"><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_OUT_2')) ?>:</div>';
		html += '<div class="mail-set-param">';

		for (var i = 0; i < count; i++)
		{
			var flag = dirs[i].disabled ? 'disabled' : (dirs[i].outcome ? 'checked' : '');

			if (outcomeLimit > 0 && i == outcomeLimit)
			{
				var onclick = "BX('imap_dirs_outcome_more').style.display = ''; this.style.display = 'none'; imapDirsPopup.resizeOverlay(); return false;";

				html += '<a href="#" onclick="'+onclick+'"><?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_MORE')) ?></a>';
				html += '<div id="imap_dirs_outcome_more" style="display: none; ">';
			}

			html += '<div class="mail-set-param-item" style="padding-left: '+(20*dirs[i].level)+'px">';
			html += '<input id="imap-dir-outcome-n'+i+'" type="radio" name="imap_dirs[outcome][]" value="'+dirs[i].path+'" '+flag+'>';
			html += '<label for="imap-dir-outcome-n'+i+'" '+(dirs[i].disabled ? 'style="color: #a0a0a0;"' : '')+'>'+dirs[i].name+'</label>';
			html += '</div>';
		}

		if (outcomeLimit > 0)
			html += '</div>';

		html += '</div>';
		html += '</form>';

		imapDirsPopup.setContent(html);
		imapDirsPopup.setButtons([
			new BX.PopupWindowButton({
				text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_SAVE')) ?>',
				className: 'popup-window-button-accept',
				events: {
					click: function()
					{
						var flag = 0;

						var imapDirsData = {
							subact: 'imapdirs'
						};
						var imapDirsForm = BX.findChild(this.popupWindow.contentContainer, {attr: {name: 'imap-dirs-form'}}, true, false);
						for (var i = 0; i < imapDirsForm.elements.length; i++)
						{
							if (imapDirsForm.elements[i].name)
							{
								if (imapDirsForm.elements[i].checked)
								{
									if (imapDirsForm.elements[i].name.match(/^imap_dirs\[income\]\[\d*\]/))
										flag = flag | 1;
									if (imapDirsForm.elements[i].name.match(/^imap_dirs\[outcome\]\[\d*\]/))
										flag = flag | 2;

									imapDirsData[imapDirsForm.elements[i].name] = imapDirsForm.elements[i].value;
								}
							}
						}

						if (flag & 1 && flag & 2)
						{
							this.popupWindow.close();
							callback(imapDirsData);
						}
						else
						{
							var alert = BX.findChildByClassName(imapDirsForm, 'imap-dirs-popup-error', true);

							BX.adjust(BX.findChildByClassName(alert, 'post-dialog-alert-text', true), {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_IMAP_DIRS_ERROR')) ?>'});
							BX.show(alert, 'block');
						}
					}
				}
			}),
			new BX.PopupWindowButton({
				text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')) ?>',
				className: 'popup-window-button',
				events: {
					click: function()
					{
						this.popupWindow.close();

						if (fallback)
							fallback();
					}
				}
			})
		]);

		imapDirsPopup.resizeOverlay();
		imapDirsPopup.show();
	}

	<? if ($arParams['CRM_AVAILABLE'] && !empty($arParams['CRM_PRECONNECT']) && !empty($arParams['IMAP_DIRS'])): ?>

	(function() {

		var form = BX('<?=($b24Mailbox ? 'b24' : ($domainMailbox ? 'domain' : 'imap')) ?>_setup_form');

		var formButton = BX.findChild(form, {attr: {name: 'enablecrm-button'}}, true, false);
		var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

		var showAlert = function(message, details, error)
		{
			var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

			var text = message;
			if (details && details.length > 0)
			{
				text += ' <span style="font-weight: normal; ">(';
				text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; setPost.animCurrent(); return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
				text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
				text += ')</span>';
			}

			BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
			BX.adjust(textCont, {html: text});
			BX.show(alert, 'block');
			setPost.animCurrent();
		};

		var enableCrm = function(data)
		{
			BX.addClass(formButton, 'webform-button-active webform-button-wait');

			BX.hide(alert, 'block');
			setPost.animCurrent();

			data.sessid = '<?=bitrix_sessid() ?>';

			BX.ajax({
				method: 'POST',
				url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=enablecrm',
				data: data,
				dataType: 'json',
				onsuccess: function(json)
				{
					if (json.result != 'error')
					{
						window.location = '?page=home';
					}
					else
					{
						if (json.imap_dirs)
							showImapDirsPopup(json.imap_dirs, enableCrm, disableCrm);

						BX.removeClass(formButton, 'webform-button-active webform-button-wait');
						showAlert(json.error, json.error_ext, true);
					}
				},
				onfailure: function()
				{
					BX.removeClass(formButton, 'webform-button-active webform-button-wait');
					showAlert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
				}
			});
		};

		var disableCrm = function()
		{
			BX.addClass(formButton, 'webform-button-active webform-button-wait');

			BX.hide(alert, 'block');
			setPost.animCurrent();

			BX.ajax({
				method: 'POST',
				url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=disablecrm',
				data: '<?=bitrix_sessid_get() ?>',
				dataType: 'json',
				onsuccess: function(json)
				{
					if (json.result != 'error')
					{
						window.location = '?page=home';
					}
					else
					{
						BX.removeClass(formButton, 'webform-button-active webform-button-wait');
						showAlert(json.error, json.error_ext, true);
					}
				},
				onfailure: function()
				{
					BX.removeClass(formButton, 'webform-button-active webform-button-wait');
					showAlert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
				}
			});
		};

		setTimeout(function() {
			showImapDirsPopup(<?=CUtil::phpToJsObject($arParams['IMAP_DIRS']) ?>, enableCrm, disableCrm);
		}, 0);

	})();

	<? endif ?>

	(function() {

		var hasMailbox = <?=(empty($arParams['MAILBOX']) ? 'false' : 'true') ?>;

		function CreateMailboxForm(form, loginMinLength)
		{
			var self = this;

			var form = form;
			var loginMinLength = typeof loginMinLength == 'undefined' ? 1 : loginMinLength;

			var loginCont = form.elements['login'].parentNode;
			var loginHint = BX.findChild(loginCont, {attr: {name: 'login-hint'}}, true, false);
			var badLoginHint = BX.findChild(form, {attr: {name: 'bad-login-hint'}}, true, false);

			var passCont = form.elements['password'].parentNode;
			var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);
			var pass2Cont = form.elements['password2'].parentNode;
			var pass2Hint = BX.findChild(pass2Cont, {attr: {name: 'pass2-hint'}}, true, false);

			var cnTimeout = false;
			var cnAjax    = false;
			var cnResults = {};

			var lastKey = false;
			var nameWasFilled = false;
			this.checkName = function(e)
			{
				var data = {
					SERVICE: form.elements['SERVICE'].value,
					login: form.elements['login'].value,
					domain: form.elements['domain'].value
				};
				var key = data.SERVICE+'/'+data.login+'/'+data.domain;

				if (key == lastKey && typeof cnAjax == 'object')
					return;
				lastKey = key;

				cnTimeout = clearTimeout(cnTimeout);
				if (typeof cnAjax == 'object')
				{
					cnAjax.abort();
					cnAjax = false;
				}

				BX.removeClass(loginCont, 'mail-set-error');
				BX.removeClass(loginCont, 'mail-set-ok');
				BX.cleanNode(loginHint);
				BX.hide(badLoginHint, 'block');

				if (data.login.length > 0 && !data.login.match(/^[a-z0-9_]+(\.?[a-z0-9_-]+)*\.?$/i))
				{
					nameWasFilled = true;

					BX.addClass(loginCont, 'mail-set-error');
					BX.adjust(loginHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_BAD')) ?>'});

					if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']))
						BX.show(badLoginHint, 'block');

					return;
				}

				if (data.login.length >= loginMinLength)
				{
					nameWasFilled = true;

					if (data.login.length > 30)
					{
						BX.addClass(loginCont, 'mail-set-error');
						BX.adjust(loginHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_LONG')) ?>'});

						return;
					}
				}
				else
				{
					if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']) && !nameWasFilled);
					else
					{
						nameWasFilled = true;

						BX.addClass(loginCont, 'mail-set-error');
						BX.adjust(loginHint, {
							text: data.login.length == 0
								? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_EMPTY')) ?>'
								: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_SHORT')) ?>'
						});
					}

					return;
				}

				var handleResponse = function(json)
				{
					BX.removeClass(form.elements['login'], 'mail-set-inp-wait');

					if (typeof json == 'undefined')
						return;

					if (json.result != 'error')
					{
						BX.addClass(loginCont, json.occupied ? 'mail-set-error' : 'mail-set-ok');
						BX.adjust(loginHint, {text: json.occupied ? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_OCCUPIED')) ?>' : '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_FREE')) ?>'});
					}

					if (typeof cnResults[key] == 'undefined')
						cnResults[key] = json;
				};
				if (typeof cnResults[key] == 'undefined')
				{
					cnTimeout = setTimeout(function() {

						if (!data.login.match(/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*$/i))
						{
							BX.addClass(loginCont, 'mail-set-error');
							BX.adjust(loginHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_NAME_BAD')) ?>'});

							if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']))
								BX.show(badLoginHint, 'block');

							return;
						}

						BX.addClass(form.elements['login'], 'mail-set-inp-wait');

						cnAjax = BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=name',
							data: data,
							dataType: 'json',
							onsuccess: handleResponse,
							onfailure: function() {
								BX.removeClass(form.elements['login'], 'mail-set-inp-wait');
							}
						});
					}, typeof e == 'object' && e.type == 'keyup' ? 400 : 0);
				}
				else
				{
					handleResponse(cnResults[key]);
				}
			};

			this.checkPassword = function(e)
			{
				if (!form.elements['password'].value.match(/^[\x21\x23-\x26\x28-\x2E\x30-\x3B\x40-\x5A\x5E\x5F\x61-\x7A]*$/))
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_BAD')) ?>'});
				}
				else if (form.elements['password'].value.length < 6)
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {
						text: form.elements['password'].value.length == 0
							? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_EMPTY')) ?>'
							: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_SHORT')) ?>'
					});
				}
				else if (form.elements['password'].value.length > 20)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_LONG')) ?>'});
				}
				else if (form.elements['password'].value == form.elements['login'].value)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_LIKELOGIN')) ?>'});
				}
				else
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}

				if (form.elements['password2'].value.length > 0)
					self.checkPassword2(e);
			};

			this.checkPassword2 = function(e)
			{
				var pass  = form.elements['password'].value;
				var pass2 = form.elements['password2'].value;

				if (pass2.length == 0 || pass2 != pass)
				{
					var error = '';

					if (pass2.length == 0)
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if (!BX.hasClass(pass2Cont, 'mail-set-ok') && !BX.hasClass(pass2Cont, 'mail-set-error'))
								return;
						}

						error = '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_EMPTY')) ?>';
					}
					else
					{
						error = pass.substr(0, pass2.length) == pass2
							? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_SHORT')) ?>'
							: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_DIFF')) ?>';
					}

					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.addClass(pass2Cont, 'mail-set-error');
					BX.adjust(pass2Hint, {text: error});
				}
				else
				{
					BX.removeClass(pass2Cont, 'mail-set-error');
					BX.addClass(pass2Cont, 'mail-set-ok');
					BX.cleanNode(pass2Hint);
				}
			};

			this.checkAndSubmit = function(e)
			{
				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkName(e);
				self.checkPassword(e);
				self.checkPassword2(e);

				if (BX.hasClass(loginCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error') && !BX.hasClass(pass2Cont, 'mail-set-error'))
					self.submit();
			};

			this.clean = function(e)
			{
				nameWasFilled = false;

				form.elements['login'].value = '';
				form.elements['password'].value = '';
				form.elements['password2'].value = '';

				BX.removeClass(loginCont, 'mail-set-ok');
				BX.removeClass(loginCont, 'mail-set-error');
				BX.removeClass(passCont, 'mail-set-ok');
				BX.removeClass(passCont, 'mail-set-error');
				BX.removeClass(pass2Cont, 'mail-set-ok');
				BX.removeClass(pass2Cont, 'mail-set-error');

				BX.cleanNode(loginHint);
				BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_SHORT')) ?>'});
				BX.cleanNode(pass2Hint);
			};

			this.alert = function(alert, message, details, error)
			{
				var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

				var text = message;
				if (details && details.length > 0)
				{
					text += ' <span style="font-weight: normal; ">(';
					text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; setPost.animCurrent(); return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
					text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
					text += ')</span>';
				}

				BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
				BX.adjust(textCont, {html: text});
				BX.show(alert, 'block');
				setPost.animCurrent();
			};

			this.enableCrm = function(data)
			{
				var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
				var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				BX.hide(alert, 'block');
				setPost.animCurrent();

				data.sessid = '<?=bitrix_sessid() ?>';

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=enablecrm',
					data: data,
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							window.location = '?page=success';
						}
						else
						{
							if (json.imap_dirs)
								showImapDirsPopup(json.imap_dirs, self.enableCrm, self.disableCrm);

							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
					}
				});
			};

			this.disableCrm = function()
			{
				var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
				var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				BX.hide(alert, 'block');
				setPost.animCurrent();

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=disablecrm',
					data: '<?=bitrix_sessid_get() ?>',
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							window.location = '?page=success';
						}
						else
						{
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
					}
				});
			};

			var prSubmit = false;
			var replacePopup = false;
			this.submit = function()
			{
				if (prSubmit)
					return false;

				var doSubmit = function()
				{
					prSubmit = true;

					var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
					var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

					var data = {};
					for (var i = 0; i < form.elements.length; i++)
					{
						if (form.elements[i].name)
						{
							var inputType = form.elements[i].type.toLowerCase();
							if (inputType == 'checkbox' || inputType == 'radio')
							{
								if (!form.elements[i].checked)
									continue;
							}

							data[form.elements[i].name] = form.elements[i].value;
						}
					}

					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=create',
						data: data,
						dataType: 'json',
						onsuccess: function(json) {
							if (json.result != 'error')
							{
								window.location = json.late_error ? '?page=home' : '?page=success';
							}
							else
							{
								<? if ($arParams['CRM_AVAILABLE']): ?>
								if (data.crm_connect == 'Y' && json.imap_dirs)
									showImapDirsPopup(json.imap_dirs, self.enableCrm, self.disableCrm);
								<? endif ?>

								BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
								self.alert(alert, json.error, json.error_ext, true);

								prSubmit = false;
							}
						},
						onfailure: function()
						{
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_FORM_ERROR')) ?>', false, true);

							prSubmit = false;
						}
					});
				};

				if (hasMailbox)
				{
					if (replacePopup === false)
					{
						replacePopup = new BX.PopupWindow('replace-mailbox', null, {
							closeIcon: true,
							closeByEsc: true,
							overlay: true,
							lightShadow: true,
							titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM')) ?>',
							content: '<?=CUtil::jsEscape(empty($emailAddress)
								? getMessage('INTR_MAIL_REPLACE_WARNING_UN')
								: getMessage('INTR_MAIL_REPLACE_WARNING', array('#EMAIL#' => $emailAddress))
							) ?>',
							buttons: [
								new BX.PopupWindowButton({
									className: 'popup-window-button-decline',
									text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')) ?>',
									events: {
										click: function()
										{
											this.popupWindow.close();
											doSubmit();
										}
									}
								}),
								new BX.PopupWindowButtonLink({
									text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')) ?>',
									className: 'popup-window-button-link-cancel',
									events: {
										click: function() {
											this.popupWindow.close();
										}
									}
								})
							]
						});
					}

					replacePopup.show();
				}
				else
				{
					doSubmit();
				}
			};
		}

		if (BX('b24_create_form'))
		{
			var b24CreateForm = BX('b24_create_form');
			var b24CmbForm = new CreateMailboxForm(b24CreateForm, 3);

			BX.bind(b24CreateForm.elements['login'], 'keyup', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['login'], 'focus', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['login'], 'blur', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['domain'], 'change', b24CmbForm.checkName);

			BX.bind(b24CreateForm.elements['password'], 'keyup', b24CmbForm.checkPassword);
			BX.bind(b24CreateForm.elements['password'], 'blur', b24CmbForm.checkPassword);

			BX.bind(b24CreateForm.elements['password2'], 'keyup', b24CmbForm.checkPassword2);
			BX.bind(b24CreateForm.elements['password2'], 'blur', b24CmbForm.checkPassword2);

			BX.bind(b24CreateForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				b24CmbForm.checkAndSubmit(e);
				return false;
			});
			BX.bind(BX('b24_create_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				b24CmbForm.checkAndSubmit(e);
				return false;
			});

			BX.bind(BX('b24_create_clear'), 'click', b24CmbForm.clean);
		}

		if (BX('domain_create_form') && BX('domain_create_save'))
		{
			var domainCreateForm = BX('domain_create_form');
			var domainCmbForm = new CreateMailboxForm(domainCreateForm);

			BX.bind(domainCreateForm.elements['login'], 'keyup', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['login'], 'focus', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['login'], 'blur', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['domain'], 'change', domainCmbForm.checkName);

			BX.bind(domainCreateForm.elements['password'], 'keyup', domainCmbForm.checkPassword);
			BX.bind(domainCreateForm.elements['password'], 'blur', domainCmbForm.checkPassword);

			BX.bind(domainCreateForm.elements['password2'], 'keyup', domainCmbForm.checkPassword2);
			BX.bind(domainCreateForm.elements['password2'], 'blur', domainCmbForm.checkPassword2);

			BX.bind(domainCreateForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				domainCmbForm.checkAndSubmit(e);
				return false;
			});
			BX.bind(BX('domain_create_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				domainCmbForm.checkAndSubmit(e);
				return false;
			});

			BX.bind(BX('domain_create_clear'), 'click', domainCmbForm.clean);
		}

		function EditMailboxForm(createForm, setupForm, deleteForm, checkForm, disableCrmForm, enableCrmForm, configCrmForm, passwordForm, editForm)
		{
			var self = this;

			var setupForm    = setupForm;
			var createForm   = createForm;

			var deleteForm   = deleteForm;
			var checkForm    = checkForm;
			var passwordForm = passwordForm;
			var editForm     = editForm;

			var passCont = passwordForm.elements['password'].parentNode;
			var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);

			if (passwordForm.elements['password2'])
			{
				var pass2Cont = passwordForm.elements['password2'].parentNode;
				var pass2Hint = BX.findChild(pass2Cont, {attr: {name: 'pass2-hint'}}, true, false);
			}

			if (enableCrmForm && enableCrmForm.elements['password'])
			{
				var passCrmCont = enableCrmForm.elements['password'].parentNode;
				var passCrmHint = BX.findChild(passCrmCont, {attr: {name: 'pass-hint'}}, true, false);
			}

			this.alert = function(alert, message, details, error)
			{
				var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

				var text = message;
				if (details && details.length > 0)
				{
					text += ' <span style="font-weight: normal; ">(';
					text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; setPost.animCurrent(); return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
					text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
					text += ')</span>';
				}

				BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
				BX.adjust(textCont, {html: text});
				BX.show(alert, 'block');
				setPost.animCurrent();
			};

			this.status = function(e)
			{
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.hide(alert, 'block');
				setPost.animCurrent();

				BX.addClass(checkForm, 'webform-button-active webform-button-wait');

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=check',
					data: '<?=bitrix_sessid_get() ?>',
					dataType: 'json',
					onsuccess: function(json)
					{
						BX.removeClass(checkForm, 'webform-button-active webform-button-wait');

						var statusBlock = BX.findChild(setupForm, {attr: {name: 'status-block'}}, true, false);
						var statusText = BX.findChild(setupForm, {attr: {name: 'status-text'}}, true, false);
						var statusAlert = BX.findChild(setupForm, {attr: {name: 'status-alert'}}, true, false);
						var statusInfo = BX.findChild(setupForm, {attr: {name: 'status-info'}}, true, false);

						statusText.innerHTML = '<?=CUtil::jsEscape(getMessage(
							'INTR_MAIL_CHECK_TEXT',
							array('#DATE#' => getMessage('INTR_MAIL_CHECK_JUST_NOW'))
						)) ?>:';

						if (json.result == 'ok')
						{
							BX.removeClass(statusBlock, 'post-status-error');
							BX.adjust(statusAlert, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CHECK_SUCCESS')) ?>'});
							BX.adjust(statusInfo, {style: {display: 'none'}});
						}
						else
						{
							BX.addClass(statusBlock, 'post-status-error');
							BX.adjust(statusAlert, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CHECK_ERROR')) ?>'});
							BX.adjust(statusInfo, {
								props: {title: json.error},
								style: {display: 'inline-block'}
							});
						}
					},
					onfailure: function()
					{
						BX.removeClass(checkForm, 'webform-button-active webform-button-wait');

						BX.removeClass(alert, 'post-dialog-alert-ok');
						BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>'});
						BX.show(alert, 'block');
						setPost.animCurrent();
					}
				});
			};

			var disableCrmPopup = false;
			this.disableCrm = function(e)
			{
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.hide(alert, 'block');
				setPost.animCurrent();

				if (disableCrmPopup === false)
				{
					disableCrmPopup = new BX.PopupWindow('disable-crm', null, {
						closeIcon: true,
						closeByEsc: true,
						overlay: true,
						lightShadow: true,
						titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_DISABLE_CRM_CONFIRM')) ?>',
						content: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_DISABLE_CRM_CONFIRM_TEXT')) ?>',
						buttons: [
							new BX.PopupWindowButton({
								className: 'popup-window-button-decline',
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_DISABLE')) ?>',
								events: {
									click: function() {
										this.popupWindow.close();

										BX.addClass(disableCrmForm, 'webform-button-active webform-button-wait');

										BX.ajax({
											method: 'POST',
											url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=disablecrm',
											data: '<?=bitrix_sessid_get() ?>',
											dataType: 'json',
											onsuccess: function(json)
											{
												if (json.result != 'error')
												{
													// @TODO: js-replace blocks
													window.location = '?page=home';
												}
												else
												{
													BX.removeClass(disableCrmForm, 'webform-button-active webform-button-wait');
													self.alert(alert, json.error, json.error_ext, true);
												}
											},
											onfailure: function()
											{
												BX.removeClass(disableCrmForm, 'webform-button-active webform-button-wait');
												self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
											}
										});
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')) ?>',
								className: 'popup-window-button-link-cancel',
								events: {
									click: function() {
										this.popupWindow.close();
									}
								}
							})
						]
					});
				}

				disableCrmPopup.show();
			};

			this.enableCrm = function(formButton)
			{
				var enableCrm = function(params)
				{
					var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-small-button-wait');

					BX.hide(alert, 'block');
					setPost.animCurrent();

					var data = {};
					for (var i = 0; i < enableCrmForm.elements.length; i++)
					{
						var inputType = enableCrmForm.elements[i].type.toLowerCase();
						if (inputType == 'checkbox' || inputType == 'radio')
						{
							if (!enableCrmForm.elements[i].checked)
								continue;
						}

						if (enableCrmForm.elements[i].name)
							data[enableCrmForm.elements[i].name] = enableCrmForm.elements[i].value;
					}

					for (var i in params)
						data[i] = params[i];

					data.subact = '';

					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=enablecrm',
						data: data,
						dataType: 'json',
						onsuccess: function(json)
						{
							if (json.result != 'error')
							{
								// @TODO: js-replace blocks
								window.location = '?page=home';
							}
							else
							{
								if (json.imap_dirs)
									showImapDirsPopup(json.imap_dirs, enableCrm);

								BX.removeClass(formButton, 'webform-small-button-wait');
								self.alert(alert, json.error, json.error_ext, true);
							}
						},
						onfailure: function()
						{
							BX.removeClass(formButton, 'webform-small-button-wait');
							self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
						}
					});
				};

				enableCrm();
			};

			this.configCrm = function(formButton)
			{
				var configCrm = function(params)
				{
					var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-small-button-wait');

					BX.hide(alert, 'block');
					setPost.animCurrent();

					var data = {};
					for (var i = 0; i < configCrmForm.elements.length; i++)
					{
						var inputType = configCrmForm.elements[i].type.toLowerCase();
						if (inputType == 'checkbox' || inputType == 'radio')
						{
							if (!configCrmForm.elements[i].checked)
								continue;
						}

						// @TODO: cases
						if (configCrmForm.elements[i].name.match(/\[\]$/))
						{
							var pname = configCrmForm.elements[i].name.substr(0, configCrmForm.elements[i].name.length-2);

							if (typeof data[pname] == 'undefined')
								data[pname] = [];

							data[pname].push(configCrmForm.elements[i].value);
						}
						else
						{
							data[configCrmForm.elements[i].name] = configCrmForm.elements[i].value;
						}
					}

					for (var i in params)
						data[i] = params[i];

					data.subact = '';

					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=configcrm',
						data: data,
						dataType: 'json',
						onsuccess: function(json)
						{
							if (json.result != 'error')
							{
								// @TODO: js-replace blocks
								window.location = '?page=home';
							}
							else
							{
								if (json.imap_dirs)
									showImapDirsPopup(json.imap_dirs, configCrm);

								BX.removeClass(formButton, 'webform-small-button-wait');
								self.alert(alert, json.error, json.error_ext, true);
							}
						},
						onfailure: function()
						{
							BX.removeClass(formButton, 'webform-small-button-wait');
							self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
						}
					});
				};

				configCrm();
			};

			var deletePopup = false;
			this.delete = function(e)
			{
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.hide(alert, 'block');
				setPost.animCurrent();

				if (deletePopup === false)
				{
					deletePopup = new BX.PopupWindow('delete-mailbox', null, {
						closeIcon: true,
						closeByEsc: true,
						overlay: true,
						lightShadow: true,
						titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM')) ?>',
						content: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM_TEXT')) ?>',
						buttons: [
							new BX.PopupWindowButton({
								className: 'popup-window-button-decline',
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')) ?>',
								events: {
									click: function()
									{
										this.popupWindow.close();

										BX.addClass(deleteForm, 'webform-button-decline-active webform-button-wait');

										BX.ajax({
											method: 'POST',
											url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=delete',
											data: '<?=bitrix_sessid_get() ?>',
											dataType: 'json',
											onsuccess: function(json)
											{
												BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');
												if (json.result != 'error')
												{
													hasMailbox = false;

													if (BX('b24_block_descr_mailbox'))
													{
														BX.hide(BX('b24_block_descr_mailbox'), 'block');
														BX.show(BX('b24_block_descr_nomailbox'), 'block');
													}
													if (BX('domain_block_descr_mailbox'))
													{
														BX.hide(BX('domain_block_descr_mailbox'), 'block');
														BX.show(BX('domain_block_descr_nomailbox'), 'block');
													}
													if (BX('imap_block_descr_mailbox'))
													{
														BX.hide(BX('imap_block_descr_mailbox'), 'block');
														BX.show(BX('imap_block_descr_nomailbox'), 'block');
													}

													if (BX('b24_block_replace_warning'))
														BX.hide(BX('b24_block_replace_warning'), 'block');
													if (BX('domain_block_replace_warning'))
														BX.hide(BX('domain_block_replace_warning'), 'block');
													if (BX('imap_block_replace_warning'))
														BX.hide(BX('imap_block_replace_warning'), 'block');

													BX.show(BX('mail-info-message'), 'block');

													BX.hide(setupForm, 'block');
													BX.show(createForm, 'block');
													setPost.animCurrent();
												}
												else
												{
													self.alert(alert, json.error, json.error_ext, true);
												}
											},
											onfailure: function()
											{
												BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');
												self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
											}
										});
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')) ?>',
								className: 'popup-window-button-link-cancel',
								events: {
									click: function() {
										this.popupWindow.close();
									}
								}
							})
						]
					});
				}

				deletePopup.show();
			};

			this.checkPassword = function(e)
			{
				if (!passwordForm.elements['password'].value.match(/^[\x21\x23-\x26\x28-\x2E\x30-\x3B\x40-\x5A\x5E\x5F\x61-\x7A]*$/))
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_BAD')) ?>'});
				}
				else if (passwordForm.elements['password'].value.length < 6)
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {
						text: passwordForm.elements['password'].value.length == 0
							? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_EMPTY')) ?>'
							: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_SHORT')) ?>'
					});
				}
				else if (passwordForm.elements['password'].value.length > 20)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_LONG')) ?>'});
				}
				else if (passwordForm.elements['password'].value == passwordForm.elements['login'].value)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_LIKELOGIN')) ?>'});
				}
				else
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}

				if (passwordForm.elements['password2'].value.length > 0)
					self.checkPassword2(e);
			};

			this.checkPassword2 = function(e)
			{
				var pass  = passwordForm.elements['password'].value;
				var pass2 = passwordForm.elements['password2'].value;

				if (pass2.length == 0 || pass2 != pass)
				{
					var error = '';

					if (pass2.length == 0)
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if (!BX.hasClass(pass2Cont, 'mail-set-ok') && !BX.hasClass(pass2Cont, 'mail-set-error'))
								return;
						}

						error = '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_EMPTY')) ?>';
					}
					else
					{
						error = pass.substr(0, pass2.length) == pass2
							? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_SHORT')) ?>'
							: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS2_DIFF')) ?>';
					}

					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.addClass(pass2Cont, 'mail-set-error');
					BX.adjust(pass2Hint, {text: error});
				}
				else
				{
					BX.removeClass(pass2Cont, 'mail-set-error');
					BX.addClass(pass2Cont, 'mail-set-ok');
					BX.cleanNode(pass2Hint);
				}
			};

			this.checkImapPassword = function(e)
			{
				if (passwordForm.elements['password'].value.length > 0)
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_EMPTY')) ?>'});
				}
			};

			this.checkCrmPassword = function(e)
			{
				if (enableCrmForm.elements['password'].value.length == 0)
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCrmCont, 'post-dialog-inp-confirm') && !BX.hasClass(passCrmCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(passCrmCont, 'post-dialog-inp-confirm');
					BX.addClass(passCrmCont, 'post-dialog-inp-error');
					BX.adjust(passCrmHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_EMPTY')) ?>'});
				}
				else
				{
					BX.removeClass(passCrmCont, 'post-dialog-inp-error');
					BX.addClass(passCrmCont, 'post-dialog-inp-confirm');
					BX.cleanNode(passCrmHint);
				}

				return !BX.hasClass(passCrmCont, 'post-dialog-inp-error');
			};

			this.checkPasswordForm = function(e)
			{
				BX.hide(BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkPassword(e);
				self.checkPassword2(e);

				return !BX.hasClass(passCont, 'mail-set-error')
					&& !BX.hasClass(pass2Cont, 'mail-set-error');
			};

			this.checkImapPasswordForm = function(e)
			{
				BX.hide(BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkImapPassword(e);

				return !BX.hasClass(passCont, 'mail-set-error');
			};

			this.cleanPassword = function(e)
			{
				passwordForm.elements['password'].value = '';
				if (passwordForm.elements['password2'])
					passwordForm.elements['password2'].value = '';

				BX.removeClass(passCont, 'mail-set-ok');
				BX.removeClass(passCont, 'mail-set-error');

				if (passwordForm.elements['password2'])
				{
					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.removeClass(pass2Cont, 'mail-set-error');
				}

				if (passwordForm.elements['password2'])
				{
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_SHORT')) ?>'});
					BX.cleanNode(pass2Hint);
				}
				else
				{
					BX.cleanNode(passHint);
				}
			};

			this.submitPassword = function()
			{
				var formButton = BX.findChild(passwordForm, {attr: {name: 'password-save'}}, true, false);
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				var data = {};
				for (var i = 0; i < passwordForm.elements.length; i++)
				{
					if (passwordForm.elements[i].name)
						data[passwordForm.elements[i].name] = passwordForm.elements[i].value;
				}
				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=password',
					data: data,
					dataType: 'json',
					onsuccess: function(json)
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

						if (json.result != 'error')
						{
							self.cleanPassword();
							self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_PASSWORD_SUCCESS')) ?>', false, false);
						}
						else
						{
							self.alert(alert, json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
					}
				});
			};
		}

		if (BX('b24_setup_form'))
		{
			var b24CreateForm    = BX('b24_create_form');
			var b24SetupForm     = BX('b24_setup_form');
			var b24DeleteForm    = BX('b24_delete_form');
			var b24CheckForm     = BX('b24_check_form');
			var b24DisableCrm    = BX('b24_disable_crm');
			var b24EnableCrm     = BX('b24_enable_crm_form');
			var b24ConfigCrm     = BX('b24_config_crm_form');
			var b24PasswordForm  = BX('b24_password_form');

			var b24EmbForm = new EditMailboxForm(
				b24CreateForm, b24SetupForm, b24DeleteForm, b24CheckForm,
				b24DisableCrm, b24EnableCrm, b24ConfigCrm,
				b24PasswordForm
			);

			BX.bind(b24PasswordForm.elements['password'], 'keyup', b24EmbForm.checkPassword);
			BX.bind(b24PasswordForm.elements['password'], 'blur', b24EmbForm.checkPassword);

			BX.bind(b24PasswordForm.elements['password2'], 'keyup', b24EmbForm.checkPassword2);
			BX.bind(b24PasswordForm.elements['password2'], 'blur', b24EmbForm.checkPassword2);

			BX.bind(b24DeleteForm, 'click', b24EmbForm.delete);
			BX.bind(b24CheckForm, 'click', b24EmbForm.status);

			BX.bind(b24DisableCrm, 'click', b24EmbForm.disableCrm);

			BX.bind(BX('b24_enable_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (!b24EnableCrm.elements['password'] || b24EmbForm.checkCrmPassword(e))
					b24EmbForm.enableCrm(BX('b24_enable_crm'));
				return false;
			});

			if (b24EnableCrm && b24EnableCrm.elements['password'])
			{
				BX.bind(b24EnableCrm.elements['password'], 'keyup', b24EmbForm.checkCrmPassword);
				BX.bind(b24EnableCrm.elements['password'], 'blur', b24EmbForm.checkCrmPassword);
			}

			BX.bind(BX('b24_config_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				b24EmbForm.configCrm(BX('b24_config_crm'));
				return false;
			});

			BX.bind(b24PasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (b24EmbForm.checkPasswordForm(e))
					b24EmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('b24_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (b24EmbForm.checkPasswordForm(e))
					b24EmbForm.submitPassword();
				return false;
			});
		}

		if (BX('domain_setup_form'))
		{
			var domainCreateForm    = BX('domain_create_form');
			var domainSetupForm     = BX('domain_setup_form');
			var domainDeleteForm    = BX('domain_delete_form');
			var domainCheckForm     = BX('domain_check_form');
			var domainDisableCrm    = BX('domain_disable_crm');
			var domainEnableCrm     = BX('domain_enable_crm_form');
			var domainConfigCrm     = BX('domain_config_crm_form');
			var domainPasswordForm  = BX('domain_password_form');

			var domainEmbForm = new EditMailboxForm(
				domainCreateForm, domainSetupForm, domainDeleteForm, domainCheckForm,
				domainDisableCrm, domainEnableCrm, domainConfigCrm,
				domainPasswordForm
			);

			BX.bind(domainPasswordForm.elements['password'], 'keyup', domainEmbForm.checkPassword);
			BX.bind(domainPasswordForm.elements['password'], 'blur', domainEmbForm.checkPassword);

			BX.bind(domainPasswordForm.elements['password2'], 'keyup', domainEmbForm.checkPassword2);
			BX.bind(domainPasswordForm.elements['password2'], 'blur', domainEmbForm.checkPassword2);

			BX.bind(domainDeleteForm, 'click', domainEmbForm.delete);
			BX.bind(domainCheckForm, 'click', domainEmbForm.status);

			BX.bind(domainDisableCrm, 'click', domainEmbForm.disableCrm);

			BX.bind(BX('domain_enable_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (!domainEnableCrm.elements['password'] || domainEmbForm.checkCrmPassword(e))
					domainEmbForm.enableCrm(BX('domain_enable_crm'));
				return false;
			});

			if (domainEnableCrm && domainEnableCrm.elements['password'])
			{
				BX.bind(domainEnableCrm.elements['password'], 'keyup', domainEmbForm.checkCrmPassword);
				BX.bind(domainEnableCrm.elements['password'], 'blur', domainEmbForm.checkCrmPassword);
			}

			BX.bind(BX('domain_config_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				domainEmbForm.configCrm(BX('domain_config_crm'));
				return false;
			});

			BX.bind(domainPasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (domainEmbForm.checkPasswordForm(e))
					domainEmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('domain_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (domainEmbForm.checkPasswordForm(e))
					domainEmbForm.submitPassword();
				return false;
			});
		}

		if (BX('imap_setup_form'))
		{
			var imapCreateForm    = BX('imap_icons');
			var imapSetupForm     = BX('imap_setup_form');
			var imapDeleteForm    = BX('imap_delete_form');
			var imapCheckForm     = BX('imap_check_form');
			var imapDisableCrm    = BX('imap_disable_crm');
			var imapEnableCrm     = BX('imap_enable_crm_form');
			var imapConfigCrm     = BX('imap_config_crm_form');
			var imapPasswordForm  = BX('imap_password_form');

			var imapEmbForm = new EditMailboxForm(
				imapCreateForm, imapSetupForm, imapDeleteForm, imapCheckForm,
				imapDisableCrm, imapEnableCrm, imapConfigCrm,
				imapPasswordForm
			);

			BX.bind(imapPasswordForm.elements['password'], 'keyup', imapEmbForm.checkImapPassword);
			BX.bind(imapPasswordForm.elements['password'], 'blur', imapEmbForm.checkImapPassword);

			BX.bind(imapDeleteForm, 'click', imapEmbForm.delete);
			BX.bind(imapCheckForm, 'click', imapEmbForm.status);

			BX.bind(imapDisableCrm, 'click', imapEmbForm.disableCrm);

			BX.bind(BX('imap_enable_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				imapEmbForm.enableCrm(BX('imap_enable_crm'));
				return false;
			});

			BX.bind(BX('imap_config_crm'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				imapEmbForm.configCrm(BX('imap_config_crm'));
				return false;
			});

			BX.bind(imapPasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (imapEmbForm.checkImapPasswordForm(e))
					imapEmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('imap_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (imapEmbForm.checkImapPasswordForm(e))
					imapEmbForm.submitPassword();
				return false;
			});
		}

		function CreateImapMailboxForm(form)
		{
			var self = this;

			var form = form;

			if (form.elements['link'])
			{
				var linkCont = form.elements['link'].parentNode;
				var linkHint = BX.findChild(linkCont, {attr: {name: 'link-hint'}}, true, false);
			}

			if (form.elements['server'])
			{
				var serverCont = form.elements['server'].parentNode;
				var portCont = form.elements['port'].parentNode;
				var serverHint = BX.findChild(serverCont, {attr: {name: 'server-hint'}}, true, false);
			}

			if (form.elements['email'])
			{
				var emailCont = form.elements['email'].parentNode;
				var emailHint = BX.findChild(emailCont, {attr: {name: 'email-hint'}}, true, false);
			}

			if (form.elements['login'])
			{
				var loginCont = form.elements['login'].parentNode;
				var loginHint = BX.findChild(loginCont, {attr: {name: 'login-hint'}}, true, false);
			}

			if (form.elements['password'])
			{
				var passCont = form.elements['password'].parentNode;
				var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);
			}

			var ceTimeout = false;
			var nameWasFilled = false;

			this.checkLink = function(e)
			{
				if (form.elements['link'].value.length > 0 && form.elements['link'].value != form.elements['link'].getAttribute('data-placeholder'))
				{
					if (form.elements['link'].value.match(/^https?:\/\/([a-z0-9](-*[a-z0-9])*\.?)+(:[0-9]+)?(\/.*)?$/i))
					{
						BX.removeClass(linkCont, 'post-dialog-inp-error');
						BX.addClass(linkCont, 'post-dialog-inp-confirm');
						BX.cleanNode(linkHint);
					}
					else
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if ('http://'.indexOf(form.elements['link'].value) == 0 || 'https://'.indexOf(form.elements['link'].value) == 0)
							{
								if (!BX.hasClass(linkCont, 'post-dialog-inp-confirm') && !BX.hasClass(linkCont, 'post-dialog-inp-error'))
									return;
							}
						}

						BX.removeClass(linkCont, 'post-dialog-inp-confirm');
						BX.addClass(linkCont, 'post-dialog-inp-error');
						BX.adjust(linkHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_LINK_BAD')) ?>'});
					}
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(linkCont, 'post-dialog-inp-confirm') && !BX.hasClass(linkCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(linkCont, 'post-dialog-inp-confirm');
					BX.addClass(linkCont, 'post-dialog-inp-error');
					BX.adjust(linkHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_LINK_EMPTY')) ?>'});
				}
			};

			this.checkServer = function(e)
			{
				if (form.elements['server'].value.length > 0 && form.elements['server'].value != form.elements['server'].getAttribute('data-placeholder'))
				{
					if (form.elements['server'].value.match(/^([a-z0-9](-*[a-z0-9])*\.?)+$/i))
					{
						BX.removeClass(serverCont, 'post-dialog-inp-error');
						BX.addClass(serverCont, 'post-dialog-inp-confirm');
						BX.cleanNode(serverHint);
					}
					else
					{
						BX.removeClass(serverCont, 'post-dialog-inp-confirm');
						BX.addClass(serverCont, 'post-dialog-inp-error');
						BX.adjust(serverHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_SERVER_BAD')) ?>'});
					}
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(serverCont, 'post-dialog-inp-confirm') && !BX.hasClass(serverCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(serverCont, 'post-dialog-inp-confirm');
					BX.addClass(serverCont, 'post-dialog-inp-error');
					BX.adjust(serverHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_SERVER_EMPTY')) ?>'});
				}
			};

			this.checkPort = function(e)
			{
				var input = form.elements['port'];
				if (input.value.match(/^[0-9]+$/) && input.value > 0 && input.value < 65536 && !BX.hasClass(input, 'post-dialog-inp-placeholder'))
				{
					BX.removeClass(portCont, 'post-dialog-inp-error');
					BX.addClass(portCont, 'post-dialog-inp-confirm');
				}
				else
				{
					BX.removeClass(portCont, 'post-dialog-inp-confirm');
					BX.addClass(portCont, 'post-dialog-inp-error');
				}
			};

			this.checkEmail = function(e)
			{
				ceTimeout = clearTimeout(ceTimeout);

				var input = form.elements['email'];

				if (!nameWasFilled)
				{
					form.elements['login'].value = BX.hasClass(input, 'post-dialog-inp-placeholder') ? '' : input.value;
					self.checkName();
				}

				if (input.value.length > 0 && input.value != input.getAttribute('data-placeholder'))
				{
					var atom = "[=a-z0-9_+~'!$&*^`|#%/?{}-]";
					var patterns = new RegExp('^('+atom+'+\\.)*('+atom+'+(@(([a-z0-9-]+\\.)*[a-z0-9-]*)?)?)?$', 'i');
					var patternf = new RegExp('^'+atom+'+(\\.'+atom+'+)*@([a-z0-9-]+\\.)+[a-z0-9-]{2,20}$', 'i');

					if (input.value.match(patterns))
					{
						BX.removeClass(emailCont, 'post-dialog-inp-error');
						BX.addClass(emailCont, 'post-dialog-inp-confirm');
						BX.cleanNode(emailHint);

						ceTimeout = setTimeout(function()
						{
							if (!input.value.match(patternf))
							{
								BX.removeClass(emailCont, 'post-dialog-inp-confirm');
								BX.addClass(emailCont, 'post-dialog-inp-error');
								BX.adjust(emailHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_EMAIL_BAD')) ?>'});
							}
						}, typeof e == 'object' && e.type == 'keyup' ? 800 : 0);
					}
					else
					{
						BX.removeClass(emailCont, 'post-dialog-inp-confirm');
						BX.addClass(emailCont, 'post-dialog-inp-error');
						BX.adjust(emailHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_EMAIL_BAD')) ?>'});
					}
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(emailCont, 'post-dialog-inp-confirm') && !BX.hasClass(emailCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(emailCont, 'post-dialog-inp-confirm');
					BX.addClass(emailCont, 'post-dialog-inp-error');
					BX.adjust(emailHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_EMAIL_EMPTY')) ?>'});
				}
			};

			this.checkName = function(e)
			{
				if (form.elements['login'].value.length > 0)
				{
					if (typeof e == 'object' && e.type == 'keyup')
						nameWasFilled = true;

					BX.removeClass(loginCont, 'post-dialog-inp-error');
					BX.addClass(loginCont, 'post-dialog-inp-confirm');
					BX.cleanNode(loginHint);
				}
				else
				{
					nameWasFilled = false;

					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(loginCont, 'post-dialog-inp-confirm') && !BX.hasClass(loginCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(loginCont, 'post-dialog-inp-confirm');
					BX.addClass(loginCont, 'post-dialog-inp-error');
					BX.adjust(loginHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_LOGIN_EMPTY')) ?>'});
				}
			};

			this.checkPassword = function(e)
			{
				var input = form.elements['password'];
				if (input.value.length > 0 || hasMailbox)
				{
					BX.removeClass(passCont, 'post-dialog-inp-error');
					BX.addClass(passCont, 'post-dialog-inp-confirm');
					BX.cleanNode(passHint);
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'post-dialog-inp-confirm') && !BX.hasClass(passCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(passCont, 'post-dialog-inp-confirm');
					BX.addClass(passCont, 'post-dialog-inp-error');
					BX.adjust(passHint, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_PASS_EMPTY')) ?>'});
				}
			};

			this.check = function(e)
			{
				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				if (form.elements['link'])
					self.checkLink(e);
				if (form.elements['server'])
				{
					self.checkServer(e);
					self.checkPort(e);
				}
				if (form.elements['email'])
					self.checkEmail(e);
				if (form.elements['login'])
					self.checkName(e);
				if (form.elements['password'])
					self.checkPassword(e);

				return !(form.elements['link'] && BX.hasClass(linkCont, 'post-dialog-inp-error'))
					&& !(form.elements['server'] && (BX.hasClass(serverCont, 'post-dialog-inp-error') || BX.hasClass(portCont, 'post-dialog-inp-error')))
					&& !(form.elements['email'] && BX.hasClass(emailCont, 'post-dialog-inp-error'))
					&& !(form.elements['login'] && BX.hasClass(loginCont, 'post-dialog-inp-error'))
					&& !(form.elements['password'] && BX.hasClass(passCont, 'post-dialog-inp-error'));
			};

			this.clean = function(e)
			{
				if (form.elements['link'])
				{
					BX.addClass(form.elements['link'], 'post-dialog-inp-placeholder');
					form.elements['link'].value = form.elements['link'].getAttribute('data-placeholder');
				}
				if (form.elements['server'])
				{
					BX.addClass(form.elements['server'], 'post-dialog-inp-placeholder');
					BX.addClass(form.elements['port'], 'post-dialog-inp-placeholder');
					form.elements['server'].value = form.elements['server'].getAttribute('data-placeholder');
					form.elements['port'].value = form.elements['port'].getAttribute('data-placeholder');
				}

				if (form.elements['email'])
				{
					BX.addClass(form.elements['email'], 'post-dialog-inp-placeholder');
					form.elements['email'].value = form.elements['email'].getAttribute('data-placeholder');
				}

				if (form.elements['login'])
					form.elements['login'].value = '';
				if (form.elements['password'])
					form.elements['password'].value = '';

				if (form.elements['link'])
					BX.removeClass(linkCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				if (form.elements['server'])
				{
					BX.removeClass(serverCont, 'post-dialog-inp-confirm post-dialog-inp-error');
					BX.removeClass(portCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				}
				if (form.elements['email'])
					BX.removeClass(emailCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				if (form.elements['login'])
					BX.removeClass(loginCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				if (form.elements['password'])
					BX.removeClass(passCont, 'post-dialog-inp-confirm post-dialog-inp-error');

				if (form.elements['link'])
					BX.cleanNode(linkHint);
				if (form.elements['server'])
					BX.cleanNode(serverHint);
				if (form.elements['email'])
					BX.cleanNode(emailHint);
				if (form.elements['login'])
					BX.cleanNode(loginHint);
				if (form.elements['password'])
					BX.cleanNode(passHint);
			};

			this.alert = function(alert, message, details, error)
			{
				var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

				var text = message;
				if (details && details.length > 0)
				{
					text += ' <span style="font-weight: normal; ">(';
					text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; setPost.animCurrent(); return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
					text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
					text += ')</span>';
				}

				BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
				BX.adjust(textCont, {html: text});
				BX.show(alert, 'block');
				setPost.animCurrent();
			};

			this.enableCrm = function(data)
			{
				var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
				var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				BX.hide(alert, 'block');
				setPost.animCurrent();

				data.sessid = '<?=bitrix_sessid() ?>';

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=enablecrm',
					data: data,
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							window.location = '?page=success';
						}
						else
						{
							if (json.imap_dirs)
								showImapDirsPopup(json.imap_dirs, self.enableCrm, self.disableCrm);

							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
					}
				});
			};

			this.disableCrm = function()
			{
				var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
				var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				BX.hide(alert, 'block');
				setPost.animCurrent();

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act=disablecrm',
					data: '<?=bitrix_sessid_get() ?>',
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							window.location = '?page=success';
						}
						else
						{
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
					}
				});
			};

			var replacePopup = false;
			this.submit = function(act)
			{
				if (typeof act == 'undefined')
					act = 'create';

				var doSubmit = function()
				{
					var formButton = BX.findChild(form, {attr: {name: act+'-save'}}, true, false);
					var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

					BX.hide(alert, 'block');
					setPost.animCurrent();

					var data = {};
					for (var i = 0; i < form.elements.length; i++)
					{
						if (form.elements[i].name && !BX.hasClass(form.elements[i], 'post-dialog-inp-placeholder'))
						{
							var inputType = form.elements[i].type.toLowerCase();
							if (inputType == 'checkbox' || inputType == 'radio')
							{
								if (!form.elements[i].checked)
									continue;
							}

							data[form.elements[i].name] = form.elements[i].value;
						}
					}

					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->getPath() ?>/ajax.php?page=home&act='+act,
						data: data,
						dataType: 'json',
						onsuccess: function(json)
						{
							if (json.result != 'error')
							{
								if (act == 'create')
								{
									window.location = json.late_error ? '?page=home' : '?page=success';
								}
								else
								{
									BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
									self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_EDIT_SUCCESS')) ?>', false, false);
								}
							}
							else
							{
								if (form.elements['oauth'] && json.oauth_url)
									form.elements['oauth'].value = json.oauth_url;

								<? if ($arParams['CRM_AVAILABLE']): ?>
								if (act == 'create' && data.crm_connect == 'Y' && json.imap_dirs)
									showImapDirsPopup(json.imap_dirs, self.enableCrm, self.disableCrm);
								<? endif ?>

								BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
								self.alert(alert, json.error, json.error_ext, true);
							}
						},
						onfailure: function()
						{
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(alert, '<?=CUtil::jsEscape(getMessage('INTR_MAIL_FORM_ERROR')) ?>', false, true);
						}
					});
				};

				var preSubmit = function()
				{
					if (form.elements['oauth'])
					{
						var handler = function()
						{
							BX.unbind(window, 'hashchange', handler);

							window.location.hash = '#oauth';

							doSubmit();
						};

						BX.bind(window, 'hashchange', handler);
						BX.util.popup(form.elements['oauth'].value, 500, 600);
					}
					else
					{
						doSubmit();
					}
				};

				if (act == 'create' && hasMailbox)
				{
					if (replacePopup === false)
					{
						replacePopup = new BX.PopupWindow('replace-mailbox', null, {
							closeIcon: true,
							closeByEsc: true,
							overlay: true,
							lightShadow: true,
							titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM')) ?>',
							content: '<?=CUtil::jsEscape(empty($emailAddress)
								? getMessage('INTR_MAIL_REPLACE_WARNING_UN')
								: getMessage('INTR_MAIL_REPLACE_WARNING', array('#EMAIL#' => $emailAddress))
							) ?>',
							buttons: [
								new BX.PopupWindowButton({
									className: 'popup-window-button-decline',
									text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')) ?>',
									events: {
										click: function()
										{
											this.popupWindow.close();

											preSubmit();
										}
									}
								}),
								new BX.PopupWindowButtonLink({
									text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')) ?>',
									className: 'popup-window-button-link-cancel',
									events: {
										click: function() {
											this.popupWindow.close();
										}
									}
								})
							]
						});
					}

					replacePopup.show();
				}
				else
				{
					preSubmit();
				}
			};
		}

		if (BX('create_imap'))
		{
			var forms = BX.findChildren(BX('create_imap'), {attr: {name: 'create-imap'}}, true, true);
			var imapForms = {};
			var imapCmbForms = {};
			for (var i in forms)
			{
				imapForms[i] = BX.findChild(forms[i], {tag: 'form'}, true, false);
				imapCmbForms[i] = new CreateImapMailboxForm(imapForms[i]);

				if (imapForms[i].elements['link'])
				{
					BX.bind(imapForms[i].elements['link'], 'keyup', imapCmbForms[i].checkLink);
					BX.bind(imapForms[i].elements['link'], 'blur', imapCmbForms[i].checkLink);
				}

				if (imapForms[i].elements['server'])
				{
					BX.bind(imapForms[i].elements['server'], 'keyup', imapCmbForms[i].checkServer);
					BX.bind(imapForms[i].elements['server'], 'blur', imapCmbForms[i].checkServer);

					BX.bind(imapForms[i].elements['port'], 'keyup', imapCmbForms[i].checkPort);
					BX.bind(imapForms[i].elements['port'], 'blur', imapCmbForms[i].checkPort);
				}

				BX.bind(imapForms[i].elements['email'], 'keyup', imapCmbForms[i].checkEmail);
				BX.bind(imapForms[i].elements['email'], 'blur', imapCmbForms[i].checkEmail);

				BX.bind(imapForms[i].elements['login'], 'keyup', imapCmbForms[i].checkName);
				BX.bind(imapForms[i].elements['login'], 'blur', imapCmbForms[i].checkName);

				BX.bind(imapForms[i].elements['password'], 'keyup', imapCmbForms[i].checkPassword);
				BX.bind(imapForms[i].elements['password'], 'blur', imapCmbForms[i].checkPassword);

				(function(i) {
					BX.bind(imapForms[i], 'submit', function(e)
					{
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
						if (imapCmbForms[i].check(e))
							imapCmbForms[i].submit();
						return false;
					});
					BX.bind(BX.findChild(imapForms[i], {attr: {name: 'create-save'}}, true, false), 'click', function(e)
					{
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
						if (imapCmbForms[i].check(e))
							imapCmbForms[i].submit();
						return false;
					});
				})(i);

				BX.addCustomEvent(forms[i], 'HideImapForm', imapCmbForms[i].clean);
			}
		}

		if (BX('edit_imap'))
		{
			var imapForm = BX.findChild(BX('edit_imap'), {tag: 'form'}, true, false);
			var imapCmbForm = new CreateImapMailboxForm(imapForm);

			if (imapForm.elements['link'])
			{
				BX.bind(imapForm.elements['link'], 'keyup', imapCmbForm.checkLink);
				BX.bind(imapForm.elements['link'], 'blur', imapCmbForm.checkLink);
			}

			if (imapForm.elements['server'])
			{
				BX.bind(imapForm.elements['server'], 'keyup', imapCmbForm.checkServer);
				BX.bind(imapForm.elements['server'], 'blur', imapCmbForm.checkServer);

				BX.bind(imapForm.elements['port'], 'keyup', imapCmbForm.checkPort);
				BX.bind(imapForm.elements['port'], 'blur', imapCmbForm.checkPort);
			}

			if (imapForm.elements['login'])
			{
				BX.bind(imapForm.elements['login'], 'keyup', imapCmbForm.checkName);
				BX.bind(imapForm.elements['login'], 'blur', imapCmbForm.checkName);
			}

			BX.bind(imapForm.elements['password'], 'keyup', imapCmbForm.checkPassword);
			BX.bind(imapForm.elements['password'], 'blur', imapCmbForm.checkPassword);

			(function(i) {
				BX.bind(imapForm, 'submit', function(e)
				{
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapCmbForm.check(e))
						imapCmbForm.submit('edit');
					return false;
				});
				BX.bind(BX.findChild(imapForm, {attr: {name: 'edit-save'}}, true, false), 'click', function(e)
				{
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapCmbForm.check(e))
						imapCmbForm.submit('edit');
					return false;
				});
			})(i);
		}

	})();

</script>
