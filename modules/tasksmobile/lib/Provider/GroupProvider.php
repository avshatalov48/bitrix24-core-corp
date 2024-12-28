<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Socialnetwork\Integration\Im;
use Bitrix\Socialnetwork\Collab\Integration\IM\Dialog;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasksmobile\Dto\GroupDto;

final class GroupProvider
{
	public static function loadByIds(array $groupIds): array
	{
		$groupIds = array_unique(array_filter(array_map('intval', $groupIds)));
		if (empty($groupIds))
		{
			return [];
		}

		$groups = [];

		$avatarTypes = Loader::includeModule('socialnetwork') ? Workgroup::getAvatarTypes() : [];
		$newGroupsData = SocialNetwork\Group::getData(
			$groupIds,
			['SITE_ID', 'IMAGE_ID', 'AVATAR_TYPE', 'PROJECT_DATE_START', 'PROJECT_DATE_FINISH', 'TYPE'],
			['MODE' => 'mobile'],
		);

		$extranetSiteId = Option::get('extranet', 'extranet_site');
		$extranetSiteId = $extranetSiteId && ModuleManager::isModuleInstalled('extranet') ? $extranetSiteId : false;

		$chatData = Im\Chat\Workgroup::getChatData([
			'group_id' => $groupIds,
			'skipAvailabilityCheck' => true,
		]);

		foreach ($newGroupsData as $group)
		{
			$originalImage = null;
			$resizedImage100 = null;

			if (!empty($group['IMAGE_ID']))
			{
				[$originalImage, $resizedImage100] = self::getImages($group['IMAGE_ID']);
			}
			else if (
				!empty($group['AVATAR_TYPE'])
				&& isset($avatarTypes[$group['AVATAR_TYPE']])
			)
			{
				$originalImage = $resizedImage100 = $avatarTypes[$group['AVATAR_TYPE']]['mobileUrl'];
			}

			$additionalData = [
				...($group['ADDITIONAL_DATA'] ?? []),
				'DIALOG_ID' => Dialog::getDialogId($chatData[$group['ID']] ?? 0),
			];

			$groups[] = new GroupDto(
				id: $group['ID'],
				name: $group['NAME'],
				image: $originalImage,
				resizedImage100: $resizedImage100,
				additionalData: $additionalData,
				dateStart: $group['PROJECT_DATE_START']?->getTimestamp(),
				dateFinish: $group['PROJECT_DATE_FINISH']?->getTimestamp(),
				isCollab: $group['TYPE'] === 'collab',
				isExtranet: $group['SITE_ID'] === $extranetSiteId,
			);
		}

		return $groups;
	}

	private static function getImages(int $imageId): array
	{
		static $cache = [];

		if (!isset($cache[$imageId]))
		{
			$src = [];

			if ($imageId > 0)
			{
				$originalFile = \CFile::getFileArray($imageId);

				if ($originalFile !== false)
				{
					$resizedFile = \CFile::resizeImageGet(
						$originalFile,
						['width' => 100, 'height' => 100],
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true,
					);
					$src = [
						$originalFile['SRC'],
						$resizedFile['src'],
					];
				}

				$cache[$imageId] = $src;
			}
		}

		return $cache[$imageId];
	}
}
