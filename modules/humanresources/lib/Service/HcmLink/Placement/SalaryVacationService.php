<?php

namespace Bitrix\HumanResources\Service\HcmLink\Placement;

use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\PlacementType;
use Bitrix\Main;

class SalaryVacationService
{
	public function isConfigured(): bool
	{
		return !empty($this->getHandlerList());
	}

	public function isAvailableForUser(int $userId): bool
	{
		return Container::getHcmLinkPersonRepository()->existByUserId($userId);
	}

	private function getHandlerList(): array
	{
		if (!Main\Loader::includeModule('rest'))
		{
			return [];
		}

		return \Bitrix\Rest\PlacementTable::getHandlersList(
			PlacementType::SALARY_VACATION->value,
		);
	}

	public function getSettingsForFrontendByUser(int $userId): array
	{
		if (!Feature::instance()->isHcmLinkAvailable())
		{
			return [
				'show' => false,
			];
		}

		if (
			!$this->isConfigured()
			|| !$this->isAvailableForUser($userId)
		)
		{
			return [
				'show' => true,
				'disabled' => true,
			];
		}

		$handlerList = $this->getHandlerList();

		return [
			'show' => true,
			'options' => array_map(
				static function ($handler) {
					$result = [
						'appId' => $handler['APP_ID'],
						'id' => $handler['ID'],
						'title' => $handler['TITLE'],
						'code' => PlacementType::SALARY_VACATION->value,
						'options' => []
					];

					if ($handler['OPTIONS']['width'])
					{
						$result['options']['bx24_width'] = $handler['OPTIONS']['width'];
					}

					return $result;
				},
				$handlerList,
			),
		];
	}
}