<?php

use Bitrix\Main\Localization;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Localization\Loc::loadMessages(__DIR__.'/component.php');

class CCrmConfigEmailtrackerAjax
{

	static $currentSite = false;
	static $currentMailbox = false;
	static $limitedLicense = false;

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!CModule::includeModule('crm'))
			$error = getMessage('CRM_MODULE_NOT_INSTALLED');

		if ($error === false)
		{
			if (!CModule::includeModule('mail'))
				$error = getMessage('MAIL_MODULE_NOT_INSTALLED');
		}

		if ($error === false)
		{
			if (!is_object($USER) || !$USER->isAuthorized())
			{
				$error = getMessage('INTR_MAIL_AUTH');
			}
			else
			{
				$crmPerms = new \CCrmPerms($USER->getId());
				if (!$crmPerms->havePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
					$error = getMessage('CRM_PERMISSION_DENIED');
			}
		}

		\CUtil::jsPostUnescape();

		if ($error === false)
		{
			if (!empty($_REQUEST['siteid']))
				self::$currentSite = \CSite::getById($_REQUEST['siteid'])->fetch();

			if (empty(self::$currentSite))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if (\CModule::includeModule('bitrix24'))
				self::$limitedLicense = !in_array(\CBitrix24::getLicenseType(), array('company', 'nfr', 'edu', 'demo'));

			$res = \Bitrix\Mail\MailboxTable::getList(array(
				'filter' => array(
					'LID'     => self::$currentSite['LID'],
					'ACTIVE'  => 'Y',
					'USER_ID' => 0,
				),
				'order' => array(
					'TIMESTAMP_X' => 'DESC',
				),
			));
			while ($mailbox = $res->fetch())
			{
				if (!empty($mailbox['OPTIONS']['flags']) && is_array($mailbox['OPTIONS']['flags']))
				{
					if (array_intersect(array('crm_preconnect', 'crm_connect'), $mailbox['OPTIONS']['flags']))
					{
						self::$currentMailbox = $mailbox;
						break;
					}
				}
			}

			$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;
			switch ($act)
			{
				case 'create':
					$result = (array) self::executeCreateMailbox($error);
					break;
				case 'edit':
					$result = (array) self::executeEditMailbox($error);
					break;
				case 'delete':
					$result = (array) self::executeDeleteMailbox($error);
					break;
				case 'check':
					$result = (array) self::executeCheckMailbox($error);
					break;
				case 'enablecrm':
					$result = (array) self::executeEnableCrm($error);
					break;
				case 'imapdirs':
					$result = (array) self::executeListImapDirs($error);
					break;
				default:
					$error = getMessage('INTR_MAIL_AJAX_ERROR');
			}
		}

		if ($error instanceof \Bitrix\Main\ErrorCollection)
			list($error, $details) = self::prepareError($error);

		self::returnJson(array_merge(array(
			'result'    => $error === false ? 'ok' : 'error',
			'error'     => $error,
			'error_ext' => !empty($details) ? $details : false,
		), $result));
	}

	private static function executeCreateMailbox(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (!empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_CRM_ALREADY');
		}

		if ($error === false)
		{
			if (!empty($_REQUEST['SERVICE']))
			{
				$service = \Bitrix\Mail\MailServicesTable::getList(array(
					'filter' => array(
						'=ID'          => $_REQUEST['SERVICE'],
						'ACTIVE'       => 'Y',
						'SERVICE_TYPE' => 'imap',
					),
				))->fetch();
			}

			if (empty($service) || $service['SITE_ID'] != self::$currentSite['LID'])
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$mbData = array(
				'SERVER'   => $service['SERVER'] ?: $_REQUEST['server'],
				'PORT'     => $service['PORT'] ?: $_REQUEST['port'],
				'NAME'     => $_REQUEST['email'],
				'LOGIN'    => $_REQUEST['login'],
				'PASSWORD' => $_REQUEST['password'],
				'USE_TLS'  => $service['ENCRYPTION'] ?: $_REQUEST['encryption'],
				'PERIOD_CHECK' => 10,
				'OPTIONS'  => array(
					'flags'     => array('crm_preconnect'),
					'sync_from' => time(),
					'imap'      => array(
						'income' => array('INBOX'),
					),
				),
			);

			if (!in_array($mbData['USE_TLS'], array('Y', 'S')))
				$mbData['USE_TLS'] = 'N';

			if (!$service['SERVER'])
			{
				$regExp = '/^(?:(?:http|https|ssl|tls|imap):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
				if (preg_match($regExp, trim($mbData['SERVER']), $matches) && $matches[1] <> '')
					$mbData['SERVER'] = $matches[1];
				else
					$error = getMessage('INTR_MAIL_FORM_ERROR');
			}

			if ($error === false)
			{
				if (!empty($_REQUEST['oauth']) && \CModule::includeModule('socialservices'))
				{
					$oauthFulfilled = false;

					if ($service['NAME'] == 'gmail' && $service['SERVER'] == 'imap.gmail.com')
					{
						$oauthClient = new \CSocServGoogleOAuth();

						if ($oauthClient->checkSettings())
						{
							$oauthUrl = $oauthClient->getUrl(
								'opener',
								array('email', 'https://mail.google.com/'),
								array('BACKURL' => uniqid('#oauth'))
							);
							$oauthEntity = $oauthClient->getEntityOAuth();

							if ($oauthEntity->getAccessToken())
								$response = $oauthEntity->getCurrentUser();

							if (empty($response['email']))
							{
								$error = new \Bitrix\Main\ErrorCollection();
								$error->setError(new \Bitrix\Main\Error(getMessage('INTR_MAIL_IMAP_OAUTH_ACC'), 0));

								if (!empty($response['error']['message']))
									$error->setError(new \Bitrix\Main\Error($response['error']['message'], -1));
							}
							else
							{
								$mbData['NAME'] = mb_strtolower(trim($response['email']));
								$mbData['LOGIN'] = mb_strtolower(trim($response['email']));
								$mbData['PASSWORD'] = "\x00oauth\x00google\x00".$USER->getId();

								$oauthFulfilled = true;
							}
						}
					}

					if ($service['NAME'] == 'outlook.com' && $service['SERVER'] == 'imap-mail.outlook.com')
					{
						$oauthClient = new \CSocServLiveIDOAuth();

						if ($oauthClient->checkSettings())
						{
							$oauthUrl = $oauthClient->getUrl(
								'opener',
								array('wl.emails', 'wl.imap', 'wl.offline_access'),
								array('BACKURL' => uniqid('#oauth'))
							);
							$oauthEntity = $oauthClient->getEntityOAuth();

							if ($oauthEntity->getAccessToken())
							{
								$httpClient = new \Bitrix\Main\Web\HttpClient();
								$httpClient->setHeader('Authorization', 'Bearer '.$oauthEntity->getToken());

								$response = self::decodeJson($httpClient->get('https://apis.live.net/v5.0/me'));
							}

							if (empty($response['emails']) || !is_array($response['emails']))
							{
								$error = new \Bitrix\Main\ErrorCollection();
								$error->setError(new \Bitrix\Main\Error(getMessage('INTR_MAIL_IMAP_OAUTH_ACC'), 0));

								if (!empty($response['error']['message']))
									$error->setError(new \Bitrix\Main\Error($response['error']['message'], -1));
							}
							else
							{
								if (!empty($response['emails']['account']))
								{
									$mbData['NAME'] = mb_strtolower(trim($response['emails']['account']));
									$mbData['LOGIN'] = mb_strtolower(trim($response['emails']['account']));
									$mbData['PASSWORD'] = "\x00oauth\x00liveid\x00".$USER->getId();

									$oauthFulfilled = true;
								}

								if ($oauthFulfilled == false)
									$error = getMessage('INTR_MAIL_IMAP_OAUTH_ACC');
							}
						}
					}

					if ($error === false)
					{
						if ($oauthFulfilled == false)
							$error = getMessage('INTR_MAIL_FORM_ERROR');
					}

					if ($error !== false && !empty($oauthUrl))
						return array('oauth_url' => $oauthUrl);
				}
			}

			if ($error === false)
			{
				if (!check_email($mbData['NAME'], true))
					$error = getMessage('INTR_MAIL_INP_EMAIL_BAD');
			}

			if ($error === false)
			{
				$unseen = \Bitrix\Mail\Helper::getImapUnseen($mbData, 'inbox', $error);
				if ($unseen !== false)
					$error = false;
			}

			if ($error === false)
			{
				if ($_REQUEST['sync_old'] == 'Y')
				{
					$maxAge     = (int) $_REQUEST['max_age'];
					$ageOptions = self::$limitedLicense ? array(3) : array(-1, 3);

					if (in_array($maxAge, $ageOptions))
					{
						if ($maxAge < 0)
							unset($mbData['OPTIONS']['sync_from']);
						else
							$mbData['OPTIONS']['sync_from'] = strtotime(sprintf('-%u days', $maxAge));
					}
					else
					{
						$error = getMessage('INTR_MAIL_MAX_AGE_ERROR');
					}
				}

				if ($_REQUEST['interval'] >= 10 || !self::$limitedLicense && $_REQUEST['interval'] > 0)
					$mbData['PERIOD_CHECK'] = (int) $_REQUEST['interval'];

				if ($_REQUEST['crm_new_entity_in'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_entity_in';
				if ($_REQUEST['crm_new_entity_out'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_entity_out';

				$newEntityList = array(\CCrmOwnerType::LeadName, \CCrmOwnerType::ContactName);
				if (!empty($_REQUEST['allow_new_entity_in']) && in_array($_REQUEST['allow_new_entity_in'], $newEntityList))
					$mbData['OPTIONS']['crm_new_entity_in'] = $_REQUEST['allow_new_entity_in'];
				if (!empty($_REQUEST['allow_new_entity_out']) && in_array($_REQUEST['allow_new_entity_out'], $newEntityList))
					$mbData['OPTIONS']['crm_new_entity_out'] = $_REQUEST['allow_new_entity_out'];

				$mbData['OPTIONS']['crm_new_lead_for'] = array();
				if (!empty($_REQUEST['new_lead_for']))
				{
					$newLeadFor = preg_split('/[\r\n,;]+/', $_REQUEST['new_lead_for']);
					foreach ($newLeadFor as $i => $item)
					{
						$email = \CMailUtil::extractMailAddress($item);
						$email = trim($email);

						$newLeadFor[$i] = check_email($email) ? $email : null;
					}

					$mbData['OPTIONS']['crm_new_lead_for'] = array_values(array_unique(array_filter($newLeadFor)));
				}

				$leadSourceList = \CCrmStatus::getStatusList('SOURCE');
				if (is_set($leadSourceList, $_REQUEST['lead_source']))
					$mbData['OPTIONS']['crm_lead_source'] = $_REQUEST['lead_source'];

				$mbData['OPTIONS']['crm_lead_resp'] = array();
				if (!empty($_REQUEST['lead_resp']['U']))
				{
					foreach ((array) $_REQUEST['lead_resp']['U'] as $item)
					{
						if (preg_match('/^U(\d+)$/i', trim($item), $matches))
							$mbData['OPTIONS']['crm_lead_resp'][] = (int) $matches[1];
					}
				}
				if (empty($mbData['OPTIONS']['crm_lead_resp']))
					$mbData['OPTIONS']['crm_lead_resp'] = array($USER->getId());

				if ($_REQUEST['crm_new_contact'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_new_contact';

				$mbData['OPTIONS']['name'] = empty($_REQUEST['name']) ? '' : trim($_REQUEST['name']);

				if (!empty($_REQUEST['black_list']))
				{
					$blacklist = preg_split('/[\r\n,;]+/', $_REQUEST['black_list']);
					foreach ($blacklist as $i => $item)
					{
						$email = \CMailUtil::extractMailAddress($item);
						$email = ltrim($email, " \t\n\r\0\x0b@");
						$email = rtrim($email);

						$blacklist[$i] = null;
						if (mb_strpos($email, '@') === false)
						{
							if (check_email(sprintf('email@%s', $email)))
								$blacklist[$i] = $email;
						}
						else
						{
							if (check_email($email))
								$blacklist[$i] = $email;
						}
					}

					$blacklist = array_unique(array_filter($blacklist));
				}
			}

			if ($error === false)
			{
				$mbData = array_merge(array(
					'LID'         => self::$currentSite['LID'],
					'ACTIVE'      => 'Y',
					'SERVICE_ID'  => $service['ID'],
					'SERVER_TYPE' => $service['SERVICE_TYPE'],
					'CHARSET'     => self::$currentSite['CHARSET'],
					'USER_ID'     => 0,
				), $mbData);

				$mbId = \CMailbox::add($mbData);

				if ($mbId > 0)
				{
					\Bitrix\Main\Config\Option::set('mail', 'last_mail_check', time(), self::$currentSite['LID']);
					\Bitrix\Main\Config\Option::set('mail', 'last_mail_check_success', $unseen >= 0 ? 'Y' : 'N', self::$currentSite['LID']);

					\Bitrix\Mail\BlacklistTable::replace(
						self::$currentSite['LID'], $mbId,
						!empty($blacklist) ? $blacklist : array()
					);
				}
				else
				{
					$error = getMessage('INTR_MAIL_SAVE_ERROR');
				}
			}

			if ($error === false)
			{
				$mbData['OPTIONS']['flags'] = array_diff($mbData['OPTIONS']['flags'], array('crm_preconnect'));
				$mbData['OPTIONS']['flags'][] = 'crm_connect';

				$imapDirs = \Bitrix\Mail\Helper::listImapDirs($mbData, $error);
				if ($imapDirs !== false)
				{
					$error = false;

					$mbData['OPTIONS']['imap'] = array(
						'income'  => array(),
						'outcome' => array(),
					);

					foreach ($imapDirs as $i => $item)
					{
						if (!empty($item['disabled']))
							continue;

						if ($item['income'])
							$mbData['OPTIONS']['imap']['income'][] = $item['path'];
						elseif ($item['outcome'])
							$mbData['OPTIONS']['imap']['outcome'][] = $item['path'];
					}

					$imapOptions = $mbData['OPTIONS']['imap'];
					if (empty($imapOptions['income']) || empty($imapOptions['outcome']))
					{
						$error = getMessage('INTR_MAIL_IMAP_DIRS');
						return array('imap_dirs' => $imapDirs);
					}
				}

				if ($error === false)
				{
					$result = \CMailbox::update($mbId, array('OPTIONS' => $mbData['OPTIONS']));

					if ($result)
					{
						$filterFields = array(
							'MAILBOX_ID'         => $mbId,
							'NAME'               => sprintf('CRM IMAP %u', $mbId),
							'ACTION_TYPE'        => 'crm_imap',
							'WHEN_MAIL_RECEIVED' => 'Y',
							'WHEN_MANUALLY_RUN'  => 'Y',
						);

						\CMailFilter::add($filterFields);
					}
					else
					{
						$error = getMessage('INTR_MAIL_SAVE_ERROR');
					}
				}

				if ($error !== false)
				{
					$result = array('late_error' => $error);
					$error  = false;

					return $result;
				}
			}
		}
	}

	private static function executeEditMailbox(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');

			$mailbox = self::$currentMailbox;
		}

		if ($error === false)
		{
			$service = \Bitrix\Mail\MailServicesTable::getList(array(
				'filter' => array(
					'=ID'          => $mailbox['SERVICE_ID'],
					'ACTIVE'       => 'Y',
					'SERVICE_TYPE' => 'imap',
				),
			))->fetch();

			$mbData = array(
				'LOGIN'   => $mailbox['LOGIN'],
				'OPTIONS' => $mailbox['OPTIONS'],
			);
			$mbData['OPTIONS']['flags'] = array_diff(
				$mbData['OPTIONS']['flags'],
				array(
					'crm_preconnect', 'crm_connect',
					'crm_deny_new_lead', 'crm_deny_entity_in', 'crm_deny_entity_out', 'crm_deny_new_contact',
				)
			);
			$mbData['OPTIONS']['flags'][] = 'crm_connect';

			if (empty($service['SERVER']) && is_set($_REQUEST, 'server'))
			{
				$regExp = '/^(?:(?:http|https|ssl|tls|imap):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
				if (preg_match($regExp, trim($_REQUEST['server']), $matches) && $matches[1] <> '')
					$mbData['SERVER'] = $matches[1];
				else
					$error = getMessage('INTR_MAIL_FORM_ERROR');
			}

			if ($error === false)
			{
				if (empty($service['PORT']) && is_set($_REQUEST, 'port'))
					$mbData['PORT'] = (int) $_REQUEST['port'];

				if (empty($service['ENCRYPTION']) && is_set($_REQUEST, 'encryption'))
					$mbData['USE_TLS'] = in_array($_REQUEST['encryption'], array('Y', 'S')) ? $_REQUEST['encryption'] : 'N';

				$isOauthMailbox = false;

				if ($service['NAME'] == 'gmail' && $service['SERVER'] == 'imap.gmail.com')
				{
					if (preg_match('/^\x00oauth\x00google\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
					{
						$oauthClient = new \CSocServGoogleOAuth();
						$isOauthMailbox = $oauthClient->checkSettings();
					}
				}

				if ($service['NAME'] == 'outlook.com' && $service['SERVER'] == 'imap-mail.outlook.com')
				{
					if (preg_match('/^\x00oauth\x00liveid\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
					{
						$oauthClient = new \CSocServGoogleOAuth();
						$isOauthMailbox = $oauthClient->checkSettings();
					}
				}

				if (!$isOauthMailbox)
				{
					if (is_set($_REQUEST, 'password'))
						$mbData['PASSWORD'] = $_REQUEST['password'];
				}

				if ($_REQUEST['interval'] >= 10 || !self::$limitedLicense && $_REQUEST['interval'] > 0)
					$mbData['PERIOD_CHECK'] = (int) $_REQUEST['interval'];

				if ($_REQUEST['crm_new_entity_in'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_entity_in';
				if ($_REQUEST['crm_new_entity_out'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_entity_out';

				$newEntityList = array(\CCrmOwnerType::LeadName, \CCrmOwnerType::ContactName);
				if (!empty($_REQUEST['allow_new_entity_in']) && in_array($_REQUEST['allow_new_entity_in'], $newEntityList))
					$mbData['OPTIONS']['crm_new_entity_in'] = $_REQUEST['allow_new_entity_in'];
				if (!empty($_REQUEST['allow_new_entity_out']) && in_array($_REQUEST['allow_new_entity_out'], $newEntityList))
					$mbData['OPTIONS']['crm_new_entity_out'] = $_REQUEST['allow_new_entity_out'];

				$mbData['OPTIONS']['crm_new_lead_for'] = array();
				if (!empty($_REQUEST['new_lead_for']))
				{
					$newLeadFor = preg_split('/[\r\n,;]+/', $_REQUEST['new_lead_for']);
					foreach ($newLeadFor as $i => $item)
					{
						$email = \CMailUtil::extractMailAddress($item);
						$email = trim($email);

						$newLeadFor[$i] = check_email($email) ? $email : null;
					}

					$mbData['OPTIONS']['crm_new_lead_for'] = array_values(array_unique(array_filter($newLeadFor)));
				}

				$leadSourceList = \CCrmStatus::getStatusList('SOURCE');
				if (is_set($leadSourceList, $_REQUEST['lead_source']))
					$mbData['OPTIONS']['crm_lead_source'] = $_REQUEST['lead_source'];

				$mbData['OPTIONS']['crm_lead_resp'] = array();
				if (!empty($_REQUEST['lead_resp']['U']))
				{
					foreach ((array) $_REQUEST['lead_resp']['U'] as $item)
					{
						if (preg_match('/^U(\d+)$/i', trim($item), $matches))
							$mbData['OPTIONS']['crm_lead_resp'][] = (int) $matches[1];
					}
				}
				if (empty($mbData['OPTIONS']['crm_lead_resp']))
					$mbData['OPTIONS']['crm_lead_resp'] = array($USER->getId());

				if ($_REQUEST['crm_new_contact'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_new_contact';

				$mbData['OPTIONS']['name'] = empty($_REQUEST['name']) ? '' : trim($_REQUEST['name']);

				if (!empty($_REQUEST['black_list']))
				{
					$blacklist = preg_split('/[\r\n,;]+/', $_REQUEST['black_list']);
					foreach ($blacklist as $i => $item)
					{
						$email = \CMailUtil::extractMailAddress($item);
						$email = ltrim($email, " \t\n\r\0\x0b@");
						$email = rtrim($email);

						$blacklist[$i] = null;
						if (mb_strpos($email, '@') === false)
						{
							if (check_email(sprintf('email@%s', $email)))
								$blacklist[$i] = $email;
						}
						else
						{
							if (check_email($email))
								$blacklist[$i] = $email;
						}
					}

					$blacklist = array_unique(array_filter($blacklist));
				}

				$imapDirs = \Bitrix\Mail\Helper::listImapDirs(array_merge($mailbox, $mbData), $error, $errors);
				if ($imapDirs !== false)
				{
					$error = false;

					if (!is_set($mbData['OPTIONS'], 'imap') || !is_array($mbData['OPTIONS']['imap']))
						$mbData['OPTIONS']['imap'] = array();

					$mbData['OPTIONS']['imap']['income']  = array();
					$mbData['OPTIONS']['imap']['outcome'] = array();

					$availableDirs = array();
					foreach ($imapDirs as $i => $item)
					{
						if (!empty($item['disabled']))
							continue;

						$availableDirs[] = $item['path'];

						if ($item['income'])
							$mbData['OPTIONS']['imap']['income'][] = $item['path'];
						elseif ($item['outcome'])
							$mbData['OPTIONS']['imap']['outcome'][] = $item['path'];
					}

					if (is_set($_REQUEST, 'imap_dirs'))
					{
						$income = (array) $_REQUEST['imap_dirs']['income'];
						$mbData['OPTIONS']['imap']['income'] = array_intersect($income, $availableDirs);

						$outcome = (array) $_REQUEST['imap_dirs']['outcome'];
						$mbData['OPTIONS']['imap']['outcome'] = array_intersect($outcome, $availableDirs);
					}

					$imapOptions = $mbData['OPTIONS']['imap'];
					if (empty($imapOptions['income']) || empty($imapOptions['outcome']))
					{
						$error = getMessage('INTR_MAIL_IMAP_DIRS');
						return array('imap_dirs' => $imapDirs);
					}
				}
				else
				{
					if ($errors instanceof \Bitrix\Main\ErrorCollection)
						$error = $errors;
				}
			}

			if ($error === false)
			{
				$unseen = \Bitrix\Mail\Helper::getImapUnseen(array_merge($mailbox, $mbData), 'inbox', $error, $errors);
				if ($unseen === false)
				{
					if ($errors instanceof \Bitrix\Main\ErrorCollection)
						$error = $errors;
				}
				else
				{
					$error = false;
				}
			}

			if ($error === false)
			{
				\Bitrix\Mail\BlacklistTable::replace(
					$mailbox['LID'], $mailbox['ID'],
					!empty($blacklist) ? $blacklist : array()
				);

				$result = \CMailbox::update($mailbox['ID'], $mbData);

				if (!$result)
					$error = getMessage('INTR_MAIL_SAVE_ERROR');
			}
		}
	}

	private static function executeDeleteMailbox(&$error)
	{
		global $USER;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');

			$mailbox = self::$currentMailbox;
		}

		if ($error === false)
			\CMailbox::delete($mailbox['ID']);
	}

	private static function executeCheckMailbox(&$error)
	{
		global $USER;

		$error  = false;
		$unseen = -1;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');

			$mailbox = self::$currentMailbox;
		}

		if ($error === false)
		{
			$unseen = \Bitrix\Mail\Helper::getImapUnseen($mailbox, 'inbox', $error);
			if ($unseen !== false)
				$error = false;

			\Bitrix\Main\Config\Option::set('mail', 'last_mail_check', time(), $mailbox['LID']);
			\Bitrix\Main\Config\Option::set('mail', 'last_mail_check_success', $unseen >= 0 ? 'Y' : 'N', $mailbox['LID']);
		}

		return array('unseen' => $unseen);
	}

	private static function executeEnableCrm(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');

			$mailbox = self::$currentMailbox;
		}

		if ($error === false)
		{
			$mbData = array(
				'OPTIONS' => $mailbox['OPTIONS'],
			);

			$mbData['OPTIONS']['flags'] = array_diff($mbData['OPTIONS']['flags'], array('crm_preconnect'));
			$mbData['OPTIONS']['flags'][] = 'crm_connect';

			if ($error === false)
			{
				$imapDirs = \Bitrix\Mail\Helper::listImapDirs($mailbox, $error);
				if ($imapDirs !== false)
				{
					$error = false;

					if (!is_set($mbData['OPTIONS'], 'imap') || !is_array($mbData['OPTIONS']['imap']))
						$mbData['OPTIONS']['imap'] = array();

					$mbData['OPTIONS']['imap']['income']  = array();
					$mbData['OPTIONS']['imap']['outcome'] = array();

					$availableDirs = array();
					foreach ($imapDirs as $i => $item)
					{
						if (!empty($item['disabled']))
							continue;

						$availableDirs[] = $item['path'];

						if ($item['income'])
							$mbData['OPTIONS']['imap']['income'][] = $item['path'];
						elseif ($item['outcome'])
							$mbData['OPTIONS']['imap']['outcome'][] = $item['path'];
					}

					if (is_set($_REQUEST, 'imap_dirs'))
					{
						$income = (array) $_REQUEST['imap_dirs']['income'];
						$mbData['OPTIONS']['imap']['income'] = array_intersect($income, $availableDirs);

						$outcome = (array) $_REQUEST['imap_dirs']['outcome'];
						$mbData['OPTIONS']['imap']['outcome'] = array_intersect($outcome, $availableDirs);
					}

					$imapOptions = $mbData['OPTIONS']['imap'];
					if (empty($imapOptions['income']) || empty($imapOptions['outcome']))
					{
						$error = getMessage('INTR_MAIL_IMAP_DIRS');
						return array('imap_dirs' => $imapDirs);
					}
				}
			}

			if ($error === false)
			{
				$result = \CMailbox::update($mailbox['ID'], $mbData);

				if ($result)
				{
					$res = \Bitrix\Mail\MailFilterTable::getList(array(
						'select' => array('ID'),
						'filter' => array(
							'=MAILBOX_ID'  => $mailbox['ID'],
							'=ACTION_TYPE' => 'crm_imap'
						)
					));
					while ($filter = $res->fetch())
						\CMailFilter::delete($filter['ID']);

					$filterFields = array(
						'MAILBOX_ID'         => $mailbox['ID'],
						'NAME'               => sprintf('CRM IMAP %u', $mailbox['ID']),
						'ACTION_TYPE'        => 'crm_imap',
						'WHEN_MAIL_RECEIVED' => 'Y',
						'WHEN_MANUALLY_RUN'  => 'Y',
					);

					\CMailFilter::add($filterFields);
				}
				else
				{
					$error = getMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeListImapDirs(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty(self::$currentMailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');

			$mailbox = self::$currentMailbox;
		}

		if ($error === false)
		{
			$imapDirs = \Bitrix\Mail\Helper::listImapDirs($mailbox, $error);
			if ($imapDirs !== false)
			{
				$error = false;

				if (is_set($_REQUEST, 'imap_dirs'))
				{
					$income = (array) $_REQUEST['imap_dirs']['income'];
					$outcome = (array) $_REQUEST['imap_dirs']['outcome'];
				}
				else
				{
					$income  = $mailbox['OPTIONS']['imap']['income'];
					$outcome = $mailbox['OPTIONS']['imap']['outcome'];
				}

				foreach ($imapDirs as $i => $item)
				{
					$imapDirs[$i]['income']  = empty($item['disabled']) && in_array($item['path'], $income);
					$imapDirs[$i]['outcome'] = empty($item['disabled']) && in_array($item['path'], $outcome);
				}

				return array('imap_dirs' => $imapDirs);
			}
		}
	}

	private static function prepareError(\Bitrix\Main\ErrorCollection $errors)
	{
		$messages = array();
		$details  = array();

		foreach ($errors as $item)
			${$item->getCode() < 0 ? 'details' : 'messages'}[] = $item;

		if (count($messages) == 1 && reset($messages)->getCode() == \Bitrix\Mail\Imap::ERR_AUTH)
		{
			$messages = array(
				new \Bitrix\Main\Error(getMessage('INTR_MAIL_IMAP_AUTH_ERR_EXT'), \Bitrix\Mail\Imap::ERR_AUTH)
			);
		}

		$reduce = function($error)
		{
			return $error->getMessage();
		};

		return array(
			join(': ', array_map($reduce, $messages)),
			join(': ', array_map($reduce, $details)),
		);
	}

	private static function decodeJson($json)
	{
		if (empty($json))
			return null;

		try
		{
			return \Bitrix\Main\Web\Json::decode($json);
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->restartBuffer();

		header('Content-Type: application/x-javascript; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($data);
		die;
	}

}

CCrmConfigEmailtrackerAjax::execute();
