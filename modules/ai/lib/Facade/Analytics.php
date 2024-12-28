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
	private const PROMPT_SECTION_REWRITE = [
		'set_ai_session_name' => 'system_chat',

		'translate_picture_request' => 'system_pic',

		'summarize_transcript' => 'system_crm_call',
		'extract_form_fields' => 'system_crm_call',
		'scoring_criteria_extraction' => 'system_crm_call',
		'call_scoring' => 'system_crm_call',

		'flows_recommendations' => 'system_flow',

		'meeting_summarization' => 'system_videocall',
		'meeting_overview' => 'system_videocall',
		'meeting_insights' => 'system_videocall',

		'site_ai_data' => 'system_site',
		'site_ai_blocks_content' => 'system_site',
		'site_ai_image_prompts_text' => 'system_site',
		'site_ai_block_content' => 'system_site',
	];

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
			if ($engine->getPayload() instanceof Prompt)
			{
				if (array_key_exists($engine->getPayload()->getPromptCode(), self::PROMPT_SECTION_REWRITE))
				{
					$section = self::PROMPT_SECTION_REWRITE[$engine->getPayload()->getPromptCode()];
				}
				else if($engine->getPayload()->hasSystemCategory())
				{
					$section = 'system';
				}
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
