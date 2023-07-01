<?php

namespace Bitrix\Crm\Tour\Sign;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;

final class CreateDocumentFromDeal extends \Bitrix\Crm\Tour\Base
{
	protected const OPTION_NAME = 'create-document-from-deal';

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour()
			&& ServiceLocator::getInstance()->get('crm.integration.sign')::isEnabled()
		;
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step-' . self::OPTION_NAME,
				'target' => '.crm-btn-dropdown-document',
				'title' => Loc::getMessage('CRM_TOUR_CDFD_STEP_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_CDFD_STEP_TEXT'),
				'article' => 16571388,
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'steps' => [
				'popup' => [
					'width' => 480,
				],
			],
			'showOverlayFromFirstStep' => true,
			'hideTourOnMissClick' => true,
		];
	}
}