<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CCrmConfigEmailtrackerComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $APPLICATION, $USER;

		if (!CModule::includeModule('crm'))
		{
			showError(getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

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

		$crmPerms = new \CCrmPerms($USER->getId());
		if (!$crmPerms->havePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			showError(getMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		$this->arParams['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] ?: \CSite::getNameFormat();

		$res = \Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('ACTIVE' => 'Y', '=SITE_ID' => SITE_ID),
			'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC'),
		));

		$this->arParams['SERVICES'] = array();
		while ($service = $res->fetch())
		{
			$this->arParams['SERVICES'][$service['ID']] = array(
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

		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'filter' => array(
				'LID'     => SITE_ID,
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
					$this->arParams['MAILBOX'] = $mailbox;
					break;
				}
			}
		}

		$this->arParams['CHECK_INTERVAL_LIST'] = array(
			2   => getMessage('INTR_MAIL_CHECK_INTERVAL_2M'),
			5   => getMessage('INTR_MAIL_CHECK_INTERVAL_5M'),
			10  => getMessage('INTR_MAIL_CHECK_INTERVAL_10M'),
			60  => getMessage('INTR_MAIL_CHECK_INTERVAL_1H'),
			120 => getMessage('INTR_MAIL_CHECK_INTERVAL_3H'),
			720 => getMessage('INTR_MAIL_CHECK_INTERVAL_12H'),
		);
		$this->arParams['DEFAULT_CHECK_INTERVAL'] = 10;

		$this->arParams['NEW_ENTITY_LIST'] = array(
			\CCrmOwnerType::LeadName    => \CCrmOwnerType::getDescription(\CCrmOwnerType::Lead),
			\CCrmOwnerType::ContactName => \CCrmOwnerType::getDescription(\CCrmOwnerType::Contact),
		);
		$this->arParams['DEFAULT_NEW_ENTITY_IN']  = \CCrmOwnerType::LeadName;
		$this->arParams['DEFAULT_NEW_ENTITY_OUT'] = \CCrmOwnerType::ContactName;

		$this->arParams['LEAD_SOURCE_LIST'] = \CCrmStatus::getStatusList('SOURCE');
		reset($this->arParams['LEAD_SOURCE_LIST']);
		$this->arParams['DEFAULT_LEAD_SOURCE'] = key($this->arParams['LEAD_SOURCE_LIST']);
		if (is_set($this->arParams['LEAD_SOURCE_LIST'], 'EMAIL'))
			$this->arParams['DEFAULT_LEAD_SOURCE'] = 'EMAIL';
		elseif (is_set($this->arParams['LEAD_SOURCE_LIST'], 'OTHER'))
			$this->arParams['DEFAULT_LEAD_SOURCE'] = 'OTHER';

		if (!empty($this->arParams['MAILBOX']))
		{
			$mailbox = $this->arParams['MAILBOX'];
			$options = $mailbox['OPTIONS'];

			if (!array_key_exists('flags', $options) || !is_array($options['flags']))
				$options['flags'] = array();

			$this->arParams['SERVICE'] = $this->arParams['SERVICES'][$mailbox['SERVICE_ID']];

			if (in_array('crm_preconnect', $options['flags']))
			{
				$this->arParams['CRM_PRECONNECT'] = true;

				$imapDirs = \Bitrix\Mail\Helper::listImapDirs($mailbox, $error, $errors);
				if ($imapDirs !== false)
				{
					$mbData = array(
						'OPTIONS' => $options,
					);

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

						$result = \CMailbox::update($mailbox['ID'], $mbData);

						if ($result > 0)
						{
							$this->arParams['MAILBOX']['OPTIONS'] = $mbData['OPTIONS'];
							unset($this->arParams['CRM_PRECONNECT']);

							$mailbox = $this->arParams['MAILBOX'];
							$options = $mailbox['OPTIONS'];

							$filterFields = array(
								'MAILBOX_ID'         => $mailbox['ID'],
								'NAME'               => sprintf('CRM IMAP %u', $mailbox['ID']),
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

			if ($mailbox['PERIOD_CHECK'] > 0 && array_key_exists($mailbox['PERIOD_CHECK'], $this->arParams['CHECK_INTERVAL_LIST']))
				$this->arParams['DEFAULT_CHECK_INTERVAL'] = $mailbox['PERIOD_CHECK'];

			if (!array_intersect(array('crm_deny_new_lead', 'crm_deny_entity_in', 'crm_deny_entity_out'), $options['flags']))
			{
				$this->arParams['DEFAULT_NEW_ENTITY_IN'] = \CCrmOwnerType::LeadName;
				$this->arParams['DEFAULT_NEW_ENTITY_OUT'] = \CCrmOwnerType::LeadName;
			}

			if (!empty($options['crm_new_entity_in']) && array_key_exists($options['crm_new_entity_in'], $this->arParams['NEW_ENTITY_LIST']))
				$this->arParams['DEFAULT_NEW_ENTITY_IN'] = $options['crm_new_entity_in'];
			if (!empty($options['crm_new_entity_out']) && array_key_exists($options['crm_new_entity_out'], $this->arParams['NEW_ENTITY_LIST']))
				$this->arParams['DEFAULT_NEW_ENTITY_OUT'] = $options['crm_new_entity_out'];

			if (!empty($options['crm_lead_source']) && array_key_exists($options['crm_lead_source'], $this->arParams['LEAD_SOURCE_LIST']))
				$this->arParams['DEFAULT_LEAD_SOURCE'] = $options['crm_lead_source'];

			if (!empty($options['crm_lead_resp']))
			{
				$this->arParams['LEAD_RESP_SELECTED'] = \Bitrix\Main\UserTable::getList(array(
					'filter' => array(
						'ID' => $options['crm_lead_resp'],
					),
				))->fetchAll();

				$order = array_flip(array_values(array_unique($options['crm_lead_resp'])));
				usort($this->arParams['LEAD_RESP_SELECTED'], function ($a, $b) use (&$order)
				{
					return isset($order[$a['ID']], $order[$b['ID']]) ? $order[$a['ID']]-$order[$b['ID']] : 0;
				});
			}

			$this->arParams['BLACKLIST'] = array();
			$res = \Bitrix\Mail\BlacklistTable::getList(array(
				'select' => array('ITEM_TYPE', 'ITEM_VALUE'), // ITEM_TYPE for fetch_data_modification
				'filter' => array('MAILBOX_ID' => $mailbox['ID']),
			));
			while ($item = $res->fetch())
				$this->arParams['BLACKLIST'][] = $item['ITEM_VALUE'];

			$this->arParams['NEW_LEAD_FOR'] = is_array($options['crm_new_lead_for']) ? $options['crm_new_lead_for'] : array();
		}

		if (empty($this->arParams['LEAD_RESP_SELECTED']))
		{
			$this->arParams['LEAD_RESP_SELECTED'] = \Bitrix\Main\UserTable::getList(array(
				'filter' => array(
					'ID' => $USER->getId(),
				),
			))->fetchAll();
		}

		if (CModule::includeModule('socialnetwork'))
			$this->arParams['COMPANY_STRUCTURE'] = \CSocNetLogDestination::getStucture();

		$this->arResult['ENABLE_CONTROL_PANEL'] = isset($this->arParams['ENABLE_CONTROL_PANEL'])
			? $this->arParams['ENABLE_CONTROL_PANEL'] : true;

		$this->includeComponentTemplate();
	}

}
