<?php

namespace Bitrix\Crm\Service\WebForm;

interface WebFormScenarioOption
{
	public function getId(): string;
	public function getCategory(): string;
	public function getTitle(): string;
	public function getDescription(): string;
	public function getIcon(): string;
	public function getMenuItems(): array;
	public function getOptions(): array;
}