<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/intranet.mail.setup/helper.php';

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class CIntranetMailConfigHomeComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $USER, $APPLICATION;

		\CBitrixComponent::includeComponentClass('bitrix:intranet.mail.config');

		$APPLICATION->setTitle(getMessage(isModuleInstalled('bitrix24') ? 'INTR_MAIL_B24_PAGE_TITLE' : 'NTR_MAIL_PAGE_TITLE'));

		if (!CModule::includeModule('mail'))
		{
			showError(getMessage('MAIL_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!is_object($USER) || !$USER->isAuthorized())
		{
			$APPLICATION->authForm('');
			return;
		}

		if (!CIntranetUtils::isExternalMailAvailable())
		{
			showError(getMessage('INTR_MAIL_UNAVAILABLE'));
			return;
		}

		$this->arParams['SERVICES'] = CIntranetMailSetupHelper::getMailServices();
		$this->arParams['MAILBOX']  = CIntranetMailSetupHelper::getUserMailbox($USER->getId());

		foreach ($this->arParams['SERVICES'] as $i => $item)
			$this->arParams['SERVICES'][$i]['server'] = mb_strtolower($item['server']);

		$page = '';
		if (array_key_exists('config', $_REQUEST))
			$page = 'home';
		if (array_key_exists('success', $_REQUEST))
			$page = 'success';

		switch ($page)
		{
			case 'success':
				return $this->executeSuccessPage();
				break;
			default:
				return $this->executeHomePage($page);
		}
	}

	private function redirect()
	{
		global $APPLICATION, $USER;

		$mailbox = $this->arParams['MAILBOX'];
		$service = $this->arParams['SERVICES'][$mailbox['SERVICE_ID']];

		if ('imap' == $service['type'])
		{
			localRedirect('/mail/', true);
			return;
		}

		$error = false;
		switch ($service['type'])
		{
			case 'imap':
				if (!empty($service['link']))
					localRedirect($service['link'], true);
				else if (!empty($mailbox['LINK']))
					localRedirect($mailbox['LINK'], true);
				else
					localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);
				break;
			case 'controller':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crRedirect = CControllerClient::ExecuteEvent('OnMailControllerRedirect', array(
					'LOCALE' => LANGUAGE_ID,
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crRedirect['result']))
				{
					localRedirect($crRedirect['result'], true);
				}
				else
				{
					$error = empty($crRedirect['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crRedirect['error']);
				}
				break;
			case 'domain':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$result = CMailDomain2::getRedirectUrl(
					LANGUAGE_ID,
					$service['token'],
					$domain, $login,
					$errorUrl = '', $error
				);

				if (is_null($result))
					$error = CMail::getErrorMessage($error);
				else
					localRedirect($result, true);
				break;
			case 'crdomain':
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crRedirect = CControllerClient::ExecuteEvent('OnMailControllerMemberRedirect', array(
					'LOCALE' => LANGUAGE_ID,
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crRedirect['result']))
				{
					localRedirect($crRedirect['result'], true);
				}
				else
				{
					$error = empty($crRedirect['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crRedirect['error']);
				}
				break;
			default:
				localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);
		}

		if ($error !== false)
		{
			CUserCounter::set($USER->getId(), 'mail_unseen', -1, SITE_ID);

			CUserOptions::setOption('global', 'last_mail_check_'.SITE_ID, time());
			CUserOptions::setOption('global', 'last_mail_check_success_'.SITE_ID, false);

			localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);
		}
	}

	private function executeSuccessPage()
	{
		global $USER, $APPLICATION;

		if (empty($this->arParams['MAILBOX']))
			localRedirect($this->arParams['PATH_TO_MAIL_CONFIG']);

		$this->includeComponentTemplate('success');
	}

	private function executeHomePage($page)
	{
		global $USER, $APPLICATION;

		if (!empty($this->arParams['MAILBOX']) && empty($page))
			return $this->redirect();

		$this->arParams['CRM_AVAILABLE'] = false;
		if (\CModule::includeModule('crm') && \CCrmPerms::isAccessEnabled())
		{
			$this->arParams['CRM_AVAILABLE'] = $USER->isAdmin() || $USER->canDoOperation('bitrix24_config')
				|| COption::getOptionString('intranet', 'allow_external_mail_crm', 'Y', SITE_ID) == 'Y';
		}

		if (\CModule::includeModule('crm'))
		{
			$this->arParams['LEAD_SOURCE_LIST'] = \CCrmStatus::getStatusList('SOURCE');
			reset($this->arParams['LEAD_SOURCE_LIST']);
			$this->arParams['DEFAULT_LEAD_SOURCE'] = key($this->arParams['LEAD_SOURCE_LIST']);
			if (is_set($this->arParams['LEAD_SOURCE_LIST'], 'EMAIL'))
				$this->arParams['DEFAULT_LEAD_SOURCE'] = 'EMAIL';
			elseif (is_set($this->arParams['LEAD_SOURCE_LIST'], 'OTHER'))
				$this->arParams['DEFAULT_LEAD_SOURCE'] = 'OTHER';
		}

		if (!empty($this->arParams['MAILBOX']))
		{
			$mbData = $this->arParams['MAILBOX'];

			$this->arParams['MAILBOX_LEAD_SOURCE'] = $this->arParams['DEFAULT_LEAD_SOURCE'];

			$this->arParams['BLACKLIST'] = array();
			$res = \Bitrix\Mail\BlacklistTable::getList(array(
				'select' => array('ITEM_TYPE', 'ITEM_VALUE'), // ITEM_TYPE for fetch_data_modification
				'filter' => array('MAILBOX_ID' => $mbData['ID']),
			));
			while ($item = $res->fetch())
				$this->arParams['BLACKLIST'][] = $item['ITEM_VALUE'];

			if (!empty($mbData['OPTIONS']['flags']) && is_array($mbData['OPTIONS']['flags']))
			{
				if ($this->arParams['CRM_AVAILABLE'] && in_array('crm_preconnect', $mbData['OPTIONS']['flags']))
				{
					if (in_array($mbData['SERVER_TYPE'], array('imap', 'controller', 'crdomain', 'domain')))
					{
						$this->arParams['CRM_PRECONNECT'] = true;

						$imapData = $mbData;
						if (in_array($mbData['SERVER_TYPE'], array('controller', 'crdomain')))
						{
							// @TODO: request controller
							$result = CMailDomain2::getImapData();

							$imapData['SERVER']  = $result['server'];
							$imapData['PORT']    = $result['port'];
							$imapData['USE_TLS'] = $result['secure'];
						}
						elseif ($mbData['SERVER_TYPE'] == 'domain')
						{
							$result = CMailDomain2::getImapData();

							$imapData['SERVER']  = $result['server'];
							$imapData['PORT']    = $result['port'];
							$imapData['USE_TLS'] = $result['secure'];
						}

						$imapDirs = \Bitrix\Mail\Helper::listImapDirs($imapData, $error, $errors);
						if ($imapDirs !== false)
						{
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
								$this->arParams['IMAP_DIRS'] = $imapDirs;
							}
							else
							{
								$mbData['OPTIONS']['flags'] = array_diff($mbData['OPTIONS']['flags'], array('crm_preconnect'));
								$mbData['OPTIONS']['flags'][] = 'crm_connect';

								$result = CMailbox::update($mbData['ID'], array('OPTIONS' => $mbData['OPTIONS']));

								if ($result > 0)
								{
									$this->arParams['MAILBOX']['OPTIONS'] = $mbData['OPTIONS'];
									unset($this->arParams['CRM_PRECONNECT']);

									$filterFields = array(
										'MAILBOX_ID'         => $mbData['ID'],
										'NAME'               => sprintf('CRM IMAP %u', $USER->getId()),
										'ACTION_TYPE'        => 'crm_imap',
										'WHEN_MAIL_RECEIVED' => 'Y',
										'WHEN_MANUALLY_RUN'  => 'Y',
									);

									\CMailFilter::add($filterFields);
								}
							}
						}
						else
						{
							$this->arParams['IMAP_ERROR'] = $error;
							if ($errors instanceof \Bitrix\Main\ErrorCollection)
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

								$this->arParams['IMAP_ERROR'] = join(': ', array_map(function($error)
								{
									return $error->getMessage();
								}, $messages));
								$this->arParams['IMAP_ERROR_EXT'] = join(': ', array_map(function($error)
								{
									return $error->getMessage();
								}, $details));
							}
						}
					}
				}

				if (!empty($mbData['OPTIONS']['crm_lead_source']) && array_key_exists($mbData['OPTIONS']['crm_lead_source'], $this->arParams['LEAD_SOURCE_LIST']))
					$this->arParams['MAILBOX_LEAD_SOURCE'] = $mbData['OPTIONS']['crm_lead_source'];
			}
		}

		foreach ($this->arParams['SERVICES'] as $service)
		{
			if ($service['type'] == 'controller')
			{
				$this->arParams['CR_DOMAINS'] = \CIntranetMailConfigComponent::getControllerDomains();
			}
			else if (in_array($service['type'], array('domain', 'crdomain')))
			{
				$this->arParams['DOMAIN_STATUS'] = self::checkDomainStatus(array(
					'type'   => $service['type'],
					'domain' => $service['server'],
					'token'  => $service['token']
				), $error);
			}
		}

		if ($USER->isAdmin() || $USER->canDoOperation('bitrix24_config'))
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array('REGISTERED' => true));
			$this->arParams['REG_DOMAIN'] = empty($crDomains['result'])? false : mb_strtolower(reset($crDomains['result']));
		}

		ob_start();

		$component = new \CBitrixComponent();
		if ($component->initComponent('bitrix:intranet.mail.config.domain'))
			$this->arParams['PATH_TO_MAIL_CFG_DOMAIN_COMPONENT'] = $component->getPath();

		ob_clean();

		$this->includeComponentTemplate();
	}

	private static function checkDomainStatus($settings, &$error)
	{
		$error = null;

		if ($settings['type'] == 'domain')
		{
			$result = CMailDomain2::getDomainStatus($settings['token'], $settings['domain'], $error);

			if (is_null($result))
				$error = CMail::getErrorMessage($error);
			else if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
				$error = CMail::getErrorMessage(CMail::ERR_API_DENIED);
		}
		else if ($settings['type'] == 'crdomain')
		{
			$result = null;

			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberDomain', array(
				'DOMAIN' => $settings['domain']
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
				else if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
			}
		}

		return $result;
	}

}
