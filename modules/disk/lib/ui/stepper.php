<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Search\Reindex\BaseObjectIndex;
use Bitrix\Disk\Search\Reindex\ExtendedIndex;
use Bitrix\Disk\Volume;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

final class Stepper
{
	public static function getHtml()
	{
		if (\Bitrix\Disk\User::isCurrentUserAdmin())
		{
			$commonSteppers[] = BaseObjectIndex::class;
		}
		$commonSteppers[Volume\Cleaner::class . CurrentUser::get()->getId()] = Loc::getMessage('DISK_STEPPER_CLEAN_TRASHCAN');
		$commonSteppers[ExtendedIndex::class] = Loc::getMessage('DISK_STEPPER_EXTENDED_INDEX');

		$htmlBlocks = [];
		foreach ($commonSteppers as $stepper => $title)
		{
			if (is_integer($stepper))
			{
				$stepper = $title;
				$title = null;
			}

			$htmlBlocks[] = \Bitrix\Main\Update\Stepper::getHtml([
				'disk'  => $stepper,
			], $title);
		}

		return implode('', $htmlBlocks);
	}
}