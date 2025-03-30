<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Type\DateTime;

abstract class AbstractPermissions extends Base
{
	private const ARTICLE = 23240636;

	protected UserPermissions $userPermissions;

	protected function __construct()
	{
		parent::__construct();
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	protected function canShow(): bool
	{
		return
			Feature::enabled(Feature\PermissionsLayoutV2::class)
			&& !$this->isUserSeenTour()
			&& $this->hasPermissions()
		;
	}

	protected function getShowDeadline(): ?DateTime
	{
		return new DateTime('01.04.2025', 'd.m.Y');
	}

	protected function getPortalMaxCreatedDate(): ?DateTime
	{
		return new DateTime('01.11.2024', 'd.m.Y');
	}

	protected function target(): string
	{
		return '.ui-toolbar-right-buttons > button';
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => static::OPTION_NAME,
				'title' => $this->title(),
				'text' => $this->text(),
				'target' => $this->target(),
				'article' => self::ARTICLE,
				'position' => 'top',
				'useDynamicTarget' => false,
				'ignoreIfTargetNotFound' => true,
				'reserveTargets' => $this->reserveTargets(),
			],
		];
	}

	protected function reserveTargets(): array
	{
		return [];
	}

	abstract protected function hasPermissions(): bool;

	abstract protected function title(): string;

	abstract protected function text(): string;
}
