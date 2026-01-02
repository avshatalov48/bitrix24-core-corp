<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Bitrix24\Feature;
use Bitrix\Call\Settings;
use Bitrix\AI\Engine;
use Bitrix\AI\Quality;
use Bitrix\AI\Tuning\Type;
use Bitrix\AI\Tuning\Manager;
use Bitrix\AI\Tuning\Defaults;
use Bitrix\AI\Integration\Baas\BaasTokenService;


class CallAISettings
{
	public const
		CALL_COPILOT_ENABLE = 'call_copilot_enable',

		CALL_COPILOT_FEATURE_NAME = 'call_copilot',
		CALL_COPILOT_AUTOSTART_FEATURE_NAME = 'call_copilot_autostart',

		TRANSCRIBE_CALL_RECORD_ENGINE = 'transcribe_track',
		TRANSCRIPTION_OVERVIEW_ENGINE = 'resume_transcription',
		TRANSCRIPTION_OVERVIEW_QUALITY = 'meeting_processing'
	;

	public const
		CALL_COPILOT_BAAS_SLIDER_CODE = 'limit_boost_copilot',
		CALL_COPILOT_HELP_SLIDER_CODE = 'limit_copilot_follow_up',
		CALL_COPILOT_DISCLAIMER_ARTICLE = [
			'CIS' => '20412666',
			'WEST' => '25775495',
		]
	;

	private const
		AI_FEATURE_NAME = 'ai_available_by_version',
		AI_BOX_AGREEMENT_CODE = 'AI_BOX_AGREEMENT'
	;

	private const
		AI_TASK_WEIGHT = 10,
		CALL_RECORD_MIN_USERS = ['B24' => 4, 'BOX' => 0],
		CALL_RECORD_MIN_LENGTH = 59
	;

	private static ?BaasTokenService $baasService = null;

	public static function isCallAIEnable(): bool
	{
		return
			Settings::isAIServiceEnabled()
			&& self::isTariffAvailable()
			&& self::isEnableBySettings()
		;
	}

	public static function isEnableBySettings(): bool
	{
		if (Loader::includeModule('ai'))
		{
			$settingItem = (new Manager)->getItem(self::CALL_COPILOT_ENABLE);
			if (isset($settingItem) && $settingItem->getValue() === true)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if AI agreement has been accepted.
	 * @param int|null $userId
	 * @return bool
	 */
	public static function isAgreementAccepted(?int $userId = null): bool
	{
		if (Loader::includeModule('ai'))
		{
			// box
			if (\Bitrix\AI\Facade\Bitrix24::shouldUseB24() === false)
			{
				$userId = $userId ?? CurrentUser::get()->getId();

				return \Bitrix\AI\Agreement::get(self::AI_BOX_AGREEMENT_CODE)?->isAcceptedByUser((int)$userId) ?? false;
			}

			// b24
			return \Bitrix\AI\Facade\Bitrix24::isFeatureEnabled(self::AI_FEATURE_NAME);
		}

		return false;
	}

	public static function isTariffAvailable(): bool
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			// box
			return Loader::includeModule('ai');
		}

		// b24
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled(self::CALL_COPILOT_FEATURE_NAME);
		}

