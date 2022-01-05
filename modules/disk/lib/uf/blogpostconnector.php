<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\CommentAux;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\AttachedObject;
use Bitrix\Socialnetwork\WorkgroupTable;

Loc::loadMessages(__FILE__);

final class BlogPostConnector extends Connector
{
	private $blogPostData;
	private $canRead = null;

	/**
	 * @return bool
	 */
	public function isAnonymousAllowed()
	{
		return true;
	}

	public static function createFromBlogPostCommentConnector(BlogPostCommentConnector $blogPostCommentConnector)
	{
		$connector = new static(null);
		$connector->loadBlogPostDataByCommentId($blogPostCommentConnector->entityId);
		$connector->entityId = $connector->blogPostData['ID'];

		return $connector;
	}

	public function getDataToShow()
	{
		return $this->getDataToShowForUser($this->getUser()->getId());
	}

	public function getDataToShowForUser(int $userId)
	{
		if(!$this->loadBlogPostData())
		{
			return null;
		}
		return array(
			'TITLE' => Loc::getMessage('DISK_UF_BLOG_POST_CONNECTOR_TITLE'),
			'DETAIL_URL' => \CComponentEngine::makePathFromTemplate($this->blogPostData['PATH'], array('post_id' => $this->entityId, )),
			'DESCRIPTION' => Ui\Text::killTags($this->blogPostData['TITLE']),
			'MEMBERS' => $this->getDestinations(),
		);
	}

	public function addComment($authorId, array $data)
	{
		static $blogPostEventIdList = null;

		$this->loadBlogPostData();
		$commentFields = Array(
			"POST_ID" => $this->entityId,
			"BLOG_ID" => $this->blogPostData['BLOG_ID'],
			"POST_TEXT" => CommentAux\FileVersion::getPostText(),
			"DATE_CREATE" => new DateTime,
			"PARENT_ID" => false,
			"AUTHOR_ID" => $authorId,
			"HAS_PROPS" => 'Y',
		);
		if(!empty($data['fileId']))
		{
			$commentFields['UF_BLOG_COMMENT_FILE'] = array($data['fileId']);
		}
		elseif(!empty($data['versionId']))
		{
			$commentFields['UF_BLOG_COMMENT_FH'] = $data['versionId'];
		}

		$comId = \CBlogComment::add($commentFields);
		if(!$comId)
		{
			return;
		}

		if(!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$provider = CommentAux\Base::init(CommentAux\FileVersion::getType(), array(
			'liveParamList' => array(
				'userId' => $authorId,
				'userGender' => (isset($data['authorGender']) ? $data['authorGender'] : ''),
				'isEnabledKeepVersion' => Configuration::isEnabledKeepVersion()
			)
		));

		\CBlogComment::addLiveComment($comId, [
			'MODE' => 'PULL_MESSAGE',
			'AUX' => 'fileversion',
			'AUX_LIVE_PARAMS' => $provider->getLiveParams(),
			'CURRENT_USER_ID' => $authorId,
		]);

		BXClearCache(true, "/blog/comment/".intval($this->entityId / 100)."/".$this->entityId."/");

		if ($blogPostEventIdList === null)
		{
			$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
			$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();
		}

		$query = \CSocNetLog::getList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => $blogPostEventIdList,
				"SOURCE_ID" => $this->entityId
			),
			false,
			false,
			array("ID", "TMP_ID")
		);
		$row = $query->fetch();
		if(!$row)
		{
			return;
		}
		$fieldsForSocnet = array(
			"ENTITY_TYPE" => SONET_ENTITY_USER,
			"ENTITY_ID" => $this->blogPostData["AUTHOR_ID"],
			"EVENT_ID" => "blog_comment",
			"=LOG_DATE" => Application::getInstance()->getConnection()->getSqlHelper()->getCurrentDateTimeFunction(),
			"MESSAGE" => "file",
			"TEXT_MESSAGE" => "file",
			"URL" => "",
			"MODULE_ID" => false,
			"SOURCE_ID" => $comId,
			"LOG_ID" => $row["ID"],
			"RATING_TYPE_ID" => "BLOG_COMMENT",
			"RATING_ENTITY_ID" => $comId,
			"USER_ID" => $authorId,
		);

