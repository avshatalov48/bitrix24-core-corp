<?php

namespace Bitrix\Crm\Tour\Sign;

use Bitrix\Crm\Tour\Base;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;

final class SignDocumentFromSlider extends Base
{
	protected const OPTION_NAME = 'sign-document-from-slider';

	protected function canShow(): bool
	{
		return (
			!$this->isUserSeenTour()
			&& ServiceLocator::getInstance()->get('crm.integration.sign')::isEnabledInCurrentTariff()
		);
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step-' . self::OPTION_NAME,
				'target' => '#crm-document-sign',
				'title' => Loc::getMessage('CRM_TOUR_SDFS_STEP_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_SDFS_STEP_TEXT'),
				'position' => 'left',
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}
}
