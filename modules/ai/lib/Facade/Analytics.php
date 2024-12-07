<?php

namespace Bitrix\AI\Facade;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Engine\ThirdParty;
use Bitrix\AI\Payload\Audio;
use Bitrix\AI\Payload\Prompt;
use Bitrix\AI\Payload\StyledPicture;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Engine\Response\Converter;

class Analytics
{
	public static function engineGenerateResultEvent(
		string $eventType,
		IEngine $engine,
		array $analyticData,
	)
	{
		$converter = new Converter(Converter::TO_CAMEL);
		if ($engine->getPayload() instanceof StyledPicture)
		{
			$section = $analyticData['c_section'] ?? '';
			$promptCode = 'prompt_' . $converter->process(($engine->getPayload()->getData()['style'] ?? $engine->getPayload()->getData()['prompt']));
			$p3 = 'format_' . $converter->process(($engine->getPayload()->getData()['format'] ?? ''));
			$analyticData['category'] = 'image_operations';
		}
		else
		{
			$section = ($engine->getPayload() instanceof Prompt) ? $engine->getPayload()->getPromptCategory() : '';
			$promptCode = ($engine->getPayload() instanceof Prompt) ? 'prompt_' . $converter->process($engine->getPayload()->getPromptCode()) : '';
			$p3 = ($engine->getPayload()->getRole() !== null) ? 'role_' . $converter->process($engine->getPayload()->getRole()->getCode()) : '';
			if ($engine->getPayload() instanceof Prompt && $engine->getPayload()->hasSystemCategory())
			{
				$section = 'system';
			}

			// temp code for catch situation with null prompt
			if ($engine->getPayload() instanceof Prompt && $promptCode === '')
			{
				AddMessage2Log('EMPTY_PROMPT_DEBUG: ' . var_export($engine->getPayload(), true));
			}
		}

		$category = $analyticData['category'] ?? '';
		if ($category === '')
		{
			$category = ($engine->getPayload() instanceof Audio) ? 'audio_operations' : 'text_operations';
		}

		if ($section === '')
		{
			$section = match ($engine->getContext()->getModuleId())
			{
				'im' => 'from_chat',
				'crm' => 'crm',
				'tasks' => 'tasks',
				default => $engine->getContext()->getModuleId(),
			};
		}

		$providerPrefix = ($engine instanceof ThirdParty) ? 'marketprovider_' : 'provider_';

		$analyticsEvent = new AnalyticsEvent($eventType, 'ai', $category);
		$analyticsEvent
			->setType(($analyticData['type'] ?? ''))
			->setSection($section)
			->setSubSection(($analyticData['c_sub_section'] ?? ''))
			->setElement(($analyticData['c_element'] ?? ''))
			->setP1($promptCode)
			->setP2($providerPrefix . $engine->getName())
			->setP3($p3)
			->setP5($converter->process($engine->getContext()->getContextId()))
			->send();
	}
}
