<?php

use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/crm.activity.editor/ajax.php');
Loc::loadMessages(__FILE__);

class CrmActivityEmailComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $APPLICATION;

		\CModule::includeModule('socialnetwork');
		\CJSCore::init(array('socnetlogdest', 'admin_interface'));

		$this->arParams['PATH_TO_DEAL_DETAILS'] = \CrmCheckPath(
			'PATH_TO_DEAL_DETAILS',
			$this->arParams['PATH_TO_DEAL_DETAILS'] ?? '',
			$APPLICATION->getCurPage().'?deal_id=#deal_id#&details'
		);

		$action = !empty($this->arParams['ACTION']) ? $this->arParams['ACTION'] : 'create';
		switch(mb_strtolower($action))
		{
			case 'view':
				return $this->executeView();
			default:
				return $this->executeEdit();
		}
	}

	protected function executeEdit()
	{
		global $USER;

		if (!\CCrmSecurityHelper::isAuthorized())
		{
			return;
		}

		$activity = $this->arParams['~ACTIVITY'];

		if (($activity['ID'] ?? null) > 0)
		{
			return;
		}

		$siteNameFormat = \CSite::getNameFormat();

		$userFields = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
			'filter' => array('=ID' => $USER->getId()),
		))->fetch();
		$userImage = \CFile::resizeImageGet(
			$userFields['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
			BX_RESIZE_IMAGE_EXACT, false
		);
		$this->arParams['USER_IMAGE'] = !empty($userImage['src']) ? $userImage['src'] : '';
		$this->arParams['USER_FULL_NAME'] = \CUser::formatName($siteNameFormat, $userFields, true, false);

		$templates = array();
		$templatesByType = array();
		$res = \CCrmMailTemplate::getList(
			array('SORT' => 'ASC', 'ENTITY_TYPE_ID' => 'DESC', 'TITLE'=> 'ASC'),
			array(
				'IS_ACTIVE' => 'Y',
				'__INNER_FILTER_TYPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_TYPE_1' => array('ENTITY_TYPE_ID' => $activity['OWNER_TYPE_ID']),
					'__INNER_FILTER_TYPE_2' => array('ENTITY_TYPE_ID' => 0),
				),
				'__INNER_FILTER_SCOPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_PERSONAL' => array(
						'OWNER_ID' => $USER->getId(),
						'SCOPE'    => \CCrmMailTemplateScope::Personal,
					),
					'__INNER_FILTER_COMMON' => array(
						'SCOPE' => \CCrmMailTemplateScope::Common,
					),
				),
			),
			false, false,
			array('TITLE', 'SCOPE', 'ENTITY_TYPE_ID', 'BODY_TYPE')
		);

		while ($item = $res->fetch())
		{
			$templates[] = array(
				'id'         => $item['ID'],
				'title'      => $item['TITLE'],
				'scope'      => $item['SCOPE'],
				'entityType' => \CCrmOwnerType::resolveName($activity['OWNER_TYPE_ID']),
			);

			$entityType = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
			if (empty($templatesByType[$entityType]))
				$templatesByType[$entityType] = array();

			$templatesByType[$entityType][] = array(
				'id'         => $item['ID'],
				'title'      => $item['TITLE'],
				'scope'      => $item['SCOPE'],
				'entityType' => $entityType,
			);
		}

		$activity['INITIAL_OWNER_TYPE_ID'] = $activity['OWNER_TYPE_ID'];
		$activity['INITIAL_OWNER_TYPE'] = \CCrmOwnerType::resolveName($activity['OWNER_TYPE_ID']);
		$activity['INITIAL_OWNER_ID'] = $activity['OWNER_ID'];

		$activity['BINDINGS'] = empty($activity['BINDINGS']) ? array() : (array) $activity['BINDINGS'];
		$activity['COMMUNICATIONS'] = empty($activity['COMMUNICATIONS']) ? array() : (array) $activity['COMMUNICATIONS'];
		$activity['STORAGE_ELEMENT_IDS'] = empty($activity['STORAGE_ELEMENT_IDS']) ? array() : (array) $activity['STORAGE_ELEMENT_IDS'];

		if (empty($activity['DESCRIPTION_HTML']) && !empty($activity['DESCRIPTION']))
		{
			switch ($activity['DESCRIPTION_TYPE'])
			{
				case \CCrmContentType::BBCode:
					$textParser = new CTextParser();
					$activity['DESCRIPTION_HTML'] = $textParser->convertText($activity['DESCRIPTION']);
					break;
				case \CCrmContentType::Html:
					$activity['DESCRIPTION_HTML'] = $activity['DESCRIPTION'];
					break;
				default:
					$activity['DESCRIPTION_HTML'] = preg_replace(
						'/[\r\n]+/'.BX_UTF_PCRE_MODIFIER,
						'<br>',
						htmlspecialcharsbx($activity['DESCRIPTION'])
					);
			}
		}

		if (empty($activity['__parent']))
		{
			$pdfFileId = false;

			if (\CCrmOwnerType::Invoice == $activity['OWNER_TYPE_ID'] && $activity['OWNER_ID'] > 0)
			{
				$invoice = \CCrmInvoice::getById($activity['OWNER_ID']);

				$activity['OWNER_TYPE_ID'] = 0;
				$activity['OWNER_ID'] = 0;

				if (!empty($invoice))
				{
					if ($invoice['UF_DEAL_ID'] > 0)
					{
						$activity['OWNER_TYPE_ID'] = \CCrmOwnerType::Deal;
						$activity['OWNER_ID'] = $invoice['UF_DEAL_ID'];
					}

					// remove (old version) suffix from message to a client
					$activity['SUBJECT'] = sprintf(
						'%s %s', \CCrmOwnerType::getDescription(\CCrmOwnerType::SmartInvoice), $invoice['ACCOUNT_NUMBER']
					);

					// encapsulate to StorageManager
					if (!empty($activity['STORAGE_ELEMENT_IDS']));
					else if ($activity['STORAGE_TYPE_ID'] == \Bitrix\Crm\Integration\StorageType::Disk)
					{
						$pdfFileId = \CCrmInvoice::savePdf($invoice['ID']);
					}
				}
			}
			else if (\CCrmOwnerType::Quote == $activity['OWNER_TYPE_ID'] && $activity['OWNER_ID'] > 0)
			{
				$quote = \CCrmQuote::getById($activity['OWNER_ID']);

				if (!empty($quote))
				{
					if ($quote['DEAL_ID'] > 0)
					{
						$activity['BINDINGS'][] = [
							'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
							'OWNER_ID' => $quote['DEAL_ID'],
						];
					}

					$activity['SUBJECT'] = $quote['TITLE'] ?: sprintf(
						'%s %s', \CCrmOwnerType::getDescription(\CCrmOwnerType::Quote), $quote['ID']
					);

					if (!empty($activity['STORAGE_ELEMENT_IDS']));
					else if ($activity['STORAGE_TYPE_ID'] == \Bitrix\Crm\Integration\StorageType::Disk)
					{
						if (!empty($activity['__owner_psid']) && $activity['__owner_psid'] > 0)
							$pdfFileId = \CCrmQuote::savePdf($quote['ID'], $activity['__owner_psid']);

						if (!($pdfFileId > 0))
						{
							$paySystems = \CCrmPaySystem::getPaySystems($quote['PERSON_TYPE_ID']);
							if (is_array($paySystems))
							{
								foreach ($paySystems as $item)
								{
									$itemActionFile = isset($item['~PSA_ACTION_FILE']) ? $item['~PSA_ACTION_FILE'] : '';
									if (preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $itemActionFile))
									{
										$pdfFileId = \CCrmQuote::savePdf($quote['ID'], $item['~ID']);
										break;
									}
								}
							}
						}
					}
				}
			}

			if ($pdfFileId > 0)
			{
				if ($pdfFile = \CFile::getFileArray($pdfFileId))
				{
					// encapsulate to StorageManager
					$storageElementId = \Bitrix\Crm\Integration\StorageManager::saveEmailAttachment(
						$pdfFile,
						\Bitrix\Crm\Integration\StorageType::Disk
					);
				}
				if ($storageElementId > 0)
				{
					$storageElement = \Bitrix\Disk\File::loadById($storageElementId, array('STORAGE'));

					$diskRightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
					$diskRightsManager->append(
						$storageElement,
						array(array(
							'ACCESS_CODE' => 'U'.$USER->getId(),
							'TASK_ID' => $diskRightsManager->getTaskIdByName(\Bitrix\Disk\RightsManager::TASK_READ),
						))
					);

					$activity['STORAGE_ELEMENT_IDS'][] = $storageElementId;
				}
			}
		}
		else
		{
			if (\CCrmActivityDirection::Incoming == $activity['__parent']['DIRECTION'])
			{
				$from = reset($activity['COMMUNICATIONS']);
				static::prepareCommunication($from);
				$from = $from['TITLE'];
			}
			else if ($USER->getId() == $activity['__parent']['AUTHOR_ID'])
			{
				$from = $this->arParams['USER_FULL_NAME'];
			}
			else
			{
				$authorFields = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'),
					'filter' => array('=ID' => $activity['__parent']['AUTHOR_ID']),
				))->fetch();
				$from = \CUser::formatName($siteNameFormat, $authorFields, true, false);
			}

			$activity['DESCRIPTION_HTML'] = sprintf(
				'<br><br>%s, %s:<br><blockquote style="margin: 0 0 0 5px; padding: 5px 5px 5px 8px; border-left: 4px solid #e2e3e5; ">%s</blockquote>',
				formatDate(
					preg_replace('/[\/.,\s:][s]/', '', $GLOBALS['DB']->dateFormatToPhp(FORMAT_DATETIME)),
					makeTimestamp($activity['__parent']['START_TIME']),
					time()+\CTimeZone::getOffset()
				),
				htmlspecialcharsbx($from),
				$this->arParams['~ACTIVITY']['DESCRIPTION_HTML']
			);

			$activity['__message_type'] = !empty($activity['__message_type'])? mb_strtoupper($activity['__message_type']) : '';
			switch ($activity['__message_type'])
			{
				case 'FWD':
					$subjectPrefix = 'Fwd';
					$activity['FORWARDED_ID'] = $activity['__parent']['ID'];
					$activity['BINDINGS'] = $activity['__parent']['BINDINGS'];
					$activity['STORAGE_TYPE_ID'] = $activity['__parent']['STORAGE_TYPE_ID'];
					$activity['~STORAGE_ELEMENT_IDS'] = $activity['__parent']['~STORAGE_ELEMENT_IDS'];
					$activity['STORAGE_ELEMENT_IDS'] = $activity['__parent']['STORAGE_ELEMENT_IDS'];
					$activity['COMMUNICATIONS'] = array();
					break;
				case 'RE':
					$subjectPrefix = 'Re';
					$activity['OWNER_TYPE_ID'] = $activity['__parent']['OWNER_TYPE_ID'];
					$activity['OWNER_ID'] = $activity['__parent']['OWNER_ID'];
					$activity['REPLIED_ID'] = $activity['__parent']['ID'];
					$activity['BINDINGS'] = $activity['__parent']['BINDINGS'];
					$activity['STORAGE_TYPE_ID'] = $activity['__parent']['STORAGE_TYPE_ID'];
					$activity['~STORAGE_ELEMENT_IDS'] = $activity['__parent']['~STORAGE_ELEMENT_IDS'];
					$activity['STORAGE_ELEMENT_IDS'] = $activity['__parent']['STORAGE_ELEMENT_IDS'];
					break;
				default:
					$activity['COMMUNICATIONS'] = array();
			}

			if (!empty($subjectPrefix))
			{
				$activity['SUBJECT'] = preg_replace(
					sprintf('/^(%s:\s*)?/i', preg_quote($subjectPrefix)),
					sprintf('%s: ', $subjectPrefix),
					$activity['SUBJECT']
				);
			}
		}

		if (\CCrmActivityStorageType::Disk != $activity['STORAGE_TYPE_ID'])
			$activity['STORAGE_ELEMENT_IDS'] = array();

		\CCrmActivity::prepareStorageElementIds($activity);
		\CCrmActivity::prepareStorageElementInfo($activity);

		$storageRightsToRevoke = array();
		if (\CCrmActivityStorageType::Disk == $activity['STORAGE_TYPE_ID'] && \CModule::includeModule('disk'))
		{
			$diskSecurityContext = new \Bitrix\Disk\Security\DiskSecurityContext($USER->getId());
			$diskRightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
			foreach ($activity['STORAGE_ELEMENT_IDS'] as $item)
			{
				$storageElement = \Bitrix\Disk\File::loadById($item, array('STORAGE'));
				if ($storageElement && $storageElement->getStorage()->isUseInternalRights() && !$storageElement->canRead($diskSecurityContext))
				{
					$diskRightsManager->append(
						$storageElement,
						array(array(
							'ACCESS_CODE' => 'U'.$USER->getId(),
							'TASK_ID' => $diskRightsManager->getTaskIdByName(\Bitrix\Disk\RightsManager::TASK_READ),
						))
					);

					\Bitrix\Crm\Integration\StorageManager::registerInterRequestFile($item, \Bitrix\Crm\Integration\StorageType::Disk);
					$storageRightsToRevoke[] = array($storageElement, array('U'.$USER->getId()));
				}
			}
		}

		if (\CCrmOwnerType::Deal == $activity['OWNER_TYPE_ID'] && $activity['OWNER_ID'] > 0)
		{
			$activity['BINDINGS'][] = array(
				'OWNER_TYPE_ID' => $activity['OWNER_TYPE_ID'],
				'OWNER_ID' => $activity['OWNER_ID'],
			);
		}

		foreach ($activity['COMMUNICATIONS'] as $k => $item)
		{
			static::prepareCommunication($item);
			$activity['COMMUNICATIONS'][$k] = $item;
		}

		if (!empty($activity['__communications']))
		{
			foreach ($activity['__communications'] as $k => $item)
			{
				static::prepareCommunication($item);
				$activity['__communications'][$k] = $item;
			}
		}

		$bindings = array(
			\CCrmOwnerType::Deal => array(),
		);
		foreach ($activity['BINDINGS'] as $item)
		{
			if (\CCrmOwnerType::Deal != $item['OWNER_TYPE_ID'])
				continue;

			$bindings[$item['OWNER_TYPE_ID']][] = $item['OWNER_ID'];
		}

		$docsBindings = array();
		foreach ($bindings as $typeId => $ids)
		{
			if (empty($ids))
				continue;

			switch ($typeId)
			{
				case \CCrmOwnerType::Deal:
				{
					$res = \CCrmDeal::getListEx(
						array(),
						array('@ID' => $ids),
						false, false,
						array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
					);
					while ($deal = $res->fetch())
					{
						$docsBindings[] = array(
							'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
							'OWNER_ID'      => $deal['ID'],
							'DOC_NAME'      => \CCrmOwnerType::getDescription(\CCrmOwnerType::Deal),
							'DOC_URL'       => \CComponentEngine::makePathFromTemplate(
								$this->arParams['PATH_TO_DEAL_DETAILS'],
								array('deal_id' => $deal['ID'])
							),
							'TITLE'         => $deal['TITLE'],
							'DESCRIPTION'   => join(', ', array_filter(array(
								$deal['COMPANY_TITLE'],
								\CUser::formatName(
									$siteNameFormat,
									array(
										'LOGIN'       => '',
										'NAME'        => $deal['CONTACT_NAME'],
										'SECOND_NAME' => $deal['CONTACT_SECOND_NAME'],
										'LAST_NAME'   => $deal['CONTACT_LAST_NAME'],
									),
									false, false
								)
							))),
						);
					}
				} break;
			}
		}

		$activity['REPLY_TO']  = array();
		$activity['REPLY_ALL'] = array();
		$activity['REPLY_CC']  = array();

		if (!empty($activity['__parent']) && 'RE' == $activity['__message_type'])
			\CrmActivityEmailComponent::prepareActivityRcpt($activity, $activity['__parent']);

		$this->arParams['MAILBOXES'] = static::prepareMailboxes();
		$this->arParams['ACTIVITY'] = $activity;
		$this->arParams['DOCS_BINDINGS'] = $docsBindings;
		$this->arParams['DOCS_READONLY'] = (\CCrmOwnerType::Lead == $activity['OWNER_TYPE_ID'] || \CCrmOwnerType::DealRecurring == $activity['OWNER_TYPE_ID']);
		$this->arParams['TEMPLATES'] = $templates;
		$this->arParams['TEMPLATES_BY_TYPE'] = $templatesByType;

		$this->includeComponentTemplate('edit');

		$diskRightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
		foreach ($storageRightsToRevoke as $item)
			call_user_func_array(array($diskRightsManager, 'revokeByAccessCodes'), $item);
	}

	protected function executeView()
	{
		global $APPLICATION, $USER;

		$activity = $this->arParams['~ACTIVITY'];
		if (empty($activity))
			return;

		$userId = $USER->getId();

		$pageSize = (int) $this->arParams['PAGE_SIZE'];
		if ($pageSize < 1 || $pageSize > 100)
			$this->arParams['PAGE_SIZE'] = ($pageSize = 5);

		$actIds  = array();
		$authIds = array($userId, $activity['AUTHOR_ID'], $activity['RESPONSIBLE_ID']);

		$res = \CCrmActivity::getList(
			array(
				'START_TIME' => 'DESC',
				'ID'         => 'DESC',
			),
			array(
				'!ID'          => $activity['ID'],
				'THREAD_ID'    => $activity['THREAD_ID'],
				'<=START_TIME' => $activity['START_TIME'],
			),
			false, false,
			array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID', 'SETTINGS'),
			array('QUERY_OPTIONS' => array('OFFSET' => 0, 'LIMIT' => $pageSize))
		);

		$this->arResult['LOG']['B'] = array();
		while ($item = $res->fetch())
		{
			$this->arResult['LOG']['B'][] = $item;

			if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
			{
				$actIds[] = $item['ID'];
			}
			else
			{
				$authIds[] = $item['AUTHOR_ID'];
				$authIds[] = $item['RESPONSIBLE_ID'];
			}
		}

		$res = \CCrmActivity::getList(
			array(
				'START_TIME' => 'ASC',
				'ID'         => 'ASC',
			),
			array(
				'!ID'         => $activity['ID'],
				'THREAD_ID'   => $activity['THREAD_ID'],
				'>START_TIME' => $activity['START_TIME'],
			),
			false, false,
			array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID', 'SETTINGS'),
			array('QUERY_OPTIONS' => array('OFFSET' => 0, 'LIMIT' => $pageSize))
		);

		$this->arResult['LOG']['A'] = array();
		while ($item = $res->fetch())
		{
			$this->arResult['LOG']['A'][] = $item;

			if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
			{
				$actIds[] = $item['ID'];
			}
			else
			{
				$authIds[] = $item['AUTHOR_ID'];
				$authIds[] = $item['RESPONSIBLE_ID'];
			}
		}

		$this->arResult['LOG']['A'] = array_reverse($this->arResult['LOG']['A']);

		$clients = array();

		if (!empty($actIds))
		{
			$res = \CCrmActivity::getCommunicationList(
				array('ID' => 'ASC'),
				array('ACTIVITY_ID' => $actIds),
				false, false,
				array()
			);

			while ($item = $res->fetch())
			{
				if (array_key_exists($item['ACTIVITY_ID'], $clients))
					continue;

				static::prepareCommunication($item);
				$clients[$item['ACTIVITY_ID']] = $item;
			}
		}

		$res = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
			'filter' => array('=ID' => array_unique($authIds)),
		));

		$authors = array();
		$nameFormat = \CSite::getNameFormat(null);
		while ($item = $res->fetch())
		{
			$item['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $item, true, false);

			$authors[$item['ID']] = $item;
		}

		$trackingAvailable = Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y';

		foreach ($this->arResult['LOG'] as $k => $log)
		{
			foreach ($log as $i => $item)
			{
				if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
				{
					$item['LOG_TITLE'] = $clients[$item['ID']]['TITLE'];
					$item['LOG_IMAGE'] = $clients[$item['ID']]['IMAGE_URL'];
				}
				else
				{
					$authorId = !empty($authors[$item['AUTHOR_ID']]) ? $item['AUTHOR_ID'] : $item['RESPONSIBLE_ID'];

					if (!empty($authors[$authorId]) && !array_key_exists('IMAGE_URL', $authors[$authorId]))
					{
						$preview = \CFile::resizeImageGet(
							$authors[$authorId]['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT, false
						);

						$authors[$authorId]['IMAGE_URL'] = $preview['src'];
					}

					$item['LOG_TITLE'] = $authors[$authorId]['NAME_FORMATTED'] ?: $item['SETTINGS']['EMAIL_META']['__email'];
					$item['LOG_IMAGE'] = $authors[$authorId]['IMAGE_URL'];
				}

				$item['__trackable'] = isset($item['SETTINGS']['IS_BATCH_EMAIL']) && !$item['SETTINGS']['IS_BATCH_EMAIL'];
				$item['__trackable'] *= $trackingAvailable || $item['SETTINGS']['READ_CONFIRMED'] > 0;

				$this->arResult['LOG'][$k][$i] = $item;
			}
		}

		$authorId = !empty($authors[$activity['AUTHOR_ID']]) ? $activity['AUTHOR_ID'] : $activity['RESPONSIBLE_ID'];

		foreach (array($authorId, $userId) as $uid)
		{
			if (!empty($authors[$uid]) && !array_key_exists('IMAGE_URL', $authors[$uid]))
			{
				$preview = \CFile::resizeImageGet(
					$authors[$uid]['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
					BX_RESIZE_IMAGE_EXACT, false
				);

				$authors[$uid]['IMAGE_URL'] = $preview['src'];
			}
		}

		$activity['__author'] = $authors[$authorId];

		$templates = array();
		$res = \CCrmMailTemplate::getList(
			array('SORT' => 'ASC', 'ENTITY_TYPE_ID' => 'DESC', 'TITLE'=> 'ASC'),
			array(
				'IS_ACTIVE' => 'Y',
				'__INNER_FILTER_TYPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_TYPE_1' => array('ENTITY_TYPE_ID' => $activity['OWNER_TYPE_ID']),
					'__INNER_FILTER_TYPE_2' => array('ENTITY_TYPE_ID' => 0),
				),
				'__INNER_FILTER_SCOPE' => array(
					'LOGIC' => 'OR',
					'__INNER_FILTER_PERSONAL' => array(
						'OWNER_ID' => $USER->getId(),
						'SCOPE'    => \CCrmMailTemplateScope::Personal,
					),
					'__INNER_FILTER_COMMON' => array(
						'SCOPE' => \CCrmMailTemplateScope::Common,
					),
				),
			),
			false, false,
			array('TITLE', 'SCOPE', 'ENTITY_TYPE_ID', 'BODY_TYPE')
		);

		while ($item = $res->fetch())
		{
			$entityType = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
			if (empty($templates[$entityType]))
				$templates[$entityType] = array();

			$templates[$entityType][] = array(
				'id'         => $item['ID'],
				'title'      => $item['TITLE'],
				'scope'      => $item['SCOPE'],
				'entityType' => $entityType,
			);
		}

		$userFields = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
			'filter' => array('=ID' => $USER->getId()),
		))->fetch();
		$this->arParams['USER_FULL_NAME'] = \CUser::formatName($nameFormat, $userFields, true, false);

		$this->arParams['MAILBOXES'] = static::prepareMailboxes();
		$this->arParams['ACTIVITY']  = $activity;
		$this->arParams['TEMPLATES'] = $templates;

		$templatePage = !empty($activity['__template']) && $activity['__template'] == 'slider' ? 'slider' : '';

		$this->includeComponentTemplate($templatePage);
	}

	public static function prepareActivityRcpt(&$activity, $parent = null)
	{
		$author    = (array) $activity['__author'];
		$settings  = !empty($parent['SETTINGS']) ? $parent['SETTINGS'] : $activity['SETTINGS'];
		$direction = !empty($parent['DIRECTION']) ? $parent['DIRECTION'] : $activity['DIRECTION'];

		$communications = array();
		if (!empty($activity['COMMUNICATIONS']) && is_array($activity['COMMUNICATIONS']))
		{
			foreach ($activity['COMMUNICATIONS'] as $item)
			{
				$id = sprintf(
					'CRM%s%u:%s',
					\CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']),
					$item['ENTITY_ID'],
					hash('crc32b', $item['TYPE'].':'.$item['VALUE'])
				);
				$communications[$id] = $item;
			}
		}

		$activity['COMMUNICATIONS'] = array_values($communications);

		$activityEmailMeta = array(
			'__email' => null,
			'from'    => array(),
			'replyTo' => array(),
			'to'      => array(),
			'cc'      => array(),
			'bcc'     => array(),
		);
		if (!empty($settings['EMAIL_META']))
		{
			$activityEmailMeta = array_merge($activityEmailMeta, $settings['EMAIL_META']);
			foreach ($activityEmailMeta as $field => $value)
			{
				if (!in_array($field, array('from', 'replyTo', 'to', 'cc', 'bcc')))
					continue;

				$result = array();
				$list = is_array($value) ? $value : explode(',', $value);
				foreach ($list as $item)
				{
					$address = new \Bitrix\Main\Mail\Address($item);
					if ($address->validate())
					{
						$result[] = $address->getEmail();
					}
				}

				$activityEmailMeta[$field] = $result;
			}
		}

		$isActivityIncoming = \CCrmActivityDirection::Incoming == $direction;
		$typesPriority = array(
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Lead,
		);

		// FROM for display
		{
			if ($isActivityIncoming)
			{
				if (count($activity['COMMUNICATIONS']) > 1)
				{
					foreach (array('from', 'replyTo') as $field)
					{
						foreach ($activity['COMMUNICATIONS'] as $item)
						{
							if (in_array(mb_strtolower($item['VALUE']), $activityEmailMeta[$field]))
							{
								if (empty($fromItem) || $typesPriority[$item['ENTITY_TYPE_ID']] < $typesPriority[$fromItem['ENTITY_TYPE_ID']])
									$fromItem = $item;
							}
						}

						if (!empty($fromItem))
							break;
					}
				}

				if (empty($fromItem))
					$fromItem = reset($activity['COMMUNICATIONS']);

				$activity['ITEM_IMAGE'] = $fromItem['IMAGE_URL'];

				$activity['ITEM_FROM_URL']   = $fromItem['VIEW_URL'];
				$activity['ITEM_FROM_TITLE'] = $fromItem['TITLE'];
				$activity['ITEM_FROM_EMAIL'] = $fromItem['TITLE'] != $fromItem['VALUE'] ? $fromItem['VALUE'] : null;
			}
			else
			{
				$activity['ITEM_IMAGE'] = $author['IMAGE_URL'];

				$activity['ITEM_FROM_TITLE'] = $author['NAME_FORMATTED'] ?: $activityEmailMeta['__email'];
				$activity['ITEM_FROM_EMAIL'] = $activityEmailMeta['__email'] != $activity['ITEM_FROM_TITLE']
					? $activityEmailMeta['__email'] : null;
				$activity['ITEM_FROM_URL']   = null;
			}
		}

		// rcpt
		{

			$activity['ITEM_TO']  = array();
			$activity['ITEM_CC']  = array();
			$activity['ITEM_BCC'] = array();

			$activity['REPLY_TO']  = array();
			$activity['REPLY_ALL'] = array();
			$activity['REPLY_CC']  = array();

			$unknownEmails = array();

			$replyField = !empty($activityEmailMeta['replyTo']) ? 'replyTo' : 'from';
			$toField = $isActivityIncoming ? $replyField : 'to';
			foreach (array($replyField, 'to', 'cc', 'bcc') as $field)
			{
				if (!empty($activityEmailMeta[$field]))
				{
					$foundEmails = array();
					foreach ($activity['COMMUNICATIONS'] as $k => $item)
					{
						if (in_array(mb_strtolower($item['VALUE']), $activityEmailMeta[$field]))
						{
							$foundEmails[] = mb_strtolower($item['VALUE']);

							$itemView = array(
								'IMAGE' => $item['IMAGE_URL'],
								'TITLE' => $item['VALUE'],
								'URL'   => $item['VIEW_URL'],
							);

							if (!$isActivityIncoming)
							{
								if ($field == 'to')
									$activity['ITEM_TO'][] = $itemView;
								if ($field == 'cc')
									$activity['ITEM_CC'][] = $itemView;
								if ($field == 'bcc')
									$activity['ITEM_BCC'][] = $itemView;
							}

							if ($field == 'to' || $isActivityIncoming && $field == $replyField)
								$activity['REPLY_ALL'][$k] = $item;
							if ($field == $toField)
								$activity['REPLY_TO'][$k] = $item;
							if ($field == 'cc')
								$activity['REPLY_CC'][$k] = $item;
						}
					}

					if ($isActivityIncoming)
					{
						foreach ($activityEmailMeta[$field] as $item)
						{
							$itemView = array('TITLE' => $item);

							if ($field != $replyField)
							{
								if ($item == mb_strtolower(trim($activityEmailMeta['__email'])))
									$itemView['IMAGE'] = $author['IMAGE_URL'];
							}

							if ($field == 'to')
								$activity['ITEM_TO'][] = $itemView;
							if ($field == 'cc')
								$activity['ITEM_CC'][] = $itemView;
							if ($field == 'bcc')
								$activity['ITEM_BCC'][] = $itemView;
						}
					}

					foreach (array_diff($activityEmailMeta[$field], $foundEmails) as $item)
					{
						if ($isActivityIncoming)
						{
							if ($field != $replyField)
							{
								if ($item == mb_strtolower(trim($activityEmailMeta['__email'])))
									continue;
							}

							if ($field != 'cc' && $field != 'bcc')
								$activity['REPLY_ALL'][$item] = $item;
						}
						else
						{
							if ($field == 'to')
								$activity['ITEM_TO'][] = array('TITLE' => $item);
							if ($field == 'cc')
								$activity['ITEM_CC'][] = array('TITLE' => $item);
							if ($field == 'bcc')
								$activity['ITEM_BCC'][] = array('TITLE' => $item);
						}

						if ($field == 'to')
							$activity['REPLY_ALL'][$item] = $item;
						if ($field == $toField)
							$activity['REPLY_TO'][$item] = $item;
						if ($field == 'cc')
							$activity['REPLY_CC'][$item] = $item;

						$unknownEmails[] = $item;
					}
				}
			}

			if (empty($activityEmailMeta[$toField]))
			{
				$activity['REPLY_TO'] = $activity['COMMUNICATIONS'];

				if (empty($activityEmailMeta['cc']) && empty($activityEmailMeta['bcc']))
				{
					$activity['REPLY_ALL'] = $activity['COMMUNICATIONS'];
				}
				else
				{
					if ($isActivityIncoming)
					{
						if (!empty($activityEmailMeta['__email']))
						{
							$replyToEmail = mb_strtolower(trim($activityEmailMeta['__email']));
						}
						else
						{
							$mailboxes = \CrmActivityEmailComponent::prepareMailboxes();
							$mailbox = reset($mailboxes);

							$replyToEmail = $mailbox['email'];
						}

						// @TODO: may be use cc/bcc?
						$activity['REPLY_TO'] = array(
							array(
								'TYPE'  => 'EMAIL',
								'VALUE' => $replyToEmail,
							)
						);
					}
				}
			}

			if (empty($activityEmailMeta['to']) && empty($activityEmailMeta['cc']) && empty($activityEmailMeta['bcc']))
			{
				if ($isActivityIncoming)
				{
					$activity['ITEM_TO'] = array(array(
						'IMAGE' => $author['IMAGE_URL'],
						'TITLE' => !empty($activityEmailMeta['__email'])
							? mb_strtolower(trim($activityEmailMeta['__email']))
							: $author['NAME_FORMATTED'],
					));
				}
				else
				{
					foreach ($activity['COMMUNICATIONS'] as $item)
					{
						$activity['ITEM_TO'][] = array(
							'IMAGE' => $item['IMAGE_URL'],
							'TITLE' => $item['VALUE'],
							'URL'   => $item['VIEW_URL'],
						);
					}
				}
			}
		}

		// unknown rcpt
		$suggested = array();
		if (!empty($unknownEmails))
		{
			$subFilter = array();
			foreach ($unknownEmails as $item)
				$subFilter[] = array('RAW_VALUE' => $item);
			$res = \CCrmFieldMulti::getList(
				array(),
				array(
					'ENTITY_ID' => 'LEAD|CONTACT|COMPANY',
					'TYPE_ID'   => 'EMAIL',
					'FILTER'    => $subFilter,
				)
			);

			while ($item = $res->fetch())
			{
				$itemTypeId = \CCrmOwnerType::resolveId($item['ENTITY_ID']);
				$itemEmail = mb_strtolower(trim($item['VALUE']));

				if (!empty($suggested[$itemEmail]) && $typesPriority[$suggested[$itemEmail]['ENTITY_TYPE_ID']] < $typesPriority[$itemTypeId])
					continue;

				$suggested[$itemEmail] = array(
					'ENTITY_ID'      => (int) $item['ELEMENT_ID'],
					'ENTITY_TYPE_ID' => $itemTypeId,
					'VALUE'          => $itemEmail,
					'TYPE'           => 'EMAIL',
				);
			}

			foreach ($suggested as $k => $item)
			{
				\CrmActivityEmailComponent::prepareCommunication($item);
				$suggested[$k] = $item;
			}
		}

		foreach (array('REPLY_TO', 'REPLY_ALL', 'REPLY_CC') as $field)
		{
			foreach ($activity[$field] as $k => $item)
			{
				if (is_array($item))
					continue;

				$activity[$field][$k] = array_key_exists($item, $suggested)
					? $suggested[$item]
					: array(
						'TYPE'  => 'EMAIL',
						'VALUE' => $item,
					);
			}
		}

		$activity['REPLY_TO']  = array_values($activity['REPLY_TO']);
		$activity['REPLY_ALL'] = array_values($activity['REPLY_ALL']);
		$activity['REPLY_CC']  = array_values($activity['REPLY_CC']);
	}

	public static function prepareCommunication(&$item)
	{
		\CCrmActivity::prepareCommunicationInfo($item);

		$entityTypes = array(
			'\CCrmContact' => \CCrmOwnerType::Contact,
			'\CCrmCompany' => \CCrmOwnerType::Company,
		);
		if ($entityClass = array_search($item['ENTITY_TYPE_ID'], $entityTypes))
		{
			$entity = $entityClass::getListEx(
				array(),
				array('ID' => $item['ENTITY_ID']),
				false, false,
				array('PHOTO', 'LOGO')
			)->fetch();

			if (!empty($entity) and $entity['PHOTO'] > 0 || $entity['LOGO'] > 0)
			{
				$fileInfo = \CFile::resizeImageGet(
					$entity['PHOTO'] ?: $entity['LOGO'],
					array('width' => 38, 'height' => 38),
					BX_RESIZE_IMAGE_EXACT, false
				);
				$item['IMAGE_URL'] = !empty($fileInfo['src']) ? $fileInfo['src'] : '';
			}
		}
	}

	public static function prepareMailboxes()
	{
		global $USER;

		static $mailboxes;

		if (is_null($mailboxes))
		{
			\CBitrixComponent::includeComponentClass('bitrix:main.mail.confirm');
			$mailboxes = \MainMailConfirmComponent::prepareMailboxes();

			$lastEmail = \CUserOptions::getOption('crm', 'activity_email_addresser', '');
			if (check_email($lastEmail))
			{
				$inList = false;

				$lastEmail = mb_strtolower(trim($lastEmail));
				foreach ($mailboxes as $k => $item)
				{
					if (empty($item['name']))
						continue;

					if (mb_strtolower(trim(sprintf('%s <%s>', $item['name'], $item['email']))) == $lastEmail)
					{
						$inList = $k;
						break;
					}
				}

				if ($inList === false)
				{
					if (preg_match('/.*?[<\[\(](.+?)[>\]\)].*/i', $lastEmail, $matches))
						$lastEmail = $matches[1];

					$lastEmail = mb_strtolower(trim($lastEmail));
					foreach ($mailboxes as $k => $item)
					{
						if (mb_strtolower($item['email']) == $lastEmail)
						{
							$inList = $k;
							break;
						}
					}
				}

				if ($inList !== false)
					array_unshift($mailboxes, reset(array_splice($mailboxes, $k, 1)));
			}
		}

		return $mailboxes;
	}

}
