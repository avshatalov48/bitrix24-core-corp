<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Tour\Model\ClientVolumeChecker;

final class NumberOfClients extends Base
{
	private array $checkResult = [];

	protected function getComponentTemplate(): string
	{
		return 'popup';
	}

	protected function canShow(): bool
	{
		$this->checkResult = (new ClientVolumeChecker())->getCheckResult();

		return !empty($this->checkResult);
	}

	protected function getSlides(): array
	{
		if (empty($this->checkResult))
		{
			return [];
		}

		return [
			[
				'innerTitle' => $this->checkResult['TITLE'],
				'innerSubTitle' => $this->checkResult['SUBTITLE'],
				'innerDescription' => $this->checkResult['DESCRIPTION'],
				'innerInfo' => $this->checkResult['INFO'],
				'innerImage' => $this->checkResult['MEDAL_ICON'],
			],
		];
	}

	protected function getOptionCategory(): string
	{
		return ClientVolumeChecker::OPTION_CATEGORY;
	}

	protected function getOptionName(): string
	{
		return ClientVolumeChecker::OPTION_NAME_PREFIX;
	}

	protected function getOptions(): array
	{
		return [
			'entityTypeId' => $this->checkResult['ENTITY_TYPE_ID'],
			'checkpoint' => $this->checkResult['CHECKPOINT'],
		];
	}
}
