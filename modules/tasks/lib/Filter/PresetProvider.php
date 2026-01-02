<?php

namespace Bitrix\Tasks\Filter;

use Bitrix\Tasks\Filter\Scope\CollaberScope;
use Bitrix\Tasks\Filter\Scope\DefaultScope;
use Bitrix\Tasks\Filter\Scope\RelationScope;
use Bitrix\Tasks\Filter\Scope\ScrumScope;
use Bitrix\Tasks\Helper\Filter;

class PresetProvider
{
	public function __construct(private readonly Filter|null $filter)
	{}

	public function getScopePresetCollection(Scope $scope): PresetCollection
	{
		$scopeConfig = match ($scope)
		{
			Scope::COLLABER => new CollaberScope(),
			Scope::SCRUM => new ScrumScope($this->filter?->getUserId() ?? 0),
			Scope::RELATION => new RelationScope(),
			default => new DefaultScope(),
		};

		return $scopeConfig->getPresetCollection();
	}
}
