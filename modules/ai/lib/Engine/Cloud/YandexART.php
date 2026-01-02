<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Engine\Trait\YandexARTCommonTrait;

class YandexART extends ImageCloudEngine implements IContext, IQueueOptional
{
	use YandexARTCommonTrait;

	protected const ENGINE_NAME = 'YandexART';
	public const ENGINE_CODE = 'YandexART';

	protected const URL_COMPLETIONS = 'https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync';
	protected const URL_COMPLETIONS_QUEUE_PATH = '/api/v1/image/generation';

	protected const MODEL = 'art://<folder>/yandex-art/latest';

	protected const DEFAULT_FORMAT = 'square';
	protected const DEFAULT_SEED = 2;// grain is any number from 0 to 2ˆ64

	protected const HTTP_STATUS_OK = 200;

	protected function getDefaultModel(): string
	{
		return 'yandex-art';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->preparePostParams();
			$postParams = array_merge($this->getParameters(), $postParams);
		}

		return $postParams;
	}

}
