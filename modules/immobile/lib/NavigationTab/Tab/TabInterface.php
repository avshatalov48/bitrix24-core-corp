<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

interface TabInterface
{
	public function isAvailable(): bool;
	public function getId(): string;
	public function getComponentData(): ?array;
	public function mergeParams(array $params): void;
	public function isNeedMergeSharedParams(): bool;
}
