<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Provider\TemplateProvider;
use Bitrix\TasksMobile\Dto\DiskFileDto;
use Bitrix\TasksMobile\Dto\FlowRequestFilter;
use Bitrix\TasksMobile\Dto\RelatedCrmItemDto;
use Bitrix\TasksMobile\Dto\TaskTemplateDto;
use Bitrix\TasksMobile\Dto\TaskTemplateTagDto;
use Bitrix\TasksMobile\Enum\TaskPriority;
use Bitrix\TasksMobile\Provider\ChecklistProvider;
use Bitrix\TasksMobile\Provider\DiskFileProvider;
use Bitrix\TasksMobile\Provider\FlowProvider;
use Bitrix\TasksMobile\Provider\GroupProvider;
use Bitrix\TasksMobile\Settings;

final class Flow extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'loadItems',
			'loadByIds',
			'getTaskCreateMetadata',
		];
	}

	/**
	 * @restMethod tasksmobile.Flow.loadItems
	 * @param FlowRequestFilter $flowSearchParams
	 * @param PageNavigation $pageNavigation
	 * @param array $extra
	 * @param string $order
	 * @return array
	 */
	public function loadItemsAction(
		FlowRequestFilter $flowSearchParams,
		PageNavigation $pageNavigation,
		array $extra = [],
		string $order = FlowProvider::ORDER_ACTIVITY,
	): array
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return [];
		}

		$result = (new FlowProvider(
			$this->getCurrentUser()->getId(),
			$order,
			$extra,
			$flowSearchParams,
			$pageNavigation
		))->getFlows();

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @restMethod tasksmobile.Flow.disableShowFlowsFeatureInfoFlagInDB
	 * @return bool
	 */
	public function disableShowFlowsFeatureInfoFlagInDBAction(): bool
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return false;
		}

		return (new FlowProvider($this->getCurrentUser()->getId()))->disableShowFlowsFeatureInfoFlag();
	}

	/**
	 * @restMethod tasksmobile.Flow.subscribeUserToPull
	 * @return bool
	 */
	public function subscribeUserToPullAction(): bool
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return false;
		}

		return (new FlowProvider($this->getCurrentUser()->getId()))->subscribeCurrentUserToPull();
	}

	/**
	 * @restMethod tasksmobile.Flow.loadByIds
	 * @return array
	 */
	public function loadByIdsAction(array $ids = []): array
	{
		if (empty($ids))
		{
			return [];
		}

		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return [];
		}

		$result = (new FlowProvider($this->getCurrentUser()->getId()))->getFlowsById($ids);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @return array
	 */
	public function getCountersAction(): array
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return [];
		}

		return (new FlowProvider($this->getCurrentUser()->getId()))->getTotalCounters();
	}

	/**
	 * @return array
	 */
	public function getSearchBarPresetsAction(): array
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return [];
		}
		$presets = (new FlowProvider($this->getCurrentUser()->getId()))->getSearchBarPresets();

		return [
			'presets' => $presets,
			'counters' => [],
		];
	}

	/**
	 * @restMethod tasksmobile.Flow.getTaskCreateMetadata
	 */
	public function getTaskCreateMetadataAction(int $flowId, CurrentUser $user, ?int $copyId = null): array
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			$this->addError(new Error('Flow feature is not available.'));

			return [];
		}

		if (!FlowAccessController::can($user->getId(), FlowAction::READ, $flowId))
		{
			$this->addError(new Error('Flow is not found or not accessible'));

			return [];
		}

		$flow = (new FlowProvider($user->getId()))->getFlowById($flowId);

		if (!$flow)
		{
			$this->addError(new Error('Flow is not found or not accessible'));

			return [];
		}

		$template = null;
		$userIds = [];

		if ($flow->templateId)
		{
			// todo remove this check after tasks 24.200.0 release
			if (method_exists(TemplateProvider::class, 'getById'))
			{
				$select = [
					'TITLE',
					'DESCRIPTION',
					'PRIORITY',
					'ACCOMPLICES',
					'AUDITORS',
					'TAGS',
					CRM\UserField::getMainSysUFCode(),
				];
				$data = TemplateProvider::getById($flow->templateId, $select);

				if ($data)
				{
					$accomplices = unserialize($data['ACCOMPLICES'], ['allowed_classes' => false]);
					$auditors = unserialize($data['AUDITORS'], ['allowed_classes' => false]);

					$template = new TaskTemplateDto(
						name: $data['TITLE'],
						description: $data['DESCRIPTION'],
						priority: TaskPriority::tryFrom((int)$data['PRIORITY']) ?? TaskPriority::Normal,
						accomplices: array_map('intval', $accomplices ?? []),
						auditors: array_map('intval', $auditors ?? []),
						files: $this->prepareDiskFiles($flow->templateId, $user->getId()),
						checklist: $this->prepareChecklist($flow->templateId, $user->getId()),
						tags: array_map(
							fn($tag) => new TaskTemplateTagDto(id: $tag, name: $tag),
							$data['TAGS'] ?? [],
						),
						crm: $this->prepareCrmElements($data),
					);

					$userIds = array_unique(
						array_merge($template->accomplices, $template->auditors)
					);
				}
			}
			else
			{
				$result = (new \Bitrix\Tasks\Item\Task\Template($flow->templateId, $user->getId()))
					->skipAccessCheck()
					->transform(new \Bitrix\Tasks\Item\Converter\Task\Template\ToTask());

				if ($result->isSuccess())
				{
					$data = \Bitrix\Tasks\Manager\Task::convertFromItem($result->getInstance());

					$template = new TaskTemplateDto(
						name: $data['TITLE'],
						description: $data['DESCRIPTION'],
						priority: TaskPriority::tryFrom((int)$data['PRIORITY']) ?? TaskPriority::Normal,
						accomplices: array_map('intval', $data['ACCOMPLICES'] ?? []),
						auditors: array_map('intval', $data['AUDITORS'] ?? []),
						files: $this->prepareDiskFiles($flow->templateId, $user->getId()),
						checklist: $this->prepareChecklist($flow->templateId, $user->getId()),
						tags: array_map(
							fn($tag) => new TaskTemplateTagDto(id: $tag, name: $tag),
							$data['TAGS'] ?? [],
						),
						crm: $this->prepareCrmElements($data),
					);

					$userIds = array_unique(
						array_merge($template->accomplices, $template->auditors)
					);
				}
			}
		}

		return $this->convertKeysToCamelCase([
			'template' => $template,
			'checklist' => $this->prepareChecklistCopy($copyId, $user->getId()),
			'flow' => $flow,
			'groups' => GroupProvider::loadByIds([$flow->groupId]),
			'users' => empty($userIds) ? [] : UserRepository::getByIds($userIds),
		]);
	}

	private function prepareChecklistCopy(?int $taskId, ?int $userId): ?array
	{
		if ($taskId === null)
		{
			return null;
		}

		$canReadTask = TaskAccessController::can(
			$userId,
			ActionDictionary::ACTION_TASK_READ,
			$taskId
		);

		if (!$canReadTask)
		{
			return null;
		}

		$items = (new ChecklistProvider($userId, TaskCheckListFacade::class))
			->getChecklistTree($taskId, true);

		return empty($items) ? null : $items;
	}

	private function prepareChecklist(int $templateId, ?int $userId): ?array
	{
		$items = (new ChecklistProvider($userId, TemplateCheckListFacade::class))
			->getChecklistTree($templateId, true);

		return empty($items) ? null : $this->convertKeysToCamelCase($items);
	}

	private function prepareDiskFiles(int $templateId, ?int $userId): array
	{
		$diskFileProvider = new DiskFileProvider($userId);
		$attachments = $diskFileProvider->getDiskFileAttachmentsByTemplate($templateId);

		return array_values(array_map(fn($attachment) => DiskFileDto::make($attachment), $attachments));
	}

	/**
	 * @param array $data
	 * @return RelatedCrmItemDto[]
	 */
	private function prepareCrmElements(array $data): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$ufCrmTaskCode = CRM\UserField::getMainSysUFCode();
		if (empty($data[$ufCrmTaskCode]) || !is_array($data[$ufCrmTaskCode]))
		{
			return [];
		}

		$ufCrmTask = CRM\UserField::getSysUFScheme()[$ufCrmTaskCode];
		$displayField =
			Field::createByType('crm', $ufCrmTaskCode)
				->setIsMultiple($ufCrmTask['MULTIPLE'] === 'Y')
				->setIsUserField(true)
				->setUserFieldParams($ufCrmTask)
				->setContext(Field::MOBILE_CONTEXT)
		;
		$display = new Display(0, [$ufCrmTaskCode => $displayField]);

		$items = CRM\Fields\Collection::createFromArray($data[$ufCrmTaskCode])->filter();
		$display = $display->setItems([[$ufCrmTaskCode => $items->toArray()]]);
		$res = $display->getValues(0);

		if (
			is_array($res[$ufCrmTaskCode]['config']['entityList'])
			&& count($res[$ufCrmTaskCode]['config']['entityList']) === $items->count()
		)
		{
			$elements = array_values(
				array_combine($items->toArray(), $res[$ufCrmTaskCode]['config']['entityList'])
			);

			return array_map(
				fn($fields) => RelatedCrmItemDto::make($fields),
				$elements
			);
		}

		return [];
	}
}
