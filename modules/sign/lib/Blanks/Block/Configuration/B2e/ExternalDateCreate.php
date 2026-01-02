<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Main\Application;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Main\Type\Date;

class ExternalDateCreate extends Configuration
{
	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		if ($document->externalDateCreateSourceType === Type\Document\ExternalDateCreateSourceType::MANUAL)
		{
			$culture = Application::getInstance()->getContext()->getCulture();
			$cultureDateFormat = (string)$culture?->getDateFormat();
			$dateFormat = $cultureDateFormat ? Date::convertFormatToPhp($cultureDateFormat) : 'Y-m-d';

			return [
				'show' => true,
				'text' => $document->externalDateCreate?->format($dateFormat) ?? '',
			];
		}

		return [
			'text' => '',
		];
	}
}