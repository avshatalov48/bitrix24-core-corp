<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Engine\Trait\KandinskyCommonTrait;

class Kandinsky extends ImageCloudEngine implements IContext, IQueueOptional
{
	use KandinskyCommonTrait;

	protected const ENGINE_NAME = 'Kandinsky';
	public const ENGINE_CODE = 'Kandinsky';

	protected const URL_COMPLETIONS = 'https://api-key.fusionbrain.ai/key/api/v1/';
	protected const URL_COMPLETIONS_QUEUE_PATH = '/api/v1/kandinsky/image/generation';

	protected const MODEL = 'Kandinsky';
	protected const MODEL_ID = 4;// id of kandinsky model

	protected const DEFAULT_FORMAT = 'square';
	protected const MAX_WIDTH = 1024;
	protected const MAX_HEIGHT = 1024;

	protected const HTTP_STATUS_OK = 200;

	protected int $modelContextLimit = 1000;

	protected function getDefaultModel(): string
	{
		return self::MODEL;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	protected function getCompletionsQueueUrlPath(): string
	{
		return self::URL_COMPLETIONS_QUEUE_PATH;
	}
}
