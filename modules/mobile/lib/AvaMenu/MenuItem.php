<?php

namespace Bitrix\Mobile\AvaMenu;

interface MenuItem
{
	public function isAvailable(): bool;
	public function getData(): array;
	public function getId(): string;
	public function getIconId(): string;
}
