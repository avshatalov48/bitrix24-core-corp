<?php

namespace Bitrix\Intranet\Integration\Templates\Bitrix24;

use Bitrix\Main\Analytics\AnalyticsEvent;

class ThemePickerAnalytics
{
	private const EVENT = 'change_portal_theme';
	private const TOOL = 'intranet';
	private const CATEGORY = 'portal';
	private const TYPE_COMMON = 'common';
	private const TYPE_PERSONAL = 'personal';
	private const SECTION = 'profile';
	private const DEFAULT_THEME_ID_PREFIX = 'themeName_';
	private const CUSTOM_THEME_ID = 'themeName_custom';
	private AnalyticsEvent $analyticsEvent;

	public function __construct(string $themeId)
	{
		$this->analyticsEvent = new AnalyticsEvent(self::EVENT, self::TOOL, self::CATEGORY);
		$this->analyticsEvent->setType(self::TYPE_PERSONAL);
		$this->analyticsEvent->setSection(self::SECTION);
		$this->analyticsEvent->setP1($this->prepareThemeId($themeId));
	}

	public function send(): void
	{
		$this->analyticsEvent->send();
	}

	public function setDefaultTheme(): self
	{
		$this->analyticsEvent->setType(self::TYPE_COMMON);

		return $this;
	}

	private function prepareThemeId(string $themeId): string
	{
		$pattern = '/custom_\d+/';

		return preg_match($pattern, $themeId) ? self::CUSTOM_THEME_ID : self::DEFAULT_THEME_ID_PREFIX . $themeId;
	}
}