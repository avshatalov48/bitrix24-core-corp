<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Ui\EntityForm\Scope;

class EntityEditorConfigScope
{
	const UNDEFINED = '';
	const PERSONAL = 'P';
	const COMMON = 'C';
	const CUSTOM = 'CUSTOM';

	private static $captions = [];

	/**
	 * @param string $scope
	 * @return bool
	 */
	public static function isDefined(string $scope): bool
	{
		return (in_array($scope, [self::PERSONAL, self::COMMON, self::CUSTOM], true));
	}

	/**
	 * @param string $entityTypeId
	 * @param string|null $moduleId
	 * @return array
	 */
	public static function getCaptions(string $entityTypeId = '', ?string $moduleId = null): array
	{
		if(!isset(self::$captions[LANGUAGE_ID]))
		{
			Loc::loadMessages(__FILE__);

			self::$captions[LANGUAGE_ID] = array(
				self::PERSONAL => Loc::getMessage('CRM_ENTITY_ED_CONFIG_SCOPE_PERSONAL'),
				self::COMMON => Loc::getMessage('CRM_ENTITY_ED_CONFIG_SCOPE_COMMON')
			);

			if ($entityTypeId && $customScopes = Scope::getInstance()->getUserScopes($entityTypeId, $moduleId))
			{
				self::$captions[LANGUAGE_ID] = array_merge(
					self::$captions[LANGUAGE_ID], ['CUSTOM' => $customScopes]
				);
			}
		}

		return self::$captions[LANGUAGE_ID];
	}

	/**
	 * @param string $scope
	 * @param string $entityTypeId
	 * @param int|null $scopeId
	 * @param string|null $moduleId
	 * @return string
	 */
	public static function getCaption(
		string $scope,
		string $entityTypeId = '',
		?int $scopeId = null,
		?string $moduleId = null
	): string
	{
		$captions = self::getCaptions($entityTypeId, $moduleId);
		if (
			$scope === self::CUSTOM
			&& $entityTypeId
			&& $scopeId
			&& isset($captions[$scope][$scopeId]['NAME'])
		)
		{
			return $captions[$scope][$scopeId]['NAME'];
		}

		if (isset($captions[$scope]) && !is_array($captions[$scope]))
		{
			return $captions[$scope];
		}

		return "[{$scope}]";
	}
}

