<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Main\Loader;

final class DepartmentStructure
{
	private const DEPARTMENT_IBLOCK_CODE = 'departments';

	private string $siteId;
	private ?int $baseDepartmentId;

	protected static array $instances = [];

	protected function __constructor(string $siteId)
	{
		$this->siteId = $siteId;
	}

	private function getInfoblockId(): ?int
	{
		if (($iblockId = Main\Config\Option::get('intranet', 'iblock_structure', null)) === null)
		{
			$iblockId = Main\Config\Option::get('main', 'wizard_departament', null, $this->siteId);
		}
		return $iblockId ? (int) $iblockId : null;
	}

	public function getBaseDepartmentId(): ?int
	{
		if (isset($this->baseDepartmentId))
		{
			return $this->baseDepartmentId;
		}
		$this->baseDepartmentId = null;
		if (Main\Loader::includeModule('iblock'))
		{
			$filter = (($iblockId = $this->getInfoblockId()) ?
				['=IBLOCK.ID' => $iblockId] :
				['=IBLOCK.CODE' => self::DEPARTMENT_IBLOCK_CODE]
			);

			if ($rootSectionInfo = Iblock\SectionTable::getList([
				'select' => ['ID'],
				'filter' => array_merge(
					$filter,
					[
						'=IBLOCK_SECTION_ID' => 0,
						'=ACTIVE' => 'Y'
					]
				),
				'order' => ['LEFT_MARGIN' => 'asc'],
				'limit' => 1,
				'cache' => ['ttl' => 84600]
			])->fetch())
			{
				$this->baseDepartmentId = $rootSectionInfo['ID'];
			}
		}
		return $this->baseDepartmentId;
	}

	public static function getInstance(?string $siteId): self
	{
		if (empty(self::$instances[$siteId]))
		{
			self::$instances[$siteId] = new self($siteId);
		}
		return self::$instances[$siteId];
	}
}