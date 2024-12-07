<?php

namespace Bitrix\Crm\Component\EntityList\NearestActivity\FrontIntegration;

use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\ItemIdentifier;
use CCrmViewHelper;
use CUtil;

abstract class FrontIntegration
{
	private bool $allowEdit;

	public function __construct(bool $allowEdit)
	{
		$this->allowEdit = $allowEdit;
	}

	public static function make(bool $isItemListMode, bool $allowEdit): self
	{
		if ($isItemListMode)
		{
			return new ItemListFrontendIntegration($allowEdit);
		}

		return new CommonFrontIntegration($allowEdit);
	}

	abstract public function onClickViewHandler(string $preparedGridId, int $activityId): string;

	abstract public function onClickAddHandler(
		string $preparedGridId,
		int $activityId,
		ItemIdentifier $itemIdentifier,
	): string;

	abstract public function isActivityViewSupport(array $activity): bool;

	public function getSubject(array $activity): string
	{
		if (isset($activity['PROVIDER_ID']))
		{
			$provider = \CCrmActivity::GetProviderById($activity['PROVIDER_ID']);
			if ($provider)
			{
				return $provider::getActivityTitle(array_merge($activity, ['COMPLETED' => 'N']));
			}
		}

		return '';
	}

	protected function getAllowEdit(): bool
	{
		return $this->allowEdit;
	}

	protected function getSettings(ItemIdentifier $itemIdentifier): string
	{
		$pingSettings = $this->getPingSettings($itemIdentifier);
		$calendarSettings = $this->calendarSettings();
		$colorSettings = $this->colorSettings();

		return CUtil::PhpToJSObject([
			'pingSettings' => $pingSettings,
			'calendarSettings' => $calendarSettings,
			'colorSettings' => $colorSettings,
		]);
	}

	protected function getPingSettings(ItemIdentifier $itemIdentifier): array
	{
		return (new TodoPingSettingsProvider(
			$itemIdentifier->getEntityTypeId(),
			$itemIdentifier->getCategoryId() ?? 0,
		))->fetchForJsComponent();
	}

	protected function colorSettings(): ?array
	{
		return (new ColorSettingsProvider())->fetchForJsComponent();
	}

	protected function calendarSettings(): array
	{
		return (new CalendarSettingsProvider())->fetchForJsComponent();
	}

	protected  function getCurrentUserInfo(): string
	{
		return CUtil::PhpToJSObject(
			CCrmViewHelper::getUserInfo(true, false)
		);
	}
}