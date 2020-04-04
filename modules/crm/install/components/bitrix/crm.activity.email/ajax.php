<?php

use Bitrix\Main\Localization\Loc;

define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__DIR__.'/class.php');

class CrmActivityEmailAjax
{

	static $crmAvailable   = false;
	static $limitedLicense = false;

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!is_object($USER) || !$USER->isAuthorized())
			$error = getMessage('CRM_ACT_EMAIL_AUTH');

		if ($error === false)
		{
			if (!CModule::includeModule('crm'))
				$error = getMessage('CRM_ACT_EMAIL_NOCRM');
		}

		\CUtil::jsPostUnescape();

		if ($error === false)
		{
			$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;

			switch ($act)
			{
				case 'log':
					$result = (array) self::executeLog($error);
					break;
				case 'logitem':
					$result = (array) self::executeLogItem($error);
					break;
				case 'newfrom':
					$result = (array) self::executeAddFromEmail($error);
					break;
				default:
					$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
			}
		}

		self::returnJson(array_merge(array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => $error
		), $result));
	}

	private static function executeLog(&$error)
	{
		$error = false;

		$itemId = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : false;
		if (!$itemId)
			$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');

		if ($error === false)
		{
			$params = !empty($_REQUEST['log']) ? $_REQUEST['log'] : false;
			if (!empty($params) && preg_match('/([ab])(\d+)/i', $params, $matches))
			{
				$type = strtoupper($matches[1]);
				$offset = (int) $matches[2];
			}
			else
			{
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
			}
		}

		if ($error === false)
		{
			$activity = \CCrmActivity::getList(
				array(),
				array('=ID' => $itemId),
				false, false,
				array('ID', 'THREAD_ID', 'START_TIME')
			)->fetch();

			if (empty($activity))
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$filter = array('!ID' => $activity['ID'], 'THREAD_ID' => $activity['THREAD_ID']);

			if ($type == 'A')
			{
				$filter['>START_TIME'] = $activity['START_TIME'];
				$order = array('START_TIME' => 'ASC', 'ID' => 'ASC');
			}
			else
			{
				$filter['<=START_TIME'] = $activity['START_TIME'];
				$order = array('START_TIME' => 'DESC', 'ID' => 'DESC');
			}

			$pageSize = !empty($_REQUEST['size']) ? (int) $_REQUEST['size'] : 5;
			$res = \CCrmActivity::getList(
				$order, $filter, false, false,
				array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID', 'SETTINGS'),
				array('QUERY_OPTIONS' => array('OFFSET' => $offset, 'LIMIT' => $pageSize))
			);

			$actIds  = array();
			$authIds = array();

			$log = array();
			while ($item = $res->fetch())
			{
				$log[] = $item;

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
		}

		if (!empty($log))
		{
			if ($type == 'A')
				$log = array_reverse($log);

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

					$clients[$item['ACTIVITY_ID']] = $item;
				}
			}

			$authors = array();

			if (!empty($authIds))
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
					'filter' => array('=ID' => array_unique($authIds)),
				));

				$nameFormat = \CSite::getNameFormat(null, $_REQUEST['site_id'] ?: '');
				while ($item = $res->fetch())
				{
					$item['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $item, true, false);
					$authors[$item['ID']] = $item;
				}
			}

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

				$log[$i] = $item;
			}

			ob_start();

			$isSlider = $_REQUEST['template'] == 'slider';
			foreach ($log as $item)
			{
				$datetimeFormat = \CModule::includeModule('intranet') ? \CIntranetUtils::getCurrentDatetimeFormat() : false;
				$startDatetimeFormatted = \CComponentUtil::getDateTimeFormatted(
					makeTimeStamp($item['START_TIME']),
					$datetimeFormat,
					\CTimeZone::getOffset()
				);
				$readDatetimeFormatted = !empty($item['SETTINGS']['READ_CONFIRMED']) && $item['SETTINGS']['READ_CONFIRMED']
					? \CComponentUtil::getDateTimeFormatted(
						$item['SETTINGS']['READ_CONFIRMED']+\CTimeZone::getOffset(),
						$datetimeFormat,
						\CTimeZone::getOffset()
					) : null;
				?>
				<div class="crm-task-list-mail-item crm-activity-email-logitem-<?=intval($item['ID']) ?>"
					data-id="<?=intval($item['ID']) ?>" data-log="<?=htmlspecialcharsbx($type) ?>">
					<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
					<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
					<span class="crm-task-list-mail-item-user"
						<? if (!empty($item['LOG_IMAGE'])): ?> style="background: url('<?=htmlspecialcharsbx($item['LOG_IMAGE']) ?>'); background-size: 23px 23px; "<? endif ?>>
						</span>
					<span class="crm-task-list-mail-item-name"><?=htmlspecialcharsbx($item['LOG_TITLE']) ?></span>
					<span class="crm-task-list-mail-item-description"><?=htmlspecialcharsbx($item['SUBJECT']) ?></span>
					<span class="crm-task-list-mail-item-date <? if ($isSlider): ?> crm-activity-email-item-date<? endif ?>">
						<span class="crm-activity-email-item-date-short">
							<?=$startDatetimeFormatted ?>
						</span>
						<span class="crm-activity-email-item-date-full">
							<? if (\CCrmActivityDirection::Outgoing == $item['DIRECTION']): ?>
								<?=getMessage('CRM_ACT_EMAIL_VIEW_SENT', array('#DATETIME#' => $startDatetimeFormatted)) ?><!--
								--><? if (isset($item['SETTINGS']['IS_BATCH_EMAIL']) && !$item['SETTINGS']['IS_BATCH_EMAIL']): ?>,
									<? if (!empty($readDatetimeFormatted)): ?>
										<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_CONFIRMED', array('#DATETIME#' => $readDatetimeFormatted)) ?>
									<? else: ?>
										<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_AWAITING') ?>
									<? endif ?>
								<? endif ?>
							<? else: ?>
								<?=getMessage('CRM_ACT_EMAIL_VIEW_RECEIVED', array('#DATETIME#' => $startDatetimeFormatted)) ?>
							<? endif ?>
						</span>
					</span>
				</div>
				<div class="crm-task-list-mail-item-inner <? if (!$isSlider): ?>crm-task-list-mail-border-bottom<? endif ?> crm-activity-email-details-<?=intval($item['ID']) ?> <? if ($isSlider): ?> crm-task-list-mail-item-inner-slider<? endif ?>"
					style="display: none; text-align: center; " data-id="<?=intval($item['ID']) ?>" data-empty="1">
					<div class="crm-task-list-mail-item-loading <? if ($isSlider): ?>crm-task-list-mail-border-bottom<? endif ?>"></div>
				</div>
				<?
			}

			$html = ob_get_clean();

			return array('html' => $html, 'count' => count($log));
		}

		return array('html' => '', 'count' => 0);
	}

	private static function executeLogItem(&$error)
	{
		global $APPLICATION, $USER;

		$error = false;

		$itemId = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : false;
		if (!$itemId)
			$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');

		$userId = $USER->getId();

		if ($error === false)
		{
			$activity = \CCrmActivity::getByID($itemId);
			if (empty($activity))
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			switch ((int) $activity['DESCRIPTION_TYPE'])
			{
				case \CCrmContentType::BBCode:
					$parser = new CTextParser();
					$activity['DESCRIPTION_HTML'] = $parser->convertText($activity['DESCRIPTION']);
					break;
				case \CCrmContentType::Html:
					$activity['DESCRIPTION_HTML'] = $activity['DESCRIPTION'];
					break;
				default:
					$activity['DESCRIPTION_HTML'] = preg_replace(
						'/[\r\n]+/'.BX_UTF_PCRE_MODIFIER, '<br>',
						htmlspecialcharsbx($activity['DESCRIPTION'])
					);
			}

			$res = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
				'filter' => array('=ID' => array($userId, $activity['AUTHOR_ID'], $activity['RESPONSIBLE_ID'])),
			));

			$authors = array();
			$nameFormat = \CSite::getNameFormat(null, $_REQUEST['site_id'] ?: '');
			while ($item = $res->fetch())
			{
				$item['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $item, true, false);

				$authors[$item['ID']] = $item;
			}

			$authorId = !empty($authors[$activity['AUTHOR_ID']]) ? $activity['AUTHOR_ID'] : $activity['RESPONSIBLE_ID'];

			foreach (array($authorId, $userId) as $uid)
			{
				if (!array_key_exists('IMAGE_URL', $authors[$uid]))
				{
					$preview = \CFile::resizeImageGet(
						$authors[$uid]['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
						BX_RESIZE_IMAGE_EXACT, false
					);

					$authors[$uid]['IMAGE_URL'] = $preview['src'];
				}
			}

			$activity['__author'] = $authors[$authorId];

			$entityTypes = array(
				'\CCrmContact' => \CCrmOwnerType::Contact,
				'\CCrmCompany' => \CCrmOwnerType::Company,
			);
			$activity['COMMUNICATIONS'] = array();
			foreach (\CCrmActivity::getCommunications($activity['ID']) as $item)
			{
				\CCrmActivity::prepareCommunicationInfo($item);

				$item['VIEW_URL'] = \CCrmOwnerType::GetEntityShowPath($item['ENTITY_TYPE_ID'], $item['ENTITY_ID']);

				if (empty($activity['COMMUNICATIONS']) || \CCrmActivityDirection::Outgoing == $activity['DIRECTION'])
				{
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
							$preview = \CFile::resizeImageGet(
								$entity['PHOTO'] ?: $entity['LOGO'],
								array('width' => 38, 'height' => 38),
								BX_RESIZE_IMAGE_EXACT, false
							);
							$item['IMAGE_URL'] = !empty($preview['src']) ? $preview['src'] : '';
						}
					}
				}

				$activity['COMMUNICATIONS'][] = $item;
			}

			\CCrmActivity::prepareStorageElementIDs($activity);
			\CCrmActivity::prepareStorageElementInfo($activity);

			$activity['__files'] = array();

			if (!empty($activity['FILES']))
			{
				foreach ($activity['FILES'] as $item)
				{
					$activity['__files'][] = array(
						'fileName' => $item['fileName'],
						'viewURL'  => $item['fileURL'],
						'fileSize' => \CFile::formatSize($item['fileSize']),
					);
				}
			}
			elseif(!empty($activity['WEBDAV_ELEMENTS']))
			{
				foreach($activity['WEBDAV_ELEMENTS'] as $item)
				{
					$activity['__files'][] = array(
						'fileName' => $item['NAME'],
						'viewURL'  => $item['VIEW_URL'],
						'fileSize' => $item['SIZE'],
					);
				}
			}
			elseif(!empty($activity['DISK_FILES']))
			{
				foreach($activity['DISK_FILES'] as $item)
				{
					$activity['__files'][] = array(
						'fileName' => $item['NAME'],
						'viewURL'  => $item['VIEW_URL'],
						'fileSize' => $item['SIZE'],
					);
				}
			}

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
				$templates[] = array(
					'id'         => $item['ID'],
					'title'      => $item['TITLE'],
					'scope'      => $item['SCOPE'],
					'entityType' => \CCrmOwnerType::resolveName($activity['ENTITY_TYPE_ID']),
				);
			}

			ob_start();

			$APPLICATION->includeComponent(
				'bitrix:crm.activity.email.body',
				$_REQUEST['template'] == 'slider' ? 'slider' : '',
				array(
					'ACTIVITY'        => $activity,
					'TEMPLATES'       => $templates,
					'LOADED_FROM_LOG' => 'Y',
				),
				false,
				array(
					'HIDE_ICONS'       => 'Y',
					'ACTIVE_COMPONENT' => 'Y',
				)
			);

			$html = ob_get_clean();

			return array('html' => $html);
		}
	}

	private static function executeAddFromEmail(&$error)
	{
		global $USER;

		$error = false;

		$name   = trim($_REQUEST['name']);
		$email  = strtolower(trim($_REQUEST['email']));
		$code   = strtolower(trim($_REQUEST['code']));
		$public = $_REQUEST['public'] == 'Y';

		if (!check_email($email, true))
			$error = getMessage(empty($email) ? 'CRM_ACT_EMAIL_NEW_FROM_EMPTY_EMAIL' : 'CRM_ACT_EMAIL_NEW_FROM_INVALID_EMAIL');

		if ($error === false)
		{
			$pending = \CUserOptions::getOption('mail', 'pending_from_emails', null);
			if (!is_array($pending))
				$pending = array();

			foreach ($pending as $key => $item)
			{
				if (time()-$item['time'] > 60*60*24*7)
					unset($pending[$key]);
			}

			\CUserOptions::setOption('mail', 'pending_from_emails', $pending);

			$key = hash('crc32b', strtolower($name).$email);

			if (empty($code))
			{
				$pending[$key] = array(
					'name'   => $name,
					'email'  => $email,
					'public' => $public,
					'code'   => \Bitrix\Main\Security\Random::getStringByCharsets(5, '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'),
					'time'   => time(),
				);
				\CUserOptions::setOption('mail', 'pending_from_emails', $pending);

				$sendResult = \CEvent::sendImmediate(
					'CRM_EMAIL_CONFIRM',
					SITE_ID,
					array(
						'EMAIL'         => $email,
						'CONFIRM_CODE'  => $pending[$key]['code'],
					)
				);
			}
			else
			{
				if (empty($pending[$key]) || strtolower($pending[$key]['code']) != $code)
					$error = getMessage('CRM_ACT_EMAIL_NEW_FROM_INVALID_CODE');

				if ($error === false)
				{
					$entry = \CUserOptions::getList(false, array(
						'USER_ID'  => $public ? 0 : $USER->getId(),
						'CATEGORY' => 'mail',
						'NAME'     => 'confirmed_from_emails',
						'COMMON'   => $public ? 'Y' : 'N',
					))->fetch();
					if (!empty($entry['VALUE']))
						$confirmed = unserialize($entry['VALUE']);

					if (empty($confirmed) || !is_array($confirmed))
						$confirmed = array();

					$confirmed[$key] = array(
						'name'  => $name,
						'email' => $email,
					);
					\CUserOptions::setOption('mail', 'confirmed_from_emails', $confirmed, $public);

					unset($pending[$key]);
					\CUserOptions::setOption('mail', 'pending_from_emails', $pending);

					return array();
				}
			}
		}
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->restartBuffer();

		header('Content-Type: application/x-javascript; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($data);
	}

}

CrmActivityEmailAjax::execute();

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';
