<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Manager\Task;

final class Auditor extends Member
{
	public static function getLegacyFieldName()
	{
		return 'AUDITORS';
	}

	public static function getIsMultiple()
	{
		return true;
	}

	public static function mergeData($primary = [], $secondary = [], bool $withAdditional = true): array
	{
		if (!$withAdditional)
		{
			return (array)$secondary;
		}

		$primaryIds = array_column($primary, 'ID');
		$secondaryIds = array_column($secondary, 'ID');

		$resultIds = array_unique(array_merge($primaryIds, $secondaryIds));

		return array_map(static fn($id) => ['ID' => $id], $resultIds);
	}
}