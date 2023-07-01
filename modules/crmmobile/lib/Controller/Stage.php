<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\CrmMobile\Dto;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('crm');
Loader::requireModule('bizproc');

class Stage extends Controller
{
	public function createAction(Factory $factory, int $categoryId, array $fields): ?Dto\Stage
	{
		$entityId = $factory->getStagesEntityId($categoryId);
		$status = new \CCrmStatus($entityId);

		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		$id = $status->Add([
			'NAME' => $fields['name'],
			'SORT' => $fields['sort'],
			'SEMANTICS' => $fields['semantics'],
			'COLOR' => $fields['color'],
		]);

		if (!$id)
		{
			$this->addError(new Error($status->GetLastError()));
			return null;
		}

		$this->resortStagesAndSave($factory, $categoryId);

		$stage = $status->GetStatusById($id);

		return new Dto\Stage([
			'id' => $stage['ID'],
			'name' => $stage['NAME'],
			'sort' => $stage['SORT'],
			'color' => $stage['COLOR'],
			'semantics' => $stage['SEMANTICS'],
			'statusId' => $stage['STATUS_ID'],
		]);
	}

	private function resortStagesAndSave(Factory $factory, int $categoryId): void
	{
		$sort = 10;
		$stageObjects = $factory->getStages($categoryId);

		foreach ($stageObjects as $stageObject)
		{
			$stageObject->setSort($sort);
			$sort += 10;
		}

		$stageObjects->save();
	}

	public function updateAction(Factory $factory, array $fields): bool
	{
		$stage = $factory->getStage($fields['statusId']);

		if (!$stage)
		{
			$this->addError(new Error(Loc::getMessage('CRM_STAGE_STAGE_NOT_FOUND')));
			return false;
		}

		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return false;
		}

		if (isset($fields['name']) && is_string($fields['name']))
		{
			$stage->setName($fields['name']);
		}

		if (isset($fields['color']) && is_string($fields['color']))
		{
			$stage->setColor($fields['color']);
		}

		$result = $stage->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return false;
		}

		if (!isset($fields['tunnels']) || !is_array($fields['tunnels']))
		{
			$fields['tunnels'] = [];
		}

		$categoriesEnabled = $factory->isCategoriesEnabled();
		$stagesEnabled = $factory->isStagesEnabled();

		if ($categoriesEnabled && $stagesEnabled)
		{
			$userId = $this->getCurrentUser()->getId();
			$tunnelManager = new \Bitrix\Crm\Automation\TunnelManager($factory->getEntityTypeId());
			$tunnelManagerResult = $tunnelManager->updateStageTunnels(
				$fields['tunnels'],
				$stage->getStatusId(),
				$userId
			);

			if (!$tunnelManagerResult->isSuccess())
			{
				$this->addErrors($tunnelManagerResult->getErrors());
				return false;
			}
		}

		return true;
	}

	public function deleteAction(Factory $factory, string $statusId): bool
	{
		$stage = $factory->getStage($statusId);

		if (!$stage)
		{
			$this->addError(new Error(Loc::getMessage('CRM_STAGE_STAGE_NOT_FOUND')));
			return false;
		}

		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return false;
		}

		if ($stage->getSystem())
		{
			$this->addError(new Error(Loc::getMessage('CRM_STAGE_STAGE_IS_SYSTEM')));
			return false;
		}

		$result = $stage->delete();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return false;
		}

		return true;
	}
}
