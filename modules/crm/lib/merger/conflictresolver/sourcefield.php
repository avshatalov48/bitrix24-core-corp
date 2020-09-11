<?php


namespace Bitrix\Crm\Merger\ConflictResolver;

/**
  * Class SourceField
 * @package Bitrix\Crm\Merger\ConflictResolver
 *
 * Source field is always resolved positively. If conflict occurred, it will be written into history events.
 */
class SourceField extends IgnoredField
{
	public function __construct(string $fieldId)
	{
		parent::__construct($fieldId);
		$this->setNeedWriteToHistory(true);
		$this->setNeedUpdateTargetIfEmpty(true);
	}

	protected function getHistoryItem($value): string
	{
		static $sources;
		if ($sources === null)
		{
			$sources = \CCrmStatus::GetStatusList('SOURCE');
		}
		return $sources[$value] ?? (string)$value;
	}
}