<?php

namespace Bitrix\Crm\Component\EntityDetails\Config;

use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Main\Event;
use Bitrix\UI\Form\EntityEditorConfiguration;
use CUserOptions;

class Scope
{
	const UI_FORM_OPTION_CATEGORY = 'ui.form.editor';
	const UI_FORM_CONFIG_ID_PREFIX = 'CRM_REQUISITE_EDIT_0_PID';

	public function getByConfigId(string $configId, string $categoryId): string
	{
		$configScope = (new EntityEditorConfiguration($configId))->getScope($categoryId);

		if (is_array($configScope))
		{
			$userScopeId = $configScope['userScopeId'];
			$configScope = $configScope['scope'];
		}
		else
		{
			$userScopeId = null;
		}

		$userScopes = \Bitrix\Ui\EntityForm\Scope::getInstance()->getUserScopes($configId, 'crm');

		if(
			$configScope === EntityEditorConfigScope::CUSTOM
			&& array_key_exists($userScopeId, $userScopes)
		)
		{
			$formSettings = \Bitrix\Ui\EntityForm\Scope::getInstance()->getScopeById($userScopeId);
			if(!$formSettings)
			{
				$configScope = EntityEditorConfigScope::UNDEFINED;
			}
		}

		return $configScope;
	}

	public static function onUIFormResetScope(Event $event)
	{
		$params = $event->getParameters();
		if (
			is_array($params)
			&& ($params['CATEGORY_NAME'] ?? '') === static::UI_FORM_OPTION_CATEGORY
			&& isset($params['GUID'])
			&& is_string($params['GUID'])
			&& isset($params['PARAMS'])
			&& is_array($params['PARAMS'])
			&& preg_match('/^' . static::UI_FORM_CONFIG_ID_PREFIX . '\\d+$/', $params['GUID'])
		)
		{
			(new EntityEditorConfiguration($params['CATEGORY_NAME']))
				->reset($params['GUID'] . '_BANK_DETAILS', $params['PARAMS'])
			;
		}

	}

	public static function onUIFormSetScope(Event $event)
	{
		$params = $event->getParameters();
		if (
			is_array($params)
			&& ($params['CATEGORY_NAME'] ?? '') === static::UI_FORM_OPTION_CATEGORY
			&& isset($params['GUID'])
			&& is_string($params['GUID'])
			&& isset($params['SCOPE'])
			&& is_string($params['SCOPE'])
			&& (
				\Bitrix\UI\Form\EntityEditorConfigScope::isDefined($params['SCOPE'])
				|| $params['SCOPE'] === \Bitrix\UI\Form\EntityEditorConfigScope::UNDEFINED
			)
			&& preg_match('/^' . static::UI_FORM_CONFIG_ID_PREFIX . '\\d+$/', $params['GUID'])
		)
		{
			(new EntityEditorConfiguration($params['CATEGORY_NAME']))
				->setScope($params['GUID'] . '_BANK_DETAILS', $params['SCOPE'])
			;
		}

	}

	public static function onRequisitePresetDelete(int $presetId): void
	{
		// Clearing options that store configs in the details editing form.
		if ($presetId > 0)
		{
			$configIdPrefix = static::UI_FORM_CONFIG_ID_PREFIX . $presetId;
			$configIdPrefixLength = strlen($configIdPrefix);

			$deletedNamesMap = [];
			$res = CUserOptions::GetList(['ID' => 'ASC'], ['CATEGORY' => static::UI_FORM_OPTION_CATEGORY]);
			while ($row = $res->Fetch())
			{
				if (
					is_string($row['NAME'])
					&& strlen($row['NAME']) >= $configIdPrefixLength
					&& substr($row['NAME'], 0, $configIdPrefixLength) === $configIdPrefix
					&& !isset($deletedNamesMap[$row['NAME']])
				)
				{
					CUserOptions::DeleteOptionsByName(static::UI_FORM_OPTION_CATEGORY, $row['NAME']);
					$deletedNamesMap[$row['NAME']] = true;
				}
			}
		}
	}
}