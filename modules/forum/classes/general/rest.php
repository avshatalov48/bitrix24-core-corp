<?
use Bitrix\Main\Loader;

if(!CModule::IncludeModule('rest'))
	return;

class CForumRestService extends IRestService
{
	const PERM_DENY = 'D';
	const PERM_READ = 'R';
	const PERM_WRITE = 'W';

	public static function OnRestServiceBuildDescription()
	{
		return array(
			"forum" => array(
				"forum.message.user.get" =>  array('callback' => array(__CLASS__, 'getUserMessage'), 'options' => array('private' => true)),
				"forum.message.delete" => array(__CLASS__, "deleteMessage")
			)
		);
	}

	public static function getUserMessage($arParams, $offset, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		global $USER;

		$result = array(
			'MESSAGES' => array(),
			'FILES' => array(),
		);

		$userId = (
			isset($arParams["USER_ID"])
			&& intval($arParams["USER_ID"]) > 0
			&& self::isAdmin()
				? $arParams["USER_ID"]
				: $USER->getId()
		);

		$otherUserMode = ($userId != $USER->getId());

		if ($userId <= 0)
		{
			throw new Bitrix\Rest\RestException("User ID can't be empty", "ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['FIRST_ID']))
		{
			$options['FIRST_ID'] = intval($arParams['FIRST_ID']);
		}
		else
		{
			$options['LAST_ID'] = isset($arParams['LAST_ID']) && intval($arParams['LAST_ID']) > 0? intval($arParams['LAST_ID']): 0;
		}

		$options['LIMIT'] = isset($arParams['LIMIT'])? (intval($arParams['LIMIT']) > 1000? 1000: intval($arParams['LIMIT'])): 100;

		$filter = Array(
			'=AUTHOR_ID' => $userId
		);

		if (isset($options['FIRST_ID']))
		{
			$order = array();

			if (intval($options['FIRST_ID']) > 0)
			{
				$filter['>ID'] = $options['FIRST_ID'];
			}
		}
		else
		{
			$order = array('ID' => 'DESC');

			if (isset($options['LAST_ID']) && intval($options['LAST_ID']) > 0)
			{
				$filter['<ID'] = intval($options['LAST_ID']);
			}
		}

		$res = Bitrix\Forum\MessageTable::getList(array(
			'filter' => $filter,
			'select' => array(
				'ID', 'POST_DATE', 'POST_MESSAGE', 'UF_FORUM_MESSAGE_DOC'
			),
			'order' => $order,
			'limit' => $options['LIMIT']
		));

		$attachedIdList = array();
		$messageAttachedList = array();

		while($messageFields = $res->fetch())
		{
			$result['MESSAGES'][$messageFields['ID']] = array(
				'ID' => (int)$messageFields['ID'],
				'MESSAGE_ID' => (int)$messageFields['ID'],
				'DATE' => $messageFields['POST_DATE'],
				'MESSAGE' => ($otherUserMode ? '' : (string)$messageFields['POST_MESSAGE']),
				'ATTACH' => array()
			);

			if (!empty($messageFields['UF_FORUM_MESSAGE_DOC']))
			{
				if (is_array($messageFields['UF_FORUM_MESSAGE_DOC']))
				{
					$attached = $messageFields['UF_FORUM_MESSAGE_DOC'];
				}
				elseif (intval($messageFields['UF_FORUM_MESSAGE_DOC']) > 0)
				{
					$attached = array(intval($messageFields['UF_FORUM_MESSAGE_DOC']));
				}
				else
				{
					$attached = array();
				}

				if (!empty($attached))
				{
					$attachedIdList = array_merge($attachedIdList, $attached);
				}

				$messageAttachedList[$messageFields['ID']] = $attached;
			}
		}

		$attachedObjectList = array();

		if (
			!empty($attachedIdList)
			&& Loader::includeModule('disk')
		)
		{
			$res = Bitrix\Disk\AttachedObject::getList(array(
				'filter' => array(
					'@ID' => array_unique($attachedIdList)
				),
				'select' => array('ID', 'OBJECT_ID')
			));
			while($attachedObjectFields = $res->fetch())
			{
				$diskObjectId = $attachedObjectFields['OBJECT_ID'];

				if ($fileData = self::getFileData($diskObjectId))
				{
					$attachedObjectList[$attachedObjectFields['ID']] = $diskObjectId;
					$result['FILES'][$diskObjectId] = $fileData;
				}
			}
		}

		foreach ($result['MESSAGES'] as $key => $value)
		{
			if ($value['DATE'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$result['MESSAGES'][$key]['DATE'] = date('c', $value['DATE']->getTimestamp());
			}

			if (!empty($messageAttachedList[$key]))
			{
				foreach($messageAttachedList[$key] as $attachedId)
				{
					if (!empty($attachedObjectList[$attachedId]))
					{
						$result['MESSAGES'][$key]['ATTACH'][] = $attachedObjectList[$attachedId];
					}
				}
			}

			$result['MESSAGES'][$key] = array_change_key_case($result['MESSAGES'][$key], CASE_LOWER);
		}

		$result['MESSAGES'] = array_values($result['MESSAGES']);
		$result['FILES'] = self::convertFileData($result['FILES']);

		return $result;
	}

	public static function deleteMessage($arFields)
	{
		global $USER;
		static $obCache = null;

		$messageId = intval($arFields['MESSAGE_ID']);

		if($messageId <= 0)
		{
			throw new Exception('Wrong message ID');
		}

		$currentUserId = (
			isset($arFields["USER_ID"])
			&& intval($arFields["USER_ID"]) > 0
			&& self::isAdmin()
				? $arFields["USER_ID"]
				: $USER->getId()
		);

		$arMessage = self::getForumMessageFields($messageId);
		if (empty($arMessage))
		{
			throw new Exception('No message found');
		}

		$currentUserPerm = self::getForumMessagePerm(array(
			'USER_ID' => $currentUserId,
			'MESSAGE_ID' => $messageId
		));

		if ($currentUserPerm < self::PERM_WRITE)
		{
			throw new Exception('No write perms');
		}

		if (
			($result = \CForumMessage::Delete($messageId))
			&& Loader::includeModule('socialnetwork')
		)
		{
			$logIdList = array();

			$res = Bitrix\Socialnetwork\LogTable::getList(array(
				'filter' => array(
					'=SOURCE_ID' => $messageId,
					'@EVENT_ID' => array('forum') // replace with provider getEventId
				),
				'select' => array('ID')
			));
			while ($logFields = $res->fetch())
			{
				if (CSocNetLog::delete($logFields['ID']))
				{
					$logIdList[] = intval($logFields['ID']);
				}
			}

			if (empty($logIdList))
			{
				$res = Bitrix\Socialnetwork\LogCommentTable::getList(array(
					'filter' => array(
						'=SOURCE_ID' => $messageId,
						'@EVENT_ID' => array('forum', 'tasks_comment', 'calendar_comment', 'timeman_entry_comment', 'report_comment', 'photo_comment', 'wiki_comment', 'lists_new_element_comment') // replace with provider getEventId
					),
					'select' => array('ID', 'LOG_ID')
				));
				while ($logCommentFields = $res->fetch())
				{
					if (CSocNetLogComments::delete($logCommentFields['ID']))
					{
						$logIdList[] = intval($logFields['LOG_ID']);
					}
				}
			}

			if (!empty($logIdList))
			{
				foreach($logIdList as $logId)
				{
					if ($obCache === null)
					{
						$obCache = new CPHPCache;
					}
					$obCache->CleanDir("/sonet/log/".intval($logId / 1000)."/".$logId."/comments/");
				}
			}
		}

		return (bool)$result;
	}

	private static function getForumMessagePerm($arFields)
	{
		global $USER;

		$result = self::PERM_DENY;

		$messageId = $arFields['MESSAGE_ID'];

		$currentUserId = (
			isset($arFields["USER_ID"])
			&& intval($arFields["USER_ID"]) > 0
			&& $USER->isAdmin()
				? $arFields["USER_ID"]
				: $USER->getId()
		);

		$arMessage = self::getForumMessageFields($messageId);
		if (empty($arMessage))
		{
			return $result;
		}

		if (
			$arMessage["AUTHOR_ID"] == $currentUserId
			|| (
				Loader::includeModule('socialnetwork')
				&& CSocNetUser::isUserModuleAdmin($currentUserId, SITE_ID)
			)
		)
		{
			$result = self::PERM_WRITE;
		}

		return $result;
	}

	private static function getForumMessageFields($messageId)
	{
		$result = array();

		$res = \Bitrix\Forum\MessageTable::getList(array(
			'filter' => array(
				'=ID' => $messageId
			),
			'select' => array('*')
		));
		if ($messageFields = $res->fetch())
		{
			$result = $messageFields;
		}
		return $result;
	}

	private static function isAdmin()
	{
		global $USER;
		return (
			$USER->isAdmin()
			|| (
				Loader::includeModule('bitrix24')
				&& \CBitrix24::isPortalAdmin($USER->getId())
			)
		);
	}

	private static function getFileData($diskObjectId)
	{
		$result = false;

		$diskObjectId = intval($diskObjectId);
		if ($diskObjectId <= 0)
		{
			return $result;
		}

		if ($fileModel = \Bitrix\Disk\File::getById($diskObjectId))
		{
			/** @var \Bitrix\Disk\File $fileModel */
			$contentType = 'file';
			$imageParams = false;
			if (\Bitrix\Disk\TypeFile::isImage($fileModel->getName()))
			{
				$contentType = 'image';
				$params = $fileModel->getFile();
				$imageParams = Array(
					'width' => (int)$params['WIDTH'],
					'height' => (int)$params['HEIGHT'],
				);
			}
			else if (\Bitrix\Disk\TypeFile::isVideo($fileModel->getName()))
			{
				$contentType = 'video';
				$params = $fileModel->getView()->getPreviewData();
				$imageParams = Array(
					'width' => (int)$params['WIDTH'],
					'height' => (int)$params['HEIGHT'],
				);
			}

			$isImage = \Bitrix\Disk\TypeFile::isImage($fileModel);
			$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

			$result = array(
				'id' => (int)$fileModel->getId(),
				'date' => $fileModel->getCreateTime(),
				'type' => $contentType,
				'name' => $fileModel->getName(),
				'size' => (int)$fileModel->getSize(),
				'image' => $imageParams,
				'authorId' => (int)$fileModel->getCreatedBy(),
				'authorName' => CUser::FormatName(CSite::getNameFormat(false), $fileModel->getCreateUser(), true, true),
				'urlPreview' => (
					$fileModel->getPreviewId()
						? $urlManager->getUrlForShowPreview($fileModel, [ 'width' => 640, 'height' => 640])
						: (
							$isImage
								? $urlManager->getUrlForShowFile($fileModel, [ 'width' => 640, 'height' => 640])
								: null
						)
				),
				'urlShow' => ($isImage ? $urlManager->getUrlForShowFile($fileModel) : $urlManager->getUrlForDownloadFile($fileModel)),
				'urlDownload' => $urlManager->getUrlForDownloadFile($fileModel)
			);
		}

		return $result;
	}

	private static function convertFileData($fileData)
	{
		if (!is_array($fileData))
		{
			return array();
		}

		foreach ($fileData as $key => $value)
		{
			if ($value['date'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$fileData[$key]['date'] = date('c', $value['date']->getTimestamp());
			}

			foreach (['urlPreview', 'urlShow', 'urlDownload'] as $field)
			{
				$url = $fileData[$key][$field];
				if (is_string($url) && $url && mb_strpos($url, 'http') !== 0)
				{
					$fileData[$key][$field] = self::getPublicDomain().$url;
				}
			}
		}

		return $fileData;
	}

	private static function getPublicDomain()
	{
		static $result = null;
		if ($result === null)
		{
			$result = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : \Bitrix\Main\Config\Option::get("main", "server_name", $_SERVER['SERVER_NAME']));
		}

		return $result;
	}
}
?>