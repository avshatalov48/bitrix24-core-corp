<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Kanban\Entity\SmartB2eDocument;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class SetDefaultKanbanEntityFields extends AgentBase
{
	private const OPTION_NAME_VIEW_FIELDS_PREFIX = 'kanban_select_more_v4';
	private const DEFAULT_FIELDS = [
		'CREATED_TIME',
		'ASSIGNEE_MEMBER',
		'SIGN_CANCELLED_MEMBER',
		'REVIEWER_MEMBER_LIST',
		'EDITOR_MEMBER_LIST',
		'NOT_SIGNED_EMPLOYER_LIST',
		'NOT_SIGNED_COMPANY_LIST',
		'EMPLOYER_LIST',
		'SIGN_CANCELLED_MEMBER_LIST',
		'SIGN_RESULT_STATUS',
	];

	private const DEAL_FIELDS = [
		'TITLE',
	];

	public static function doRun(): bool
	{
		$languageService = Container::getInstance()->getSignB2eLanguageService();
		$typeService = Container::getInstance()->getSignB2eTypeService();
		$defaultLanguage = $languageService->getDefaultLanguage();

		if (!$typeService->isCreated())
		{
			return false;
		}

		$defaultCategoryId = $typeService->getDefaultCategoryId();
		if (!$defaultCategoryId)
		{
			return false;
		}

		$optionName = sprintf(
			'%s_%s_%d_common',
			self::OPTION_NAME_VIEW_FIELDS_PREFIX,
			mb_strtolower(CCrmOwnerType::SmartB2eDocumentName),
			$defaultCategoryId,
		);

		$languageService->loadTranslations(SmartB2eDocument::class, $defaultLanguage);

		$fields = [];

		foreach (self::DEFAULT_FIELDS as $field)
		{
			$key = 'CRM_KANBAN_SMART_B2E_DOCUMENT_FIELD_NAME_' . $field;
			$fields[$field] = Loc::getMessage($key, null, $defaultLanguage) ?? '';
		}

		$languageService->loadTranslations(\CAllCrmDeal::class, $defaultLanguage);
		foreach (self::DEAL_FIELDS as $field)
		{
			$key = 'CRM_FIELD_' . $field;
			$fields[$field] = Loc::getMessage($key, null, $defaultLanguage) ?? '';
		}

		$currentFields = \CUserOptions::GetOption('crm', $optionName, []);

		$resultFields = array_unique(array_merge($fields, $currentFields), SORT_REGULAR);

		\CUserOptions::SetOption('crm', $optionName, $resultFields, true);

		return false;
	}
}
