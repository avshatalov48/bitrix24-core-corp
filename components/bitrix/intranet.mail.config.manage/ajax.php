<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php');

\Bitrix\Main\Localization\Loc::loadMessages(__DIR__.'/class.php');

class CIntranetMailConfigManageAjax
{

	static $siteId;

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

			self::$siteId = $site['LID'];
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
			$result = (array) self::handleManageAction($act, $error);
		}

		if ($error instanceof \Bitrix\Main\ErrorCollection)
			list($error, $details) = self::prepareError($error);

		self::returnJson(array_merge(array(
			'result'    => $error === false ? 'ok' : 'error',
			'error'     => $error,
			'error_ext' => !empty($details) ? $details : false,
		), $result));
	}

	private static function handleManageAction($act, &$error)
	{
		global $USER;

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
			$error = GetMessage('ACCESS_DENIED');

		if ($error === false)
		{
			switch ($act)
			{
				case 'create':
					return self::executeManageCreateMailbox($error);
					break;
				case 'password':
					return self::executeManageChangePassword($error);
					break;
				case 'release':
					return self::executeManageReleaseMailbox($error);
					break;
				case 'delete':
					return self::executeManageDeleteMailbox($error);
					break;
				case 'settings':
					return self::executeManageSettings($error);
					break;
				default:
					$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			}
		}
	}

	private static function executeManageCreateMailbox(&$error)
	{
		$domainUsers = array('vacant' => array(), 'occupied' => array());
		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (!isset($_REQUEST['create']))
			{
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
			else
			{
				$exists = $_REQUEST['create'] == 0;
				$userId = (int) $_REQUEST['USER_ID'];

				if (!empty($_REQUEST['MAILBOX_OWNER']['U']))
				{
					$mailboxOwners = (array) $_REQUEST['MAILBOX_OWNER']['U'];
					if (preg_match('/^U(\d+)$/i', trim(reset($mailboxOwners)), $matches))
						$userId = (int) $matches[1];
				}

				if ($exists)
				{
					if (!empty($_REQUEST['MAILBOX']))
					{
						$serviceId = 0;

						if (preg_match('/^(?<sid>\d+):(?<login>[^@]+)@(?<domain>[^@]+)$/i', trim($_REQUEST['MAILBOX']), $matches))
						{
							$serviceId = $matches['sid'];
							$domain = mb_strtolower($matches['domain']);
							$login = mb_strtolower($matches['login']);
						}
					}
					else
					{
						$serviceId = $_REQUEST['sservice'];
						$domain = mb_strtolower($_REQUEST['sdomain']);
						$login = mb_strtolower($_REQUEST['suser']);
					}
				}
				else
				{
					$serviceId = $_REQUEST['cservice'];
					$domain = mb_strtolower($_REQUEST['cdomain']);
					$login = mb_strtolower($_REQUEST['cuser']);
					$password  = $_REQUEST['password'];
					$password2 = $_REQUEST['password2'];
				}
			}
		}

		if ($error === false)
		{
			if ($exists && !$userId)
				$error = getMessage('INTR_MAIL_INP_USER_EMPTY');

			if ($error === false && $userId)
			{
				$dbUser = CUser::getList(
					'ID', 'ASC',
					array('ID_EQUAL_EXACT' => $userId),
					array('FIELDS' => 'ID')
				);
				if (!$dbUser->fetch())
					$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
		}

		if ($error === false && $userId)
		{
			$userMailbox = CIntranetMailSetupHelper::getUserMailbox($userId);
			if (!empty($userMailbox) && empty($_REQUEST['confirm']))
				$error = getMessage('INTR_MAIL_USER_MAILBOX_REPLACE_CONFIRM').'<input type="hidden" name="confirm" value="1">';
		}

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();
			if (empty($services[$serviceId]) || !in_array($services[$serviceId]['type'], array('controller', 'domain', 'crdomain')))
			{
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
			else
			{
				$service = $services[$serviceId];

				if ($service['type'] == 'controller')
				{
					$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
					$arDomains = empty($crDomains['result']) ? array() : array_map('mb_strtolower', $crDomains['result']);
					if (!is_array($arDomains) || !in_array($domain, $arDomains))
						$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else if ($service['type'] == 'crdomain')
				{
					$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array());
					$arDomains = empty($crDomains['result']) ? array() : array_map('mb_strtolower', $crDomains['result']);
					if (!is_array($arDomains) || !in_array($domain, $arDomains))
						$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else if ($service['type'] == 'domain')
				{
					if (mb_strtolower($service['server']) != $domain)
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
				}
			}
		}

		if ($error === false)
		{
			if (!$exists && $password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			if ($service['type'] == 'controller')
			{
				if (mb_strtolower(trim($domain)) == 'bitrix24.com')
				{
					$error = getMessage('INTR_MAIL_FORM_ERROR');
				}
				else
				{
					$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckName', array(
						'DOMAIN' => $domain,
						'NAME'   => $login
					));
					if (isset($crCheckName['result']))
					{
						$isExistsNow = (boolean) $crCheckName['result'];
					}
					else
					{
						$error = empty($crCheckName['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckName['error']);
					}
				}
			}
			else if ($service['type'] == 'crdomain')
			{
				$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberName', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crCheckName['result']))
				{
					$isExistsNow = (boolean) $crCheckName['result'];
				}
				else
				{
					$error = empty($crCheckName['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crCheckName['error']);
				}
			}
			else if ($service['type'] == 'domain')
			{
				$isExistsNow = CMailDomain2::isUserExists($service['token'], $domain, $login, $error);
				if (is_null($isExistsNow))
					$error = CMail::getErrorMessage($error);
			}

			if ($error === false)
			{
				if ($exists)
				{
					if ($isExistsNow == false)
						$error = CMail::getErrorMessage(CMail::ERR_API_USER_NOTFOUND);

					if ($error === false)
					{
						if ($service['type'] == 'controller')
						{
							$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMailbox', array(
								'DOMAIN' => $domain,
								'NAME'   => $login
							));
							if (!isset($crCheckMailbox['result']))
							{
								$error  = empty($crCheckMailbox['error'])
									? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
									: CMail::getErrorMessage($crCheckMailbox['error']);
							}
						}
					}

					if ($error === false)
					{
						$mailbox = \Bitrix\Mail\MailboxTable::getList(array(
							'select' => array('ID'),
							'filter' => array(
								'=ACTIVE'   => 'Y',
								'>USER_ID'  => 0,
								'!=USER_ID' => $userId,
								'LOGIN'     => $login . '@' . $domain,
							),
						))->fetch();

						if (!empty($mailbox))
							$error = GetMessage('INTR_MAIL_MAILBOX_OCCUPIED');
					}
				}
				else
				{
					if ($isExistsNow == true)
						$error = CMail::getErrorMessage(CMail::ERR_API_NAME_OCCUPIED);

					if ($error === false)
					{
						if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $login))
							$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
					}

					if ($error === false)
					{
						if ($service['type'] == 'controller')
						{
							$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddUser', array(
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
						else if ($service['type'] == 'crdomain')
						{
							$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberUser', array(
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
						else if ($service['type'] == 'domain')
						{
							$result = CMailDomain2::addUser(
								$service['token'],
								$domain, $login, $password,
								$error
							);

							if (is_null($result))
								$error = CMail::getErrorMessage($error);
						}

						if ($error === false)
						{
							if (empty($domainUsers['vacant'][$service['id']]))
								$domainUsers['vacant'][$service['id']] = array();
							if (empty($domainUsers['vacant'][$service['id']][$domain]))
								$domainUsers['vacant'][$service['id']][$domain] = array();
							$domainUsers['vacant'][$service['id']][$domain][] = $login;
						}
					}
				}

				if ($error === false && $userId)
				{
					if (!empty($userMailbox))
					{
						$res = CMailbox::delete($userMailbox['ID']);
						if (in_array($userMailbox['SERVER_TYPE'], array('domain', 'controller', 'crdomain')) && $res)
						{
							list($login_tmp, $domain_tmp) = explode('@', mb_strtolower($userMailbox['LOGIN']), 2);
							if (empty($domainUsers['vacant'][$userMailbox['SERVICE_ID']]))
								$domainUsers['vacant'][$userMailbox['SERVICE_ID']] = array();
							if (empty($domainUsers['vacant'][$userMailbox['SERVICE_ID']][$domain_tmp]))
								$domainUsers['vacant'][$userMailbox['SERVICE_ID']][$domain_tmp] = array();
							$domainUsers['vacant'][$userMailbox['SERVICE_ID']][$domain_tmp][] = $login_tmp;
						}
					}

					$arFields = array(
						'LID'         => self::$siteId,
						'ACTIVE'      => 'Y',
						'SERVICE_ID'  => $serviceId,
						'NAME'        => $login . '@' . $domain,
						'LOGIN'       => $login . '@' . $domain,
						'SERVER_TYPE' => $service['type'],
						'USER_ID'     => $userId
					);

					if ($password)
						$arFields['PASSWORD'] = $password;

					$res = CMailbox::add($arFields);
					if (!$res)
					{
						$error = GetMessage('INTR_MAIL_SAVE_ERROR');
					}
					else
					{
						if (!empty($domainUsers['vacant'][$serviceId][$domain]))
						{
							if ($key = array_search($login, $domainUsers['vacant'][$serviceId][$domain]))
								array_splice($domainUsers['vacant'][$serviceId][$domain], $key, 1);
						}
						if (empty($domainUsers['occupied'][$serviceId]))
							$domainUsers['occupied'][$serviceId] = array();
						if (empty($domainUsers['occupied'][$serviceId][$domain]))
							$domainUsers['occupied'][$serviceId][$domain] = array();
						$domainUsers['occupied'][$serviceId][$domain][] = $login;
					}
				}
			}
		}

		return array(
			'users' => $domainUsers,
		);
	}

	private static function executeManageChangePassword(&$error)
	{
		$error = false;

		$mailbox   = $_REQUEST['MAILBOX'];
		$password  = $_REQUEST['password'];
		$password2 = $_REQUEST['password2'];

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (preg_match('/^\d+$/i', $mailbox))
			{
				$mailbox = \Bitrix\Mail\MailboxTable::getList(array(
					'filter' => array(
						'=ID'          => $mailbox,
						'>USER_ID'     => 0,
						'@SERVER_TYPE' => array('controller', 'domain', 'crdomain'),
					)
				))->fetch();
				if (!empty($mailbox))
				{
					$mailboxId = $mailbox['ID'];
					list($login, $domain) = explode('@', mb_strtolower($mailbox['LOGIN']), 2);
					$service = \CIntranetMailSetupHelper::getDomainService($mailbox['SERVICE_ID']);
				}
			}
			elseif (preg_match('/^(?<sid>\d+):(?<login>[^@]+)@(?<domain>[^@]+)$/i', $mailbox, $matches))
			{
				$login  = $matches['login'];
				$domain = $matches['domain'];

				$service = \CIntranetMailSetupHelper::getDomainService($matches['sid']);
			}

			if (empty($service) || !in_array($service['type'], array('controller', 'domain', 'crdomain')))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			if ($service['type'] == 'domain')
			{
				$result = \CMailDomain2::changePassword($service['token'], $domain, $login, $password, $error);
				if (is_null($result))
					$error = CMail::getErrorMessage($error);
			}
			else if ($service['type'] == 'crdomain')
			{
				$crResponse = \CControllerClient::executeEvent('OnMailControllerChangeMemberPassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password,
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? getMessage('INTR_MAIL_CONTROLLER_INVALID')
						: \CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($service['type'] == 'controller')
			{
				$crResponse = \CControllerClient::executeEvent('OnMailControllerChangePassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password,
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? getMessage('INTR_MAIL_CONTROLLER_INVALID')
						: \CMail::getErrorMessage($crResponse['error']);
				}
			}

			if ($error === false)
			{
				if (!empty($mailboxId))
				{
					$res = \CMailbox::update($mailboxId, array('PASSWORD' => $password));
					if (!$res)
						$error = getMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeManageReleaseMailbox(&$error)
	{
		$domainUsers = array('vacant' => array());
		$error = false;

		$mailboxId = $_REQUEST['MAILBOX_ID'];

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = \Bitrix\Mail\MailboxTable::getList(array(
				'filter' => array(
					'=ID'      => $mailboxId,
					'>USER_ID' => 0,
				)
			))->fetch();
			if (empty($mailbox))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$res = \CMailbox::delete($mailbox['ID']);
			if (in_array($mailbox['SERVER_TYPE'], array('domain', 'controller', 'crdomain')) && $res)
			{
				list($login_tmp, $domain_tmp) = explode('@', mb_strtolower($mailbox['LOGIN']), 2);
				$domainUsers['vacant'][$mailbox['SERVICE_ID']] = array(
					$domain_tmp => array($login_tmp),
				);
			}
		}

		return array(
			'users' => $domainUsers,
		);
	}

	private static function executeManageDeleteMailbox(&$error)
	{
		$domainUsers = array('occupied' => array());
		$error = false;

		$mailbox = $_REQUEST['MAILBOX'];

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (empty($_REQUEST['confirm']))
				$error = getMessage('INTR_MAIL_CSRF');
		}

		if ($error === false)
		{
			if (preg_match('/^(?<sid>\d+):(?<login>[^@]+)@(?<domain>[^@]+)$/i', $mailbox, $matches))
			{
				$serviceId = $matches['sid'];
				$login     = $matches['login'];
				$domain    = $matches['domain'];

				$service = \CIntranetMailSetupHelper::getDomainService($serviceId);
			}

			if (empty($service) || !in_array($service['type'], array('controller', 'domain', 'crdomain')))
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($service['type'] == 'domain')
			{
				\CMailDomain2::deleteUser($service['token'], $domain, $login, $error);
			}
			else if ($service['type'] == 'crdomain')
			{
				$crResponse = \CControllerClient::executeEvent('OnMailControllerDeleteMemberUser', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? getMessage('INTR_MAIL_CONTROLLER_INVALID')
						: \CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($service['type'] == 'controller')
			{
				$crResponse = \CControllerClient::executeEvent('OnMailControllerDeleteUser', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? getMessage('INTR_MAIL_CONTROLLER_INVALID')
						: \CMail::getErrorMessage($crResponse['error']);
				}
			}
		}

		if ($error === false)
		{
			$domainUsers['occupied'][$serviceId] = array(
				$domain => array($login),
			);
		}

		return array(
			'users' => $domainUsers,
		);
	}

	private static function executeManageSettings(&$error)
	{
		$error = false;

		if (!check_bitrix_sessid())
			$error = getMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			COption::setOptionString('intranet', 'allow_external_mail_crm', $_REQUEST['allow_crm'] == 'Y' ? 'Y' : 'N', false, self::$siteId);

			if (!empty($_REQUEST['blacklist']))
			{
				$blacklist = preg_split('/[\r\n,;]+/', $_REQUEST['blacklist']);
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

			\Bitrix\Mail\BlacklistTable::replace(
				self::$siteId, 0,
				!empty($blacklist) ? $blacklist : array()
			);
		}
	}

	private static function prepareError(\Bitrix\Main\ErrorCollection $errors)
	{
		$messages = array();
		$details  = array();

		foreach ($errors as $item)
			${$item->getCode() < 0 ? 'details' : 'messages'}[] = $item;

		$reduce = function($error)
		{
			return $error->getMessage();
		};

		return array(
			join(': ', array_map($reduce, $messages)),
			join(': ', array_map($reduce, $details)),
		);
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

CIntranetMailConfigManageAjax::execute();
