<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\Bizproc;
use Bitrix\Bizproc\Api\Data\WorkflowStateService\WorkflowStateToGet;
use Bitrix\Bizproc\Api\Service\WorkflowStateService;
use Bitrix\BizprocMobile\UI\WorkflowUserGridView;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Mobile\UI\StatefulList\BaseAction;

class LoadListAction extends BaseAction
{
	public function run(PageNavigation $pageNavigation, array $extra = [])
	{
		$currentUserId = $this->getCurrentUser()->getId();

		if ($pageNavigation->getOffset() === 0)
		{
			Bizproc\Integration\Push\WorkflowPush::subscribeUser($currentUserId);
		}

		$service = new WorkflowStateService();
		$toGet = new WorkflowStateToGet();

		$toGet->setFilterUserId($currentUserId);

		if (!empty($extra['filterPresetId']))
		{
			$toGet->setFilterPresetId($extra['filterPresetId']);
		}

		if (!empty($extra['filterSearchQuery']) && is_string($extra['filterSearchQuery']))
		{
			$toGet->setFilterSearchQuery($extra['filterSearchQuery']);
		}

		if (!empty($extra['filterParams']['ID']) && is_array($extra['filterParams']['ID']))
		{
			$toGet->setFilterWorkflowIds(
				array_map(
				'strval',
				$extra['filterParams']['ID'],
				)
			);
		}

		$toGet->setLimit($pageNavigation->getLimit());
		$toGet->setOffset($pageNavigation->getOffset());
		$toGet->setAdditionalSelectFields(['STARTED_BY', 'STATE_TITLE', 'TEMPLATE.NAME']);

		$result = $service->getList($toGet);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return $this->showErrors();
		}

		$userIds = [];
		$items = [];
		foreach ($result->getWorkflowStatesCollection() as $workflowState)
		{
			$workflowView = new WorkflowUserGridView($workflowState, $currentUserId);
			$workflowView->setModified($result->getUserModified($workflowState->getId()));

			$items[] = $workflowView;

			$faces = $workflowView->getFaces();
			$userIds[] = $faces->getUniqueUserIds();
		}

		return [
			'items' => $items,
			'users' => UserRepository::getByIds(array_merge(...$userIds)),
			'permissions' => [],
		];
	}
}
