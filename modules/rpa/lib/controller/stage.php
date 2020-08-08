<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Engine\CheckPermissions;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;
use Bitrix\Rpa\Model\FieldTable;
use Bitrix\Rpa\Model\StageToStageTable;
use Bitrix\Rpa\UserPermissions;

class Stage extends Base
{
	const SORT_STEP = 1000;

	public function configureActions(): array
	{
		$configureActions = parent::configureActions();
		$configureActions['add'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_STAGE, UserPermissions::ACTION_CREATE),
			],
		];
		$configureActions['update'] =
		$configureActions['delete'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_STAGE, UserPermissions::ACTION_MODIFY),
			],
		];
		$configureActions['getTasks'] =
		$configureActions['get'] = [
			'+prefilters' => [
				new CheckPermissions(UserPermissions::ENTITY_STAGE, UserPermissions::ACTION_VIEW),
			],
		];

		return $configureActions;
	}

	public function getAction(\Bitrix\Rpa\Model\Stage $stage): array
	{
		return [
			'stage' => $this->prepareData($stage),
		];
	}

	public function listForTypeAction(\Bitrix\Rpa\Model\Type $type, PageNavigation $pageNavigation = null): ?array
	{
		if(!Driver::getInstance()->getUserPermissions()->canViewType($type->getId()))
		{
			$this->addError(new Error('RPA_PERMISSION_TYPE_VIEW_DENIED'));
			return null;
		}
		$result = [
			'stages' => [],
		];
		$stages = $type->getStages($pageNavigation);
		foreach($stages as $stage)
		{
			$result['stages'][] = $this->prepareData($stage, false);
		}

		return $result;
	}

	public function addAction(array $fields, string $eventId = ''): ?array
	{
		$stage = new \Bitrix\Rpa\Model\Stage();
		return $this->updateAction($stage, $fields, $eventId);
	}

	public function updateAction(\Bitrix\Rpa\Model\Stage $stage, array $fields, string $eventId = ''): ?array
	{
		$isNew = (!($stage->getId() > 0));
		unset($fields['id']);
		if($stage->getId() > 0)
		{
			unset($fields['typeId']);
		}
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		$previousStageId = null;
		if(isset($fields['previousStageId']))
		{
			$previousStageId = (int) $fields['previousStageId'];
		}
		$fields = $converter->process($fields);
		foreach($fields as $name => $value)
		{
			if($stage->entity->hasField($name))
			{
				$stage->set($name, $value);
			}
		}
		if($previousStageId !== null)
		{
			$sort = $this->getSortByPreviousStageId($stage, $previousStageId);
			$stage->setSort($sort);
		}
		$result = $stage->save();
		if($result->isSuccess())
		{
			$permissionResult = $this->processPermissions($stage, $fields);
			$permissionResult = $this->savePermissions($stage, $permissionResult);
			if(!$permissionResult->isSuccess())
			{
				$this->addErrors($permissionResult->getErrors());
			}
			if(isset($fields['FIELDS']))
			{
				$fieldResult = FieldTable::mergeSettings($stage->getTypeId(), $stage->getId(), $fields['FIELDS']);
				if(!$fieldResult->isSuccess())
				{
					$this->addErrors($fieldResult->getErrors());
				}
			}
			if(isset($fields['POSSIBLE_NEXT_STAGES']))
			{
				if(!is_array($fields['POSSIBLE_NEXT_STAGES']))
				{
					$fields['POSSIBLE_NEXT_STAGES'] = [];
				}
				$possibleNextStagesResult = $this->processPossibleNextStages($stage, $fields['POSSIBLE_NEXT_STAGES']);
				if(!$possibleNextStagesResult->isSuccess())
				{
					$this->addErrors($possibleNextStagesResult->getErrors());
				}
			}
			$userPermissions = Driver::getInstance()->getUserPermissions();
			$userPermissions->loadUserPermissions();

			if($isNew)
			{
				$stage->getType()->getStages()->add($stage);
				Driver::getInstance()->getPullManager()->sendStageAddedEvent($stage, $eventId);
			}
			else
			{
				Driver::getInstance()->getPullManager()->sendStageUpdatedEvent($stage, $eventId);
			}

			if($previousStageId !== null)
			{
				$stage->getType()->resortStages();
			}

			return $this->getAction($stage);
		}
		else
		{
			$this->addErrors($result->getErrors());
			return null;
		}
	}

	public function deleteAction(\Bitrix\Rpa\Model\Stage $stage): void
	{
		$stageId = $stage->getId();
		$typeId = $stage->getTypeId();
		$result = $stage->delete();
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
		elseif($this->getCurrentUser())
		{
			Driver::getInstance()->getPullManager()->sendStageDeletedEvent($stageId, $typeId, $this->getCurrentUser()->getId());
		}
	}

	protected function processPossibleNextStages(\Bitrix\Rpa\Model\Stage $stage, array $possibleNextStages): Result
	{
		$result = new Result();

		$currentSettings = StageToStageTable::getList([
			'filter' => [
				'=STAGE_ID' => $stage->getId(),
			],
		])->fetchAll();

		$skipAdding = [];
		foreach($currentSettings as $currentSetting)
		{
			$isFound = false;
			foreach($possibleNextStages as $id)
			{
				if((int) $currentSetting['STAGE_TO_ID'] === (int) $id)
				{
					$isFound = true;
					break;
				}
			}
			if(!$isFound)
			{
				StageToStageTable::delete($currentSetting['ID']);
			}
			else
			{
				$skipAdding[$currentSetting['STAGE_TO_ID']] = $currentSetting['STAGE_TO_ID'];
			}
		}

		foreach($possibleNextStages as $id)
		{
			if(!isset($skipAdding[$id]))
			{
				$addResult = StageToStageTable::add([
					'STAGE_ID' => $stage->getId(),
					'STAGE_TO_ID' => $id,
				]);
				if($addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
			}
		}

		return $result;
	}

	public function prepareData(\Bitrix\Rpa\Model\Stage $stage, bool $isFullInfo = true): array
	{
		$data = $this->convertKeysToCamelCase($stage->collectValues());
		$data['color'] = $stage->getColor();
		$data['isFirst'] = $stage->isFirst();
		$data['isSuccess'] = $stage->isSuccess();
		$data['isFail'] = $stage->isFail();
		if($isFullInfo)
		{
			$data['tasks'] = [];
			$data['robotsCount'] = 0;
			$taskManager = Driver::getInstance()->getTaskManager();
			if($taskManager)
			{
				$data['tasks'] = $taskManager->getTypeStageTasks($stage->getTypeId(), $stage->getId());
				$data['robotsCount'] = $taskManager->countTypeStageRobots($stage->getTypeId(), $stage->getId());
			}
			$userPermissions = Driver::getInstance()->getUserPermissions();
			if($userPermissions->canModifyType($stage->getTypeId()))
			{
				$settings = $stage->getFieldSettings(false);
				foreach($settings as $visibility => $fields)
				{
					$data['fields'][$visibility] = $fields;
				}
			}
			//$data['settingsUrl'] = Driver::getInstance()->getUrlManager()->getStageDetailUrl($stage->getId());
			$data['possibleNextStages'] = array_values($stage->getPossibleNextStageIds());
			$canModifyItemsInStage = $userPermissions->canModifyItemsInStage($stage->getType(), $stage->getId());
			$canMoveFromStage = $userPermissions->canMoveFromStage($stage->getType(), $stage->getId());
			$data['permissions'] = [
				//'canAddItem' => //$canModifyItemsInStage,
				'droppable' => ($canModifyItemsInStage || $canMoveFromStage || $userPermissions->canMoveToStage($stage)),
				'canMoveFrom' => $canMoveFromStage,
			];
			if($canModifyItemsInStage && !UserPermissions::canMoveAnywhere())
			{
				foreach($stage->getUserFieldCollection() as $userField)
				{
					if($userField->isVisible())
					{
						$data['userFields'][$userField->getName()] = [
							'name' => $userField->getName(),
							'title' => $userField->getTitle(),
							'mandatory' => $userField->isMandatory(),
						];
					}
				}
			}
		}

		return $data;
	}

	protected function getSortByPreviousStageId(\Bitrix\Rpa\Model\Stage $newStage, int $stageId): int
	{
		$stages = $newStage->getType()->getStages()->getAll();
		$nextStage = $sort = null;
		if($stageId === 0)
		{
			$sort = static::SORT_STEP;
			if(isset($stages[0]))
			{
				$nextStage = $stages[0];
			}
		}
		else
		{
			foreach($stages as $index => $stage)
			{
				if($stage->getId() === $stageId)
				{
					if(isset($stages[$index + 1]))
					{
						$nextStage = $stages[$index + 1];
						$sort = $stage->getSort();
					}
					else
					{
						$sort = $stage->getSort() + static::SORT_STEP;
					}
				}
			}
		}
		if($nextStage)
		{
			$sort = floor((($nextStage->getSort() - $sort) / 2) + $sort);
			if($nextStage->getSort() <= $sort)
			{
				$nextStageSort = $this->getSortByPreviousStageId($nextStage, $nextStage->getId());
				$nextStage->setSort($nextStageSort);
				$nextStage->save();
				$sort = floor((($nextStage->getSort() - $sort) / 2) + $sort);
			}
		}

		return $sort;
	}

	public function getTasksAction(\Bitrix\Rpa\Model\Stage $stage): array
	{
		$taskManager = new TaskManager();

		return [
			'tasks' => $taskManager->getTypeStageTasks($stage->getTypeId(), $stage->getId())
		];
	}

	public function saveAllAction(\Bitrix\Rpa\Model\Type $type, array $stages): array
	{
		$currentStages = $type->getStages();
		$models = [];
		foreach($stages as $fields)
		{
			$fields['id'] = (int)$fields['id'];
			if($fields['id'] > 0)
			{
				$stage = $currentStages->getByPrimary($fields['id']);
				if($stage)
				{
					$this->updateAction($stage, $fields);
					$models[] = $stage;
				}
			}
			else
			{
				$stage = new \Bitrix\Rpa\Model\Stage();
				$this->updateAction($stage, $fields);
				$models[] = $stage;
			}
		}

		$result = [
			'stages' => [],
		];

		foreach($currentStages as $currentStage)
		{
			if(!in_array($currentStage, $models))
			{
				$this->deleteAction($currentStage);
			}
		}

		foreach($models as $stage)
		{
			$result['stages'][$stage->getId()] = $this->prepareData($stage, false);
		}

		return $result;
	}
}