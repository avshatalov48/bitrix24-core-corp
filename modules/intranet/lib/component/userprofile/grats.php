<?php
namespace Bitrix\Intranet\Component\UserProfile;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class Grats
{
	private $profileId;
	private $pathToPostEdit;
	private $pathToUserGrat;
	private $pathToPost;
	private $pathToUser;
	private $pageSize;

	public function __construct($params)
	{
		if (!empty($params['profileId']))
		{
			$this->profileId = intval($params['profileId']);
		}
		if (!empty($params['pathToUser']))
		{
			$this->pathToUser = $params['pathToUser'];
		}
		if (!empty($params['pageSize']))
		{
			$this->pageSize = intval($params['pageSize']);
		}
		if (!empty($params['pathToPost']))
		{
			$this->pathToPost = $params['pathToPost'];
		}
		if (!empty($params['pathToPostEdit']))
		{
			$this->pathToPostEdit = $params['pathToPostEdit'];
		}
		if (!empty($params['pathToUserGrat']))
		{
			$this->pathToUserGrat = $params['pathToUserGrat'];
		}
	}

	private function getProfileId()
	{
		return $this->profileId;
	}

	private function getPathToPostEdit()
	{
		return $this->pathToPostEdit;
	}

	private function getPathToUserGrat()
	{
		return $this->pathToUserGrat;
	}

	private function getPathToPost()
	{
		return $this->pathToPost;
	}

	private function getPathToUser()
	{
		return $this->pathToUser;
	}

	private function getPageSize()
	{
		return $this->pageSize;
	}

	private function getGratitudesIblockId()
	{

		$result = false;

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		return \Bitrix\Socialnetwork\Component\LogList::getGratitudesIblockId();
	}

	public function getStub()
	{
		global $USER;

		$result = array(
			'BADGES' => array()
		);

		$result['URL_ADD'] = \CComponentEngine::makePathFromTemplate(
			$this->getPathToPostEdit(),
			array(
				"user_id" => $USER->getId(),
				"post_id" => 0
			)
		);

		$result['URL_LIST'] = \CComponentEngine::makePathFromTemplate(
			$this->getPathToUserGrat(),
			array(
				"user_id" => $USER->getId()
			)
		);

		$uri = new \Bitrix\Main\Web\Uri($result['URL_ADD']);
		$result['URL_ADD'] = $uri->addParams(array("gratUserId" => $this->getProfileId()))->getUri();

		$cache = new \CPHPCache;
		$cacheTime = 2592000;
		$cacheId = "user_grat_enum";
		$cachePath = "/user_grat_enum";

		if ($cache->initCache($cacheTime, $cacheId, $cachePath))
		{
			$vars = $cache->getVars();
			$badgesData = $vars['BADGES_DATA'];
		}
		else
		{
			$badgesData = array();

			$cache->startDataCache($cacheTime, $cacheId, $cachePath);

			$honourIblockId = $this->getGratitudesIblockId();

			if ($honourIblockId > 0)
			{
				$res = \CIBlockPropertyEnum::getList(
					array("DEF"=>"DESC", "SORT"=>"ASC"),
					array(
						"IBLOCK_ID" => $honourIblockId,
						"CODE" => "GRATITUDE"
					)
				);
				while($enumFields = $res->fetch())
				{
					$badgesData[] = array(
						'ID' => $enumFields['ID'],
						'SORT' => $enumFields['SORT'],
						'CODE' => $enumFields['XML_ID'],
						'NAME' => $enumFields['VALUE'],
					);
				}
				usort($badgesData, function($a, $b) {
					if ($a['SORT'] == $b['SORT'])
					{
						return 0;
					}
					return ($a['SORT'] > $b['SORT']) ? +1 : -1;
				});
			}

			$cache->endDataCache(array(
				'BADGES_DATA' => $badgesData,
			));
		}

		if (
			is_array($badgesData)
			&& !empty($badgesData)
		)
		{
			$result['BADGES'] = $badgesData;
		}

		return $result;
	}

