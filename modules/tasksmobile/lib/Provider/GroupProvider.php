<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Helper\Workgroup;
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
			['IMAGE_ID', 'AVATAR_TYPE', 'PROJECT_DATE_START', 'PROJECT_DATE_FINISH'],
			['MODE' => 'mobile']
		);

		foreach ($newGroupsData as $group)
		{
			$originalImage = null;
			$resizedImage100 = null;

			if (!empty($group['IMAGE_ID']))
			{
				[$originalImage, $resizedImage100] = self::getImages($group['IMAGE_ID']);
			}
			elseif (
				!empty($group['AVATAR_TYPE'])
				&& isset($avatarTypes[$group['AVATAR_TYPE']])
			)
			{
				$originalImage = $resizedImage100 = $avatarTypes[$group['AVATAR_TYPE']]['mobileUrl'];
			}

			if (empty($originalImage))
			{
				$originalImage = $resizedImage100 = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/project/images/default-avatar.png';
			}
			elseif (empty($resizedImage100))
			{
				$resizedImage100 = $originalImage;
			}

			$groups[] = new GroupDto(
				id: $group['ID'],
				name: $group['NAME'],
				image: $originalImage,
				resizedImage100: $resizedImage100,
				additionalData: ($group['ADDITIONAL_DATA'] ?? []),
				dateStart: $group['PROJECT_DATE_START']?->getTimestamp(),
				dateFinish: $group['PROJECT_DATE_FINISH']?->getTimestamp(),
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
