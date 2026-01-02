<?php declare(strict_types=1);

namespace Bitrix\AI\Facade;

use Bitrix\Main\License\UrlProvider;

class AiUrlManager
{
	protected const BITRIX_MODELS_HOST = 'https://b24ai.';
	protected const AUDIO_TRANSCRIPTIONS_ENDPOINT = '/v1/audio/transcriptions';
	protected const AUDIO_CALL_TRANSCRIPTIONS_ENDPOINT = '/v1/call/transcriptions';
	protected const CHAT_COMPLETIONS_ENDPOINT = '/v1/chat/completions';
	protected const PROXY_HOST = 'https://ai-proxy.';
	protected const STATIC_PROXY_HOST = 'https://static-ai-proxy.';
	protected const SERVER_LIST_CONFIG_ENDPOINT = '/settings/config.json';
	protected const PROMPT_BASE_ENDPOINT = '/v2/box.json';
	protected string $domain = '';

	public function __construct(UrlProvider $urlProvider)
	{
		$this->domain = $urlProvider->getTechDomain();
	}

	public function getAudioCompletionsUrl(): string
	{
		return self::BITRIX_MODELS_HOST . $this->domain . self::AUDIO_TRANSCRIPTIONS_ENDPOINT;
	}

	public function getAudioCallCompletionsUrl(): string
	{
		return self::BITRIX_MODELS_HOST . $this->domain . self::AUDIO_CALL_TRANSCRIPTIONS_ENDPOINT;
	}

	public function getChatCompletionsUrl(): string
	{
		return self::BITRIX_MODELS_HOST . $this->domain . self::CHAT_COMPLETIONS_ENDPOINT;
	}

	public function getPromptBaseUrl(): string
	{
		return self::STATIC_PROXY_HOST . $this->domain . self::PROMPT_BASE_ENDPOINT;
	}

	public function getServerListConfigUrl(): string
	{
		return self::PROXY_HOST . $this->domain . self::SERVER_LIST_CONFIG_ENDPOINT;
	}
}