	public function getGratitudes()
	{
		global $CACHE_MANAGER;

		$result = array();

		if(
			(
				!Loader::includeModule("extranet")
				|| \CExtranet::isIntranetUser(SITE_ID, $this->getProfileId())
			)
			&& Loader::includeModule("iblock")
		)
		{
			$badgesData = array();

			$cache = new \CPHPCache;
			$cacheTime = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
			$cacheId = $this->getProfileId();
			$cachePath = "/user_grat_".intval($this->getProfileId() / TAGGED_user_card_size);

			if ($cache->initCache($cacheTime, $cacheId, $cachePath))
			{
				$vars = $cache->getVars();
				$badgesData = $vars['BADGES_DATA'];
				$postIdList = $vars['POSTS_ID'];
				$authorsList = $vars['AUTHORS_DATA'];
			}
			else
			{
				$cache->startDataCache($cacheTime, $cacheId, $cachePath);
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->startTagCache($cachePath);
					$CACHE_MANAGER->registerTag("BLOG_POST_GRATITUDE_TO_".$this->getProfileId());
				}

				$badgesData = [];
				$iblockElementsIdList = [];
				$authorsList = [];

				if (Loader::includeModule('socialnetwork'))
				{
					$gratitudesData = \Bitrix\Socialnetwork\Component\LogList::getGratitudesIblockData([
						'userId' => $this->getProfileId()
					]);
					$badgesData = $gratitudesData['BADGES_DATA'];
					$iblockElementsIdList = $gratitudesData['ELEMENT_ID_LIST'];
				}

				$authorsIdList = [];
				$postIdList = [];
				if (!empty($iblockElementsIdList))
				{
					$gratitudesData = \Bitrix\Socialnetwork\Component\LogList::getGratitudesBlogData([
						'iblockElementsIdList' => $iblockElementsIdList
					]);
					$iblockElementsIdList = $gratitudesData['ELEMENT_ID_LIST'];
					$authorsIdList = $gratitudesData['AUTHOR_ID_LIST'];
					$postIdList = $gratitudesData['POST_ID_LIST'];
				}

				foreach($badgesData as $badgeEnumId => $badgeData)
				{
					foreach($badgeData['ID'] as $key => $elementId)
					{
						if (in_array($elementId, $iblockElementsIdList))
						{
							$badgesData[$badgeEnumId]['COUNT']++;
						}
						else
						{
							unset($badgesData[$badgeEnumId]['ID'][$key]);
						}
					}
					$badgesData[$badgeEnumId]['ID'] = array_values($badgesData[$badgeEnumId]['ID']);
				}

				if (!empty($authorsIdList))
				{
					$res = \Bitrix\Main\UserTable::getList(array(
						'filter' => array(
							'@ID' => $authorsIdList,
						),
						'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO')
					));
					while($userFields = $res->fetch())
					{
						$authorsList[$userFields['ID']] = array_merge($userFields, array(
							'NAME_FORMATTED' => \CUser::formatName(\CSite::getNameFormat(), $userFields, true, false),
							'PHOTO' => \Bitrix\Intranet\Component\UserProfile::getUserPhoto($userFields["PERSONAL_PHOTO"], 100),
							'URL' => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array('user_id' => $userFields['ID']))
						));
					}
				}

				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}

				$cache->endDataCache(array(
					'BADGES_DATA' => $badgesData,
					'POSTS_ID' => $postIdList,
					'AUTHORS_DATA' => $authorsList
				));
			}

			$result = array(
				'BADGES' => $badgesData,
				'POSTS_ID' => $postIdList,
				'AUTHORS' => $authorsList
			);
		}

		return $result;
	}

	public function getGratitudePostListAction(array $params = array())
	{
		$result = array();

		$gratitudesData = $this->getGratitudes();

		if (
			empty($gratitudesData)
			|| empty($gratitudesData['POSTS_ID'])
			|| empty($gratitudesData['BADGES'])
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$postsData = [];

		$filter = array(
			'EVENT_ID' => 'blog_post_grat',
			'@SOURCE_ID' => $gratitudesData['POSTS_ID'],
		);

		$result['POSTS_COUNT'] = \CSocNetLog::getList(
			array(),
			$filter,
			array(),
			false,
			array('ID'),
			array(
				'CHECK_RIGHTS' => 'Y',
				'USE_FOLLOW' => 'N',
				'USE_SUBSCRIBE' => 'N'
			)
		);

		$res = \CSocNetLog::getList(
			array('LOG_DATE' => 'DESC'),
			$filter,
			false,
			array(
				'nPageSize' => $this->getPageSize(),
				'iNumPage' => (!empty($params['pageNum']) && intval($params['pageNum']) > 0 ? intval($params['pageNum']) : 1)
			),
			array('SOURCE_ID'),
			array(
				'CHECK_RIGHTS' => 'Y',
				'USE_FOLLOW' => 'N',
				'USE_SUBSCRIBE' => 'N'
			)
		);

		$postIdList = array();
		while($logEntryFields = $res->fetch())
		{
			$postIdList[] = $logEntryFields['SOURCE_ID'];
		}

		if (
			!empty($postIdList)
			&& Loader::includeModule('blog')
		)
		{
			$res = \Bitrix\Blog\PostTable::getList(array(
				'filter' => array(
					'@ID' => $postIdList
				),
				'select' => array('ID', 'AUTHOR_ID', 'UF_GRATITUDE', 'MICRO', 'TITLE', 'DETAIL_TEXT', 'DATE_PUBLISH')
			));
			while($postFields = $res->fetch())
			{
				$postsData[$postFields['ID']] = $postFields;

				if ($postFields["MICRO"] === "Y")
				{
					$title = \CTextParser::clearAllTags($postFields['DETAIL_TEXT']);

					$parser = new \CTextParser();
					$parser->allow = [
						'CLEAR_SMILES' => 'Y',
						'NL2BR' => 'N'
					];
					$title = preg_replace("/&nbsp;/isu", "", $parser->convertText($title));
					$title = preg_replace("/\\<br \\/\\>/isu", " ", $title);

					$postsData[$postFields['ID']]['TITLE'] = truncateText($title, 100);
				}
				unset($postsData[$postFields['ID']]['DETAIL_TEXT']);

				$postsData[$postFields['ID']]['DATE_PUBLISH_TS'] = MakeTimeStamp($postFields['DATE_PUBLISH']);
				$postsData[$postFields['ID']]['DATE_FORMATTED'] = \CComponentUtil::getDateTimeFormatted(array(
					'TIMESTAMP' => $postsData[$postFields['ID']]['DATE_PUBLISH_TS'],
					'HIDE_TODAY' => false
				));

				$postsData[$postFields['ID']]['URL'] = \CComponentEngine::MakePathFromTemplate($this->getPathToPost(), array(
					"user_id" => $postFields["AUTHOR_ID"],
					"post_id" => $postFields["ID"]
				));

				foreach($gratitudesData['BADGES'] as $badgeEnumId => $badgeData)
				{
					if (in_array(intval($postFields['UF_GRATITUDE']), $badgeData['ID']))
					{
						$postsData[$postFields['ID']]['BADGE_ID'] = $badgeEnumId;
						break;
					}
				}
			}
		}

		$postIdList = array_keys($postsData);
		$contentIdList = array_map(function($val) { return 'BLOG_POST-'.$val; }, $postIdList);

		$ratingDataList = \CRatings::getRatingVoteResult("BLOG_POST", $postIdList);
		foreach($ratingDataList as $entityId => $ratingData)
		{
			if (is_set($postsData[$entityId]))
			{
				$postsData[$entityId]['RATING_DATA'] = $ratingData;
			}
		}

		$ratingDataList = \CRatings::getEntityRatingData(array(
			'entityTypeId' => "BLOG_POST",
			'entityId' => $postIdList
		));
		foreach($ratingDataList as $entityId => $ratingData)
		{
			if (
				is_set($postsData[$entityId])
				&& is_set($postsData[$entityId]['RATING_DATA'])
			)
			{
				$postsData[$entityId]['RATING_DATA']['TOP'] = $ratingData;
			}
		}

		$res = \Bitrix\Socialnetwork\UserContentViewTable::getList(array(
			'filter' => array(
				'@CONTENT_ID' => $contentIdList
			),
			'select' => array('CNT', 'CONTENT_ID', 'RATING_TYPE_ID', 'RATING_ENTITY_ID'),
			'runtime' => array(
				new Entity\ExpressionField('CNT', 'COUNT(*)')
			),
			'group' => array('CONTENT_ID')
		));
		while($postContentViewData = $res->fetch())
		{
			$postId = intval($postContentViewData['RATING_ENTITY_ID']);
			if (is_set($postsData[$postId]))
			{
				$postsData[$postId]['CONTENT_VIEW_CNT'] = intval($postContentViewData['CNT']);
			}
		}

		$result['BADGES'] = $gratitudesData['BADGES'];
		$result['POSTS'] = $postsData;
		$result['AUTHORS'] = $gratitudesData['AUTHORS'];

		return $result;
	}

}