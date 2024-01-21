<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Entity\EntityEditorConfigScope;

final class MenuIdResolver
{
	public const SCOPE = 'crm_scope_timeline';

	public static function getMenuId(int $entityTypeId, string $userId, ?int $categoryId = null): string
	{
		$configScope = 0;
		try
		{
			$entityEditorConfig = EntityEditorConfig::createWithCurrentScope($entityTypeId, [
				'USER_ID' => $userId,
				'CATEGORY_ID' => $categoryId,
			]);
			$configScope = $entityEditorConfig->getScope();
			$userScopeId = $entityEditorConfig->getUserScopeId();
		}
		finally
		{
			$entityType = \CCrmOwnerType::ResolveName($entityTypeId);
			if (!$configScope || $configScope === EntityEditorConfigScope::PERSONAL)
			{
				return mb_strtolower($entityType . ($categoryId > 0 ? '_' . $categoryId : '') . '_menu');
			}

			$optionNameParts = [
				self::SCOPE,
				$configScope,
				$entityType,
			];

			if ($categoryId)
			{
				$optionNameParts[] = $categoryId;
			}

			$optionNameParts[] = $userScopeId;

			return mb_strtolower(implode('_', $optionNameParts));
		}
	}
}