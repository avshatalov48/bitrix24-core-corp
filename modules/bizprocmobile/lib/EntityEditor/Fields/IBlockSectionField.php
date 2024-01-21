<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;

class IBlockSectionField extends BaseField
{
	protected int $linkIBlockId = -1;
	protected ?array $list = null;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		if (isset($property['Options']) && (int)$property['Options'] > 0)
		{
			$this->linkIBlockId = (int)$property['Options'];
		}
	}

	public function getType(): string
	{
		return 'entity-selector';
	}

	public function getConfig(): array
	{
		return [
			'selectorType' => 'iblock-property-section',
			'provider' => ['options' => ['iblockId' => $this->linkIBlockId]],
			'entityList' => array_values($this->getEntityList()),
		];
	}

	protected function convertToMobileType($value): ?int
	{
		$entityList = $this->getEntityList();

		if (is_numeric($value) && (int)$value > 0)
		{
			$value = (int)$value;
			if ($value > 0 && array_key_exists($value, $entityList))
			{
				return $value;
			}
		}

		return null;
	}

	protected function convertToWebType($value): ?int
	{
		return $this->convertToMobileType($value);
	}

	protected function getEntityList(): array
	{
		if ($this->list === null)
		{
			$list = [];

			if ($this->linkIBlockId > 0)
			{
				$sectionIds = (array)$this->value;
				Collection::normalizeArrayValuesByInt($sectionIds, false);

				if ($sectionIds && Loader::includeModule('iblock'))
				{
					$sections = \CIBlockSection::GetTreeList(['IBLOCK_ID' => $this->linkIBlockId]);
					while ($section = $sections->GetNext())
					{
						$sectionId = (int)$section['ID'];
						if (in_array($sectionId, $sectionIds, true))
						{
							$list[$sectionId] = ['id' => $sectionId, 'title' => $section['~NAME']];
						}
					}
				}
			}

			$this->list = $list;
		}

		return $this->list;
	}
}
