<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\AiUrlManager;
use Bitrix\AI\Quality;
use Bitrix\Main\DI\ServiceLocator;

final class Bitrix24 extends CloudEngine implements IContext, IQueueOptional
{
	use Engine\Trait\BitrixGPTCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'BitrixGPT 4.5';

	public const ENGINE_CODE = 'b24ai';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MODEL = 'default';
	protected const TEMPERATURE = 0.12;

	protected const ABSENT_QUALITIES = [
		Quality::QUALITIES['give_advice'],
		Quality::QUALITIES['ai_site'],
	];

	protected int $modelContextLimit = 15745;


	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL;
	}

	public function hasQuality(Quality $quality): bool
	{
		return !array_intersect($quality->getRequired(), self::ABSENT_QUALITIES);
	}

	protected function availableForModules(): array
	{
		return [];
	}

	protected function getCompletionsUrl(): string
	{
		return $this->getAiUrlManager()->getChatCompletionsUrl();
	}

	protected function getAiUrlManager(): AiUrlManager
	{
		return ServiceLocator::getInstance()->get(AiUrlManager::class);
	}
}
