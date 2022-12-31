<?php

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Controller\PublicErrorsTrait;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\Main\Result;

class UpdateItemStageAction extends Action
{
	use PublicErrorsTrait;

	public function run(int $id, int $stageId, string $entityType, array $extra = [])
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$result = $this->update($id, $stageId, $entityType, $extra);
		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);
		}
	}

	private function update(int $id, int $stageId, string $entityType, array $extra = []): Result
	{
		return (
			Entity::getInstance($entityType)
				->prepare($extra)
				->updateItemStage($id, $stageId)
		);
	}
}
