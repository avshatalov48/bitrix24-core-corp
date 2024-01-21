<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Settings\Mode;
use Bitrix\Crm\Traits;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Config\Option;
use CCrmOwnerType;

final class Analytics
{
	use Traits\Singleton;

	private const AI_DEV_ANALYTICS_ENABLE_LOGGER_OPTION_NAME = 'AI_DEV_ANALYTICS_ENABLE_LOGGER_OPTION_NAME';

	// fixed values
	private const CONTEXT_CATEGORY = 'crm_operations';
	private const CONTEXT_TOOL = 'AI';
	private const CONTEXT_SECTION = 'crm';

	public const CONTEXT_EVENT_CALL = 'call_parsing'; // operations with mail and chats are possible in future

	public const CONTEXT_TYPE_AUTO = 'auto';
	public const CONTEXT_TYPE_MANUAL = 'manual'; // implemented on the frontend
	public const CONTEXT_TYPE_UNKNOWN = 'unknown';

	public const CONTEXT_ELEMENT_COPILOT_BTN = 'copilot_button';
	public const CONTEXT_ELEMENT_FEEDBACK_SEND = 'feedback_send';				// implemented on the frontend
	public const CONTEXT_ELEMENT_FEEDBACK_REFUSED = 'feedback_refused';			// implemented on the frontend
	public const CONTEXT_ELEMENT_CONFLICT_ACCEPT = 'conflict_accept_changes';	// implemented on the frontend
	public const CONTEXT_ELEMENT_CONFLICT_CANCEL = 'conflict_cancel_changes';	// implemented on the frontend

	public const STATUS_SUCCESS = 'success';
	public const STATUS_SUCCESS_FIELDS = 'success_fields';
	public const STATUS_SUCCESS_COMMENT = 'success_comment_only';
	public const STATUS_ERROR_NO_LIMITS = 'error_no_limits';
	public const STATUS_ERROR_GPT = 'error_gpt';
	public const STATUS_ERROR_B24 = 'error_b24';

	public function isLoggerEnabled(): bool
	{
		return Option::get('crm', self::AI_DEV_ANALYTICS_ENABLE_LOGGER_OPTION_NAME, 'N') === 'Y';
	}

	public function setLoggerEnabled(bool $isEnabled = true): void
	{
		Option::set('crm', self::AI_DEV_ANALYTICS_ENABLE_LOGGER_OPTION_NAME, $isEnabled ? 'Y' : 'N');
	}

	public function sendAnalytics(
		string $contextEvent,
		string $contextType,
		string $contextElement,
		string $status,
		int $ownerTypeId,
		string $additionalParam
	): void
	{
		$ownerType = $this->getOwnerType($ownerTypeId);
		$crmMode = $this->getCurrentCrmMode();

		$event = new AnalyticsEvent($contextEvent, self::CONTEXT_TOOL, self::CONTEXT_CATEGORY);
		$event
			->setType($contextType)
			->setElement($contextElement)
			->setSection(self::CONTEXT_SECTION)
			->setSubSection($ownerType)
			->setP1($crmMode)
			->setP2($additionalParam)
			->setStatus($status)
			->send()
		;

		if ($this->isLoggerEnabled())
		{
			AIManager::logger()->info(
				'{date}: {class}: analytics successfully sent with parameters: '
				. '{class} / {event} / {tool} / {category} / {type} / {element} / {section} / {subSection} / {p1} / {p2} / {status}' . PHP_EOL,
				[
					'class' => self::class,
					'event' => $contextEvent,
					'tool' => self::CONTEXT_TOOL,
					'category' => self::CONTEXT_CATEGORY,
					'type' => $contextType,
					'element' => $contextElement,
					'section' => self::CONTEXT_SECTION,
					'subSection' => $ownerType,
					'p1' => $crmMode,
					'p2' => $additionalParam,
					'status' => $status,
				],
			);
		}
	}

	private function getOwnerType(int $ownerTypeId): string
	{
		$map = [
			CCrmOwnerType::Deal => 'deal',
			CCrmOwnerType::Lead => 'lead',
		];

		return $map[$ownerTypeId] ?? 'unknown';
	}

	private function getCurrentCrmMode(): string
	{
		$map = [
			Mode::CLASSIC => 'crmMode_classic',
			Mode::SIMPLE => 'crmMode_simple',
		];

		return $map[Mode::getCurrent()] ?? '';
	}
}
