<?php

namespace Bitrix\Crm\Controller\RepeatSale;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentAssignmentUserController;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentAssignmentUserItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Result;

final class Segment extends JsonController
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}

	// region actions
	public function saveAction(
		array $data,
		?int $id = null,
		?string $eventId = null,
	): Result
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if (
			!$availabilityChecker->isAvailable()
			|| !$availabilityChecker->hasPermission()
			|| !$availabilityChecker->isItemsCountsLessThenLimit()
			|| !Container::getInstance()->getUserPermissions()->repeatSale()->canEdit()
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$controller = RepeatSaleSegmentController::getInstance();

		if ($id)
		{
			$entity = $controller->getById($id, true);
			if ($entity === null)
			{
				$this->addError(ErrorCode::getNotFoundError());

				return new Result();
			}

			$context = clone Container::getInstance()->getContext();
			$context->setEventId($eventId);

			$oldSegmentItem = SegmentItem::createFromEntity($entity);
			$segmentItem = SegmentItem::createFromArray(array_merge($oldSegmentItem->toArray(), $data));

			$this->prepareSegmentItem($segmentItem);

			$result = $controller->update($id, $segmentItem, $context);

			if ($result->isSuccess())
			{
				$userController = RepeatSaleSegmentAssignmentUserController::getInstance();
				$userController->deleteBySegmentId($id);
				foreach ($segmentItem->getAssignmentUserIds() as $userId)
				{
					$item = SegmentAssignmentUserItem::createFromArray([
						'userId' => $userId,
						'segmentId' => $id,
					]);

					$userController->add($item);
				}
			}
		}
		else
		{
			$this->addError(ErrorCode::getRequiredArgumentMissingError('id'));

			return new Result();
		}

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result;
	}

	public function activeAction(int $id, string $isEnabled): Result
	{
		if (!Container::getInstance()->getUserPermissions()->repeatSale()->canEdit())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if (
			!$availabilityChecker->isAvailable()
			|| !$availabilityChecker->hasPermission()
			|| !$availabilityChecker->isItemsCountsLessThenLimit()
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$entity = RepeatSaleSegmentController::getInstance()->getById($id, true);
		if ($entity === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return new Result();
		}

		$isEnabledValue = ($isEnabled === 'Y');

		$segmentItem = SegmentItem::createFromEntity($entity)
			->setIsEnabled($isEnabledValue)
		;

		$result = RepeatSaleSegmentController::getInstance()->update($id, $segmentItem);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		if ($isEnabledValue && $availabilityChecker->isEnablePending())
		{
			(new Flow())->enableAction();
			$this->sendAnalytics();
		}

		(new Logger())->info(
			'Segment was ' . ($isEnabledValue ? 'enabled' : 'disabled'),
			[
				'userId' => $this->getCurrentUser()?->getId() ?? Container::getInstance()->getContext()->getUserId(),
				'segmentCode' => $segmentItem->getCode(),
				'segmentId' => $segmentItem->getId(),
			]
		);

		return $result;
	}
	// endregion

	private function prepareSegmentItem(SegmentItem $segmentItem): void
	{
		$factory = Container::getInstance()->getFactory($segmentItem->getEntityTypeId());
		if (!$factory->isCategoriesSupported())
		{
			return;
		}

		$categoryId = $segmentItem->getEntityCategoryId();

		$category = $factory->getCategory($categoryId);
		if (!$category)
		{
			$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');
			$resolver = $fieldRepository->getDefaultCategoryIdResolver($segmentItem->getEntityTypeId());

			$categoryId = $resolver();
			$segmentItem->setEntityCategoryId($categoryId);
		}

		$stageId = $segmentItem->getEntityStageId();
		$stages = $factory->getStages($categoryId)->getAll();
		$isStageExist = (bool)(array_filter($stages, static fn($stage) => $stage->getStatusId() === $stageId));

		if (!$isStageExist)
		{
			$firstStage = reset($stages);
			$segmentItem->setEntityStageId($firstStage->getStatusId());
		}
	}

	private function sendAnalytics(): void
	{
		$event = new AnalyticsEvent('banner_click', Dictionary::TOOL_CRM, Dictionary::CATEGORY_BANNERS);
		$event
			->setType('repeat_sale_start_empty')
			->setSection('grid')
			->setElement('start_flow')
			->send()
		;
	}
}
