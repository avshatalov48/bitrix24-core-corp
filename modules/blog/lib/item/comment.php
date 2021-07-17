<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage blog
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Blog\Item;

use Bitrix\Blog\CommentTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;

class Comment
{
	private $fields;

	public function __construct()
	{
		$this->fields = array();
	}

	public static function getById($commentId = 0)
	{
		static $cachedFields = array();

		$commentItem = false;
		$commentId = intval($commentId);

		if ($commentId > 0)
		{
			$commentItem = new Comment;
			$commentFields = array();

			if (isset($cachedFields[$commentId]))
			{
				$commentFields = $cachedFields[$commentId];
			}
			else
			{
				$select = array('*', 'UF_BLOG_COMM_URL_PRV');

				if (
					Option::get('disk', 'successfully_converted', false)
					&& ModuleManager::isModuleInstalled('disk')
				)
				{
					$select[] = 'UF_BLOG_COMMENT_FILE';
				}

				$res = CommentTable::getList(array(
					'filter' => array('=ID' => $commentId),
					'select' => $select
				));
				if ($fields = $res->fetch())
				{
					$commentFields = $fields;

					if ($commentFields['DATE_CREATE'] instanceof \Bitrix\Main\Type\DateTime)
					{
						$commentFields['DATE_CREATE'] = $commentFields['DATE_CREATE']->toString();
					}
				}

				$cachedFields[$commentId] = $commentFields;
			}

			$commentItem->setFields($commentFields);
		}

		return $commentItem;
	}

