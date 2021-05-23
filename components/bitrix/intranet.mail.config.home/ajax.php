<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php');

\Bitrix\Main\Localization\Loc::loadMessages(__DIR__.'/class.php');

class CIntranetMailConfigHomeAjax
{

	static $siteId;
	static $siteCharset;

	static $crmAvailable   = false;
	static $limitedLicense = false;

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!CModule::IncludeModule('mail'))
			$error = GetMessage('MAIL_MODULE_NOT_INSTALLED');

		if ($error === false)
		{
			if (!is_object($USER) || !$USER->IsAuthorized())
				$error = GetMessage('INTR_MAIL_AUTH');
		}

		if ($error === false)
		{
			if (!CIntranetUtils::IsExternalMailAvailable())
				$error = GetMessage('INTR_MAIL_UNAVAILABLE');
		}

		if ($error === false)
		{
			$site = \CSite::getById($_REQUEST['siteid'])->fetch();
			
			if (empty($site))
				$error = getMessage('INTR_MAIL_AJAX_ERROR');

			self::$siteId      = $site['LID'];
			self::$siteCharset = $site['CHARSET'];
		}

		if ($error === false)
		{
			if (\CModule::includeModule('crm') && \CCrmPerms::isAccessEnabled())
			{
				self::$crmAvailable = $USER->isAdmin() || $USER->canDoOperation('bitrix24_config')
					|| COption::getOptionString('intranet', 'allow_external_mail_crm', 'Y', self::$siteId) == 'Y';
			}

			if (\CModule::includeModule('bitrix24'))
				self::$limitedLicense = !in_array(\CBitrix24::getLicenseType(), array('company', 'nfr', 'edu', 'demo'));

			$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;
			$result = (array) self::handleDefaultAction($act, $error);
		}

		if ($error instanceof \Bitrix\Main\ErrorCollection)
			list($error, $details) = self::prepareError($error);

		self::returnJson(array_merge(array(
			'result'    => $error === false ? 'ok' : 'error',
			'error'     => $error,
			'error_ext' => !empty($details) ? $details : false,
		), $result));
	}

	private static function handleDefaultAction($act, &$error)
	{
		switch ($act)
		{
			case 'name':
				return self::executeCheckName($error);
				break;
			case 'create':
				return self::executeCreateMailbox($error);
				break;
			case 'edit':
				return self::executeEditMailbox($error);
				break;
			case 'delete':
				return self::executeDeleteMailbox($error);
				break;
			case 'check':
				return self::executeCheck($error);
				break;
			case 'disablecrm':
				return self::executeDisableCrm($error);
				break;
			case 'enablecrm':
				return self::executeEnableCrm($error);
				break;
			case 'configcrm':
				return self::executeConfigCrm($error);
				break;
			case 'imapdirs':
				return self::executeListImapDirs($error);
				break;
			case 'password':
				return self::executeChangePassword($error);
				break;
			default:
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}
	}

	private static function executeCheckName(&$error)
	{
		$error    = false;
		$occupied = -1;

		$serviceId = isset($_REQUEST['SERVICE']) ? $_REQUEST['SERVICE'] : null;
		if (empty($serviceId))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			else if (!in_array($services[$serviceId]['type'], array('controller', 'domain', 'crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			else if (empty($_REQUEST['login']) || empty($_REQUEST['domain']))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');

			foreach ($services as $i => $item)
				$services[$i]['server'] = mb_strtolower($item['server']);
		}

		if ($error === false)
		{
			if ($services[$serviceId]['type'] == 'controller')
			{
				$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckName', array(
					'DOMAIN' => $_REQUEST['domain'],
					'NAME'   => $_REQUEST['login']
				));
				if (isset($crCheckName['result']))
				{
					$occupied = (boolean) $crCheckName['result'];
				}
				else
				{
					$error = empty($crCheckName['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crCheckName['error']);
				}
			}
			else if ($services[$serviceId]['type'] == 'crdomain')
			{
				if ($services[$serviceId]['server'] != $_REQUEST['domain'])
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else
				{
					$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberName', array(
						'DOMAIN' => $_REQUEST['domain'],
						'NAME'   => $_REQUEST['login']
					));
					if (isset($crCheckName['result']))
					{
						$occupied = (boolean) $crCheckName['result'];
					}
					else
					{
						$error = empty($crCheckName['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckName['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'domain')
			{
				if ($services[$serviceId]['server'] != mb_strtolower($_REQUEST['domain']))
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else
				{
					$result = CMailDomain2::isUserExists(
						$services[$serviceId]['token'],
						$_REQUEST['domain'], $_REQUEST['login'],
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
					else
						$occupied = (boolean) $result;
				}
			}
		}

		return array('occupied' => $occupied);
	}

	private static function executeCreateMailbox(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$serviceId = isset($_REQUEST['SERVICE']) ? $_REQUEST['SERVICE'] : null;
			if (empty($serviceId))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$unseen = 0;

			if ($services[$serviceId]['type'] == 'controller')
			{
				if (mb_strtolower(trim($_REQUEST['domain'])) == 'bitrix24.com')
				{
					$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
				else if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
				}

				if ($error === false)
				{
					$mbData = array(
						'NAME'     => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'LOGIN'    => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'PASSWORD' => $_REQUEST['password'],
						'CHARSET'  => self::$siteCharset,
						'OPTIONS'  => array(
							'flags'     => array(),
							'sync_from' => time(),
						),
					);

					$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddUser', array(
						'DOMAIN'   => $_REQUEST['domain'],
						'NAME'     => $_REQUEST['login'],
						'PASSWORD' => $_REQUEST['password']
					));
					if (!isset($crResponse['result']))
					{
						$error = empty($crResponse['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crResponse['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'crdomain')
			{
				if ($services[$serviceId]['server'] != mb_strtolower($_REQUEST['domain']))
					$error = GetMessage('INTR_MAIL_FORM_ERROR');

				if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config') && $services[$serviceId]['encryption'] != 'N')
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);

				if ($error === false)
				{
					if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
						$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
				}

				if ($error === false)
				{
					$mbData = array(
						'NAME'     => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'LOGIN'    => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'PASSWORD' => $_REQUEST['password'],
						'CHARSET'  => self::$siteCharset,
						'OPTIONS'  => array(
							'flags'     => array(),
							'sync_from' => time(),
						),
					);

					$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberUser', array(
						'DOMAIN'   => $_REQUEST['domain'],
						'NAME'     => $_REQUEST['login'],
						'PASSWORD' => $_REQUEST['password']
					));
					if (!isset($crResponse['result']))
					{
						$error = empty($crResponse['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crResponse['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'domain')
			{
				if ($services[$serviceId]['server'] != mb_strtolower($_REQUEST['domain']))
					$error = GetMessage('INTR_MAIL_FORM_ERROR');

				if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config') && $services[$serviceId]['encryption'] != 'N')
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);

				if ($error === false)
				{
					if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
						$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
				}

				if ($error === false)
				{
					$mbData = array(
						'NAME'     => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'LOGIN'    => $_REQUEST['login'] . '@' . $_REQUEST['domain'],
						'PASSWORD' => $_REQUEST['password'],
						'CHARSET'  => self::$siteCharset,
						'OPTIONS'  => array(
							'flags'     => array(),
							'sync_from' => time(),
						),
					);

					$result = CMailDomain2::addUser(
						$services[$serviceId]['token'],
						$_REQUEST['domain'],
						$_REQUEST['login'],
						$_REQUEST['password'],
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
				}
			}
			else if ($services[$serviceId]['type'] == 'imap')
			{
				$mbData = array(
					'NAME'     => $_REQUEST['email'],
					'LINK'     => $services[$serviceId]['link'] ?: $_REQUEST['link'],
					'SERVER'   => $services[$serviceId]['server'] ?: $_REQUEST['server'],
					'PORT'     => $services[$serviceId]['port'] ?: $_REQUEST['port'],
					'LOGIN'    => $_REQUEST['login'],
					'PASSWORD' => $_REQUEST['password'],
					'USE_TLS'  => $services[$serviceId]['encryption'] ?: $_REQUEST['encryption'],
					'CHARSET'  => self::$siteCharset,
					'OPTIONS'  => array(
						'flags'     => array(),
						'sync_from' => time(),
					),
				);
				if (!in_array($mbData['USE_TLS'], array('Y', 'S')))
					$mbData['USE_TLS'] = 'N';

				if (!$services[$serviceId]['link'])
				{
					$regExp = '/^(https?:\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)(:[0-9]+)?(\/.*)?$/i';
					if (preg_match($regExp, trim($mbData['LINK']), $matches) && $matches[2] <> '')
					{
						$mbData['LINK'] = $matches[0];
						if ($matches[1] == '')
							$mbData['LINK'] = 'http://' . $mbData['LINK'];
					}
					else
					{
						$error = getMessage('INTR_MAIL_FORM_ERROR');
					}
				}

				if (!$services[$serviceId]['server'])
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

						if ($services[$serviceId]['name'] == 'gmail' && $services[$serviceId]['server'] == 'imap.gmail.com')
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

						if ($services[$serviceId]['name'] == 'outlook.com' && $services[$serviceId]['server'] == 'imap-mail.outlook.com')
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
					$unseen = \Bitrix\Mail\Helper::getImapUnseen($mbData, 'inbox', $error, $errors);

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
			}

			if ($error === false)
			{
				if (self::$crmAvailable && $_REQUEST['crm_connect'] == 'Y')
				{
					$mbData['OPTIONS']['flags'][] = 'crm_preconnect';

					if ($_REQUEST['crm_new_lead'] != 'Y')
					{
						$mbData['OPTIONS']['flags'][] = 'crm_deny_new_lead';
					}
					elseif (\CModule::includeModule('crm'))
					{
						$leadSourceList = \CCrmStatus::getStatusList('SOURCE');
						if (is_set($leadSourceList, $_REQUEST['lead_source']))
							$mbData['OPTIONS']['crm_lead_source'] = $_REQUEST['lead_source'];
					}

					if ($_REQUEST['crm_new_contact'] != 'Y')
						$mbData['OPTIONS']['flags'][] = 'crm_deny_new_contact';
				}

				if ($services[$serviceId]['type'] == 'imap' && $_REQUEST['sync_old'] == 'Y')
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
			}

			if ($error === false)
			{
				if ($mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID()))
					CMailbox::delete($mailbox['ID']);

				$mbData = array_merge(array(
					'LID'         => self::$siteId,
					'ACTIVE'      => 'Y',
					'SERVICE_ID'  => $serviceId,
					'SERVER_TYPE' => $services[$serviceId]['type'],
					'USER_ID'     => $USER->GetID()
				), $mbData);

				$mbId = CMailbox::add($mbData);

				if ($mbId > 0)
				{
					CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, self::$siteId);

					CUserOptions::SetOption('global', 'last_mail_check_'.self::$siteId, time());
					CUserOptions::SetOption('global', 'last_mail_check_success_'.self::$siteId, $unseen >= 0);
				}
				else
				{
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}

			if ($error === false)
			{
				if (self::$crmAvailable && $_REQUEST['crm_connect'] == 'Y')
				{
					$mbData['OPTIONS']['flags'] = array_diff($mbData['OPTIONS']['flags'], array('crm_preconnect'));
					$mbData['OPTIONS']['flags'][] = 'crm_connect';

					$imapData = $mbData;
					if (in_array($services[$serviceId]['type'], array('controller', 'crdomain')))
					{
						// @TODO: request controller
						$result = CMailDomain2::getImapData();

						$imapData['SERVER']  = $result['server'];
						$imapData['PORT']    = $result['port'];
						$imapData['USE_TLS'] = $result['secure'];
					}
					elseif ($services[$serviceId]['type'] == 'domain')
					{
						$result = CMailDomain2::getImapData();

						$imapData['SERVER']  = $result['server'];
						$imapData['PORT']    = $result['port'];
						$imapData['USE_TLS'] = $result['secure'];
					}

					$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error);
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
						$result = CMailbox::update($mbId, array('OPTIONS' => $mbData['OPTIONS']));

						if ($result > 0)
						{
							$filterFields = array(
								'MAILBOX_ID'         => $mbId,
								'NAME'               => sprintf('CRM IMAP %u', $USER->getID()),
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
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || $mailbox['SERVER_TYPE'] != 'imap')
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$serviceId = $mailbox['SERVICE_ID'];
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($services[$serviceId]['name'] == 'gmail' && $services[$serviceId]['server'] == 'imap.gmail.com')
			{
				if (preg_match('/^\x00oauth\x00google\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
				{
					$oauthClient = new \CSocServGoogleOAuth();
					if ($oauthClient->checkSettings())
						$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
			}

			if ($services[$serviceId]['name'] == 'outlook.com' && $services[$serviceId]['server'] == 'imap-mail.outlook.com')
			{
				if (preg_match('/^\x00oauth\x00liveid\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
				{
					$oauthClient = new \CSocServLiveIDOAuth();
					if ($oauthClient->checkSettings())
						$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
			}
		}

		if ($error === false)
		{
			$mbData = array(
				'LINK'     => $services[$serviceId]['link'] ?: $_REQUEST['link'],
				'SERVER'   => $services[$serviceId]['server'] ?: $_REQUEST['server'],
				'PORT'     => $services[$serviceId]['port'] ?: $_REQUEST['port'],
				'LOGIN'    => $mailbox['LOGIN'],
				'PASSWORD' => $_REQUEST['password'] ?: $mailbox['PASSWORD'],
				'USE_TLS'  => $services[$serviceId]['encryption'] ?: $_REQUEST['encryption'],
				'CHARSET'  => $mailbox['CHARSET'] ?: self::$siteCharset,
				'OPTIONS'  => is_array($mailbox['OPTIONS']) ? $mailbox['OPTIONS'] : array(),
			);
			if (!in_array($mbData['USE_TLS'], array('Y', 'S')))
				$mbData['USE_TLS'] = 'N';

			if ($error === false)
			{
				$unseen = \Bitrix\Mail\Helper::getImapUnseen($mbData, 'inbox', $error, $errors);

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
				$result = CMailbox::update($mailbox['ID'], $mbData);

				if ($result > 0)
				{
					CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, self::$siteId);

					CUserOptions::SetOption('global', 'last_mail_check_'.self::$siteId, time());
					CUserOptions::SetOption('global', 'last_mail_check_success_'.self::$siteId, $unseen >= 0);
				}
				else
				{
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeDeleteMailbox(&$error)
	{
		global $USER;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if ($mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID()))
			{
				if ($error === false)
				{
					CMailbox::delete($mailbox['ID']);

					CUserCounter::Clear($USER->GetID(), 'mail_unseen', self::$siteId);

					CUserOptions::DeleteOption('global', 'last_mail_check_'.self::$siteId);
					CUserOptions::DeleteOption('global', 'last_mail_check_success_'.self::$siteId);
				}
			}
		}
	}

	private static function executeCheck(&$error)
	{
		global $USER;

		$error  = false;
		$unseen = -1;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			switch ($mailbox['SERVER_TYPE'])
			{
				case 'imap':
					$unseen = CMailUtil::checkImapMailbox(
						$mailbox['SERVER'], $mailbox['PORT'], $mailbox['USE_TLS'],
						$mailbox['LOGIN'], $mailbox['PASSWORD'],
						$error, 30
					);
					break;
				case 'controller':
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMailbox', array(
						'DOMAIN' => $domain,
						'NAME'   => $login
					));
					if (isset($crCheckMailbox['result']))
					{
						$unseen = intval($crCheckMailbox['result']);
					}
					else
					{
						$error  = empty($crCheckMailbox['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckMailbox['error']);
					}
					break;
				case 'crdomain':
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberMailbox', array(
						'DOMAIN' => $domain,
						'NAME'   => $login
					));
					if (isset($crCheckMailbox['result']))
					{
						$unseen = intval($crCheckMailbox['result']);
					}
					else
					{
						$error  = empty($crCheckMailbox['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckMailbox['error']);
					}
					break;
				case 'domain':
					$serviceId = $mailbox['SERVICE_ID'];
					$services  = CIntranetMailSetupHelper::getMailServices();
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$result = CMailDomain2::getUnreadMessagesCount(
						$services[$serviceId]['token'],
						$domain, $login,
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
					else
						$unseen = intval($result);
					break;
			}

			CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, self::$siteId);

			if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', $mailbox['OPTIONS']['flags']))
			{
				$imapData = $mailbox;
				if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
				{
					// @TODO: request controller
					$result = CMailDomain2::getImapData();

					$imapData['SERVER']  = $result['server'];
					$imapData['PORT']    = $result['port'];
					$imapData['USE_TLS'] = $result['secure'];
				}
				elseif ($mailbox['SERVER_TYPE'] == 'domain')
				{
					$result = CMailDomain2::getImapData();

					$imapData['SERVER']  = $result['server'];
					$imapData['PORT']    = $result['port'];
					$imapData['USE_TLS'] = $result['secure'];
				}

				$unseen = \Bitrix\Mail\Helper::getImapUnseen($imapData, 'inbox', $error);
				if ($unseen !== false)
					$error = false;
				else
					$unseen = -1;
			}

			CUserOptions::SetOption('global', 'last_mail_check_'.self::$siteId, time());
			CUserOptions::SetOption('global', 'last_mail_check_success_'.self::$siteId, $unseen >= 0);
		}

		return array('unseen' => $unseen);
	}

	private static function executeDisableCrm(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = \CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$options = $mailbox['OPTIONS'];
			$options['flags'] = array_diff($options['flags'], array('crm_preconnect', 'crm_connect'));

			$res = \CMailbox::update($mailbox['ID'], array('OPTIONS' => $options));

			if (!$res)
				$error = getMessage('INTR_MAIL_SAVE_ERROR');
		}
	}

	private static function executeEnableCrm(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = \CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if (!self::$crmAvailable)
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$mbData = array(
				'OPTIONS' => is_array($mailbox['OPTIONS']) ? $mailbox['OPTIONS'] : array()
			);

			if (!$mailbox['CHARSET'])
			{
				$mailbox['CHARSET'] = self::$siteCharset;
				$mbData['CHARSET']  = self::$siteCharset;
			}

			if (empty($mailbox['PASSWORD']) && !empty($_REQUEST['password']))
			{
				$mailbox['PASSWORD'] = $_REQUEST['password'];
				$mbData['PASSWORD'] = $_REQUEST['password'];
			}

			if (!is_set($mbData['OPTIONS'], 'flags') || !is_array($mbData['OPTIONS']['flags']))
				$mbData['OPTIONS']['flags'] = array();

			if (!in_array('crm_preconnect', $mbData['OPTIONS']['flags']))
				$mbData['OPTIONS']['sync_from'] = time();

			$mbData['OPTIONS']['flags'] = array_diff(
				$mbData['OPTIONS']['flags'],
				array('crm_deny_new_lead', 'crm_deny_new_contact', 'crm_connect', 'crm_preconnect')
			);
			$mbData['OPTIONS']['flags'][] = 'crm_connect';

			if ($_REQUEST['subact'] != 'imapdirs')
			{
				if ($_REQUEST['crm_new_lead'] != 'Y')
				{
					$mbData['OPTIONS']['flags'][] = 'crm_deny_new_lead';
				}
				elseif (\CModule::includeModule('crm'))
				{
					$leadSourceList = \CCrmStatus::getStatusList('SOURCE');
					if (is_set($leadSourceList, $_REQUEST['lead_source']))
						$mbData['OPTIONS']['crm_lead_source'] = $_REQUEST['lead_source'];
				}

				if ($_REQUEST['crm_new_contact'] != 'Y')
					$mbData['OPTIONS']['flags'][] = 'crm_deny_new_contact';
			}

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

			if ($error === false)
			{
				$imapData = $mailbox;
				if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
				{
					// @TODO: request controller
					$result = CMailDomain2::getImapData();

					$imapData['SERVER']  = $result['server'];
					$imapData['PORT']    = $result['port'];
					$imapData['USE_TLS'] = $result['secure'];
				}
				elseif ($mailbox['SERVER_TYPE'] == 'domain')
				{
					$result = CMailDomain2::getImapData();

					$imapData['SERVER']  = $result['server'];
					$imapData['PORT']    = $result['port'];
					$imapData['USE_TLS'] = $result['secure'];
				}

				$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error, $errors);
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
						$income = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
							$_REQUEST['imap_dirs']['income'], 'UTF-8', self::$siteCharset
						);
						$mbData['OPTIONS']['imap']['income'] = array_intersect($income, $availableDirs);

						$outcome = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
							$_REQUEST['imap_dirs']['outcome'], 'UTF-8', self::$siteCharset
						);
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
				$result = CMailbox::update($mailbox['ID'], $mbData);

				if ($result > 0)
				{
					if (in_array('crm_connect', $mbData['OPTIONS']['flags']))
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
							'NAME'               => sprintf('CRM IMAP %u', $USER->getID()),
							'ACTION_TYPE'        => 'crm_imap',
							'WHEN_MAIL_RECEIVED' => 'Y',
							'WHEN_MANUALLY_RUN'  => 'Y',
						);

						\CMailFilter::add($filterFields);
					}
				}
				else
				{
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeConfigCrm(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = \CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$mbData = array(
				'OPTIONS' => $mailbox['OPTIONS'],
			);
			$mbData['OPTIONS']['flags'] = array_diff(
				$mbData['OPTIONS']['flags'],
				array('crm_deny_new_lead', 'crm_deny_new_contact', 'crm_connect', 'crm_preconnect')
			);
			$mbData['OPTIONS']['flags'][] = 'crm_connect';

			if ($_REQUEST['crm_new_lead'] != 'Y')
			{
				$mbData['OPTIONS']['flags'][] = 'crm_deny_new_lead';
				unset($mbData['OPTIONS']['crm_lead_source']);
			}
			elseif (\CModule::includeModule('crm'))
			{
				$leadSourceList = \CCrmStatus::getStatusList('SOURCE');
				if (is_set($leadSourceList, $_REQUEST['lead_source']))
					$mbData['OPTIONS']['crm_lead_source'] = $_REQUEST['lead_source'];
			}

			if ($_REQUEST['crm_new_contact'] != 'Y')
				$mbData['OPTIONS']['flags'][] = 'crm_deny_new_contact';

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

			$imapData = array_merge($mailbox, $mbData);
			if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
			{
				// @TODO: request controller
				$result = CMailDomain2::getImapData();

				$imapData['SERVER']  = $result['server'];
				$imapData['PORT']    = $result['port'];
				$imapData['USE_TLS'] = $result['secure'];
			}
			elseif ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$result = CMailDomain2::getImapData();

				$imapData['SERVER']  = $result['server'];
				$imapData['PORT']    = $result['port'];
				$imapData['USE_TLS'] = $result['secure'];
			}

			$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error, $errors);
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
					$income = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
						$_REQUEST['imap_dirs']['income'], 'UTF-8', self::$siteCharset
					);
					$mbData['OPTIONS']['imap']['income'] = array_intersect($income, $availableDirs);

					$outcome = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
						$_REQUEST['imap_dirs']['outcome'], 'UTF-8', self::$siteCharset
					);
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

	private static function executeListImapDirs(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$imapData = $mailbox;
			if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
			{
				// @TODO: request controller
				$result = CMailDomain2::getImapData();

				$imapData['SERVER']  = $result['server'];
				$imapData['PORT']    = $result['port'];
				$imapData['USE_TLS'] = $result['secure'];
			}
			elseif ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$result = CMailDomain2::getImapData();

				$imapData['SERVER']  = $result['server'];
				$imapData['PORT']    = $result['port'];
				$imapData['USE_TLS'] = $result['secure'];
			}

			$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error, $errors);
			if ($imapDirs !== false)
			{
				$error = false;

				if (is_set($_REQUEST, 'imap_dirs'))
				{
					$income = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
						$_REQUEST['imap_dirs']['income'], 'UTF-8', self::$siteCharset
					);
					$outcome = (array) \Bitrix\Main\Text\Encoding::convertEncoding(
						$_REQUEST['imap_dirs']['outcome'], 'UTF-8', self::$siteCharset
					);
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
			else
			{
				if ($errors instanceof \Bitrix\Main\ErrorCollection)
					$error = $errors;
			}
		}
	}

	private static function executeChangePassword(&$error)
	{
		global $USER;

		$error = false;

		$password  = $_REQUEST['password'];
		$password2 = $_REQUEST['password2'];

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($mailbox['ID'] != $_REQUEST['ID'])
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if (in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')) && $password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			$serviceId = $mailbox['SERVICE_ID'];
			$services = CIntranetMailSetupHelper::getMailServices();

			if ($services[$serviceId]['name'] == 'gmail' && $services[$serviceId]['server'] == 'imap.gmail.com')
			{
				if (preg_match('/^\x00oauth\x00google\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
				{
					$oauthClient = new \CSocServGoogleOAuth();
					if ($oauthClient->checkSettings())
						$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
			}

			if ($services[$serviceId]['name'] == 'outlook.com' && $services[$serviceId]['server'] == 'imap-mail.outlook.com')
			{
				if (preg_match('/^\x00oauth\x00liveid\x00(\d+)$/', $mailbox['PASSWORD']) && \CModule::includeModule('socialservices'))
				{
					$oauthClient = new \CSocServLiveIDOAuth();
					if ($oauthClient->checkSettings())
						$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
			}
		}

		if ($error === false)
		{
			if ($mailbox['SERVER_TYPE'] == 'crdomain')
			{
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangeMemberPassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$domainService = CIntranetMailSetupHelper::getDomainService($mailbox['SERVICE_ID']);

				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$result = CMailDomain2::changePassword(
					$domainService['token'],
					$domain, $login, $password,
					$error
				);

				if (is_null($result))
					$error = CMail::getErrorMessage($error);
			}
			else if ($mailbox['SERVER_TYPE'] == 'controller')
			{
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangePassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'imap')
			{
				$mailbox['PASSWORD'] = $password;
				$unseen = \Bitrix\Mail\Helper::getImapUnseen($mailbox, 'inbox', $error, $errors);

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
				$res = CMailbox::update($mailbox['ID'], array('PASSWORD' => $password));

				if (!$res)
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
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

		$APPLICATION->RestartBuffer();

		header('Content-Type: application/x-javascript; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($data);
		die;
	}

}

CIntranetMailConfigHomeAjax::execute();
