<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php');

\Bitrix\Main\Localization\Loc::loadMessages(__DIR__.'/class.php');

class CIntranetMailConfigDomainAjax
{

	static $siteId;

	static $crmAvailable   = false;
	static $limitedLicense = false;

	public static function execute()
	{
		global $USER;

		\CBitrixComponent::includeComponentClass('bitrix:intranet.mail.config');

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
			$result = (array) self::handleDomainAction($act, $error);
		}

		if ($error instanceof \Bitrix\Main\ErrorCollection)
			list($error, $details) = self::prepareError($error);

		self::returnJson(array_merge(array(
			'result'    => $error === false ? 'ok' : 'error',
			'error'     => $error,
			'error_ext' => !empty($details) ? $details : false,
		), $result));
	}

	private static function handleDomainAction($act, &$error)
	{
		global $USER;

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
			$error = GetMessage('ACCESS_DENIED');

		if ($error === false)
		{
			if (\CIntranetMailConfigComponent::isFeatureAvailable('domain_service') < 1)
				$error = getMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			switch ($act)
			{
				case 'whois':
					return self::executeDomainWhois($error);
					break;
				case 'suggest':
					return self::executeDomainSuggest($error);
					break;
				case 'initget':
					return self::executeDomainInitGet($error);
					break;
				case 'get':
					return self::executeDomainGet($error);
					break;
				case 'create':
					return self::executeDomainCreate($error);
					break;
				case 'check':
					return self::executeDomainCheck($error);
					break;
				default:
					$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			}
		}
	}

	private static function executeDomainWhois(&$error)
	{
		$error = false;
		$occupied = -1;

		if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]\.ru$/i', $_REQUEST['domain']) || preg_match('/^..--/i', $_REQUEST['domain']))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerWhoisDomain', array(
				'DOMAIN' => $_REQUEST['domain']
			));
			if (isset($crResponse['result']))
			{
				$occupied = (boolean) $crResponse['result'];
			}
			else
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
		}

		return array('occupied' => $occupied);
	}

	private static function executeDomainSuggest(&$error)
	{
		$error = false;
		$suggestions = array();

		if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]\.ru$/i', $_REQUEST['domain']) || preg_match('/^..--/i', $_REQUEST['domain']))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$words = explode('-', preg_replace('/\.ru$/i', '', $_REQUEST['domain']));
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerSuggestDomain', array(
				'WORD1' => array_pop($words),
				'WORD2' => array_pop($words),
				'TLDS'  => array('ru')
			));
			if (isset($crResponse['result']) && is_array($crResponse['result']))
			{
				foreach ($crResponse['result'] as $entry)
					$suggestions[] = $entry;
			}
			else
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
		}

		return array('suggestions' => $suggestions);
	}

	private static function executeDomainInitGet(&$error)
	{
		global $USER;

		CAgent::removeAgent('CIntranetUtils::notifyMailDomain("noreg", "'.self::$siteId.'", '.$USER->getId().');', 'intranet');
		CAgent::removeAgent('CIntranetUtils::notifyMailDomain("noreg", "'.self::$siteId.'", '.$USER->getId().', 1);', 'intranet');
		CAgent::addAgent('CIntranetUtils::notifyMailDomain("noreg", "'.self::$siteId.'", '.$USER->getId().');', 'intranet', 'N', 3600*24);

		return array();
	}

	private static function executeDomainGet(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (CIntranetMailSetupHelper::getDomainService())
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			if (!empty($_REQUEST['sdomain']))
				$domain = strtolower($_REQUEST['sdomain']);
			else if (!empty($_REQUEST['domain']))
				$domain = strtolower($_REQUEST['domain']);
			else
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array('REGISTERED' => true));
			if (!isset($crDomains['result']) || !is_array($crDomains['result']))
			{
				$error = empty($crDomains['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crDomains['error']);
			}
			else
			{
				if (empty($crDomains['result']))
				{
					if (empty($_REQUEST['eula']) || $_REQUEST['eula'] != 'Y')
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
				}
				else if (strtolower(reset($crDomains['result'])) != $domain)
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
			}
		}
		
		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerRegDomain', array(
				'DOMAIN' => $domain,
				'IP'     => $_SERVER['REMOTE_ADDR']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}

			if ($error === false)
			{
				$result = \Bitrix\Mail\MailServicesTable::add(array(
					'SITE_ID'      => self::$siteId,
					'ACTIVE'       => 'Y',
					'SERVICE_TYPE' => 'crdomain',
					'NAME'         => $domain,
					'SERVER'       => $domain,
					'ENCRYPTION'   => $_REQUEST['public'] == 'Y' ? 'N' : 'Y',
					'FLAGS'        => CMail::F_DOMAIN_REG
				));

				if ($result->isSuccess())
					CAgent::addAgent('CIntranetUtils::checkMailDomain('.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 600);

				if (!$result->isSuccess())
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}

		return array();
	}

	private static function executeDomainCreate(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (CIntranetMailSetupHelper::getDomainService())
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberDomain', array(
				'DOMAIN' => $_REQUEST['domain']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
			else
			{
				$result = $crResponse['result'];

				if (!is_array($result))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');

				if ($error === false)
				{
					$domainStage = $result['stage'];
					if ($result['stage'] == 'owner-check')
					{
						$domainSecrets = array(
							'name'    => $result['secrets']['name'],
							'content' => $result['secrets']['content']
						);
					}
				}
			}

			if ($error === false)
			{
				$result = \Bitrix\Mail\MailServicesTable::add(array(
					'SITE_ID'      => self::$siteId,
					'ACTIVE'       => 'Y',
					'SERVICE_TYPE' => 'crdomain',
					'NAME'         => strtolower($_REQUEST['domain']),
					'SERVER'       => strtolower($_REQUEST['domain']),
					'ENCRYPTION'   => $_REQUEST['public'] == 'Y' ? 'N' : 'Y'
				));

				if ($result->isSuccess())
				{
					CAgent::addAgent('CIntranetUtils::checkMailDomain('.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 600);
					CAgent::addAgent('CIntranetUtils::notifyMailDomain("nocomplete", '.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 3600*24*3);
				}

				if (!$result->isSuccess())
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}

		return array(
			'stage'   => isset($domainStage) ? $domainStage : '',
			'secrets' => isset($domainSecrets) ? $domainSecrets : ''
		);
	}

	private static function executeDomainCheck(&$error)
	{
		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$domainService = CIntranetMailSetupHelper::getDomainService();
			if (empty($domainService) || !in_array($domainService['type'], array('crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberDomain', array(
				'DOMAIN' => $domainService['server']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
			else
			{
				$result = $crResponse['result'];

				if (!is_array($result))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');

				if ($error === false)
				{
					$domainLastCheck = $result['last_check'];
					$domainNextCheck = strtotime($result['next_check']) > time() ? $result['next_check'] : null;
					$domainStage = $result['stage'];
					$domainSecrets = array(
						'name'    => $result['secrets']['name'],
						'content' => $result['secrets']['content']
					);
				}
			}
		}

		return array(
			'last_check' => isset($domainLastCheck) ? FormatDate(
				array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
				strtotime($domainLastCheck)
			) : '',
			'next_check' => isset($domainNextCheck) ? FormatDate(
				array('s' => 'sdiff', 'i' => 'idiff', 'H' => 'Hdiff', 'd' => 'ddiff', 'm' => 'mdiff', 'Y' => 'Ydiff'),
				time() - (strtotime($domainNextCheck) - time())
			) : '',
			'stage'   => isset($domainStage) ? $domainStage : '',
			'secrets' => isset($domainSecrets) ? $domainSecrets : ''
		);
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

		$APPLICATION->RestartBuffer();

		header('Content-Type: application/x-javascript; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($data);
		die;
	}

}

CIntranetMailConfigDomainAjax::execute();
