<?php

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\Mobile\Trait\PublicErrorsTrait;
use Bitrix\Main\Result;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Kanban\Entity;

class ChangeCategoryAction extends Action
{
	use PublicErrorsTrait;

	public function run(array $ids, string $entityType, int $categoryId)
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$result = $this->change($ids, $entityType, $categoryId);
		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);
		}
	}

	private function change(array $ids, string $entityType, int $categoryId): Result
	{
		return Entity::getInstance($entityType)->changeCategory($ids, $categoryId);
	}
}
