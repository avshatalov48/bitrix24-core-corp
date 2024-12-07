<?php

namespace Bitrix\Crm\Controller\Activity\Layout;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\Provider\ConfigurableRestApp;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Activity\BindingExists;
use Bitrix\Crm\Controller\Validator\Activity\ActivityExists;
use Bitrix\Crm\Controller\Validator\Activity\ReadPermission;
use Bitrix\Crm\Controller\Validator\Activity\UpdatePermission;
use Bitrix\Crm\Controller\Validator\Validation;
use Bitrix\Crm\Engine\ActionFilter\CheckRestApplicationContext;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\Entity\Repository\RestAppLayoutBlocksRepository;
use Bitrix\Main\Error;
use CCrmActivity;
use CRestServer;

class Blocks extends Base
{
	protected RestAppLayoutBlocksRepository $restAppLayoutBlocksRepository;

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new CheckRestApplicationContext(),
		];
	}

	protected function init(): void
	{
		parent::init();

		$this->restAppLayoutBlocksRepository = new RestAppLayoutBlocksRepository();
	}

	public function getAction(CRestServer $server, int $entityTypeId, int $entityId, int $activityId): ?array
	{
		if (!$this->validateActivity($activityId, $entityTypeId, $entityId))
		{
			return null;
		}

		$layoutDto = $this->restAppLayoutBlocksRepository
			->fetchLayoutBlocksForActivityItem($activityId, $server->getClientId())
		;

		return [
			'layout' => $layoutDto,
		];
	}

	public function setAction(
		CRestServer $server,
		int $entityTypeId,
		int $entityId,
		int $activityId,
		array $layout = []
	): array|null
	{
		if (!$this->validateActivity($activityId, $entityTypeId, $entityId, true))
		{
			return null;
		}

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		if (
			!$this->isAssociateTimelineItemConfigurable($activity, $entityTypeId, $entityId)
			|| $this->isConfigurableRestAppActivity($activity)
		)
		{
			$this->addError(self::getUnsuitableActivityTypeError());

			return null;
		}

		$updateResult = $this->restAppLayoutBlocksRepository
			->setLayoutBlocksForActivityItem(
				$activityId,
				$server->getClientId(),
				$layout,
			)
		;

		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return null;
		}

		$this->notifyTimelineAboutActivityUpdate($activity);

		return [
			'success' => true,
		];
	}

	public function deleteAction(CRestServer $server, int $entityTypeId, int $entityId, int $activityId): array|null
	{
		if (!$this->validateActivity($activityId, $entityTypeId, $entityId, true))
		{
			return null;
		}

		$deleteResult = $this->restAppLayoutBlocksRepository
			->deleteLayoutBlocksForActivityItem($activityId, $server->getClientId())
		;

		if ($deleteResult === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());

			return null;
		}

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		$this->notifyTimelineAboutActivityUpdate($activity);

		return [
			'success' => true,
		];
	}

	private function validateActivity(
		int $activityId,
		int $entityTypeId,
		int $entityId,
		bool $checkUpdatePermission = false,
	): bool
	{
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return false;
		}

		$binding = new Activity\BindIdentifier($itemIdentifier, $activityId);

		$validation = (new Validation())
			->validate($binding->getActivityId(), [new ActivityExists()])
			->validate($binding, [new BindingExists()])
			->validate($itemIdentifier, [new ReadPermission()])
		;

		if ($checkUpdatePermission)
		{
			$validation->validate($itemIdentifier, [new UpdatePermission()]);
		}

		$this->addErrors($validation->getErrors());

		return $validation->isSuccess();
	}

	protected function isAssociateTimelineItemConfigurable(array $activity, int $entityTypeId, int $entityId): bool
	{
		$context = new Context(
			new ItemIdentifier($entityTypeId, $entityId),
			Context::REST,
		);

		$item = Container::getInstance()
			->getTimelineScheduledItemFactory()::createItem($context, $activity)
		;

		return $item instanceof Configurable;
	}

	protected function isConfigurableRestAppActivity(array $activity): bool
	{
		$activityProvider = CCrmActivity::GetActivityProvider($activity);

		return $activityProvider === ConfigurableRestApp::class;
	}

	private function notifyTimelineAboutActivityUpdate(array $activity): void
	{
		ActivityController::getInstance()->notifyTimelinesAboutActivityUpdate(
			$activity,
			null,
			true,
		);
	}

	public static function getUnsuitableActivityTypeError(): Error
	{
		return new Error(
			"This activity type is not supported for adding layout blocks",
			'UNSUITABLE_ACTIVITY_TYPE_ERROR',
		);
	}
}
