<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\RepeatSale\Segment\DataFormatter;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Localization\Loc;

class CrmRepeatSaleSegmentTitleField extends BaseLinkedEntitiesField
{
	public const TYPE = 'crm_repeat_sale_segment_title';

	protected function getFormattedValueForKanban($fieldValue, ?int $itemId = null, ?Options $displayOptions = null): string
	{
		$this->setWasRenderedAsHtml(true);

		return $this->getPreparedValue((int)$fieldValue);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		return [
			'value' => $this->getPreparedValue((int)$fieldValue),
		];
	}

	protected function getPreparedValue(int $elementId): string
	{
		$dataFormatter = DataFormatter::getInstance();
		$title = $dataFormatter->getTitle($elementId);
		if ($title === null)
		{
			return '';
		}

		$container = Container::getInstance();
		if (!$container->getUserPermissions()->repeatSale()->canRead())
		{
			$container->getLocalization()->loadMessages();

			return Loc::getMessage('CRM_COMMON_HIDDEN_ITEM');
		}

		$uri = $dataFormatter->getUri($elementId);

		return '<a 
			href="' . $uri . '"
			onclick="BX.Crm.Router.Instance.openRepeatSaleSegmentSlider(' . $elementId . '); return false;"
			>' . $this->sanitizeString($title) . '</a>
		';
	}
}
