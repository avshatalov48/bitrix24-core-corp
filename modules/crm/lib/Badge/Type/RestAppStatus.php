<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\Model\CustomBadgeTable;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;

class RestAppStatus extends Badge
{
	protected ?array $customBadges = null;

	public function getFieldName(): string
	{
		if (!is_array($this->customBadges))
		{
			$this->customBadges = $this->loadCustomBadges();
		}
		foreach ($this->customBadges as $customBadge)
		{
			if ($customBadge['CODE'] === $this->getValue())
			{
				return $this->getTranslatedText($customBadge['TITLE']);
			}
		}

		return '';
	}

	public function getValuesMap(): array
	{
		if (!is_array($this->customBadges))
		{
			$this->customBadges = $this->loadCustomBadges();
		}
		$result = [];
		foreach ($this->customBadges as $customBadge)
		{
			$result[] = new ValueItem(
				$customBadge['CODE'],
				$this->getTranslatedText($customBadge['VALUE']),
				$this->getTextColor((string)$customBadge['TYPE']),
				$this->getBackgroundColor((string)$customBadge['TYPE']),
			);
		}

		return $result;
	}

	public function getType(): string
	{
		return self::REST_APP_TYPE;
	}

	private function loadCustomBadges(): array
	{
		$result = CustomBadgeTable::getList()->fetchAll();
		foreach ($result as $key => $record)
		{
			if ($record['TITLE'])
			{
				$result[$key]['TITLE'] = Json::decode($record['TITLE']);
			}
			if ($record['VALUE'])
			{
				$result[$key]['VALUE'] = Json::decode($record['VALUE']);
			}
		}

		return $result;
	}

	private function getTranslatedText($rawText): string
	{
		if (is_string($rawText))
		{
			return $rawText;
		}
		if (is_array($rawText))
		{
			$currentLang = Application::getInstance()->getContext()->getLanguage();
			$text = (string)($rawText[$currentLang] ?? $rawText['en'] ?? '');
			if ($text !== '')
			{
				return $text;
			}
			if (!empty($rawText))
			{
				return (string)array_unshift($rawText);
			}
		}

		return '';
	}

	private function getTextColor(string $type): string
	{
		switch ($type)
		{
			case CustomBadgeTable::TYPE_SUCCESS:
				return '#76950b';
			case CustomBadgeTable::TYPE_FAILURE:
				return '#dd4e5f';
			case CustomBadgeTable::TYPE_WARNING:
				return '#755c18';
			case CustomBadgeTable::TYPE_PRIMARY:
				return '#1097c2';
			case CustomBadgeTable::TYPE_SECONDARY:
				return '#79818b';
		}

		return '#79818b';
	}

	private function getBackgroundColor(string $type): string
	{
		switch ($type)
		{
			case CustomBadgeTable::TYPE_SUCCESS:
				return '#e9f6d6';
			case CustomBadgeTable::TYPE_FAILURE:
				return '#f3d5d3';
			case CustomBadgeTable::TYPE_WARNING:
				return '#ebe997';
			case CustomBadgeTable::TYPE_PRIMARY:
				return '#dcf5fd';
			case CustomBadgeTable::TYPE_SECONDARY:
				return '#e0e2e4';
		}

		return '#e0e2e4';
	}
}
