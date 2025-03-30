<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Main\Config\Option;
use CUserOptions;

class AiPreset extends Base
{
	private const OPTION_NAME_SHOW_TIME = 'recognition_promo_show_time';
	private const OPTION_NAME_SHOW_COUNT = 'recognition_promo_show_count';
	protected int $numberOfViewsLimit = 3;
	private ?int $numberOfViews = null;
	private ?string $showTime = null;

	protected function canShow(): bool
	{
		$isEnableRecognitionPromo = false;

		// if (!\Bitrix\Main\Loader::includeModule('voximplant'))
		// {
		// 	return $isEnableRecognitionPromo;
		// }
		//
		// $now = time();
		// $crmAiPresetActivationTime = Option::get('crm', 'preset_crm_ai_activated');
		//
		//
		// if ($crmAiPresetActivationTime && (strtotime('+60 days', $crmAiPresetActivationTime) > $now))
		// {
		// 	$recognitionPromoShowTime = $this->getShowTime();
		// 	$recognitionPromoShowCount = $this->getNumberOfViews();
		//
		// 	$isEnableRecognitionPromo = (
		// 		!$recognitionPromoShowTime
		// 		|| ($recognitionPromoShowTime !== 'N' && $recognitionPromoShowTime < $now)
		// 	) && $recognitionPromoShowCount < $this->numberOfViewsLimit;
		// }

		return $isEnableRecognitionPromo;
	}

	protected function getComponentTemplate(): string
	{
		return 'ai_preset';
	}

	protected function getOptions(): array
	{
		return [
			'numberOfViews' => $this->getNumberOfViews(),
			'optionCategory' => $this->getOptionCategory(),
			'optionNameShowCount' => self::OPTION_NAME_SHOW_COUNT,
			'optionNameShowTime' => self::OPTION_NAME_SHOW_TIME,
		];
	}

	private function getNumberOfViews(): ?int
	{
		if (empty($this->numberOfViews))
		{
			$this->numberOfViews = (int)CUserOptions::GetOption($this->getOptionCategory(), self::OPTION_NAME_SHOW_COUNT);
		}

		return $this->numberOfViews;
	}

	private function getShowTime(): ?string
	{
		if (empty($this->showTime))
		{
			$this->showTime = CUserOptions::GetOption($this->getOptionCategory(), self::OPTION_NAME_SHOW_TIME);
		}

		return $this->showTime;
	}
}
