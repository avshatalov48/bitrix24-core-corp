<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

final class RendererFactory
{
	private static array $renderers = [
		TextRenderer::TYPE_TEXT_FIELD_TEXT => TextRenderer::class,
		TextRenderer::TYPE_TEXT_FIELD_EMAIL => TextRenderer::class,
		TextRenderer::TYPE_TEXT_FIELD_URL => TextRenderer::class,
		TextRenderer::TYPE_TEXT_FIELD_NUMBER => TextRenderer::class,
		TextareaRenderer::TYPE_TEXTAREA_FIELD => TextareaRenderer::class,
		SelectRenderer::TYPE_SELECT_FIELD => SelectRenderer::class,
		RadioRenderer::TYPE_RADIO_FIELD => RadioRenderer::class,
		CustomRenderer::TYPE_CUSTOM_FIELD => CustomRenderer::class,
	];

	public static function create(array $field, array $config = []): RendererInterface
	{
		$type = $field['TYPE'] ?? '';
		if (!self::supports($type))
		{
			throw new \InvalidArgumentException("Unsupported field type: {$type}");
		}

		$rendererClass = self::$renderers[$type];

		return new $rendererClass($field, $config);
	}

	public static function supports(string $type): bool
	{
		return isset(self::$renderers[$type]);
	}

	public static function getSupportedTypes(): array
	{
		return array_keys(self::$renderers);
	}
}
