<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;

final class YandexGPT extends CloudEngine implements IContext, IQueueOptional
{
	use Engine\Trait\YandexGPTCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'YandexGPT Pro 5';
	public const ENGINE_CODE = 'YandexGPT';

	protected const URL_COMPLETIONS = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';

	protected const MODEL = 'gpt://<folder>/yandexgpt/rc';
	protected const TEMPERATURE = 0.75;
	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';
	protected const HTTP_STATUS_OK = 200;

	protected int $modelContextLimit = 3000;

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return 'yandexgpt-lite';
	}

	private function addPostParamsCredential(array &$postParams): void
	{
	}
}
