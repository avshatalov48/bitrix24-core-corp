<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if ($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->includeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID'                   => 'SEND_AND_SAVE',
			'ACTIVE_ITEM_ID'       => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST'    => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT'    => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST'    => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT'    => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST'   => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT'   => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST'  => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL'  => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST'   => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$limitedLicense = false;
if (\CModule::includeModule('bitrix24'))
{
	if ($limitedLicense = !in_array(\CBitrix24::getLicenseType(), array('company', 'nfr', 'edu', 'demo')))
		\CBitrix24::initLicenseInfoPopupJS();
}

if (\CModule::includeModule('socialservices'))
{
	foreach ($arParams['SERVICES'] as $id => $settings)
	{
		if ($settings['type'] != 'imap')
			continue;

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

CJSCore::init(array('socnetlogdest', 'popup', 'fx'));
$APPLICATION->setAdditionalCSS('/bitrix/components/bitrix/main.post.form/templates/.default/style.css');

$respList = array();
$respLast = array();
$respSelected = array();
foreach ($arParams['LEAD_RESP_SELECTED'] as $item)
{
	$id = sprintf('U%u', $item['ID']);

	$respList[$id] = array(
		'id'       => $id,
		'entityId' => $item['ID'],
		'name'     => \CUser::formatName($arParams['NAME_TEMPLATE'], $item, true),
		'avatar'   => '',
		'desc'     => $item['WORK_POSITION'] ?: $item['PERSONAL_PROFESSION'] ?: '&nbsp;'
	);
	$respLast[$id] = $id;
	$respSelected[$id] = 'users';
}

?>

<div class="mail-set-wrap" style="max-width: 1080px; ">
	<div class="mail-top-title-block" style="margin-bottom: 20px; ">
		<div class="mail-top-title-icon"></div>
		<?=getMessage('INTR_MAIL_TRACKER_DESCR') ?>
	</div>
	<div class="mail-set-block-wrap" id="mail-set-block-wrap">
		<div class="mail-set-block mail-set-block-active" id="mail-set-block">
			<div id="mail-set-third" class="mail-set-third-wrap" style="display: block; ">
				<div class="mail-set-third">

					<? if (!empty($arParams['MAILBOX'])): ?>

						<? $lastMailCheck = \Bitrix\Main\Config\Option::get('mail', 'last_mail_check', null, SITE_ID); ?>
						<? $lastMailCheckSuccess = \Bitrix\Main\Config\Option::get('mail', 'last_mail_check_success', null, SITE_ID); ?>

						<div id="imap_setup_form" class="mail-set-imap-setup">
							<div class="mail-set-title" style="padding-bottom: 20px; ">
								<?=getMessage('INTR_MAIL_MAILBOX_STATUS') ?> <span class="mail-set-title-name"><?=htmlspecialcharsbx($arParams['MAILBOX']['NAME']) ?></span>
							</div>
							<div name="status-block" class="mail-set-item-block">
								<div class="mail-set-item-block-r" style="border: none; ">
									<span id="imap_check_form" class="webform-button">
										<?=getMessage('INTR_MAIL_CHECK') ?>
									</span>&nbsp;
								</div>
								<div class="mail-set-item-block-l">
									<span name="status-text" class="post-dialog-stat-text">
										<? if (isset($lastMailCheck) && $lastMailCheck > 0): ?>
											<?=getMessage('INTR_MAIL_CHECK_TEXT', array(
												'#DATE#' => formatDate(
													array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
													(int) $lastMailCheck
												)
											)) ?>:
										<? else: ?>
											<?=getMessage('INTR_MAIL_CHECK_TEXT_NA') ?>
										<? endif ?>
									</span>
									<span name="status-alert" class="post-dialog-stat-alert<? if ($lastMailCheckSuccess == 'N'): ?> post-status-error<? endif ?>" style="margin: 0px; ">
										<? if (in_array($lastMailCheckSuccess, array('Y', 'N'))): ?>
											<?=getMessage($lastMailCheckSuccess == 'Y' ? 'INTR_MAIL_CHECK_SUCCESS' : 'INTR_MAIL_CHECK_ERROR') ?>
										<? endif ?>
									</span>
									<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>
								</div>
							</div>
						</div>

						<div id="edit_imap" name="edit-imap" class="post-dialog-wrap">
							<form>
								<? if (!empty($arParams['CRM_PRECONNECT'])): ?>
									<div name="post-dialog-alert" class="post-dialog-alert">
										<span class="post-dialog-alert-align"></span>
										<span class="post-dialog-alert-icon"></span>
										<span name="post-dialog-alert-text" class="post-dialog-alert-text">
											<?=getMessage('INTR_MAIL_CRM_PRECONNECT'); ?>
											<? if (!empty($arParams['IMAP_ERROR'])): ?>
												&mdash; <?=$arParams['IMAP_ERROR'] ?>
												<? if (!empty($arParams['IMAP_ERROR_EXT'])): ?>
													<span style="font-weight: normal; ">
														(<a href="#" onclick="this.style.display = 'none'; BX.findNextSibling(this, {class: 'post-dialog-alert-text-ext'}).style.display = ''; return false; "><?=getMessage('INTR_MAIL_ERROR_EXT') ?></a><?
														?><span class="post-dialog-alert-text-ext" style="display: none; "><?=$arParams['IMAP_ERROR_EXT'] ?></span>)</span>
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
								<input type="hidden" name="act" value="edit">
								<?=bitrix_sessid_post() ?>
								<? if (empty($arParams['SERVICE']['server'])): ?>
									<div class="post-dialog-inp-item">
										<div class="post-dialog-inp-serv">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_SERVER') ?></span>
											<input id="server" name="server" type="text" class="post-dialog-inp"
												value="<?=htmlspecialcharsbx($arParams['MAILBOX']['SERVER']) ?>">
											<div name="server-hint" class="mail-inp-description"></div>
										</div><div class="post-dialog-inp-post">
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PORT') ?></span>
											<input id="port" name="port" type="text" class="post-dialog-inp"
												value="<?=htmlspecialcharsbx($arParams['MAILBOX']['PORT']) ?>">
										</div>
									</div>
								<? endif ?>
								<? if (empty($arParams['SERVICE']['encryption'])): ?>
									<div class="post-dialog-inp-item">
										<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_ENCRYPT') ?></span>
										<span class="post-dialog-inp-select-wrap">
											<select name="encryption" class="post-dialog-inp-select">
												<option value="Y"<? if ($arParams['MAILBOX']['USE_TLS'] == 'Y'): ?> selected<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_YES') ?></option>
												<? if (PHP_VERSION_ID >= 50600): ?>
													<option value="S"<? if ($arParams['MAILBOX']['USE_TLS'] == 'S'): ?> selected<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_SKIP') ?></option>
												<? endif ?>
												<option value="N"<? if (!in_array($arParams['MAILBOX']['USE_TLS'], array('Y', 'S'))): ?> selected<? endif ?>><?=getMessage('INTR_MAIL_INP_ENCRYPT_NO') ?></option>
											</select>
										</span>
									</div>
								<? endif ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_NAME') ?></span>
									<input name="name" type="text" class="post-dialog-inp"
										value="<?=htmlspecialcharsbx($arParams['MAILBOX']['OPTIONS']['name']) ?>">
								</div>
								<? if (!$isOauthMailbox): ?>
									<div class="post-dialog-inp-item">
										<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_LOGIN') ?></span>
										<input disabled type="text" class="post-dialog-inp"
											value="<?=htmlspecialcharsbx($arParams['MAILBOX']['LOGIN']) ?>">
									</div>
									<div class="post-dialog-inp-item">
										<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_PASS') ?></span>
										<input name="password" type="password" class="post-dialog-inp">
										<div name="pass-hint" class="mail-inp-description"></div>
									</div>
								<? endif ?>
								<div class="mail-set-item-block-crm" id="mail-set-item-block-crm" style="min-width: 836px; ">
									<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper">
										<div class="mail-set-item-block-crm-wrapper-dec">
											<div class="mail-set-crm">
												<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_MAILBOX_OPTIONS') ?></div>
											</div>
											<div class="mail-set-crm-item">
												<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CHECK_INTERVAL') ?></label>
												<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="edit_imap_interval_<?=$arParams['DEFAULT_CHECK_INTERVAL'] ?>">
													<input type="radio" name="interval" value="0">
													<div class="mail-set-singleselect-wrapper">
														<? foreach ($arParams['CHECK_INTERVAL_LIST'] as $value => $title): ?>
															<? $disabled = $limitedLicense && $value < 10; ?>
															<input type="radio" name="interval" value="<?=$value ?>" id="edit_imap_interval_<?=$value ?>"
																<? if ($value == $arParams['DEFAULT_CHECK_INTERVAL']): ?> checked<? endif ?>
																<? if ($disabled): ?> disabled<? endif ?>>
															<label for="edit_imap_interval_<?=$value ?>"
																<? if ($disabled): ?> onclick="showLicenseInfoPopup('interval'); "<? endif ?>><?=htmlspecialcharsbx($title) ?></label>
														<? endforeach ?>
													</div>
												</label>
												<? if ($limitedLicense): ?>
													<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('interval'); "></span>
												<? endif ?>
											</div>
											<? $imapDirsList = array_merge(
												$arParams['MAILBOX']['OPTIONS']['imap']['income'],
												$arParams['MAILBOX']['OPTIONS']['imap']['outcome']
											); ?>
											<div class="mail-set-crm-item">
												<label class="mail-set-crm-check-label" style="display: block; padding-bottom: 1px; margin-bottom: -1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; ">
													<?=getMessage('INTR_MAIL_IMAP_DIRS_LIST') ?>:
													<a href="#" style="margin-left: 3px; color: #303031; border-bottom: 1px dashed #303031; "
														onclick="loadImapDirsPopup(this, 'edit_imap_dirs'); return false; "
														title="<?=join(', ', $imapDirsList) ?>" ><?=join(', ', $imapDirsList) ?></a>
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
											<div class="mail-set-crm-item">
												<a href="#" class="mail-set-textarea-show <? if (!empty($arParams['BLACKLIST'])): ?>mail-set-textarea-show-open<? endif ?>"
													onclick="toggleCrmBlacklist(this, 'edit_imap_crm_blacklist'); return false; "><?=getMessage('INTR_MAIL_CRM_BLACKLIST') ?></a>
												<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_HINT')) ?>"></span>
												<div class="mail-set-textarea-wrapper" id="edit_imap_crm_blacklist"
													<? if (empty($arParams['BLACKLIST'])): ?> style="display: none; "<? endif ?>>
													<div class="mail-set-textarea" id="mail-set-textarea">
														<textarea class="mail-set-textarea-input" name="black_list"
															placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_PROMPT')) ?>"><?
															echo join(', ', $arParams['BLACKLIST']);
														?></textarea>
													</div>
												</div>
											</div>
											<div class="mail-set-crm" style="margin-top: 20px; ">
												<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_OPTIONS') ?></div>
											</div>
											<?
											$crmNewEntityInDenied = in_array('crm_deny_new_lead', $arParams['MAILBOX']['OPTIONS']['flags']);
											$crmNewEntityInDenied = $crmNewEntityInDenied || in_array('crm_deny_entity_in', $arParams['MAILBOX']['OPTIONS']['flags']);
											$crmNewEntityOutDenied = in_array('crm_deny_new_lead', $arParams['MAILBOX']['OPTIONS']['flags']);
											$crmNewEntityOutDenied = $crmNewEntityOutDenied || in_array('crm_deny_entity_out', $arParams['MAILBOX']['OPTIONS']['flags']);
											?>
											<div class="mail-set-crm-item">
												<input class="mail-set-crm-check" id="edit_imap_crm_new_entity_in" type="checkbox" name="crm_new_entity_in" value="Y"
													<? if (!$crmNewEntityInDenied): ?> checked<? endif ?>>
												<? list($label1, $label2) = explode('#ENTITY#', getMessage('INTR_MAIL_INP_CRM_NEW_ENTITY_IN'), 2); ?>
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_entity_in"><?=$label1 ?></label>
												<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="edit_imap_allow_new_entity_in_<?=$arParams['DEFAULT_NEW_ENTITY_IN'] ?>">
													<input id="edit_imap_allow_new_entity_in_0" type="radio" name="allow_new_entity_in" value="0">
													<label for="edit_imap_allow_new_entity_in_0"><?=htmlspecialcharsbx($arParams['NEW_ENTITY_LIST'][$arParams['DEFAULT_NEW_ENTITY_IN']]) ?></label>
													<div class="mail-set-singleselect-wrapper">
														<? foreach ($arParams['NEW_ENTITY_LIST'] as $value => $title): ?>
															<input type="radio" name="allow_new_entity_in" value="<?=$value ?>" id="edit_imap_allow_new_entity_in_<?=$value ?>"
																<? if ($value == $arParams['DEFAULT_NEW_ENTITY_IN']): ?> checked<? endif ?>>
															<label for="edit_imap_allow_new_entity_in_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
														<? endforeach ?>
													</div>
												</label>
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_entity_in"><?=$label2 ?></label>
											</div>
											<div class="mail-set-crm-item">
												<input class="mail-set-crm-check" id="edit_imap_crm_new_entity_out" type="checkbox" name="crm_new_entity_out" value="Y"
													<? if (!$crmNewEntityOutDenied): ?> checked<? endif ?>>
												<? list($label1, $label2) = explode('#ENTITY#', getMessage('INTR_MAIL_INP_CRM_NEW_ENTITY_OUT'), 2); ?>
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_entity_out"><?=$label1 ?></label>
												<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="edit_imap_allow_new_entity_out_<?=$arParams['DEFAULT_NEW_ENTITY_OUT'] ?>">
													<input id="edit_imap_allow_new_entity_out_0" type="radio" name="allow_new_entity_out" value="0">
													<label for="edit_imap_allow_new_entity_out_0"><?=htmlspecialcharsbx($arParams['NEW_ENTITY_LIST'][$arParams['DEFAULT_NEW_ENTITY_OUT']]) ?></label>
													<div class="mail-set-singleselect-wrapper">
														<? foreach ($arParams['NEW_ENTITY_LIST'] as $value => $title): ?>
															<input type="radio" name="allow_new_entity_out" value="<?=$value ?>" id="edit_imap_allow_new_entity_out_<?=$value ?>"
																<? if ($value == $arParams['DEFAULT_NEW_ENTITY_OUT']): ?> checked<? endif ?>>
															<label for="edit_imap_allow_new_entity_out_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
														<? endforeach ?>
													</div>
												</label>
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_entity_in"><?=$label2 ?></label>
											</div>
											<? $crmNewContactDenied = in_array('crm_deny_new_contact', $arParams['MAILBOX']['OPTIONS']['flags']); ?>
											<div class="mail-set-crm-item">
												<input class="mail-set-crm-check" id="edit_imap_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y"
													<? if (!$crmNewContactDenied): ?> checked<? endif ?>>
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
											</div>
											<div class="mail-set-crm-item">
												<label class="mail-set-crm-check-label" for="edit_imap_crm_new_lead_always">
													<?=getMessage(
														'INTR_MAIL_INP_CRM_NEW_LEAD_ALLWAYS',
														array(
															'#LIST#' => sprintf(
																'<a href="#" class="mail-set-textarea-show %s" id="edit_imap_crm_new_lead_for_link" onclick="%s">%s</a>',
																!empty($arParams['NEW_LEAD_FOR']) ? 'mail-set-textarea-show-open' : '',
																"toggleCrmBlacklist(this, 'edit_imap_crm_new_lead_for'); return false; ",
																getMessage('INTR_MAIL_INP_CRM_NEW_LEAD_ALLWAYS_LIST')
															)
														)
													) ?>
												</label>
												<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_NEW_LEAD_FOR_HINT')) ?>"></span>
												<div class="mail-set-textarea-wrapper" id="edit_imap_crm_new_lead_for"
													<? if (empty($arParams['NEW_LEAD_FOR'])): ?> style="display: none; "<? endif ?>>
													<div class="mail-set-textarea" id="mail-set-textarea">
														<textarea class="mail-set-textarea-input" name="new_lead_for"
															placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_NEW_LEAD_FOR_PROMPT')) ?>"><?
															echo join(', ', $arParams['NEW_LEAD_FOR']);
														?></textarea>
													</div>
												</div>
											</div>
											<div class="mail-set-crm-check-ext" id="edit_imap_crm_new_entity_resp">
												<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_ENTITY_SOURCE') ?>:</label>
												<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="edit_imap_lead_source_<?=$arParams['DEFAULT_LEAD_SOURCE'] ?>">
													<input type="radio" name="lead_source" value="0">
													<div class="mail-set-singleselect-wrapper">
														<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
															<input type="radio" name="lead_source" value="<?=$value ?>" id="edit_imap_lead_source_<?=$value ?>"
																<? if ($value == $arParams['DEFAULT_LEAD_SOURCE']): ?> checked<? endif ?>>
															<label for="edit_imap_lead_source_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
														<? endforeach ?>
													</div>
												</label><br>
												<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_ENTITY_RESP') ?>:</label>
												<div class="feed-add-post-destination-wrap mail-set-crm-resp-wrap" id="edit_imap_lead_resp_container"
													style="margin: 5px 0 0 0; background-color: #ffffff; ">
													<span id="edit_imap_lead_resp_item"></span>
													<span class="feed-add-destination-input-box" id="edit_imap_lead_resp_input_box" style="display: none; ">
														<input type="text" value="" class="feed-add-destination-inp" id="edit_imap_lead_resp_input">
													</span>
													<a href="javascript:void(0)" class="feed-add-destination-link" id="edit_imap_lead_resp_tag"
														style="display: inline-block; "><?=getMessage('INTR_MAIL_CRM_RESP_ADD') ?></a>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="post-dialog-footer">
									<a id="imap_edit_save" name="edit-save" href="#" class="webform-button webform-button-accept">
										<?=getMessage('INTR_MAIL_INP_EDIT_SAVE') ?>
									</a>
									<span id="imap_delete_form2" class="webform-button webform-button-decline">
										<?=getMessage('INTR_MAIL_MAILBOX_DELETE_SHORT') ?>
									</span>
								</div>
								<input type="submit" style="position: absolute; visibility: hidden; ">
							</form>
						</div>

						<script type="text/javascript">

							BX.SocNetLogDestination.init({
								name : 'edit_imap_lead_resp_selector',
								searchInput : BX('edit_imap_lead_resp_input'),
								departmentSelectDisable : true,
								extranetUser :  false,
								allowAddSocNetGroup: false,
								bindMainPopup : {
									node : BX('edit_imap_lead_resp_container'),
									offsetTop : '5px',
									offsetLeft: '15px'
								},
								bindSearchPopup : {
									node : BX('edit_imap_lead_resp_container'),
									offsetTop : '5px',
									offsetLeft: '15px'
								},
								callback : {
									select : function(item, type, search)
									{
										BX.SocNetLogDestination.BXfpSelectCallback({
											item: item,
											type: type,
											varName: 'lead_resp',
											bUndeleted: false,
											containerInput: BX('edit_imap_lead_resp_item'),
											valueInput: BX('edit_imap_lead_resp_input'),
											formName: 'edit_imap_lead_resp_selector',
											tagInputName: 'edit_imap_lead_resp_tag',
											tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_SET')) ?>',
											tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_ADD')) ?>'
										});
									},
									unSelect : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
										formName: 'edit_imap_lead_resp_selector',
										inputContainerName: 'edit_imap_lead_resp_item',
										inputName: 'edit_imap_lead_resp_input',
										tagInputName: 'edit_imap_lead_resp_tag',
										tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_SET')) ?>',
										tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_ADD')) ?>'
									}),
									openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
										inputBoxName: 'edit_imap_lead_resp_input_box',
										inputName: 'edit_imap_lead_resp_input',
										tagInputName: 'edit_imap_lead_resp_tag'
									}),
									closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
										inputBoxName: 'edit_imap_lead_resp_input_box',
										inputName: 'edit_imap_lead_resp_input',
										tagInputName: 'edit_imap_lead_resp_tag'
									}),
									openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
										inputBoxName: 'edit_imap_lead_resp_input_box',
										inputName: 'edit_imap_lead_resp_input',
										tagInputName: 'edit_imap_lead_resp_tag'
									})
								},
								items : {
									users : <?=CUtil::phpToJSObject($respList) ?>,
									groups : {},
									sonetgroups : {},
									department : <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department']) ?>,
									departmentRelation : <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department_relation']) ?>
								},
								itemsLast : {
									users : <?=CUtil::phpToJSObject($respLast) ?>,
									sonetgroups : {},
									department : {},
									groups : {}
								},
								itemsSelected: <?=CUtil::phpToJSObject($respSelected) ?>,
								destSort: {}
							});

							BX.bind(BX('edit_imap_lead_resp_input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
								formName: 'edit_imap_lead_resp_selector',
								inputName: 'edit_imap_lead_resp_input'
							}));
							BX.bind(BX('edit_imap_lead_resp_input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
								formName: 'edit_imap_lead_resp_selector',
								inputName: 'edit_imap_lead_resp_input',
								tagInputName: 'edit_imap_lead_resp_tag'
							}));
							BX.bind(BX('edit_imap_lead_resp_input'), 'paste', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
								formName: 'edit_imap_lead_resp_selector',
								inputName: 'edit_imap_lead_resp_input'
							}));
							BX.bind(BX('edit_imap_lead_resp_input'), 'paste', BX.defer(BX.SocNetLogDestination.BXfpSearch, {
								formName: 'edit_imap_lead_resp_selector',
								inputName: 'edit_imap_lead_resp_input',
								tagInputName: 'edit_imap_lead_resp_tag',
								onPasteEvent: true
							}));

							BX.bind(BX('edit_imap_lead_resp_tag'), 'click', function (e) {
								BX.SocNetLogDestination.openDialog('edit_imap_lead_resp_selector');
								BX.PreventDefault(e);
							});
							BX.bind(BX('edit_imap_lead_resp_container'), 'click', function (e) {
								BX.SocNetLogDestination.openDialog('edit_imap_lead_resp_selector');
								BX.PreventDefault(e);
							});

						</script>

					<? endif ?>

					<div id="imap_icons" class="mail-set-img-wrap"<? if (!empty($arParams['MAILBOX'])): ?> style="display: none; "<? endif ?>>
						<? foreach ($arParams['SERVICES'] as $id => $settings): ?>
							<? if ($settings['type'] != 'imap') continue; ?>
							<a onclick="toggleImapForm(this, <?=$id ?>); return false; " href="#imap-<?=$id ?>" id="imap-<?=$id ?>-link" name="imap-link"
								class="mail-set-serv"<? if (strlen($settings['name']) > 15): ?> style="font-size: 18px; "<? endif ?>><?
								if ($settings['icon']): ?><img src="<?=$settings['icon'] ?>" alt="<?=htmlspecialcharsbx($settings['name']) ?>"><?
								else: ?>&nbsp;<?=htmlspecialcharsbx($settings['name']) ?>&nbsp;<? endif ?></a>
						<? endforeach ?>
					</div>

					<div id="create_imap" class="mail-set-imap-cont-wrap" style="display: none; ">

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
									<?=bitrix_sessid_post() ?>
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
											<span class="post-dialog-inp-label"><?=getMessage('INTR_MAIL_INP_NAME') ?></span>
											<input name="name" type="text" class="post-dialog-inp">
										</div>
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
									<div class="mail-set-item-block-crm" id="mail-set-item-block-crm" style="min-width: 836px; ">
										<div class="mail-set-item-block-crm-wrapper" id="mail-set-item-block-crm-wrapper">
											<div class="mail-set-item-block-crm-wrapper-dec">
												<div class="mail-set-crm">
													<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_MAILBOX_OPTIONS') ?></div>
												</div>
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
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_interval"><?=getMessage('INTR_MAIL_INP_CHECK_INTERVAL') ?></label>
													<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_interval_<?=$arParams['DEFAULT_CHECK_INTERVAL'] ?>">
														<input type="radio" name="interval" value="0">
														<div class="mail-set-singleselect-wrapper">
															<? foreach ($arParams['CHECK_INTERVAL_LIST'] as $value => $title): ?>
																<? $disabled = $limitedLicense && $value < 10; ?>
																<input type="radio" name="interval" value="<?=$value ?>" id="create_imap_<?=$id ?>_interval_<?=$value ?>"
																	<? if ($value == $arParams['DEFAULT_CHECK_INTERVAL']): ?> checked<? endif ?>
																	<? if ($disabled): ?> disabled<? endif ?>>
																<label for="create_imap_<?=$id ?>_interval_<?=$value ?>"
																	<? if ($disabled): ?>
																		class="mail-set-singleselect-option-disabled"
																		onclick="showLicenseInfoPopup('interval'); "
																	<? endif ?>><?=htmlspecialcharsbx($title) ?></label>
															<? endforeach ?>
														</div>
													</label>
													<? if ($limitedLicense): ?>
														<span class="mail-set-icon-lock" onclick="showLicenseInfoPopup('interval'); "></span>
													<? endif ?>
												</div>
												<div class="mail-set-crm-item" >
													<a href="#" class="mail-set-textarea-show"
														onclick="toggleCrmBlacklist(this, 'create_imap_<?=$id ?>_crm_blacklist'); return false; "><?=getMessage('INTR_MAIL_CRM_BLACKLIST') ?></a>
													<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_HINT')) ?>"></span>
													<div class="mail-set-textarea-wrapper" id="create_imap_<?=$id ?>_crm_blacklist" style="display: none; ">
														<div class="mail-set-textarea" id="mail-set-textarea">
															<textarea class="mail-set-textarea-input" name="black_list"
																placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_BLACKLIST_PROMPT')) ?>"></textarea>
														</div>
													</div>
												</div>
												<div class="mail-set-crm" style="margin-top: 20px; ">
													<div class="mail-set-crm-title"><?=getMessage('INTR_MAIL_CRM_OPTIONS') ?></div>
												</div>
												<div class="mail-set-crm-item">
													<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_new_entity_in" type="checkbox" name="crm_new_entity_in" value="Y" checked>
													<? list($label1, $label2) = explode('#ENTITY#', getMessage('INTR_MAIL_INP_CRM_NEW_ENTITY_IN'), 2); ?>
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_entity_in"><?=$label1 ?></label>
													<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_allow_new_entity_in_<?=$arParams['DEFAULT_NEW_ENTITY_IN'] ?>">
														<input id="create_imap_<?=$id ?>_allow_new_entity_in_0" type="radio" name="allow_new_entity_in" value="0">
														<label for="create_imap_<?=$id ?>_allow_new_entity_in_0"><?=htmlspecialcharsbx($arParams['NEW_ENTITY_LIST'][$arParams['DEFAULT_NEW_ENTITY_IN']]) ?></label>
														<div class="mail-set-singleselect-wrapper">
															<? foreach ($arParams['NEW_ENTITY_LIST'] as $value => $title): ?>
																<input type="radio" name="allow_new_entity_in" value="<?=$value ?>" id="create_imap_<?=$id ?>_allow_new_entity_in_<?=$value ?>"
																	<? if ($value == $arParams['DEFAULT_NEW_ENTITY_IN']): ?> checked<? endif ?>>
																<label for="create_imap_<?=$id ?>_allow_new_entity_in_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
															<? endforeach ?>
														</div>
													</label>
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_entity_in"><?=$label2 ?></label>
												</div>
												<div class="mail-set-crm-item">
													<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_new_entity_out" type="checkbox" name="crm_new_entity_out" value="Y" checked>
													<? list($label1, $label2) = explode('#ENTITY#', getMessage('INTR_MAIL_INP_CRM_NEW_ENTITY_OUT'), 2); ?>
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_entity_out"><?=$label1 ?></label>
													<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_allow_new_entity_out_<?=$arParams['DEFAULT_NEW_ENTITY_OUT'] ?>">
														<input id="create_imap_<?=$id ?>_allow_new_entity_out_0" type="radio" name="allow_new_entity_out" value="0">
														<label for="create_imap_<?=$id ?>_allow_new_entity_out_0"><?=htmlspecialcharsbx($arParams['NEW_ENTITY_LIST'][$arParams['DEFAULT_NEW_ENTITY_OUT']]) ?></label>
														<div class="mail-set-singleselect-wrapper">
															<? foreach ($arParams['NEW_ENTITY_LIST'] as $value => $title): ?>
																<input type="radio" name="allow_new_entity_out" value="<?=$value ?>" id="create_imap_<?=$id ?>_allow_new_entity_out_<?=$value ?>"
																	<? if ($value == $arParams['DEFAULT_NEW_ENTITY_OUT']): ?> checked<? endif ?>>
																<label for="create_imap_<?=$id ?>_allow_new_entity_out_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
															<? endforeach ?>
														</div>
													</label>
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_entity_in"><?=$label2 ?></label>
												</div>
												<div class="mail-set-crm-item">
													<input class="mail-set-crm-check" id="create_imap_<?=$id ?>_crm_new_contact" type="checkbox" name="crm_new_contact" value="Y" checked>
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_contact"><?=getMessage('INTR_MAIL_INP_CRM_NEW_CONTACT') ?></label>
												</div>
												<div class="mail-set-crm-item">
													<label class="mail-set-crm-check-label" for="create_imap_<?=$id ?>_crm_new_lead_always">
														<?=getMessage(
															'INTR_MAIL_INP_CRM_NEW_LEAD_ALLWAYS',
															array(
																'#LIST#' => sprintf(
																	'<a href="#" class="mail-set-textarea-show" id="create_imap_<?=$id ?>_crm_new_lead_for_link" onclick="%s">%s</a>',
																	"toggleCrmBlacklist(this, 'create_imap_{$id}_crm_new_lead_for'); return false; ",
																	getMessage('INTR_MAIL_INP_CRM_NEW_LEAD_ALLWAYS_LIST')
																)
															)
														) ?>
													</label>
													<span class="post-dialog-stat-info" title="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_NEW_LEAD_FOR_HINT')) ?>"></span>
													<div class="mail-set-textarea-wrapper" id="create_imap_<?=$id ?>_crm_new_lead_for" style="display: none; ">
														<div class="mail-set-textarea" id="mail-set-textarea">
															<textarea class="mail-set-textarea-input" name="new_lead_for"
																placeholder="<?=htmlspecialcharsbx(getMessage('INTR_MAIL_CRM_NEW_LEAD_FOR_PROMPT')) ?>"></textarea>
														</div>
													</div>
												</div>
												<div class="mail-set-crm-check-ext" id="create_imap_<?=$id ?>_crm_new_lead_resp">
													<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_ENTITY_SOURCE') ?>:</label>
													<label class="mail-set-singleselect mail-set-singleselect-line" data-checked="create_imap_<?=$id ?>_lead_source_<?=$arParams['DEFAULT_LEAD_SOURCE'] ?>">
														<input type="radio" name="lead_source" value="0">
														<div class="mail-set-singleselect-wrapper">
															<? foreach ($arParams['LEAD_SOURCE_LIST'] as $value => $title): ?>
																<input type="radio" name="lead_source" value="<?=$value ?>" id="create_imap_<?=$id ?>_lead_source_<?=$value ?>"
																	<? if ($value == $arParams['DEFAULT_LEAD_SOURCE']): ?> checked<? endif ?>>
																<label for="create_imap_<?=$id ?>_lead_source_<?=$value ?>"><?=htmlspecialcharsbx($title) ?></label>
															<? endforeach ?>
														</div>
													</label><br>
													<label class="mail-set-crm-check-label"><?=getMessage('INTR_MAIL_INP_CRM_ENTITY_RESP') ?>:</label>
													<div class="feed-add-post-destination-wrap mail-set-crm-resp-wrap" id="create_imap_<?=$id ?>_lead_resp_container"
														style="margin: 5px 0 0 0; background-color: #ffffff; ">
														<span id="create_imap_<?=$id ?>_lead_resp_item"></span>
														<span class="feed-add-destination-input-box" id="create_imap_<?=$id ?>_lead_resp_input_box" style="display: none; ">
															<input type="text" value="" class="feed-add-destination-inp" id="create_imap_<?=$id ?>_lead_resp_input">
														</span>
														<a href="javascript:void(0)" class="feed-add-destination-link" id="create_imap_<?=$id ?>_lead_resp_tag"
															style="display: inline-block; "><?=getMessage('INTR_MAIL_CRM_RESP_ADD') ?></a>
													</div>
												</div>
											</div>
										</div>
									</div>
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

							<script type="text/javascript">

								BX.SocNetLogDestination.init({
									name : 'create_imap_<?=$id ?>_lead_resp_selector',
									searchInput : BX('create_imap_<?=$id ?>_lead_resp_input'),
									departmentSelectDisable : true,
									extranetUser :  false,
									allowAddSocNetGroup: false,
									bindMainPopup : {
										node : BX('create_imap_<?=$id ?>_lead_resp_container'),
										offsetTop : '5px',
										offsetLeft: '15px'
									},
									bindSearchPopup : {
										node : BX('create_imap_<?=$id ?>_lead_resp_container'),
										offsetTop : '5px',
										offsetLeft: '15px'
									},
									callback : {
										select : function(item, type, search)
										{
											BX.SocNetLogDestination.BXfpSelectCallback({
												item: item,
												type: type,
												varName: 'lead_resp',
												bUndeleted: false,
												containerInput: BX('create_imap_<?=$id ?>_lead_resp_item'),
												valueInput: BX('create_imap_<?=$id ?>_lead_resp_input'),
												formName: 'create_imap_<?=$id ?>_lead_resp_selector',
												tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag',
												tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_SET')) ?>',
												tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_ADD')) ?>'
											});
										},
										unSelect : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
											formName: 'create_imap_<?=$id ?>_lead_resp_selector',
											inputContainerName: 'create_imap_<?=$id ?>_lead_resp_item',
											inputName: 'create_imap_<?=$id ?>_lead_resp_input',
											tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag',
											tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_SET')) ?>',
											tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CRM_RESP_ADD')) ?>'
										}),
										openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
											inputBoxName: 'create_imap_<?=$id ?>_lead_resp_input_box',
											inputName: 'create_imap_<?=$id ?>_lead_resp_input',
											tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag'
										}),
										closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
											inputBoxName: 'create_imap_<?=$id ?>_lead_resp_input_box',
											inputName: 'create_imap_<?=$id ?>_lead_resp_input',
											tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag'
										}),
										openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
											inputBoxName: 'create_imap_<?=$id ?>_lead_resp_input_box',
											inputName: 'create_imap_<?=$id ?>_lead_resp_input',
											tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag'
										})
									},
									items : {
										users : <?=CUtil::phpToJSObject($respList) ?>,
										groups : {},
										sonetgroups : {},
										department : <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department']) ?>,
										departmentRelation : <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department_relation']) ?>
									},
									itemsLast : {
										users : <?=CUtil::phpToJSObject($respLast) ?>,
										sonetgroups : {},
										department : {},
										groups : {}
									},
									itemsSelected: <?=CUtil::phpToJSObject($respSelected) ?>,
									destSort: {}
								});

								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
									formName: 'create_imap_<?=$id ?>_lead_resp_selector',
									inputName: 'create_imap_<?=$id ?>_lead_resp_input'
								}));
								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
									formName: 'create_imap_<?=$id ?>_lead_resp_selector',
									inputName: 'create_imap_<?=$id ?>_lead_resp_input',
									tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag'
								}));
								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_input'), 'paste', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
									formName: 'create_imap_<?=$id ?>_lead_resp_selector',
									inputName: 'create_imap_<?=$id ?>_lead_resp_input'
								}));
								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_input'), 'paste', BX.defer(BX.SocNetLogDestination.BXfpSearch, {
									formName: 'create_imap_<?=$id ?>_lead_resp_selector',
									inputName: 'create_imap_<?=$id ?>_lead_resp_input',
									tagInputName: 'create_imap_<?=$id ?>_lead_resp_tag',
									onPasteEvent: true
								}));

								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_tag'), 'click', function (e) {
									BX.SocNetLogDestination.openDialog('create_imap_<?=$id ?>_lead_resp_selector');
									BX.PreventDefault(e);
								});
								BX.bind(BX('create_imap_<?=$id ?>_lead_resp_container'), 'click', function (e) {
									BX.SocNetLogDestination.openDialog('create_imap_<?=$id ?>_lead_resp_selector');
									BX.PreventDefault(e);
								});

							</script>

						<? endforeach ?>

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

									setTimeout(function() {
										block.style.overflow = '';
										block.style.height   = '';
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
									block.style.height     = block.offsetHeight+'px';
									block.style.overflow   = 'hidden';
									block.style.position   = '';
									block.style.visibility = '';
									block.style.transition = 'height .1s';

									block.offsetHeight; // o.O
									block.style.height = '0px';
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
						}

						function toggleCrmBlacklist(link, blacklistId, show)
						{
							var blacklist = BX(blacklistId);
							var openClass = BX.hasClass(link, 'post-dialog-stat-link')
								? 'post-dialog-stat-link-open'
								: 'mail-set-textarea-show-open';

							show = typeof show == 'undefined' ? blacklist.style.display == 'none' : show;
							if (show)
							{
								BX.addClass(link, openClass);
								BX.show(blacklist, 'block');
							}
							else
							{
								BX.removeClass(link, openClass);
								BX.hide(blacklist, 'block');
							}
						}

						function showLicenseInfoPopup(id)
						{
							var descrs = {
								'age': '<?=CUtil::jsEscape(getMessage('MAIL_MAIL_CRM_LICENSE_DESCR_AGE')) ?>',
								'interval': '<?=CUtil::jsEscape(getMessage('MAIL_MAIL_CRM_LICENSE_DESCR_INTERVAL')) ?>'
							};

							B24.licenseInfoPopup.show(
								'mail_setup_'+id,
								'<?=CUtil::jsEscape(getMessage('MAIL_MAIL_CRM_LICENSE_TITLE')) ?>',
								descrs[id]
							);
						}

						(function() {

							var inputPlaceholder = function(input, text, isFake)
							{
								var isFake = isFake == false ? false : true;

								BX.adjust(input, {attrs: {'data-placeholder': text}});

								if (input.value == '')
								{
									if (isFake)
										BX.addClass(input, 'post-dialog-inp-placeholder');
									input.value = text;
								}

								BX.bind(input, 'focus', function()
								{
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

								BX.bind(input, 'blur', function()
								{
									if (input.value == '')
									{
										BX.addClass(input, 'post-dialog-inp-placeholder');
										input.value = text;
									}
								});
							};

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
										if (this.checked)
										{
											if (this.value == 0)
											{
												var input1 = BX(input.getAttribute('data-checked'));
												if (input1)
												{
													var label0 = BX.findNextSibling(this, {tag: 'label', attr: {'for': this.id}});
													var label1 = BX.findNextSibling(input1, {tag: 'label', attr: {'for': input1.id}});
													if (label0 && label1)
														BX.adjust(label0, {text: label1.innerHTML});
												}
											}
											else
											{
												input.setAttribute('data-checked', this.id);
											}
										}
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

							var selectInputs = BX.findChildrenByClassName(BX('create_imap'), 'mail-set-singleselect', true);
							for (var i in selectInputs)
								singleselect(selectInputs[i]);

							var selectInputs = BX.findChildrenByClassName(BX('edit_imap'), 'mail-set-singleselect', true);
							for (var i in selectInputs)
								singleselect(selectInputs[i]);

						})();

					</script>

				</div>
			</div>
		</div>
	</div>
</div>

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

						var imapDirsData = {};
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

							BX.adjust(BX.findChildByClassName(alert, 'post-dialog-alert-text', true), {text: '<?=getMessage('INTR_MAIL_IMAP_DIRS_ERROR') ?>'});
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

	<? if (!empty($arParams['CRM_PRECONNECT']) && !empty($arParams['IMAP_DIRS'])): ?>

	(function() {

		var form = BX('edit_imap');

		var _alert = function(message, details, error)
		{
			var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);
			var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

			var text = message;
			if (details && details.length > 0)
			{
				text += ' <span style="font-weight: normal; ">(';
				text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
				text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
				text += ')</span>';
			}

			BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
			BX.adjust(textCont, { html: text });
			BX.show(alert, 'block');
		};

		var enableCrm = function(data)
		{
			BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
			BX.addClass(BX('imap_edit_save'), 'webform-button-active webform-button-wait');

			data.sessid = '<?=bitrix_sessid() ?>';

			BX.ajax({
				method: 'POST',
				url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=enablecrm',
				data: data,
				dataType: 'json',
				onsuccess: function(json)
				{
					if (json.result != 'error')
					{
						window.location = '?success';
					}
					else
					{
						if (json.imap_dirs)
							showImapDirsPopup(json.imap_dirs, enableCrm);

						BX.removeClass(BX('imap_edit_save'), 'webform-button-accept-active webform-button-wait');
						_alert(json.error, json.error_ext, true);
					}
				},
				onfailure: function()
				{
					BX.removeClass(BX('imap_edit_save'), 'webform-button-accept-active webform-button-wait');
					_alert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
				}
			});
		};

		setTimeout(function() {
			showImapDirsPopup(<?=CUtil::phpToJsObject($arParams['IMAP_DIRS']) ?>, enableCrm);
		}, 0);

	})();

	<? endif ?>

	(function() {

		function ImapMailboxForm(form)
		{
			var self = this;

			var form = form;

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
				if (input.value.length > 0)
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

				return !(form.elements['server'] && (BX.hasClass(serverCont, 'post-dialog-inp-error') || BX.hasClass(portCont, 'post-dialog-inp-error')))
					&& !(form.elements['email'] && BX.hasClass(emailCont, 'post-dialog-inp-error'))
					&& !(form.elements['login'] && BX.hasClass(loginCont, 'post-dialog-inp-error'))
					&& !(form.elements['password'] && BX.hasClass(passCont, 'post-dialog-inp-error'));
			};

			this.clean = function(e)
			{
				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');

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

				if (form.elements['server'])
					BX.cleanNode(serverHint);
				if (form.elements['email'])
					BX.cleanNode(emailHint);
				if (form.elements['login'])
					BX.cleanNode(loginHint);
				if (form.elements['password'])
					BX.cleanNode(passHint);
			};

			this.enableCrm = function(data)
			{
				var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);

				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				data.sessid = '<?=bitrix_sessid() ?>';

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=enablecrm',
					data: data,
					dataType: 'json',
					onsuccess: function(json)
					{
						if (json.result != 'error')
						{
							window.location = '?success';
						}
						else
						{
							if (json.imap_dirs)
								showImapDirsPopup(json.imap_dirs, self.enableCrm);

							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert(json.error, json.error_ext, true);
						}
					},
					onfailure: function()
					{
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
						self.alert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')); ?>', false, true);
					}
				});
			};

			this.alert = function(message, details, error)
			{
				var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);
				var textCont = BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false);

				var text = message;
				if (details && details.length > 0)
				{
					text += ' <span style="font-weight: normal; ">(';
					text += '<a href="#" onclick="this.style.display = \'none\'; BX.findNextSibling(this, {class: \'post-dialog-alert-text-ext\'}).style.display = \'\'; return false; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_ERROR_EXT')) ?></a>';
					text += '<span class="post-dialog-alert-text-ext" style="display: none; ">'+details+'</span>';
					text += ')</span>';
				}

				BX[error ? 'removeClass' : 'addClass'](alert, 'post-dialog-alert-ok');
				BX.adjust(textCont, { html: text });
				BX.show(alert, 'block');
			};

			var deletePopup = false;
			this.delete = function()
			{
				var deleteForm = this;

				if (deletePopup === false)
				{
					deletePopup = new BX.PopupWindow('delete-mailbox', null, {
						closeIcon: true,
						closeByEsc: true,
						overlay: true,
						lightShadow: true,
						titleBar: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM')); ?>',
						content: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_REMOVE_CONFIRM_TEXT')); ?>',
						buttons: [
							new BX.PopupWindowButton({
								className: 'popup-window-button-decline',
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')); ?>',
								events: {
									click: function()
									{
										this.popupWindow.close();

										BX.addClass(deleteForm, 'webform-button-decline-active webform-button-wait');

										BX.ajax({
											method: 'POST',
											url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=delete',
											data: '<?=bitrix_sessid_get() ?>',
											dataType: 'json',
											onsuccess: function(json)
											{
												if (json.result != 'error')
												{
													window.location = '?delete';
												}
												else
												{
													BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');
													self.alert(json.error, json.error_ext, true);
												}
											},
											onfailure: function()
											{
												BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');
												self.alert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
											}
										});
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_INP_CANCEL')); ?>',
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

				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');

				deletePopup.show();
			};

			this.status = function()
			{
				var checkForm = this;
				var setupForm = BX.findParent(checkForm, {attr: {name: 'status-block'}});

				var statusText  = BX.findChild(setupForm, {attr: {name: 'status-text'}}, true, false);
				var statusAlert = BX.findChild(setupForm, {attr: {name: 'status-alert'}}, true, false);
				var statusInfo  = BX.findChild(setupForm, {attr: {name: 'status-info'}}, true, false);

				BX.addClass(checkForm, 'webform-button-active webform-button-wait');

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=check',
					data: '<?=bitrix_sessid_get() ?>',
					dataType: 'json',
					onsuccess: function(json)
					{
						BX.removeClass(checkForm, 'webform-button-active webform-button-wait');

						statusText.innerHTML = '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CHECK_TEXT', array(
							'#DATE#' => getMessage('INTR_MAIL_CHECK_JUST_NOW'),
						))) ?>:';

						if (json.result == 'ok')
						{
							BX.removeClass(statusAlert, 'post-status-error');
							BX.adjust(statusAlert, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CHECK_SUCCESS')) ?>'});
							BX.adjust(statusInfo, {style: {display: 'none'}});
						}
						else
						{
							BX.addClass(statusAlert, 'post-status-error');
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

						BX.addClass(statusAlert, 'post-status-error');
						BX.adjust(statusAlert, {text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_CHECK_ERROR')) ?>'});
						BX.adjust(statusInfo, {
							props: {title: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>'},
							style: {display: 'inline-block'}
						});
					}
				});
			};

			this.submit = function(act)
			{
				if (typeof act == 'undefined')
					act = 'create';

				var doSubmit = function(params)
				{
					var formButton = BX.findChild(form, {attr: {name: act+'-save'}}, true, false);

					BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
					BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

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

							// @TODO: cases
							if (form.elements[i].name.match(/\[\]$/))
							{
								var pname = form.elements[i].name.substr(0, form.elements[i].name.length-2);

								if (typeof data[pname] == 'undefined')
									data[pname] = [];

								data[pname].push(form.elements[i].value);
							}
							else
							{
								data[form.elements[i].name] = form.elements[i].value;
							}
						}
					}

					if (typeof params != 'undefined')
					{
						for (var i in data)
						{
							if (i.match(/^imap_dirs(\[|\$)/))
								delete data[i];
						}

						for (var i in params)
							data[i] = params[i];
					}

					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act='+act,
						data: data,
						dataType: 'json',
						onsuccess: function(json)
						{
							if (json.result != 'error')
							{
								if (act == 'create')
								{
									window.location = '?success';
								}
								else
								{
									BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
									self.alert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MAILBOX_EDIT_SUCCESS')) ?>', false, false);
								}
							}
							else
							{
								if (form.elements['oauth'] && json.oauth_url)
									form.elements['oauth'].value = json.oauth_url;

								// @TODO: update imap dirs list
								if (json.imap_dirs)
									showImapDirsPopup(json.imap_dirs, act == 'create' ? self.enableCrm : doSubmit);

								BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
								self.alert(json.error, json.error_ext, true);
							}
						},
						onfailure: function()
						{
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');
							self.alert('<?=CUtil::jsEscape(getMessage('INTR_MAIL_AJAX_ERROR')) ?>', false, true);
						}
					});
				};

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
		}

		if (BX('create_imap'))
		{
			var forms = BX.findChildren(BX('create_imap'), {attr: {name: 'create-imap'}}, true, true);
			var imapForms = {};
			var imapCmbForms = {};
			for (var i in forms)
			{
				imapForms[i] = BX.findChild(forms[i], {tag: 'form'}, true, false);
				imapCmbForms[i] = new ImapMailboxForm(imapForms[i]);

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
					BX.bind(imapForms[i], 'submit', function(e) {
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
						if (imapCmbForms[i].check(e))
							imapCmbForms[i].submit();
						return false;
					});
					BX.bind(BX.findChild(imapForms[i], {attr: {name: 'create-save'}}, true, false), 'click', function(e) {
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
			imapForm = BX.findChild(BX('edit_imap'), {tag: 'form'}, true, false);
			imapEmbForm = new ImapMailboxForm(imapForm);

			if (imapForm.elements['server'])
			{
				BX.bind(imapForm.elements['server'], 'keyup', imapEmbForm.checkServer);
				BX.bind(imapForm.elements['server'], 'blur', imapEmbForm.checkServer);

				BX.bind(imapForm.elements['port'], 'keyup', imapEmbForm.checkPort);
				BX.bind(imapForm.elements['port'], 'blur', imapEmbForm.checkPort);
			}

			if (imapForm.elements['login'])
			{
				BX.bind(imapForm.elements['login'], 'keyup', imapEmbForm.checkName);
				BX.bind(imapForm.elements['login'], 'blur', imapEmbForm.checkName);
			}

			(function(i) {
				BX.bind(imapForm, 'submit', function(e) {
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapEmbForm.check(e))
						imapEmbForm.submit('edit');
					return false;
				});
				BX.bind(BX.findChild(imapForm, {attr: {name: 'edit-save'}}, true, false), 'click', function(e) {
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapEmbForm.check(e))
						imapEmbForm.submit('edit');
					return false;
				});
			})(i);

			BX.bind(BX('imap_check_form'), 'click', imapEmbForm.status);

			//BX.bind(BX('imap_delete_form'), 'click', imapEmbForm.delete);
			BX.bind(BX('imap_delete_form2'), 'click', imapEmbForm.delete);
		}

	})();

</script>
