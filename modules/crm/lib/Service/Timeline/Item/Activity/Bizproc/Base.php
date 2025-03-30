<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Common;

abstract class Base extends Activity
{
	use Workflow;

	private const ACTIVITY_TYPE_ID = 'BizprocWorkflowCompleted';

	protected function getActivityTypeId(): string
	{
		return self::ACTIVITY_TYPE_ID;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::BIZPROC;
	}

	public function getLogo(): ?Body\Logo
	{
		return Common\Logo::getInstance(Common\Logo::BIZPROC)
			->createLogo()
			?->setInCircle()
			?->setAdditionalIconCode('check')
			?->setAdditionalIconType(Body\Logo::ICON_TYPE_SUCCESS)
		;
	}

	protected function getActivityModel()
	{
		return $this->getAssociatedEntityModel();
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public function getMenuItems(): array
	{
		$settings = $this->getActivitySettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		if (empty($workflowId))
		{
			return [];
		}

		return [
			'log' => $this->createLogMenuItem($workflowId)?->setScopeWeb()
		];
	}

	protected function getActivitySettings(): array
	{
		$settings = $this->getActivityModel()?->get('SETTINGS');

		return is_array($settings) ? $settings : [];
	}
}