<?php declare(strict_types=1);

namespace Bitrix\AI\Integration\Intranet;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UserAccessTable;

class DepartmentService
{
	protected static function getStructureIBlockId(): int
	{
		return (int)Option::get('intranet', 'iblock_structure', 0);
	}

	protected function hasIntranet(): bool
	{
		try
		{
			return Loader::includeModule('intranet');
		}
		catch (LoaderException $exception)
		{
			return false;
		}
	}

	public function getDepartments(): array
	{
		if ($this->hasIntranet())
		{
			$structureIBlockId = self::getStructureIBlockId();
			if ($structureIBlockId <= 0)
			{
				return [];
			}

			$query = SectionTable::query()
				->setSelect([
					'ID',
					'NAME',
					'DEPTH_LEVEL',
					'IBLOCK_SECTION_ID',
					'LEFT_MARGIN',
					'RIGHT_MARGIN',
				])
				->setOrder(['LEFT_MARGIN' => 'asc'])
				->where('IBLOCK_ID', $structureIBlockId)
				->where('ACTIVE', 'Y')
				->fetchCollection()
			;

			return $query->getIdList();
		}

		return [];
	}
}