	public function setFields($fields = array()): void
	{
		$this->fields = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public static function checkDuplicate(array $params = [], int &$duplicateCommentId = 0): bool
	{
		$message = (
			isset($params['MESSAGE'])
			&& trim((string)$params['MESSAGE']) !== ''
				? trim((string)$params['MESSAGE'])
				: ''
		);

		$blogId = (
			isset($params['BLOG_ID'])
			&& (int)$params['BLOG_ID'] > 0
				? (int)$params['BLOG_ID']
				: 0
		);

		$postId = (
			isset($params['POST_ID'])
			&& (int)$params['POST_ID'] > 0
				? (int)$params['POST_ID']
				: 0
		);

		$authorId = (
			isset($params['AUTHOR_ID'])
			&& (int)$params['AUTHOR_ID'] > 0
				? (int)$params['AUTHOR_ID']
				: 0
		);

		if (
			$message === ''
			|| $blogId <= 0
			|| $postId <= 0
		)
		{
			return false;
		}

		\CTimeZone::Disable();
		$res = \CBlogComment::getList(
			[ "ID" => "DESC" ],
			[
				"BLOG_ID" => $blogId,
				"POST_ID" => $postId,
				"AUTHOR_ID" => $authorId,
				">DATE_CREATE" => ConvertTimeStamp(time()-60*30, "FULL")
			],
			false,
			[ "nTopCount" => 1 ],
			[ 'ID', 'POST_TEXT' ]
		);
		\CTimeZone::Enable();

		if (
			($duplicateComment = $res->fetch())
			&& mb_strlen($message) > 10
			&& md5((string)$duplicateComment['POST_TEXT']) === md5($message)
		)
		{
			$duplicateCommentId = (int)$duplicateComment['ID'];
			return false;
		}

		return true;
	}

	public static function actionsAfter(array $params)
	{
		static $blogPostEventIdList = null;

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$message = (
			isset($params["MESSAGE"])
			&& trim($params["MESSAGE"])
				? trim($params["MESSAGE"])
				: ''
		);

		$blogId = (
			isset($params["BLOG_ID"])
			&& intval($params["BLOG_ID"]) > 0
				? intval($params["BLOG_ID"])
				: 0
		);

		$blogOwnerId = (
			isset($params["BLOG_OWNER_ID"])
			&& intval($params["BLOG_OWNER_ID"]) > 0
				? intval($params["BLOG_OWNER_ID"])
				: 0
		);

		$postId = (
			isset($params["POST_ID"])
			&& intval($params["POST_ID"]) > 0
				? intval($params["POST_ID"])
				: 0
		);

		$postTitle = (
			isset($params["POST_TITLE"])
			&& trim($params["POST_TITLE"])
				? trim($params["POST_TITLE"])
				: ''
		);

		$postAuthorId = (
			isset($params["POST_AUTHOR_ID"])
			&& intval($params["POST_AUTHOR_ID"]) > 0
				? intval($params["POST_AUTHOR_ID"])
				: 0
		);

		$commentId = (
			isset($params["COMMENT_ID"])
			&& intval($params["COMMENT_ID"]) > 0
				? intval($params["COMMENT_ID"])
				: 0
		);

		$commentAuthorId = (
			isset($params["AUTHOR_ID"])
			&& intval($params["AUTHOR_ID"]) > 0
				? intval($params["AUTHOR_ID"])
				: 0
		);

		if (
			$message == ''
			|| $blogId <= 0
			|| $blogOwnerId <= 0
			|| $postAuthorId <= 0
			|| $postId <= 0
			|| $commentId <= 0
			|| $commentAuthorId <= 0
		)
		{
			return false;
		}

		\BXClearCache(true, "/blog/comment/".intval($postId / 100)."/".$postId."/");
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query("UPDATE b_blog_image SET COMMENT_ID=".intval($commentId)." WHERE BLOG_ID=".$blogId." AND POST_ID=".$postId." AND IS_COMMENT = 'Y' AND (COMMENT_ID = 0 OR COMMENT_ID is null) AND USER_ID=".$commentAuthorId);

		if ($blogPostEventIdList === null)
		{
			$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
			$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();
		}

		$res = \CSocNetLog::getList(
			array(),
			array(
				"EVENT_ID" => $blogPostEventIdList,
				"SOURCE_ID" => $postId
			),
			false,
			false,
			array("ID")
		);

		if ($log = $res->fetch())
		{
			$extranetSiteId = false;
			if (Loader::includeModule('extranet'))
			{
				$extranetSiteId = \CExtranet::getExtranetSiteId();
			}

			$logSiteId = array();
			$res = \CSocNetLog::getSite($log["ID"]);
			while ($logSite = $res->fetch())
			{
				$logSiteId[] = $logSite["LID"];
			}

			$siteId = (
				$extranetSiteId
				&& count($logSiteId) == 1
				&& $logSiteId[0] == $extranetSiteId
					? $extranetSiteId
					: $logSiteId[0]
			);

			$postUrl = Option::get("socialnetwork", "userblogpost_page", '/company/personal/users/'.$blogOwnerId.'/blog/#post_id#/', $siteId);
			$postUrl = \CComponentEngine::makePathFromTemplate(
				$postUrl,
				array(
					"user_id" => $postAuthorId,
					"post_id" => $postId
				)
			);

			$fieldsSocnet = array(
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"ENTITY_ID" => $blogOwnerId,
				"EVENT_ID" => "blog_comment",
				"USER_ID" => $commentAuthorId,
				"=LOG_DATE" => $helper->getCurrentDateTimeFunction(),
				"MESSAGE" => $message,
				"TEXT_MESSAGE" => $message,
				"URL" => $postUrl,
				"MODULE_ID" => false,
				"SOURCE_ID" => $commentId,
				"LOG_ID" => $log["ID"],
				"RATING_TYPE_ID" => "BLOG_COMMENT",
				"RATING_ENTITY_ID" => $commentId
			);

			$parser = new \CTextParser();
			$tagInlineList = $parser->detectTags($message);

			if (!empty($tagInlineList))
			{
				$fieldsSocnet["TAG"] = $tagInlineList;
			}

			$logCommentId = \CSocNetLogComments::add($fieldsSocnet, false, false);

			if ($logCommentId > 0)
			{
				\CSocNetLog::counterIncrement(
					$logCommentId,
					false,
					false,
					"LC",
					\CSocNetLogRights::checkForUserAll($log["ID"])
				);
			}

			$postSonetRights = \CBlogPost::getSocnetPerms($postId);
			$userCode = array();
			$mailUserId = array();
			if (!empty($postSonetRights["U"]))
			{
				$mailUserId = array_keys($postSonetRights["U"]);
				foreach($postSonetRights["U"] as $k => $v)
				{
					$userCode[] = "U".$k;
				}
			}

			$fieldsIM = Array(
				"TYPE" => "COMMENT",
				"TITLE" => $postTitle,
				"URL" => $postUrl,
				"ID" => $postId,
				"COMMENT_ID" => $commentId,
				"FROM_USER_ID" => $commentAuthorId,
				"TO_USER_ID" => array($postAuthorId),
				"TO_SOCNET_RIGHTS" => $userCode,
				"TO_SOCNET_RIGHTS_OLD" => array(
					"U" => array(),
					"SG" => array()
				),
				"AUTHOR_ID" => $postAuthorId,
				"BODY" => $message,
			);

			$fieldsIM["EXCLUDE_USERS"] = array();

			$res = \CSocNetLogFollow::getList(
				array(
					"CODE" => "L".$log["ID"],
					"TYPE" => "N"
				),
				array("USER_ID")
			);

			while ($unfollower = $res->fetch())
			{
				$fieldsIM["EXCLUDE_USERS"][$unfollower["USER_ID"]] = $unfollower["USER_ID"];
			}

			\CBlogPost::notifyIm($fieldsIM);

			if (!empty($mailUserId))
			{
				\CBlogPost::notifyMail(array(
					"type" => "COMMENT",
					"userId" => $mailUserId,
					"authorId" => $commentAuthorId,
					"postId" => $postId,
					"commentId" => $commentId,
					"siteId" => $siteId,
					"postUrl" => \CComponentEngine::makePathFromTemplate(
						'/pub/post.php?post_id=#post_id#',
						array(
							"post_id"=> $postId
						)
					)
				));
			}

			$siteResult = \CSite::getByID($siteId);

			if ($site = $siteResult->fetch())
			{
				\CBlogComment::addLiveComment($commentId, array(
					"DATE_TIME_FORMAT" => $site["FORMAT_DATETIME"],
					"NAME_TEMPLATE" => \CSite::getNameFormat(null, $siteId),
					"SHOW_LOGIN" => "Y",
					"MODE" => "PULL_MESSAGE"
				));
			}
		}

		return true;
	}

	public static function processCommentShare($params = array())
	{
		$commentText = (isset($params['commentText']) ? $params['commentText'] : '');
		$authorId = (isset($params['authorId']) ? intval($params['authorId']) : 0);
		$postId = (isset($params['postId']) ? intval($params['postId']) : 0);
		$blogId = (isset($params['blogId']) ? intval($params['blogId']) : 0);
		$siteId = (isset($params['siteId']) ? $params['siteId'] : SITE_ID);

		if (
			$commentText == ''
			|| $postId <= 0
		)
		{
			return false;
		}

		if ($blogId <= 0)
		{
			$postFields = \CBlogPost::getById($postId);
			$blogId = intval($postFields['BLOG_ID']);
		}

		if ($blogId <= 0)
		{
			return false;
		}

		$userIdToShareList = array();

		preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $commentText, $matches);

		if (!empty($matches))
		{
			foreach($matches[1] as $userId)
			{
				$userId = intval($userId);
				if (
					$userId > 0
					&& $userId != $authorId
				)
				{
					$postPerm = \CBlogPost::getSocNetPostPerms(array(
						"POST_ID" => $postId,
						"NEED_FULL" => true,
						"USER_ID" => $userId,
						"IGNORE_ADMIN" => true
					));

					if ($postPerm < \Bitrix\Blog\Item\Permissions::PREMODERATE)
					{
						$userIdToShareList[] = $userId;
					}
				}
			}
		}

		$userIdToShareList = array_unique($userIdToShareList);
		if (empty($userIdToShareList))
		{
			return false;
		}

		$newRightsList = array();

		foreach($userIdToShareList as $userId)
		{
			$newRightsList[] = 'U'.$userId;
		}

		$fullRightsList = $newRightsList;

		$blogPermsList = \CBlogPost::getSocnetPerms($postId);
		foreach($blogPermsList as $entityType => $entitiesList)
		{
			foreach($entitiesList as $entityId => $rightsList)
			{
				$fullRightsList = array_merge($fullRightsList, $rightsList);
			}
		}
		$fullRightsList = array_unique($fullRightsList);

		$commentId = \Bitrix\Socialnetwork\ComponentHelper::processBlogPostShare(
			array(
				"POST_ID" => $postId,
				"BLOG_ID" => $blogId,
				"SITE_ID" => $siteId,
				"SONET_RIGHTS" => $fullRightsList,
				"NEW_RIGHTS" => $newRightsList,
				"USER_ID" => $authorId
			),
			array(
				"PATH_TO_USER" => Option::get("main", "TOOLTIP_PATH_TO_USER", '/company/personal/user/#user_id#/', $siteId),
				"PATH_TO_POST" => Option::get("socialnetwork", "userblogpost_page", '/company/personal/user/#user_id#/blog/#post_id#', $siteId),
				"NAME_TEMPLATE" => \CSite::getNameFormat(),
				"SHOW_LOGIN" => "Y",
				"LIVE" => "N",
				"MENTION" => "Y"
			)
		);

		return $commentId;
	}
}
