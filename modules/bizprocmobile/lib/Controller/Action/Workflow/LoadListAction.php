<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\Bizproc;
use Bitrix\Bizproc\Api\Data\WorkflowStateService\WorkflowStateToGet;
use Bitrix\Bizproc\Api\Service\WorkflowStateService;
use Bitrix\BizprocMobile\UI\WorkflowView;
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

		if (isset($extra['filterPresetId']))
		{
			$toGet->setFilterPresetId($extra['filterPresetId']);
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

		$result = $service->getFullFilledList($toGet);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return $this->showErrors();
		}

		$items = array_map(
			static fn($workflow) => new WorkflowView($workflow, $currentUserId),
			array_values($result->getWorkflowStatesList()),
		);

		$userIds = array_column($result->getMembersInfo(), 'ID');

		return [
			'items' => $items,
			'users' => UserRepository::getByIds($userIds),
			'permissions' => [],
		];
	}
}