		return false;
	}

	public static function isAutoStartRecordingEnable(): bool
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			// box
			return (self::getRecordMinUsers() > 0);
		}

		// b24
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled(self::CALL_COPILOT_AUTOSTART_FEATURE_NAME);
		}

		return false;
	}

	/**
	 * Returns minimum users in a call to auto start AI processing.
	 * @return int
	 */
	public static function getRecordMinUsers(): int
	{
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$defaultValue = self::CALL_RECORD_MIN_USERS['B24'];
		}
		else
		{
			$defaultValue = self::CALL_RECORD_MIN_USERS['BOX'];
		}

		return (int)Option::get('call', 'call_record_min_users', $defaultValue);
	}

	public static function isBaasServiceAvailable(): bool
	{
		if (
			Loader::includeModule('ai')
			&& Loader::includeModule('baas')
		)
		{
			if (!self::$baasService)
			{
				self::$baasService = new BaasTokenService();
			}

			return self::$baasService->isAvailable();
		}

		return false;
	}

	/**
	 * Check if baas service has active packages.
	 * @return bool
	 */
	public static function isBaasServiceHasPackage(): bool
	{
		return true;//todo: Review Baas

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			return true;//todo: Review Baas in a box
		}

		if (
			Loader::includeModule('ai')
			&& Loader::includeModule('baas')
		)
		{
			if (!self::$baasService)
			{
				self::$baasService = new BaasTokenService();
			}

			return
				self::$baasService->hasPackage()
				&& self::$baasService->canConsume()//self::AI_TASK_WEIGHT
			;
		}

		return false;
	}

	/**
	 * Minimum record length in seconds for AI to start processing.
	 * @return int
	 */
	public static function getRecordMinDuration(): int
	{
		return (int)Option::get('call', 'call_record_min_length', self::CALL_RECORD_MIN_LENGTH);
	}

	public static function getFeedBackLink(): string
	{
		return Option::get('call', 'call_ai_feedback_link', '');
	}

	public static function getBaasSliderCode(): string
	{
		return Option::get('call', 'call_ai_baas_code', self::CALL_COPILOT_BAAS_SLIDER_CODE);
	}

	public static function getHelpSliderCode(): string
	{
		return Option::get('call', 'call_ai_help_code', self::CALL_COPILOT_HELP_SLIDER_CODE);
	}

	public static function getHelpUrl(): string
	{
		Loader::includeModule('ui');
		$url = (new \Bitrix\UI\Helpdesk\Url())->getByCodeArticle(self::CALL_COPILOT_HELP_SLIDER_CODE);

		return $url->getLocator();
	}


	public static function getAgreementUrl(): string
	{
		return '/online/?AI_UX_TRIGGER=box_agreement';
	}

	public static function getBaasUrl(): string
	{
		return '/online/?FEATURE_PROMOTER='.self::CALL_COPILOT_BAAS_SLIDER_CODE;
	}

	public static function getDisclaimerArticleCode(): string
	{
		$isCis =
			\Bitrix\Main\Application::getInstance()->getLicense()->isCis()
			|| Loc::getCurrentLang() === 'ru'
		;

		return $isCis
			? self::CALL_COPILOT_DISCLAIMER_ARTICLE['CIS']
			: self::CALL_COPILOT_DISCLAIMER_ARTICLE['WEST']
		;
	}

	public static function getDisclaimerUrl(): string
	{
		$article = self::getDisclaimerArticleCode();
		Loader::includeModule('ui');
		$url = (new \Bitrix\UI\Helpdesk\Url())->getByCodeArticle($article);

		return $url->getLocator();
	}

	public static function isDebugEnable(): bool
	{
		return !empty(Option::get('call', 'call_debug_chats', ''));
	}

	public static function isLoggingEnable(): bool
	{
		return (bool)Option::get('call', 'call_log', false);
	}

	/**
	 * @see \Bitrix\AI\Tuning\Manager::loadExternal
	 * @event `ai:onTuningLoad`
	 * @return EventResult
	 */
	public static function onTuningLoad(): EventResult
	{
		$result = new EventResult;

		if (!Settings::isAIServiceEnabled())
		{
			return $result;
		}

		$items = [];
		$groups = [];
		if (!empty(Engine::getListAvailable('call'))) /** @see \Bitrix\AI\Engine::CATEGORIES */
		{
			$groups['call_copilot'] = [
				'title' => Loc::getMessage('CALL_SETTINGS_COPILOT_GROUP'),
				'description' => Loc::getMessage('CALL_SETTINGS_COPILOT_DESCRIPTION'),
				//todo: Add 'helpdesk' article here
			];

			$items[self::CALL_COPILOT_ENABLE] = [
				'group' => 'call_copilot',
				'title' => Loc::getMessage('CALL_SETTINGS_COPILOT_TITLE'),
				'header' => Loc::getMessage('CALL_SETTINGS_COPILOT_HEADER'),
				'type' => Type::BOOLEAN,
				'default' => true,
			];

			$items[self::TRANSCRIBE_CALL_RECORD_ENGINE] = array_merge(
				[
					'group' => 'call_copilot',
					'title' => Loc::getMessage('CALL_SETTINGS_COPILOT_PROVIDER_TRANSCRIBE'),
				],
				Defaults::getProviderSelectFieldParams('call') /** @see \Bitrix\AI\Engine::CATEGORIES */
			);

			$quality = new Quality([
				Quality::QUALITIES[self::TRANSCRIPTION_OVERVIEW_QUALITY]
			]);

			$items[self::TRANSCRIPTION_OVERVIEW_ENGINE] = array_merge(
				[
					'group' => 'call_copilot',
					'title' => Loc::getMessage('CALL_SETTINGS_COPILOT_PROVIDER_RESUME'),
				],
				Defaults::getProviderSelectFieldParams('text', $quality) /** @see \Bitrix\AI\Engine::CATEGORIES */
			);
		}

		$result->modifyFields([
			'items' => $items,
			'groups' => $groups,
			'itemRelations' => [
				'call_copilot' => [
					self::CALL_COPILOT_ENABLE => [
						self::TRANSCRIBE_CALL_RECORD_ENGINE,
						self::TRANSCRIPTION_OVERVIEW_ENGINE,
					],
				],
			],
		]);

		return $result;
	}

	/**
	 * @return bool
	 */
	public static function isB24Mode(): bool
	{
		if (Loader::includeModule('ai') && \Bitrix\AI\Facade\Bitrix24::shouldUseB24() === true)
		{
			return true;
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			return true;
		}

		return false;
	}

	public static function isAIAvailableInCall(): ?CallAIError
	{
		$error = null;
		if (!Settings::isAIServiceEnabled())
		{
			$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR, 'AI service is unavailable');
		}
		elseif (!Loader::includeModule('ai'))
		{
			$error = new CallAIError(CallAIError::AI_MODULE_ERROR,  'AI service is unavailable');
		}
		elseif (!self::isTariffAvailable())
		{
			$error = new CallAIError(CallAIError::AI_UNAVAILABLE_ERROR, 'AI service is unavailable');
		}
		elseif (!self::isEnableBySettings())
		{
			$error = new CallAIError(CallAIError::AI_SETTINGS_ERROR, 'AI service is disabled by settings');
		}
		elseif (!self::isAgreementAccepted())
		{
			$error = new CallAIError(CallAIError::AI_AGREEMENT_ERROR, 'AI service agreement must be accepted');
		}
		elseif (!self::isAutoStartRecordingEnable())
		{
			if (!self::isBaasServiceHasPackage())
			{
				$error = new CallAIError(CallAIError::AI_NOT_ENOUGH_BAAS_ERROR, 'It is not enough baas packages');
			}
		}

		return $error;
	}
}
