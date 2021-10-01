<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Main\Engine\Controller;

/**
 * Class Notification
 * @package Bitrix\Crm\Controller\Timeline
 */
class Notification extends Controller
{
	/** @var \CCrmPerms  */
	private static $userPermissions;

	/**
	 * @param int $messageId
	 * @param array $options
	 * @return array
	 */
	public function getMessageInfoAction(int $messageId, array $options = [])
	{
		return $this->hasReadPermissions($messageId)
			? NotificationsManager::getMessageByInfoId($messageId, $options)
			: [];
	}

	/**
	 * @param int $messageId
	 * @return bool
	 */
	private function hasReadPermissions(int $messageId): bool
	{
		$result = false;

		$permissions = self::getCurrentUserPermissions();

		$activities = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmOwnerType::Activity,
				'PROVIDER_ID' => 'CRM_NOTIFICATION',
				'ASSOCIATED_ENTITY_ID' => $messageId,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			[
				'ID',
				'OWNER_ID',
				'OWNER_TYPE_ID',
				'RESPONSIBLE_ID'
			]
		);
		while ($activity = $activities->fetch())
		{
			$responsibleID = isset($activity['RESPONSIBLE_ID']) ? (int)$activity['RESPONSIBLE_ID'] : 0;

			$hasReadPermissions = (
				$responsibleID === $permissions->getUserID()
				|| \CCrmActivity::CheckReadPermission(
					$activity['OWNER_TYPE_ID'],
					$activity['OWNER_ID'],
					$permissions
				)
			);

			if ($hasReadPermissions)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * @return \CCrmPerms
	 */
	private static function getCurrentUserPermissions(): \CCrmPerms
	{
		if (self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		return self::$userPermissions;
	}
}
