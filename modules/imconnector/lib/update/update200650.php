<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader,
	Bitrix\Main\UserTable,
	Bitrix\Main\FileTable,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Update\Stepper;

use Bitrix\Disk\Internals\VersionTable;


final class Update200650 extends Stepper
{
	private const PORTION = 100;
	private const OPTION_NAME = 'imconnector_deleting_forgotten_files';
	protected static $moduleId = 'imconnector';

	/**
	 * @param array $result
	 * @return bool
	 */
	public function execute(array &$result): bool
	{
		$return = false;

		$status = $this->loadCurrentStatus();

		if ($status['count'] > 0)
		{
			if (
				!is_numeric($status['lastId'])
				|| $status['lastId'] < 0
			)
			{
				$status['lastId'] = 0;
			}

			$found = false;
			$files = [];

			$rawFile = FileTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=MODULE_ID' => self::$moduleId,
					'>ID' => $status['lastId']
				],
				'limit' => self::PORTION,
				'order' => ['ID' => 'ASC'],
			]);

			while ($rowFile = $rawFile->fetch())
			{
				if (!empty($rowFile['ID']))
				{
					$files[$rowFile['ID']] = $rowFile['ID'];
				}

				$status['lastId'] = $rowFile['ID'];
				$status['number']++;
				$found = true;
			}

			if (!empty($files))
			{
				$rawUser = UserTable::getList([
					'select' => ['PERSONAL_PHOTO'],
					'filter' => ['=PERSONAL_PHOTO' => $files],
				]);
				while ($rowUser = $rawUser->fetch())
				{
					if (!empty($files[$rowUser['PERSONAL_PHOTO']]))
					{
						unset($files[$rowUser['PERSONAL_PHOTO']]);
					}
				}
			}

			if(
				!empty($files)
				&& Loader::includeModule('disk')
			)
			{
				$rawDisk = VersionTable::getList([
					'select' => ['FILE_ID'],
					'filter' => ['=FILE_ID' => $files],
				]);
				while ($rowDisk = $rawDisk->fetch())
				{
					if (!empty($files[$rowDisk['FILE_ID']]))
					{
						unset($files[$rowDisk['FILE_ID']]);
					}
				}
			}

			if (!empty($files))
			{
				foreach ($files as $fileId)
				{
					\CFile::delete($fileId);
				}
			}

			if ($found)
			{
				Option::set(self::$moduleId, self::OPTION_NAME, serialize($status));
				$return = true;
			}

			if ($found === false)
			{
				Option::delete(self::$moduleId, ['name' => self::OPTION_NAME]);
			}
		}

		return $return;
	}

	/**
	 * @return array
	 */
	public function loadCurrentStatus()
	{
		$status = Option::get(self::$moduleId, self::OPTION_NAME, '');
		$status = ($status !== '' ? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$status = [
				'lastId' => 0,
				'number' => 0,
				'count' => FileTable::getCount(['=MODULE_ID' => self::$moduleId]),
			];
		}

		return $status;
	}
}