		\CSocNetLogComments::add($fieldsForSocnet, false, false, false);
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}
		if(!Loader::includeModule('socialnetwork'))
		{
			return false;
		}
		$cacheTtl = 2592000;
		$cacheId = 'blog_post_socnet_general_' . $this->entityId . '_' . LANGUAGE_ID. '_diskconnector';
		$timezoneOffset = \CTimeZone::getOffset();
		if($timezoneOffset != 0)
		{
			$cacheId .= "_" . $timezoneOffset;
		}
		$cacheDir = '/blog/socnet_post/gen/' . intval($this->entityId / 100) . '/' . $this->entityId;

		$cache = new \CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$post = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();

			$queryPost = \CBlogPost::getList(array(), array("ID" => $this->entityId), false, false, array(
					"ID",
					"BLOG_ID",
					"PUBLISH_STATUS",
					"TITLE",
					"AUTHOR_ID",
					"ENABLE_COMMENTS",
					"NUM_COMMENTS",
					"VIEWS",
					"CODE",
					"MICRO",
					"DETAIL_TEXT",
					"DATE_PUBLISH",
					"CATEGORY_ID",
					"HAS_SOCNET_ALL",
					"HAS_TAGS",
					"HAS_IMAGES",
					"HAS_PROPS",
					"HAS_COMMENT_IMAGES"
				));
			$post = $queryPost->fetch();

			if (!empty($post['DETAIL_TEXT']))
			{
				$post['DETAIL_TEXT'] = \Bitrix\Main\Text\Emoji::decode($post['DETAIL_TEXT']);
			}

			$cache->endDataCache($post);
		}
		if(!$post)
		{
			$this->canRead = false;
			return false;
		}

		$this->canRead = \CBlogPost::getSocNetPostPerms($this->entityId, true, $userId, $post["AUTHOR_ID"]) >= BLOG_PERMS_READ;

		if (!$this->canRead)
		{
			$perms = \CBlogPost::getSocNetPerms($this->entityId);
			$this->canRead = (
				is_array($perms)
				&& !empty($perms['UP'])
			);

			if(
				!$this->canRead
				&& !empty($perms['SG'])
			)
			{
				$sonetGroupsIdList = array_unique(array_keys($perms['SG']));
				if (
					!empty($sonetGroupsIdList)
					&& Loader::includeModule('socialnetwork')
				)
				{
					$res = WorkgroupTable::getList([
						'filter' => [
							'@ID' => $sonetGroupsIdList,
							'=LANDING' => 'Y',
							'=ACTIVE' => 'Y'
						],
						'limit' => 1
					]);
					if ($res->fetch())
					{
						$this->canRead = true;
					}
				}
			}
		}

		return $this->canRead;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

	protected function loadBlogPostData()
	{
		if(isset($this->blogPostData))
		{
			return $this->blogPostData;
		}

		$this->blogPostData = \CBlogPost::getList(
			array(),
			array(
				'ID' => $this->entityId,
			),
			false,
			false,
			array(
				'ID', 'PATH', 'TITLE', 'BLOG_ID', 'AUTHOR_ID', 'HAS_SOCNET_ALL',
			)
		)->fetch();

		return $this->blogPostData;
	}

	protected function loadBlogPostDataByCommentId($commentId)
	{
		if(isset($this->blogPostData))
		{
			return $this->blogPostData;
		}

		$this->blogPostData = \CBlogPost::getList(
			array(),
			array(
				'COMMENT_ID' => (int)$commentId,
			),
			false,
			false,
			array(
				'ID', 'PATH', 'TITLE', 'BLOG_ID', 'AUTHOR_ID', 'HAS_SOCNET_ALL',
			)
		)->fetch();

		return $this->blogPostData;
	}

	protected function getDestinations()
	{
		$isExtranetInstalled = Loader::includeModule("extranet");

		$members = array();
		if($this->blogPostData["HAS_SOCNET_ALL"] != "Y")
		{

			$perm = \CBlogPost::getSocnetPermsName($this->entityId);
			foreach($perm as $type => $v)
			{
				foreach($v as $vv)
				{
					if($type == "SG")
					{

						if($socNetGroup = \CSocNetGroup::getByID($vv["ENTITY_ID"]))
						{
							$name = $socNetGroup["~NAME"];
							$link = \CComponentEngine::makePathFromTemplate($this->getPathToGroup(), array("group_id" => $vv["ENTITY_ID"]));

							$groupSiteID = false;

							$queryGroupSite = \CSocNetGroup::getSite($vv["ENTITY_ID"]);

							while($groupSite = $queryGroupSite->fetch())
							{

								if(!$isExtranetInstalled || $groupSite["LID"] != \CExtranet::getExtranetSiteID()
								)
								{
									$groupSiteID = $groupSite["LID"];
									break;
								}
							}

							if($groupSiteID)
							{

								$tmp = \CSocNetLogTools::processPath(array("GROUP_URL" => $link), $this->getUser()->getId(), $groupSiteID); // user_id is not important parameter
								$link = ($tmp["URLS"]["GROUP_URL"] <> '' ? $tmp["URLS"]["GROUP_URL"] : $link);
							}
							$isExtranet = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($vv["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]));

							$members[] = array(
								"NAME" => $name,
								"LINK" => $link,
								'AVATAR_SRC' => Ui\Avatar::getGroup($socNetGroup['IMAGE_ID']),
								"IS_EXTRANET" => ($isExtranet ? "Y" : "N")
							);
						}
					}
					elseif($type == "U")
					{
						if(in_array("US" . $vv["ENTITY_ID"], $vv["ENTITY"]))
						{
							array_unshift($members, array(
								"NAME" => Loc::getMessage('DISK_UF_BLOG_POST_CONNECTOR_MEMBERS_ALL'),
								"LINK" => null,
								'AVATAR_SRC' => Ui\Avatar::getDefaultGroup(),
								"IS_EXTRANET" => "N",
							));
						}
						else
						{
							$name = \CUser::formatName('#NAME# #LAST_NAME#', array(
								"NAME" => $vv["~U_NAME"],
								"LAST_NAME" => $vv["~U_LAST_NAME"],
								"SECOND_NAME" => $vv["~U_SECOND_NAME"],
								"LOGIN" => $vv["~U_LOGIN"],
								"NAME_LIST_FORMATTED" => "",
							), false);
							$isExtranet = (is_array($GLOBALS["arExtranetUserID"]) && in_array($vv["ENTITY_ID"], $GLOBALS["arExtranetUserID"]));

							$members[] = array(
								"NAME" => $name,
								"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $vv["ENTITY_ID"])),
								'AVATAR_SRC' => Ui\Avatar::getPerson($vv['U_PERSONAL_PHOTO']),
								"IS_EXTRANET" => ($isExtranet ? "Y" : "N")
							);
						}
					}
					elseif($type == "DR")
					{
						$members[] = array(
							"NAME" => $vv["EL_NAME"],
							"LINK" => null,
							'AVATAR_SRC' => Ui\Avatar::getDefaultGroup(),
							"IS_EXTRANET" => "N",
						);
					}
				}
			}
		}
		else
		{
			$members[] = array(
				"NAME" => Loc::getMessage('DISK_UF_BLOG_POST_CONNECTOR_MEMBERS_ALL'),
				"LINK" => null,
				'AVATAR_SRC' => Ui\Avatar::getDefaultGroup(),
				"IS_EXTRANET" => "N",
			);
		}

		return $members;
	}

	public static function clearCacheByObjectId($id)
	{
		$attachedObjects = AttachedObject::getModelList(array(
			'filter' => array(
				'=ENTITY_TYPE' => self::className(),
				'=OBJECT_ID' => $id,
			))
		);

		foreach($attachedObjects as $attachedObject)
		{
			BXClearCache(true, "/blog/socnet_post/".intval($attachedObject->getEntityId() / 100)."/".$attachedObject->getEntityId()."/");
		}
	}
}
