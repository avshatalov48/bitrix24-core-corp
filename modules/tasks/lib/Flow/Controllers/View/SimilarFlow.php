<?php

namespace Bitrix\Tasks\Flow\Controllers\View;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\UI\ScopeDictionary;
use CComponentEngine;
use Throwable;

class SimilarFlow extends Controller
{
	use MessageTrait;
	use ControllerTrait;

	protected int $userId;
	protected FlowProvider $provider;

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->provider = new FlowProvider();
	}

	public function configureActions(): array
	{
		return [
			'list' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function listAction(int $flowId, PageNavigation $pageNavigation): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$flow = $this->provider->getFlow($flowId, ['ID', 'CREATOR_ID']);
			$similarFlowsQuery = (new FlowQuery($this->userId))
				->setSelect(['ID', 'NAME', 'CREATOR_ID', 'GROUP_ID', 'TEMPLATE_ID', 'ACTIVE'])
				->setWhere(
					(new ConditionTree())
						->whereNot('ID', $flow->getId())
						->where('CREATOR_ID', $flow->getCreatorId())
						->where('ACTIVE', 1)
				)
				->setPageNavigation($pageNavigation)
				->setOrderBy(['ACTIVITY' => 'DESC', 'ID' => 'DESC']);

			$similarFlowsCollection = $this->provider->getList($similarFlowsQuery);
		}
		catch (ProviderException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		$similarFlows = [];
		foreach ($similarFlowsCollection as $flow)
		{
			$similarFlows[] = [
				'id' => $flow->getId(),
				'name' => $flow->getName(),
				'createTaskUri' => $this->prepareCreateTaskUri($flow),
			];
		}

		return $similarFlows;
	}

	protected function prepareCreateTaskUri(Flow $flow): string
	{
		$createButtonUri = new Uri(
			CComponentEngine::makePathFromTemplate(
				RouteDictionary::PATH_TO_USER_TASK,
				[
					'action' => 'edit',
					'task_id' => 0,
					'user_id' => $this->userId,
				],
			),
		);

		$createButtonUri->addParams(['SCOPE' => ScopeDictionary::SCOPE_TASKS_FLOW]);
		$createButtonUri->addParams(['FLOW_ID' => $flow->getId()]);
		$createButtonUri->addParams(['GROUP_ID' => $flow->getGroupId()]);

		if ($flow->getTemplateId())
		{
			$createButtonUri->addParams(['TEMPLATE' => $flow->getTemplateId()]);
		}

		$demoSuffix = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

		$createButtonUri->addParams([
			'ta_cat' => 'task_operations',
			'ta_sec' => \Bitrix\Tasks\Helper\Analytics::SECTION['flows'],
			'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['flows_grid'],
			'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['flow_popup'],
			'p1' => 'isDemo_' . $demoSuffix,
		]);

		return $createButtonUri->getUri();
	}
}