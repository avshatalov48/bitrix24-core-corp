<?php

namespace Bitrix\Tasks\Rest\Controllers\ViewedGroup;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Rest\Controllers\Task\Comment;

final class User extends Base
{
	protected const ITEM = 'USER';
	protected const LIST = 'USERS';

	protected const VIEWED_TYPE = Comments\Viewed\Enum::USER;

	/**
	 * @param $fields
	 * @return bool|null
	 * @throws SystemException
	 */
	public function markAsReadAction($fields): ?bool
	{
		$fields['GROUP_ID'] = ($fields['GROUP_ID'] ?? null);
		$fields['ROLE'] = ($fields['ROLE'] ?? null);

		if (Comments\Viewed\Group::isOn())
		{
			$r = (new Comments\Viewed\Group())->markAsRead(
				$fields['GROUP_ID'],
				$fields['ROLE'],
				Comments\Viewed\Enum::resolveTypeById(Comments\Viewed\Enum::USER)
			);

			if ($r->isSuccess() === false)
			{
				$this->addErrors($r->getErrors());
				return null;
			}

			$params = Comments\Viewed\Event::prepare($fields);
			Comments\Viewed\Event::addByTypeCounterService(Comments\Viewed\Enum::USER,  $params);
			Comments\Viewed\Event::addByTypePushService(Comments\Viewed\Enum::USER, $params);

			return true;
		}

		return $this->forward(
			new Comment(),
			'readAll',
			[
				'groupId' => $fields['GROUP_ID'],
				'userId' => $fields['USER_ID'],
				'role' => $fields['ROLE'],
			]
		);
	}
}
