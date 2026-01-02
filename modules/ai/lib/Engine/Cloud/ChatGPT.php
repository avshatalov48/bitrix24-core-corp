<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Context\Message;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\AI\Tokenizer\GPT;
use Bitrix\Main\Application;

final class ChatGPT extends CloudEngine implements IContext, IQueueOptional
{
	use Engine\Trait\ChatGPTCommonTrait;

	/**
	 * Limit of context messages.
	 * For GPT-4 it's 15, it's nessessary to reduce messages for chat scenario.
	 */
	private const GTP4_CONTEXT_MESSAGES_LIMIT = 16;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'gpt-3.5-turbo';
	public const ENGINE_CODE = 'ChatGPT';

	protected const URL_COMPLETIONS = 'https://api.openai.com/v1/chat/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MODEL = 'gpt-3.5-turbo-16k';
	protected const TEMPERATURE = 0.12;

	protected int $modelContextLimit = 15666;

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL;
	}

	public function setUserParameters(array $params): void
	{
		$toSet = [];

		if (isset($params['temperature']))
		{
			$toSet['temperature'] = (float)$params['temperature'];
		}

		if ($params['model'] ?? null)
		{
			$toSet['model'] = (string)$params['model'];
		}

		$this->setParameters($toSet);
	}

	protected function getSystemParameters(): array
	{
		return [
			'model' => $this->getModel(),
			'temperature' => self::TEMPERATURE,
		];
	}

	/**
	 * Check if engine is available for current region.
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return !in_array($region, ['ru', 'by', 'cn']);
	}

	/**
	 * @inheritDoc
	 */
	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		$zone = Bitrix24::getPortalZone();
		if (\in_array($zone, ['ru', 'by', 'cn'], true))
		{
			return false;
		}

		$prefer = [
			Quality::QUALITIES['translate'],
			Quality::QUALITIES['fields_highlight'],
		];

		return $quality === null || !empty(array_intersect($quality->getRequired(), $prefer));
	}
}
