<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Livefeed extends Base
{
	const PROVIDER_ID = 'CRM_LF_MESSAGE';
	const PROVIDER_TYPE_ID_ENTRY = 'LOG_ENTRY';
	const PROVIDER_TYPE_ID_COMMENT = 'LOG_COMMENT';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_NAME');
	}
	
	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * Check if activity can be completed interactively by user.
	 * @return bool
	 */
	public static function isCompletable()
	{
		return false;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return false;
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Main\Result();
		if (
			$action == 'UPDATE'
			&& isset($fields['COMPLETED'])
			&& $fields['COMPLETED'] == 'N'
		)
		{
			$fields['PROVIDER_PARAMS'] = array(
				'NO_AUTOCOMPLETE' => 'Y'
			);

			if (
				is_array($params)
				&& isset($params["PREVIOUS_FIELDS"])
				&& is_array($params["PREVIOUS_FIELDS"])
				&& isset($params["PREVIOUS_FIELDS"]["PROVIDER_PARAMS"])
				&& is_array($params["PREVIOUS_FIELDS"]["PROVIDER_PARAMS"])
			)
			{
				$fields['PROVIDER_PARAMS'] = array_merge($params["PREVIOUS_FIELDS"]["PROVIDER_PARAMS"], $fields['PROVIDER_PARAMS']);
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_ENTRY'),
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_ENTRY,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Outgoing => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_ENTRY')
				)
			),
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT'),
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_COMMENT,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Outgoing => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_OUT'),
					\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_IN')
				)
			),
		);
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_ENTRY'),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_ENTRY
			),
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_OUT'),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_COMMENT,
				'DIRECTION' => \CCrmActivityDirection::Outgoing
			),
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_IN'),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_COMMENT,
				'DIRECTION' => \CCrmActivityDirection::Incoming
			)
		);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		$direction = (int)$direction;
		if ($providerTypeId === self::PROVIDER_TYPE_ID_ENTRY)
		{
			return Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_ENTRY');
		}
		elseif ($providerTypeId === self::PROVIDER_TYPE_ID_COMMENT)
		{
			if ($direction === \CCrmActivityDirection::Outgoing)
				return Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_OUT');

			return Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TYPE_LOG_COMMENT_IN');
		}

		return '';
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		if ($activity['PROVIDER_TYPE_ID'] === self::PROVIDER_TYPE_ID_ENTRY)
		{
			return self::renderViewEntry($activity);
		}
		elseif ($activity['PROVIDER_TYPE_ID'] === self::PROVIDER_TYPE_ID_COMMENT)
		{
			return self::renderViewComment($activity);
		}
		else
		{
			return '';
		}
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view of log entry.
	 */
	public static function renderViewEntry(array $activity)
	{
		global $USER_FIELD_MANAGER;

		static $blogPostEventIdList = null;

		if (Loader::includeModule('socialnetwork'))
		{
			$res = \CSocNetLog::getList(
				array(),
				array(
					'ID' => $activity['ASSOCIATED_ENTITY_ID']
				),
				false,
				array('nTopCount' => 1),
				array('ID', 'SOURCE_ID', 'EVENT_ID')
			);

			if ($blogPostEventIdList === null)
			{
				$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
				$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();
			}

			if (
				($log = $res->fetch())
				&& in_array($log['EVENT_ID'], $blogPostEventIdList)
			)
			{
				$activity["USERFIELDS"] = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $log["SOURCE_ID"], LANGUAGE_ID);
			}
			else
			{
				$activity["USERFIELDS"] = $USER_FIELD_MANAGER->getUserFields("SONET_LOG", $activity['ASSOCIATED_ENTITY_ID'], LANGUAGE_ID);
			}
		}

		$bbCodeParser = new \CTextParser();
		if (isset($activity['USERFIELDS']))
		{
			$bbCodeParser->allow['USERFIELDS'] = $activity['USERFIELDS'];
			$bbCodeParser->imageWidth = 550;
			$bbCodeParser->imageHeight = 550;
		}

		$link = self::getRenderLink($activity);
		$subject = self::getRenderSubject($activity);

		return '<div class="crm-task-list-live-feed">
				<div class="crm-task-list-live-feed-inner">
					'.$subject.'
					<p>
						'.$bbCodeParser->convertText($activity['DESCRIPTION']).'
					</p>'.
					$link
					.'
				</div>
			</div><!--crm-task-live-feed-->';
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view of log comment.
	 */
	public static function renderViewComment(array $activity)
	{
		global $USER_FIELD_MANAGER;

		if (Loader::includeModule('socialnetwork'))
		{
			$res = \CSocNetLogComments::getList(
				array(),
				array(
					'ID' => $activity['ASSOCIATED_ENTITY_ID']
				),
				false,
				array('nTopCount' => 1),
				array('ID', 'SOURCE_ID', 'EVENT_ID')
			);
			if (
				($comment = $res->fetch())
				&& in_array($comment['EVENT_ID'], array('blog_comment'))
			)
			{
				$activity["USERFIELDS"] = $USER_FIELD_MANAGER->getUserFields("BLOG_COMMENT", $comment["SOURCE_ID"], LANGUAGE_ID);
			}
			else
			{
				$activity["USERFIELDS"] = $USER_FIELD_MANAGER->getUserFields("SONET_COMMENT", $activity['ASSOCIATED_ENTITY_ID'], LANGUAGE_ID);
			}
		}

		$bbCodeParser = new \CTextParser();
		if (isset($activity['USERFIELDS']))
		{
			$bbCodeParser->allow['USERFIELDS'] = $activity['USERFIELDS'];
			$bbCodeParser->imageWidth = 500;
			$bbCodeParser->imageHeight = 500;
		}

		$link = self::getRenderLink($activity);
		$parentActivity = self::getRenderParentActivity($activity);
		$authorName = \CUser::formatName(\CSite::getNameFormat(false), array(
			'NAME' => $activity['RESPONSIBLE_NAME'],
			'LAST_NAME' => $activity['RESPONSIBLE_LAST_NAME'],
			'SECOND_NAME' => $activity['RESPONSIBLE_SECOND_NAME'],
			'LOGIN' => $activity['RESPONSIBLE_LOGIN']
		), true);

		$authorPhoto = \CFile::resizeImageGet(
			$activity["RESPONSIBLE_PERSONAL_PHOTO"],
			array('width' => 38, 'height' => 38),
			BX_RESIZE_IMAGE_EXACT,
			false
		);

		$avatarPath = (is_array($authorPhoto) && isset($authorPhoto['src']) ? $authorPhoto['src'] : '');

		return '<div class="crm-task-list-live-comment">
				'.$parentActivity.'
				<div class="crm-task-list-live-comment-inner">
					<div class="crm-task-list-live-comment-inner-header">
						<span class="crm-task-list-live-comment-inner-user"'.(!empty($avatarPath) ? ' style="background: url(\''.$avatarPath.'\')"' : '').'></span>
						<span class="crm-task-list-live-comment-inner-body">
							<span class="crm-task-list-live-comment-inner-user-container">
								<span class="crm-task-list-live-comment-inner-user-info">
									<a href="" class="crm-task-list-live-comment-inner-user-title">'.$authorName.'</a>
								</span>
							</span>
							<p>
								'.$bbCodeParser->convertText($activity['DESCRIPTION']).'
							</p>'.
							$link
							.'
						</span>
					</div>
				</div>
			</div><!--crm-task-live-comment-->';
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Log subject.
	 */
	public static function getSubject(array $activity)
	{
		return (
			$activity['PROVIDER_TYPE_ID'] == self::PROVIDER_TYPE_ID_ENTRY
			&& $activity['SUBJECT'] != Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TITLE_ENTRY')
				? $activity['SUBJECT']
				: ''
		);
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Log rendered subject.
	 */
	public static function getRenderSubject(array $activity)
	{
		$subject = self::getSubject($activity);

		return (
			!empty($subject)
				? '<h4 class="crm-task-list-live-feed-title">'.$subject.'</h4>'
				: ''
		);
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Log rendered author.
	*/
	public static function getRenderAuthor(array $activity)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_AUTHOR_ACTION'.$activity['RESPONSIBLE_PERSONAL_GENDER'], array(
			'#USER_NAME#' => \CUser::formatName(\CSite::getNameFormat(false), array(
				'NAME' => $activity['RESPONSIBLE_NAME'],
				'LAST_NAME' => $activity['RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME' => $activity['RESPONSIBLE_SECOND_NAME'],
				'LOGIN' => $activity['RESPONSIBLE_LOGIN']
			), true)
		));
	}

	/**
	 * @param string $date date/time in site format.
	 * @return string Formatted date/time.
	 */
	public static function getRenderDateTime($dateTime)
	{
		$result = '';
		if (!empty($dateTime))
		{
			$result = \CComponentUtil::getDateTimeFormatted(MakeTimeStamp($dateTime));
		}

		return $result;
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Parent Log activity.
	 */
	public static function getRenderParentActivity(array $activity)
	{
		$result = '';
		if (
			$activity['PROVIDER_TYPE_ID'] == self::PROVIDER_TYPE_ID_COMMENT
			&& intval($activity['PARENT_ID']) > 0
		)
		{
			if ($parentActivity = \CCrmActivity::getByID($activity['PARENT_ID'], false))
			{
				$subject = self::getSubject($parentActivity);
				if (!empty($subject))
				{
					$message = $subject;
				}
				else
				{
					$bbCodeParser = new \CTextParser();
					$message = strip_tags($bbCodeParser->convert4mail($parentActivity['DESCRIPTION']));
				}

				$dateTime = self::getRenderDateTime($parentActivity['CREATED']);
				$authorName = self::getRenderAuthor($parentActivity);

				$authorPhoto = \CFile::resizeImageGet(
					$parentActivity["RESPONSIBLE_PERSONAL_PHOTO"],
					array('width' => 22, 'height' => 22),
					BX_RESIZE_IMAGE_EXACT,
					false
				);

				$avatarPath = (is_array($authorPhoto) && isset($authorPhoto['src']) ? $authorPhoto['src'] : '');

				$result .= '
					<div class="crm-task-list-live-comment-container">
						<div class="crm-task-list-live-comment-item">
							<span class="crm-task-list-live-comment-user"'.(!empty($avatarPath) ? ' style="background: url(\''.$avatarPath.'\')"' : '').'></span>
							<span class="crm-task-list-live-comment-name">'.$authorName.'</span>
							<span class="crm-task-list-live-comment-description">'.$message.'</span>
							<span class="crm-task-list-live-comment-date">'.$dateTime.'</span>
						</div><!--crm-task-list-live-comment-->
					</div><!--crm-task-list-live-comment-container-->
				';
			}
		}

		return $result;
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Log entry/comment link.
	 */
	public static function getRenderLink(array $activity)
	{
		$result = '';

		$linkTitle = (
			$activity['PROVIDER_TYPE_ID'] == self::PROVIDER_TYPE_ID_ENTRY
				? Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_LINK_ENTRY')
				: Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_LINK_COMMENT')
		);

		$url = self::getRenderUrl($activity);

		$result = (
			!empty($url)
				? '<div class="crm-task-list-live-feed-link"><a href="'.$url.'" class="crm-task-list-live-feed-link-item">'.$linkTitle.'</a></div>'
				: ''
		);

		return $result;
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Log entry/comment source URL.
	 */
	public static function getRenderUrl(array $activity)
	{
		$result = '';

		if (intval($activity["ASSOCIATED_ENTITY_ID"]) > 0)
		{
			if ($activity["PROVIDER_TYPE_ID"] == self::PROVIDER_TYPE_ID_ENTRY)
			{
				$result = '/crm/stream/?log_id='.$activity["ASSOCIATED_ENTITY_ID"];
			}
			elseif ($activity["PROVIDER_TYPE_ID"] == self::PROVIDER_TYPE_ID_COMMENT)
			{
				$res = \CSocNetLogComments::getList(
					array(),
					array(
						"ID" => $activity["ASSOCIATED_ENTITY_ID"]
					),
					false,
					false,
					array("ID", "LOG_ID")
				);
				if (
					($comment = $res->fetch())
					&& (intval($comment["LOG_ID"]) > 0)
				)
				{
					$result = '/crm/stream/?log_id='.$comment["LOG_ID"].'?commentId='.$activity["ASSOCIATED_ENTITY_ID"].'#com'.$activity["ASSOCIATED_ENTITY_ID"];
				}
			}
		}

		return $result;
	}

	public static function addComment($comment, $log)
	{
		$res = \CCrmActivity::getList(
			array(),
			array(
				'=PROVIDER_ID' => self::PROVIDER_ID,
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_ENTRY,
				'=ASSOCIATED_ENTITY_ID' => $log['ID'],
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID', 'COMMUNICATIONS')
		);

		if ($parentActivity = $res->fetch())
		{
			$fields = array(
				"TYPE" => "COMMENT_OUT",
				"COMMUNICATIONS" => $parentActivity['COMMUNICATIONS'],
				"BINDINGS" => \CCrmActivity::getBindings($parentActivity['ID']),
				"MESSAGE" => $comment['MESSAGE'],
				"USER_ID" => $comment['USER_ID'],
				"RESPONSIBLE_USER_ID" => $log['USER_ID'],
				"ENTITY_ID" => $comment["ID"],
				"PARENT_ID" => $parentActivity['ID']
			);

			$res = \Bitrix\Main\UserTable::getList(array(
				'filter' => array("=ID" => $comment['USER_ID']),
				'select' => array("EXTERNAL_AUTH_ID")
			));

			if (
				($user = $res->fetch())
				&& ($user["EXTERNAL_AUTH_ID"] == 'email')
			)
			{
				$fields["TYPE"] = "COMMENT_IN";
			}

			self::addActivity($fields);
		}
	}

	public static function addActivity($fields)
	{
		global $USER_FIELD_MANAGER;
		$result = false;

		$type = (
			!empty($fields["TYPE"])
				? $fields["TYPE"]
				: 'ENTRY'
		);

		$communications = (
			!empty($fields["COMMUNICATIONS"])
			&& is_array($fields["COMMUNICATIONS"])
				? $fields["COMMUNICATIONS"]
				: array()
		);

		$bindings = (
			!empty($fields["BINDINGS"])
			&& is_array($fields["BINDINGS"])
				? $fields["BINDINGS"]
				: array()
		);

		$message = (
			!empty($fields["MESSAGE"])
				? $fields["MESSAGE"]
				: ''
		);

		$title = (
			!empty($fields["TITLE"])
				? $fields["TITLE"]
				: ''
		);

		$userId = (
			!empty($fields["USER_ID"])
				? intval($fields["USER_ID"])
				: 0
		);

		$responsibleUserId = (
			!empty($fields["RESPONSIBLE_USER_ID"])
				? intval($fields["RESPONSIBLE_USER_ID"])
				: $userId
		);

		$entityId = (
			!empty($fields["ENTITY_ID"])
				? intval($fields["ENTITY_ID"])
				: 0
		);

		$parentId = (
			!empty($fields["PARENT_ID"])
				? intval($fields["PARENT_ID"])
				: 0
		);

		if (
			empty($communications)
			|| empty($message)
			|| $userId <= 0
			|| $responsibleUserId <= 0
			|| $entityId <= 0
		)
		{
			return $result;
		}

		if (!empty($bindings))
		{
			switch($type)
			{
				case 'COMMENT_IN':
				case 'BLOG_COMMENT_IN':
					$subject = Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TITLE_COMMENT_IN');
					$endTime = '';
					$completed = 'N';
					$direction = \CCrmActivityDirection::Incoming;
					$providerTypeId = self::PROVIDER_TYPE_ID_COMMENT;
					$ufEntity = ($type == 'BLOG_COMMENT_IN' ? 'BLOG_COMMENT' : 'SONET_COMMENT');
					$ufEntityId = $entityId;
					$entityId = ($type == 'BLOG_COMMENT_IN' ? self::getLogCommentId($entityId, 'blog_comment') : $entityId);
					$ufCode = ($type == 'BLOG_COMMENT_IN' ? 'UF_BLOG_COMMENT_FILE' : 'UF_SONET_COM_DOC');
					break;
				case 'COMMENT_OUT':
				case 'BLOG_COMMENT_OUT':
					$subject = Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TITLE_COMMENT_OUT');
					$endTime = '';
					$completed = 'Y';
					$direction = \CCrmActivityDirection::Outgoing;
					$providerTypeId = self::PROVIDER_TYPE_ID_COMMENT;
					$ufEntity = ($type == 'BLOG_COMMENT_OUT' ? 'BLOG_COMMENT' : 'SONET_COMMENT');
					$ufEntityId = $entityId;
					$entityId = ($type == 'BLOG_COMMENT_OUT' ? self::getLogCommentId($entityId, 'blog_comment') : $entityId);
					$ufCode = ($type == 'BLOG_COMMENT_OUT' ? 'UF_BLOG_COMMENT_FILE' : 'UF_SONET_COM_DOC');
					break;
				case 'ENTRY':
				case 'BLOG_POST':
					$subject = (!empty($title) ? $title : Loc::getMessage('CRM_ACTIVITY_PROVIDER_LIVEFEED_TITLE_ENTRY'));
					$endTime = null;
					$completed = 'Y';
					$direction = \CCrmActivityDirection::Outgoing;
					$providerTypeId = self::PROVIDER_TYPE_ID_ENTRY;
					$ufEntity = ($type == 'BLOG_POST' ? 'BLOG_POST' : 'SONET_LOG');
					$ufEntityId = ($type == 'BLOG_POST' ? self::getSourceEntryId($entityId) : $entityId);
					$ufCode = ($type == 'BLOG_POST' ? 'UF_BLOG_POST_FILE' : 'UF_SONET_LOG_DOC');
					break;
				default:
			}

			$uf = ($ufEntityId > 0 ? $USER_FIELD_MANAGER->getUserFields($ufEntity, $ufEntityId, LANGUAGE_ID, 0) : null);
			$fileIdList = array();

			if (
				is_array($uf)
				&& isset($uf[$ufCode])
				&& !empty($uf[$ufCode]["VALUE"])
				&& Loader::includeModule('disk')
			)
			{
				foreach ($uf[$ufCode]["VALUE"] as $attachedObjectId)
				{
					$attachedObject = \Bitrix\Disk\AttachedObject::loadById($attachedObjectId);
					if ($attachedObject)
					{
						$fileIdList[] = $attachedObject->getObjectId();
					}
				}
			}

			$activityFields = array(
				'TYPE_ID' =>  \CCrmActivityType::Provider,
				'PROVIDER_ID' => self::PROVIDER_ID,
				'SUBJECT' => $subject,
				'START_TIME' => convertTimeStamp((time() + \CTimeZone::getOffset()), 'FULL', SITE_ID),
				'END_TIME' => $endTime,
				'COMPLETED' => $completed,
				'PRIORITY' => \CCrmActivityPriority::Medium,
				'DESCRIPTION' => $message,
				'DESCRIPTION_TYPE' => \CCrmContentType::BBCode,
				'LOCATION' => '',
				'DIRECTION' => $direction,
				'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
				'BINDINGS' => $bindings,
				'SETTINGS' => array(),
				'AUTHOR_ID' => $userId,
				'RESPONSIBLE_ID' => $responsibleUserId,
				'PROVIDER_TYPE_ID' => $providerTypeId,
				'ASSOCIATED_ENTITY_ID' => $entityId,
				'PARENT_ID' => $parentId
			);

			if (!empty($fileIdList))
			{
				$activityFields['STORAGE_TYPE_ID'] = \CCrmActivity::getDefaultStorageTypeID();
				$activityFields['STORAGE_ELEMENT_IDS'] = $fileIdList;
			}

			$activityId = \CCrmActivity::add($activityFields, false, true, array('REGISTER_SONET_EVENT' => false));
			if($activityId > 0)
			{
				\CCrmActivity::saveCommunications($activityId, $communications, $activityFields, true, false);
				$result = $activityId;
			}
		}

		return $result;
	}

	private static function getSourceEntryId($logId)
	{
		$result = false;

		if (Loader::includeModule('socialnetwork'))
		{
			$res = \CSocNetLog::getList(
				array(),
				array(
					'ID' => $logId
				),
				false,
				array('nTopCount' => 1),
				array('ID', 'SOURCE_ID')
			);
			if ($log = $res->fetch())
			{
				$result = intval($log['SOURCE_ID']);
			}
		}

		return $result;
	}

	private static function getLogCommentId($sourceId, $eventId)
	{
		$result = false;

		if (Loader::includeModule('socialnetwork'))
		{
			$res = \CSocNetLogComments::getList(
				array(),
				array(
					'SOURCE_ID' => $sourceId,
					'EVENT_ID' => $eventId
				),
				false,
				array('nTopCount' => 1),
				array('ID', 'SOURCE_ID')
			);
			if ($comment = $res->fetch())
			{
				$result = intval($comment['ID']);
			}
		}

		return $result;
	}

	public static function readComments($params)
	{
		$userId = (
			!empty($params["USER_ID"])
				? intval($params["USER_ID"])
				: 0
		);

		$timeStamp = (
			!empty($params["TIMESTAMP"])
				? intval($params["TIMESTAMP"])
				: 0
		);

		if ($userId > 0)
		{
			$res = \CCrmActivity::getList(
				array(),
				array(
					'=RESPONSIBLE_ID' => $userId,
					'=PROVIDER_ID' => self::PROVIDER_ID,
					'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_COMMENT,
					'=DIRECTION' => \CCrmActivityDirection::Incoming,
					'=COMPLETED' => 'N',
					'>START_TIME' => convertTimeStamp(($timeStamp + \CTimeZone::getOffset()), 'FULL', SITE_ID),
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID', 'PROVIDER_PARAMS')
			);

			while($activity = $res->fetch())
			{
				if (
					!is_array($activity['PROVIDER_PARAMS'])
					|| !isset($activity['PROVIDER_PARAMS']['NO_AUTOCOMPLETE'])
					|| $activity['PROVIDER_PARAMS']['NO_AUTOCOMPLETE'] != "Y"
				)
				{
					\CCrmActivity::complete($activity['ID']);
				}
			}
		}
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY
		);
	}
}