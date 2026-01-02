<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Extension;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\V2\FormV2Feature;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Integration\Intranet\Settings;

class Config
{
	public function getCoreSettings(): array
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$tariffService = Container::getInstance()->getTariffService();

		$editPath = Container::getInstance()->getLinkService()->getCreateTask($currentUserId);

		$settingsTools = (new Settings());

		return [
			'currentUserId' => $currentUserId,
			'limits' => [
				'mailUserIntegration' => $tariffService->isMailUserAvailable(),
				'mailUserIntegrationFeatureId' => $tariffService->getMailUserFeature(),
				'project' => $tariffService->isProjectAvailable(),
				'projectFeatureId' => $tariffService->getProjectFeatureId(),
				'stakeholders' => $tariffService->isStakeholderAvailable(),
			],
			'defaultDeadline' => Container::getInstance()->getDeadlineRepository()->getByUserId($currentUserId)->toArray(),
			'chatType' => \Bitrix\Tasks\V2\Internal\Integration\Im\Chat::ENTITY_TYPE,
			'features' => [
				'isV2Enabled' => FormV2Feature::isOn(),
				'isMiniformEnabled' => FormV2Feature::isOn('miniform'),
				'isFlowEnabled' => FlowFeature::isOn(),
				'isProjectsEnabled' => $settingsTools->isToolAvailable(Settings::TOOLS['projects']),
				'isTemplateEnabled' => $settingsTools->isToolAvailable(Settings::TOOLS['templates']),
			],
			'paths' => [
				'editPath' => $editPath,
			],
		];
	}

	public function getAnalyticsSettings(): array
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		$analyticsHelper = Analytics::getInstance($currentUserId);
		$tariffService = Container::getInstance()->getTariffService();

		return [
			'userType' => $analyticsHelper->getUserTypeParameter(),
			'isDemo' => $tariffService->isDemo(),
		];
	}
}
