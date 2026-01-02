<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

interface RendererInterface
{
	public function render(): string;
	public function validateConfig(): void;
	public static function getSupportedTypes(): array;
}
