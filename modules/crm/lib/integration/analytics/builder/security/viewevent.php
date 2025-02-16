<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Security;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Request;

final class ViewEvent extends AbstractBuilder
{
	public static function createFromRequest(Request $request): self
	{
		$analyticsFromRequest = $request->get('st');
		if (!is_array($analyticsFromRequest))
		{
			return new self();
		}

		return self::createFromArray($analyticsFromRequest);
	}

	public static function createFromArray(array $analyticsData): self
	{
		$event = new self();

		if (!empty($analyticsData['c_section']) && is_string($analyticsData['c_section']))
		{
			$event->setSection($analyticsData['c_section']);
		}

		if (!empty($analyticsData['c_sub_section']) && is_string($analyticsData['c_sub_section']))
		{
			$event->setSubSection($analyticsData['c_sub_section']);
		}

		return $event;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	/**
	 * @inheritDoc
	 */
	protected function buildCustomData(): array
	{
		return [
			'event' => Dictionary::EVENT_SETTINGS_VIEW,
			'category' => Dictionary::CATEGORY_SETTINGS_OPERATIONS,
		];
	}
}
