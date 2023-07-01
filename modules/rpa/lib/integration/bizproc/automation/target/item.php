<?php
namespace Bitrix\Rpa\Integration\Bizproc\Automation\Target;

use Bitrix\Bizproc\Automation\Engine\TemplatesScheme;
use Bitrix\Main\Loader;
use Bitrix\Rpa\Model\TypeTable;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class Item extends Base
{
	public function getDocumentStatus(): int
	{
		$entity = $this->getFields();
		return (int)($entity['STAGE_ID'] ?? null);
	}

	public function setDocumentStatus($statusId): bool
	{
		return false;
	}

	public function getDocumentStatusList($categoryId = 0): array
	{
		$list = [];
		$typeId = $this->getTypeId();
		$type = TypeTable::getById($typeId)->fetchObject();

		if ($type)
		{
			foreach($type->getStages() as $stage)
			{
				$list[$stage->getId()] = [
					'NAME'  => $stage->getName(),
					'COLOR' => $stage->getColor(),
				];
			}
		}

		return $list;
	}

	private function getTypeId(): int
	{
		return (int) str_replace('T', '', $this->getDocumentType()[2]);
	}

	public function getTemplatesScheme(): ?TemplatesScheme
	{
		$scheme = new \Bitrix\Rpa\Integration\Bizproc\Automation\Engine\TemplatesScheme();
		$scheme->build();

		return $scheme;
	}
}