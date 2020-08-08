<?php
namespace Bitrix\Rpa\Integration\Bizproc\Automation;

use Bitrix\Bizproc\WorkflowTemplateTable;
use Bitrix\Rpa\Integration\Bizproc\Automation\Target;
use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use Bitrix\Rpa\Integration\Bizproc\Document;

class Factory
{
	private static $isBizprocEnabled;
	private static $triggerRegistry;

	public static function canUseAutomation(): bool
	{
		return static::isBizprocEnabled();
	}

	public static function runOnAdd($typeId, $itemId, array $fields = null)
	{
		if (empty($itemId) || !static::canUseAutomation())
		{
			return;
		}

		$documentType = Document\Item::makeComplexType($typeId);
		$documentId = Document\Item::makeComplexId($typeId, $itemId);

		$automationTarget = static::createTarget($documentType, $documentId[2]);
		if ($fields)
		{
			$automationTarget->setFields($fields);
		}

		$automationTarget->getRuntime()->onDocumentAdd();
	}

	public static function runOnStatusChanged($typeId, $itemId, array $fields = null)
	{
		if (empty($itemId) || !static::canUseAutomation())
		{
			return;
		}

		$documentType = Document\Item::makeComplexType($typeId);
		$documentId = Document\Item::makeComplexId($typeId, $itemId);

		$automationTarget = static::createTarget($documentType, $documentId[2]);
		if ($fields)
		{
			$automationTarget->setFields($fields);
		}
		$automationTarget->getRuntime()->onDocumentStatusChanged();
	}

	/**
	 * Create Target instance by entity type.
	 * @param array $documentType Document type id.
	 * @param int|null $documentId Document id.
	 * @return Target\Base Target instance, child of BaseTarget.
	 * @throws NotSupportedException
	 */
	public static function createTarget(array $documentType, $documentId = null)
	{
		$target = new Target\Item();
		$target->setDocumentType($documentType);

		if ($documentId)
		{
			$target->setDocumentId($documentId);
		}

		return $target;
	}

	/**
	 * @return Trigger\Base[] Registered triggers array.
	 */
	private static function getTriggerRegistry()
	{
		if (self::$triggerRegistry === null)
		{
			self::$triggerRegistry = [];
			/** @var Trigger\Base $triggerClass */
			foreach ([] as $triggerClass)
			{
				if ($triggerClass::isEnabled())
				{
					self::$triggerRegistry[] = $triggerClass;
				}
			}
		}

		return self::$triggerRegistry;
	}

	/**
	 * @param int $documentType Document type id.
	 * @return array
	 */
	public static function getAvailableTriggers($documentType): array
	{
		$description = [];
		/**
		 * @var Trigger\Base $triggerClass
		 */
		foreach (self::getTriggerRegistry() as $triggerClass)
		{
			if ($triggerClass::isSupported($documentType))
			{
				$description[] = $triggerClass::toArray();
			}
		}

		return $description;
	}

	/**
	 * @param $code - Trigger string code.
	 * @return bool|Trigger\Base Trigger class name or false.
	 */
	public static function getTriggerByCode($code)
	{
		$code = (string)$code;

		foreach (self::getTriggerRegistry() as $triggerClass)
		{
			if ($triggerClass::getCode() === $code)
			{
				return $triggerClass::className();
			}
		}

		return false;
	}

	private static function isBizprocEnabled()
	{
		if (static::$isBizprocEnabled === null)
		{
			static::$isBizprocEnabled = Loader::includeModule('bizproc');
		}

		return static::$isBizprocEnabled;
	}

	public static function onAfterStageDelete(int $typeId, int $stageId)
	{
		if (!static::canUseAutomation())
		{
			return;
		}

		$documentType = Document\Item::makeComplexType($typeId);

		$template = WorkflowTemplateTable::getList([
			'filter' => [
				'=MODULE_ID' => $documentType[0],
				'=ENTITY' => $documentType[1],
				'=DOCUMENT_TYPE' => $documentType[2],
				'=DOCUMENT_STATUS' => $stageId,
			],
			'select' => ['ID']
		])->fetch();
		if ($template)
		{
			\CBPDocument::DeleteWorkflowTemplate($template['ID'], $documentType, $errors);
		}
	}
}