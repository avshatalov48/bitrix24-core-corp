<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation;

use Bitrix\Bitrix24\Feature;
use Bitrix\Tasks\Integration\Bizproc\Automation\Target;
use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use Bitrix\Tasks\Integration\Bizproc\Document;

class Factory
{
	private static $isBizprocEnabled;
	private static $triggerRegistry;

	public static function canUseAutomation()
	{
		return static::isBizprocEnabled() && static::isFeatureEnabled();
	}

	public static function runOnAdd($documentType, $documentId, array $fields = null)
	{
		if (empty($documentId) || !static::canUseAutomation())
		{
			return;
		}

		$automationTarget = static::createTarget($documentType, $documentId);
		if ($fields)
		{
			$automationTarget->setFields($fields);
		}

		$automationTarget->getRuntime()->onDocumentAdd();
	}

	public static function runOnStatusChanged($documentType, $documentId, array $fields = null)
	{
		if (empty($documentId) || !static::canUseAutomation())
		{
			return;
		}

		$automationTarget = static::createTarget($documentType, $documentId);
		if ($fields)
		{
			$automationTarget->setFields($fields);
		}
		$automationTarget->getRuntime()->onDocumentStatusChanged();
	}

	public static function stopAutomation($documentType, $documentId)
	{
		if (empty($documentId) || !static::canUseAutomation())
		{
			return;
		}

		$automationTarget = static::createTarget($documentType, $documentId);
		$automationTarget->getRuntime()->onDocumentMove();
	}

	/**
	 * Create Target instance by entity type.
	 * @param mixed $documentType Document type id.
	 * @param int|null $documentId Document id.
	 * @return Target\Base Target instance, child of BaseTarget.
	 * @throws NotSupportedException
	 */
	public static function createTarget($documentType, $documentId = null)
	{
		$target = null;
		if (Document\Task::isPersonalTask($documentType))
		{
			$target = new Target\PersonalTask();
		}
		elseif (Document\Task::isPlanTask($documentType))
		{
			$target = new Target\PlanTask();
		}
		elseif (Document\Task::isProjectTask($documentType))
		{
			$target = new Target\ProjectTask();
		}

		if (!$target)
		{
			throw new NotSupportedException("Document type '{$documentType}' is not supported in current context.");
		}

		$target->setDocumentType(['tasks', Document\Task::class, $documentType]);

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
			foreach ([
						Trigger\Status::className(),
						Trigger\ExpiredSoon::className(),
						Trigger\Expired::className(),
						//Trigger\WebHook::className(),
						//Trigger\App::className()
					 ]
					 as $triggerClass
			)
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
	public static function getAvailableTriggers($documentType)
	{
		$description = array();
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

	private static function isFeatureEnabled()
	{
		static $enabled;
		if ($enabled === null)
		{
			$enabled = Loader::includeModule('bitrix24')
				? Feature::isFeatureEnabled('tasks_automation')
				: true;
		}

		return $enabled;
	}
}