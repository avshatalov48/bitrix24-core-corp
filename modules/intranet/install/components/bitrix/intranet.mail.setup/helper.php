<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/component.php');

class CIntranetMailSetupHelper
{

	public static function getDomainService($id = null)
	{
		$services = self::getMailServices();

		if ($id)
		{
			if (!empty($services[$id]))
				$settings = $services[$id];
		}
		else
		{
			foreach ($services as $service)
			{
				if (in_array($service['type'], array('domain', 'crdomain')))
					$settings = $service;
			}
		}

		return empty($settings) ? false : $settings;
	}

	public static function getMailServices()
	{
		static $services;

		if (is_null($services))
		{
			$dbServices = \Bitrix\Mail\MailServicesTable::getList(array(
				'filter' => array('=ACTIVE' => 'Y', '=SITE_ID' => SITE_ID),
				'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC')
			));

			$services = array();
			while (($service = $dbServices->fetch()) !== false)
			{
				$services[$service['ID']] = array(
					'id'         => $service['ID'],
					'type'       => $service['SERVICE_TYPE'],
					'name'       => $service['NAME'],
					'link'       => $service['LINK'],
					'icon'       => \Bitrix\Mail\MailServicesTable::getIconSrc($service['NAME'], $service['ICON']),
					'server'     => $service['SERVER'],
					'port'       => $service['PORT'],
					'encryption' => $service['ENCRYPTION'],
					'token'      => $service['TOKEN'],
					'flags'      => $service['FLAGS'],
					'sort'       => $service['SORT']
				);
			}
		}

		return $services;
	}

	public static function getUserMailbox($userId)
	{
		static $mailboxes = array();

		if (is_null($mailboxes[$userId]))
		{
			$mailbox = \Bitrix\Mail\MailboxTable::getList(array(
				'filter' => array(
					'=LID'         => SITE_ID,
					'=ACTIVE'      => 'Y',
					'=USER_ID'     => (int) $userId,
					'@SERVER_TYPE' => array('imap', 'controller', 'domain', 'crdomain'),
				),
				'order' => array('ID' => 'DESC'),
			))->fetch();

			$mailboxes[$userId] = $mailbox;
		}

		return $mailboxes[$userId];
	}

	public static function createMailbox($exists, $userId, $serviceId, $domain, $login, $password, &$error)
	{
		$error = false;

		if (intval($userId))
		{
			$dbUser = CUser::getList(
				$by = 'ID', $order = 'ASC',
				array('ID_EQUAL_EXACT' => intval($userId)),
				array('FIELDS' => 'ID')
			);
			if (!$dbUser->fetch())
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$services = self::getMailServices();

			if (empty($services[$serviceId]) || !in_array($services[$serviceId]['type'], array('controller', 'domain', 'crdomain')))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$service = $services[$serviceId];

			if ($service['type'] == 'controller')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
				$arDomains = empty($crDomains['result']) ? array() : $crDomains['result'];
				if (!is_array($arDomains) || !in_array($domain, $arDomains))
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
			}
			else if ($service['type'] == 'crdomain')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array());
				$arDomains = empty($crDomains['result']) ? array() : $crDomains['result'];
				if (!is_array($arDomains) || !in_array($domain, $arDomains))
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
			}
			else if ($service['type'] == 'domain')
			{
				if ($service['server'] != $domain)
					$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
		}

		if ($error === false && !$exists)
		{
			if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $login))
				$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);

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
					$result = CMailDomain2::addUser($service['token'], $domain, $login, $password, $error);
					if (is_null($result))
						$error = CMail::getErrorMessage($error);
				}
			}
		}

		if ($error === false && intval($userId))
		{
			if ($exists)
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

				if ($error === false)
				{
					$mailbox = \Bitrix\Mail\MailboxTable::getList(array(
						'select' => array('ID'),
						'filter' => array(
							'=ACTIVE'   => 'Y',
							'>USER_ID'  => 0,
							'!=USER_ID' => (int) $userId,
							'LOGIN'     => $login . '@' . $domain,
						),
					))->fetch();

					if (!empty($mailbox))
						$error = GetMessage('INTR_MAIL_MAILBOX_OCCUPIED');
				}
			}

			if ($error === false)
			{
				$mailbox = self::getUserMailbox($userId);
				if (!empty($mailbox))
					$res = CMailbox::delete($mailbox['ID']);

				$arFields = array(
					'LID'         => SITE_ID,
					'ACTIVE'      => 'Y',
					'SERVICE_ID'  => $serviceId,
					'NAME'        => $service['name'],
					'LOGIN'       => $login . '@' . $domain,
					'SERVER_TYPE' => $service['type'],
					'USER_ID'     => intval($userId)
				);

				$res = CMailbox::add($arFields);
				if (!$res)
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}

		if ($error === false)
			return $login . '@' . $domain;
	}

}

