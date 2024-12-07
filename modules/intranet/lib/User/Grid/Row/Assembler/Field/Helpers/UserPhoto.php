<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;

trait UserPhoto
{
	public function getUserPhotoUrl(array $userFields): string
	{
		$result = '';

		if (empty($userFields))
		{
			return $result;
		}

		if (empty($userFields['PERSONAL_PHOTO']))
		{
			switch($userFields['PERSONAL_GENDER'] ?? '')
			{
				case 'M':
					$suffix = 'male';
					break;
				case 'F':
					$suffix = 'female';
					break;
				default:
					$suffix = 'unknown';
			}
			$userFields['PERSONAL_PHOTO'] = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
		}

		if (empty($userFields['PERSONAL_PHOTO']))
		{
			return $result;
		}

		$file = \CFile::getFileArray($userFields['PERSONAL_PHOTO']);
		if (!empty($file))
		{
			$fileResized = \CFile::resizeImageGet(
				$file,
				[
					'width' => 100,
					'height' => 100
				],
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);

			$result = Uri::urnEncode($fileResized['src']);
		}

		return $result;
	}
}