<?php

namespace Bitrix\Crm\RepeatSale\Segment\Controller;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\RepeatSale\Job\Controller\RepeatSaleJobController;
use Bitrix\Crm\RepeatSale\Job\JobItem;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegment;
use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentTable;
use Bitrix\Crm\RepeatSale\Segment\SegmentAssignmentUserItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Service\Handler\Result;
use Bitrix\Crm\RepeatSale\Service\Handler\SystemHandler;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;
use CCrmContentType;
use CCrmOwnerType;

final class RepeatSaleSegmentController
{
	use Singleton;

	public function add(SegmentItem $segmentItem): AddResult
	{
		$result = RepeatSaleSegmentTable::add($this->getFields($segmentItem));

		if (!$result->isSuccess())
		{
			return $result;
		}

		$jobItem = JobItem::createFromArray([
			'segmentId' => $result->getId(),
			'scheduleType' => SystemHandler::getType()->value,
		]);

		$jobAddResult = RepeatSaleJobController::getInstance()->add($jobItem);
		if (!$jobAddResult->isSuccess())
		{
			$result->addErrors($jobAddResult->getErrors());
		}

		$addUsersResult = $this->updateAssignmentUsers($result->getId(), $segmentItem);
		if (!$addUsersResult->isSuccess())
		{
			$result->addErrors($addUsersResult->getErrors());
		}

		return $result;
	}

	public function update(int $id, SegmentItem $segmentItem, ?Context $context = null): UpdateResult
	{
		$result = RepeatSaleSegmentTable::update($id, $this->getFields($segmentItem));

		if (!$result->isSuccess())
		{
			return $result;
		}

		$updateUsersResult = $this->updateAssignmentUsers($id, $segmentItem);
		if (!$updateUsersResult->isSuccess())
		{
			$result->addErrors($updateUsersResult->getErrors());
		}

		// @todo realtime, maybe later
//		if ($context && $result->isSuccess())
//		{
//			(new PullManager())->sendUpdateSegmentPullEvent($id, [
//				'eventId' => $context->getEventId(),
//			]);
//		}

		return $result;
	}

	private function getFields(SegmentItem $segmentItem): array
	{
		return [
			'TITLE' => $segmentItem->getTitle(),
			'PROMPT' => $segmentItem->getPrompt(),
			'IS_ENABLED' => $segmentItem->isEnabled(),
			'CODE' => $segmentItem->getCode(),
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal, // @todo temporary only deal support
			'ENTITY_CATEGORY_ID' => $segmentItem->getEntityCategoryId(),
			'ENTITY_STAGE_ID' => $segmentItem->getEntityStageId(),
			'ENTITY_TITLE_PATTERN' => $segmentItem->getEntityTitlePattern(),
			'CALL_ASSESSMENT_ID' => $segmentItem->getCallAssessmentId(),
			'IS_AI_ENABLED' => $segmentItem->isAiEnabled(),
			'UPDATED_AT' => new DateTime(),
			'UPDATED_BY_ID' => Container::getInstance()->getContext()->getUserId(),
		];
	}

	private function updateAssignmentUsers(int $id, SegmentItem $segmentItem): Result
	{
		$userController = RepeatSaleSegmentAssignmentUserController::getInstance();
		$userController->deleteBySegmentId($id);

		$result = new Result();

		foreach ($segmentItem->getAssignmentUserIds() as $userId)
		{
			$item = SegmentAssignmentUserItem::createFromArray([
				'userId' => $userId,
				'segmentId' => $id,
			]);

			$addResult = $userController->add($item);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
		}

		return $result;
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*'];
		$filter = $params['filter'] ?? [];
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];
		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;

		$query = RepeatSaleSegmentTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
		;

		return QueryHelper::decompose($query);
	}

	public function getById(int $id, bool $loadAssignmentUsers = false): ?RepeatSaleSegment
	{
		$select = ['*'];
		if ($loadAssignmentUsers)
		{
			$select[] = 'ASSIGNMENT_USERS';
		}

		return RepeatSaleSegmentTable::query()
			->setSelect($select)
			->setFilter(['=ID' => $id])
			->fetchObject()
		;
	}

	public function getTotalCount(array $filter = []): int
	{
		return (int)(RepeatSaleSegmentTable::query()->setFilter($filter)->queryCountTotal() ?? 0);
	}

	public function delete(int $id): DeleteResult
	{
		$result = RepeatSaleSegmentTable::delete($id);

		RepeatSaleSegmentAssignmentUserController::getInstance()->deleteBySegmentId($id);

		$jobs = RepeatSaleJobController::getInstance()->getList([
			'select' => ['ID', 'SEGMENT_ID'],
			'filter' => [
				'SEGMENT_ID' => $id,
			],
		]);

		$queueController = RepeatSaleQueueController::getInstance();
		$jobController = RepeatSaleJobController::getInstance();
		$userController = RepeatSaleSegmentAssignmentUserController::getInstance();

		// @var $job \Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJob
		foreach ($jobs as $job)
		{
			$jobId = $job->getId();

			$queueController->deleteByJobId($jobId);
			$jobController->delete($jobId);
			$userController->deleteBySegmentId($job->getSegmentId());
		}

		(new Logger())->info('Segment and all its surroundings have been deleted', ['id' => $id]);

		return $result;
	}

	public function updateClientCoverage(?int $segmentId, int $suitableClientsCount): ?UpdateResult
	{
		if ($segmentId <= 0 || $suitableClientsCount < 0)
		{
			return null;
		}

		$ttl = 3600;

		$factory = Container::getInstance()->getFactory(CCrmOwnerType::Company);
		$categoryId = $factory?->getDefaultCategory()?->getId() ?? 0;
		$totalCompanies = $factory?->getItemsCount(['CATEGORY_ID' => $categoryId], $ttl);

		$factory = Container::getInstance()->getFactory(CCrmOwnerType::Contact);
		$categoryId = $factory?->getDefaultCategory()?->getId() ?? 0;
		$totalContacts = $factory?->getItemsCount(['CATEGORY_ID' => $categoryId], $ttl);

		$totalClients = $totalCompanies + $totalContacts;
		if ($totalClients <= 0)
		{
			$clientCoverage = 0;
		}
		else
		{
			$clientCoverage = (int)round(($suitableClientsCount / $totalClients) * 100);
		}

		return RepeatSaleSegmentTable::update($segmentId, [
			'CLIENT_COVERAGE' => min($clientCoverage, 100),
			'CLIENT_FOUND' => $suitableClientsCount,
		]);
	}

	public function collectCopilotData(int $segmentId, bool $sanitizeData = true): array
	{
		if ($segmentId <= 0)
		{
			return [];
		}

		$entityItem = $this->getById($segmentId, true);
		if (!$entityItem)
		{
			return [];
		}

		$item = SegmentItem::createFromEntity($entityItem);
		$description = $item->getDescription() ?? '';
		$caseText =  $item->getPrompt() ?? '';

		if ($sanitizeData)
		{
			$cleaner = static function(string $input): string {
				$input = TextHelper::cleanTextByType($input, CCrmContentType::BBCode);

				return trim(str_replace('&nbsp;', '', $input));
			};

			$description = $cleaner($description);
			$caseText = $cleaner($caseText);
		}

		return [
			'name' => $item->getTitle(),
			'description' => $description,
			'case_text' => $caseText,
		];
	}
}
