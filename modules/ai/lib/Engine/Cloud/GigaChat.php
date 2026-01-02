<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Engine\Models\GigaChatModel;
use Bitrix\AI\Engine\Trait\GigachatCommonTrait;

final class GigaChat extends CloudEngine implements IContext, IQueueOptional
{
	use GigachatCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'GigaChat 2.0';
	public const ENGINE_CODE = 'GigaChat';

	protected const URL_COMPLETIONS = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MAX_OUTPUT_TOKENS = 1024;
	protected const DEFAULT_MODEL = GigaChatModel::Lite;
	protected const TEMPERATURE = 0.87; //from 0 to 2
	protected const VARIANTS = 1; //from 1 to 4
	protected const HTTP_STATUS_OK = 200;

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL->value;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}
}
