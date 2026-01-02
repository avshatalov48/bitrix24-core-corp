<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Extranet\User;
use \CSite;

class MemberService
{
	const USER_TYPE_MAIL = 'email';
	const USER_DEPARTMENT_CODE = 'UF_DEPARTMENT';
	const USER_CRM_CODE = 'UF_USER_CRM_ENTITY';

	private array $avatarSize = [
		'width' => 38,
		'height' => 38
	];
	private array $cache = [];

	/**
	 * Members constructor.
	 *
	 * @param array $avatarSize ex. ['width' => 38,'height' => 38]
	 */
	public function __construct(array $avatarSize = [])
	{
		if (isset($avatarSize['width'], $avatarSize['height']))
		{
			$this->avatarSize = $avatarSize;
		}
	}

	/**
	 * Fill data-array with task members (author,responsible,accomplices,auditors).
	 * @param array $items Task items.
	 * @return array
	 */
	public function getUsers(array $items): array
	{
		$membersIds = $this->extractMemberIds($items);
		$members = $this->getByIds($membersIds);
		// fill items
		foreach ($items as &$item)
		{
			$item = $this->fillItem($item, $members);
		}

		return $items;
	}

	public function getByIds(array $memberIds): array
	{
		$result = [];
		$memberIds = array_unique($memberIds);
		// read from cache
		foreach ($memberIds as $arKey => $memberId)
		{
			if (isset($this->cache[$memberId]))
			{
				$result[$memberId] = $this->cache[$memberId];
				unset($memberIds[$arKey]);
			}
		}

		if (empty($memberIds))
		{
			return $result;
		}

		// read from database
		$select = [
			'ID',
			'PERSONAL_PHOTO',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EXTERNAL_AUTH_ID',
			self::USER_DEPARTMENT_CODE,
		];
		if (Loader::includeModule('crm'))
		{
			$select[] = self::USER_CRM_CODE;
		}
		$res = \Bitrix\Main\UserTable::getList([
			'select' => $select,
			'filter' => [
				'ID' => $memberIds,
			],
		]);
		while ($row = $res->fetch())
		{
			if ($row['PERSONAL_PHOTO'])
			{
				$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
					$row['PERSONAL_PHOTO'],
					$this->avatarSize,
					BX_RESIZE_IMAGE_EXACT
				);
			}
			$row['USER_NAME'] = \CUser::FormatName(\CSite::GetNameFormat(), $row, true, false);
			$member = [
				'id' => $row['ID'],
				'photo' => $row['PERSONAL_PHOTO'],
				'name' => $row['USER_NAME'],
				'crm' => false,
				'mail' => false,
				'extranet' => false,
				'collaber' => false,
				'url' => "/company/personal/user/{$row['ID']}/",
			];
			if (isset($row[self::USER_CRM_CODE]) && $row[self::USER_CRM_CODE])
			{
				$member['crm'] = true;
			}
			elseif ($row['EXTERNAL_AUTH_ID'] == self::USER_TYPE_MAIL)
			{
				$member['mail'] = true;
			}
			elseif (!isset($row[self::USER_DEPARTMENT_CODE][0]) || !$row[self::USER_DEPARTMENT_CODE][0])
			{
				if (User::isCollaber($row['ID']))
				{
					$member['collaber'] = true;
				}
				else
				{
					$member['extranet'] = true;
				}
			}
			$result[$member['id']] = $member;
			$this->cache[$member['id']] = $member;
		}

		return $result;
	}

	private function fillItem(array $item, array $members): array
	{
		$item['data']['author'] = $members[$item['data']['author']] ?? null;
		$item['data']['responsible'] = $members[$item['data']['responsible']] ?? null;

		return $item;
	}

	private function extractMemberIds(array $items): array
	{
		$membersIds = [];
		foreach ($items as $item)
		{
			// try to get author id if isset
			if (!empty($item['data']['author']))
			{
				$membersIds[] = $item['data']['author'];
			}
			// try to get responsible id if isset
			if (!empty($item['data']['responsible']))
			{
				$membersIds[] = $item['data']['responsible'];
			}
		}

		return $membersIds;
	}
}