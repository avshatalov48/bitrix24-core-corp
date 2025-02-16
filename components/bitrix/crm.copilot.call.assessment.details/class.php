<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('crm'))
{
	return;
}

class CCrmCopilotCallAssessmentDetailsComponent extends Base
{
	public function executeComponent(): void
	{
		global $APPLICATION;

		if (
			!AIManager::isAiCallProcessingEnabled()
			|| !Container::getInstance()->getUserPermissions()->canReadCopilotCallAssessmentSettings())
		{
			$this->showError(
				'CRM_COPILOT_CALL_ASSESSMENT_DETAILS_ACCESS_DENIED_MSGVER_1',
				'CRM_COPILOT_CALL_ASSESSMENT_DETAILS_ACCESS_DENIED_DESCRIPTION',
			);

			return;
		}

		$id = (int)($this->arParams['ID'] ?? 0);
		if ($id)
		{
			$callAssessmentItem = CopilotCallAssessmentController::getInstance()->getById($id);
			if (!$callAssessmentItem)
			{
				$this->showError('CRM_COPILOT_CALL_ASSESSMENT_DETAILS_NOT_FOUND');

				return;
			}

			$request = Application::getInstance()->getContext()->getRequest();
			$this->arResult['isCopy'] = $request->get('copy') === 'Y';

			$APPLICATION->setTitle(htmlspecialcharsbx($callAssessmentItem->getTitle()));
			$this->arResult['data'] = CallAssessmentItem::createFromEntity($callAssessmentItem)->toArray();

			$controlData = $this->arResult['data']['controlData'] ?? [];
			$headItems = $controlData['headItems'] ?? [];
			$userIds = [];
			foreach ($headItems as $headItem)
			{
				$headType = $headItem[0] ?? null;
				if ($headType === 'user')
				{
					$userIds[] = $headItem[1];
				}
			}

			$this->arResult['data']['users'] = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);
		}
		else
		{
			$APPLICATION->setTitle(Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_DETAILS_TITLE'));
			$this->arResult['data'] = CallAssessmentItem::createFromArray([
				'title' => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_DETAILS_TITLE'),
			])->toArray();
		}

		$this->arResult['copilotSettings'] = $this->getCopilotSettings();
		$this->arResult['baasSettings'] = $this->getBaasSettings();
		$this->arResult['readOnly'] =
			!Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings()
			|| !AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
		;
		$this->arResult['isEnabled'] = AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment);

		$this->includeComponentTemplate();
	}

	private function showError(string $messageCode, string $descriptionCode = ''): void
	{
		$this->getApplication()->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => Loc::getMessage($messageCode),
				'DESCRIPTION' => empty($descriptionCode) ? '' : Loc::getMessage($descriptionCode),
			]
		);
	}

	private function getCopilotSettings(): array
	{
		if (!AIManager::isEnabledInGlobalSettings(EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE))
		{
			return [];
		}

		return [
			'moduleId' => 'crm',
			'contextId' => 'crm_call_assessment_settings_prompt_' . CurrentUser::get()->getId(),
			'category' => Bitrix\AI\SharePrompt\Enums\Category::CRM_COMMENT_FIELD->value,
			'autoHide' => true,
		];
	}

	private function getBaasSettings(): array
	{
		return [
			'isAvailable' => AIManager::isBaasServiceAvailable(),
			'hasPackage' => AIManager::isBaasServiceHasPackage(),
			'aiPackagesEmptySliderCode' => AIManager::AI_PACKAGES_EMPTY_SLIDER_CODE,
		];
	}
}
