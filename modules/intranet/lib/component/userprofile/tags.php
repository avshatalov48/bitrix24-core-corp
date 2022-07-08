<?php
namespace Bitrix\Intranet\Component\UserProfile;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class Tags
{
	private $permissions;
	private $profileId;
	private $pathToUser;

	public function __construct($params)
	{
		if (!empty($params['permissions']))
		{
			$this->permissions = $params['permissions'];
		}
		if (!empty($params['pathToUser']))
		{
			$this->pathToUser = $params['pathToUser'];
		}
		if (!empty($params['profileId']))
		{
			$this->profileId = intval($params['profileId']);
		}
	}

	private function getProfileId()
	{
		return $this->profileId;
	}

	private function getPermissions()
	{
		return $this->permissions;
	}

	private function getPathToUser()
	{
		return $this->pathToUser;
	}

	public function getStub()
	{

		$result = [];

		$res = \Bitrix\Socialnetwork\UserTagTable::getList([
			'filter' => [
				'USER_ID' => $this->getProfileId()
			],
			'select' => [ 'CNT' ],
			'runtime' => [
				new Entity\ExpressionField('CNT', 'COUNT(*)')
			]
		]);
		if ($tagFields = $res->fetch())
		{
			$result['COUNT'] = $tagFields['CNT'];
		}

		return $result;
	}

	public function getTagsListAction()
	{
		$result = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$result = [];

		$countData = \Bitrix\Socialnetwork\UserTagTable::getUserTagCountData([
			'userId' => $this->getProfileId()
		]);

		$usersTopData = \Bitrix\Socialnetwork\UserTagTable::getUserTagTopData([
			'userId' => $this->getProfileId(),
			'topCount' => 3
		]);

		foreach($countData as $tagName => $count)
		{
			$result[$tagName] = [
				'COUNT' => $count,
				'USERS' => (!empty($usersTopData[$tagName]) ? $usersTopData[$tagName] : []),
				'CHECKSUM' => md5($tagName)
			];
		}

		return $result;
	}

	public function getTagDataAction(array $params = array())
	{
		global $USER;
		$result = [];

		if (
			empty($params)
			|| empty(trim($params['tag']))
			|| !Loader::includeModule('socialnetwork')

		)
		{
			return $result;
		}

		$tag = trim($params['tag']);

		$result = \Bitrix\Socialnetwork\Item\UserTag::getTagData([
			'currentUserId' => ($USER->isAuthorized() ? $USER->getId() : 0),
			'tag' => $tag,
			'pathToUser' => $this->getPathToUser(),
			'pageSize' => 10,
			'page' => (!empty($params['page']) ? intval($params['page']) : 1),
			'checksum' => md5($tag)
		]);

		return $result;
	}

	public function searchTagsAction(array $params = array())
	{
		$result = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$searchString = (
			!empty($params)
			&& !empty($params['searchString'])
				? trim($params['searchString'])
				: ''
		);

		$query = new Entity\Query(\Bitrix\Socialnetwork\UserTagTable::getEntity());
		$query->setSelect(['NAME', 'CNT']);
		$query->setGroup('NAME');
		$query->registerRuntimeField(
			'CNT',
			new Entity\ExpressionField('CNT', 'CASE WHEN (MIN(USER_ID) = 0) THEN COUNT(*)-1 ELSE COUNT(*) END')
		);

		$query->setOrder(array('CNT' => 'DESC'));

		$subQuery = new Entity\Query(\Bitrix\Socialnetwork\UserTagTable::getEntity());
		$subQuery->addSelect('NAME');
		$subQuery->setFilter([
			'=USER_ID' => $this->getProfileId(),
		]);

		$query->registerRuntimeField(
			'',
			new Entity\ReferenceField(
				'REF_NAME',
				Entity\Base::getInstanceByQuery($subQuery),
				array('=this.NAME' => 'ref.NAME'),
				array('join_type' => 'LEFT')
			)
		);

		$filter = [
			'=REF_NAME.NAME' => null
		];

		if (!empty($searchString))
		{
			$filter['%=NAME'] = $searchString.'%';
		}

		$query->setFilter($filter);
		$query->setLimit(5);

		$res = $query->exec();

		$tagsList = [];

		while($userTagFields = $res->fetch())
		{
			$tagsList[] = $userTagFields['NAME'];
			$result[] = [
				'NAME' => $userTagFields['NAME'],
				'CHECKSUM' => md5($userTagFields['NAME']),
				'CNT' => $userTagFields['CNT'],
				'USERS' => []
			];
		}

		if (!empty($tagsList))
		{
			$usersTopData = \Bitrix\Socialnetwork\UserTagTable::getUserTagTopData([
				'tagName' => $tagsList,
				'userId' => $this->getProfileId(),
				'topCount' => 3
			]);
		}

		foreach($result as $key => $tagData)
		{
			if (isset($usersTopData[$tagData['NAME']]))
			{
				$result[$key]['USERS'] = $usersTopData[$tagData['NAME']];
			}
		}

		return $result;
	}

	public function addTagAction(array $params = array())
	{
		global $USER;

		$result = [];

		if (
			empty($params)
			|| empty(trim($params['tag']))
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$tag = trim($params['tag']);
		$userId = (!empty($params['userId']) && intval($params['userId']) > 0 ? intval($params['userId']) : $this->getProfileId());

		if (
			$userId != $this->getProfileId()
			&& $userId != $USER->getId()
		)
		{
			return $result;
		}

		if ($userId == $this->getProfileId())
		{
			$permissions = $this->getPermissions();
			if (!$permissions['edit'])
			{
				return $result;
			}
		}

		if (\Bitrix\Socialnetwork\UserTagTable::add([
			'USER_ID' => $userId,
			'NAME' => $tag
		]))
		{
			$countData = \Bitrix\Socialnetwork\UserTagTable::getUserTagCountData([
				'userId' => $this->getProfileId(),
				'tagName' => mb_strtolower($tag)
			]);

			$usersTopData = \Bitrix\Socialnetwork\UserTagTable::getUserTagTopData([
				'userId' => $this->getProfileId(),
				'tagName' => mb_strtolower($tag),
				'topCount' => 3
			]);

			foreach($countData as $tagName => $count)
			{
				$result[$tagName] = [
					'COUNT' => $count,
					'USERS' => (!empty($usersTopData[$tagName]) ? $usersTopData[$tagName] : []),
					'CHECKSUM' => md5($tagName),
				];
			}
		}

		return $result;
	}

	public function removeTagAction(array $params = array())
	{
		$result = false;

		if (
			empty($params)
			|| empty(trim($params['tag']))
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$permissions = $this->getPermissions();

		if (!$permissions['edit'])
		{
			return $result;
		}

		$tag = trim($params['tag']);

		return \Bitrix\Socialnetwork\UserTagTable::delete([
			'USER_ID' => $this->getProfileId(),
			'NAME' => $tag
		]);
	}

}