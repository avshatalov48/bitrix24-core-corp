<?php

namespace Bitrix\Tasks\Flow\Controllers\View;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Helper\Analytics;

class Access extends Controller
{
	use MessageTrait;

	protected int $userId;

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
	}

	/**
	 * @restMethod tasks.flow.View.Access.check
	 */
	public function checkAction(
		int $flowId,
		string $context = '',
		string $demoFlow = '',
		string $guideFlow = ''
	): ?Component
	{
		$action = $flowId <= 0 ? FlowAction::CREATE : FlowAction::UPDATE;

		if ($context === 'edit-form')
		{
			$element = $demoFlow === 'Y' ? 'create_demo_button' : 'create_button';
			$subSection = $guideFlow === 'Y' ? 'flow_guide' : 'flows_grid';
			$element = $guideFlow === 'Y' ? 'guide_button' : $element;

			$this->sendFlowCreateStartAnalytics($element, $subSection);
		}

		if (FlowAccessController::can($this->userId, $action, $flowId))
		{
			return null;
		}

		return $this->getAccessDeniedStub();
	}

	protected function getAccessDeniedStub(): Component
	{
		return new Component(
			'bitrix:tasks.error',
			'',
			[
				'TITLE' => $this->getAccessDeniedError(),
				'DESCRIPTION' => $this->getAccessDeniedDescription(),
			]
		);
	}

	private function sendFlowCreateStartAnalytics(string $element, string $subSection): void
	{
		$demoSuffix = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

		Analytics::getInstance($this->userId)->onFlowCreate(
			Analytics::EVENT['flow_create_start'],
			Analytics::SECTION['tasks'],
			Analytics::ELEMENT[$element],
			Analytics::SUB_SECTION[$subSection],
			['p1' => 'isDemo_' . $demoSuffix]
		);
	}
}
