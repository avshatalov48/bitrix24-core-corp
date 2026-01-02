<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\V2\Internal\Entity;

class CheckListRepository implements CheckListRepositoryInterface
{
	public function getIdsByEntity(int $entityId, Entity\CheckList\Type $type): array
	{
		/** @var CheckListFacade $facade */
		$facade = $this->getFacade($type);

		/** @var DataManager $dataTable */
		$dataTable = $facade::getCheckListDataController();

		$checkLists =
			$dataTable::query()
				->setSelect(['ID'])
				->where($facade::$entityIdName, $entityId)
				->fetchAll()
		;

		$ids = [];
		foreach ($checkLists as $checkList)
		{
			$ids[] = (int)$checkList['ID'];
		}

		return $ids;
	}

	public function getAttachmentIdsByEntity(int $entityId, Entity\CheckList\Type $type): array
	{
		$ids = $this->getIdsByEntity($entityId, $type);
		if (empty($ids))
		{
			return [];
		}

		/** @var CheckListFacade $facade */
		$facade = $this->getFacade($type);

		/** @var DataManager $dataTable */
		$dataTable = $facade::getCheckListDataController();

		$checkLists =
			$dataTable::query()
				->setSelect(['ID', Entity\UF\UserField::CHECKLIST_ATTACHMENTS])
				->whereIn('ID', $ids)
				->fetchAll()
		;

		$attachments = array_column($checkLists, Entity\UF\UserField::CHECKLIST_ATTACHMENTS);
		$attachments = array_filter($attachments, 'is_array');
		$attachments = array_merge(...$attachments);

		Collection::normalizeArrayValuesByInt($attachments, false);

		return $attachments;
	}

	private function getFacade(Entity\CheckList\Type $type): string
	{
		return match ($type)
		{
			Entity\CheckList\Type::Task => TaskCheckListFacade::class,
			Entity\CheckList\Type::Template => TemplateCheckListFacade::class,
		};
	}
}
