<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation;

use Bitrix\Bizproc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use Bitrix\Tasks\Integration\Bizproc\Document;

class Factory
{
	private static $isBizprocEnabled;
	private static $triggerRegistry;

	public static function canUseAutomation(): bool
	{
		if (!static::isBizprocEnabled())
		{
			return false;
		}

		if (static::isFeatureEnabled())
		{
			return true;
		}

		if (static::isFeatureEnabledByFlowTrial())
		{
			return true;
		}

		return false;
	}

	public static function isAutomationEnabled(): bool
	{
		if (
			static::isBizprocEnabled()
			&& class_exists(Bizproc\Integration\Intranet\ToolsManager::class)
		)
		{
			return Bizproc\Integration\Intranet\ToolsManager::getInstance()->isRobotsAvailable();
		}

		return static::canUseAutomation();
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
		elseif (Document\Task::isScrumProjectTask($documentType))
		{
			$target = new Target\ScrumProjectTask();
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
						Trigger\TasksFieldChangedTrigger::className(),
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
				$description[] = $triggerClass::toArray($documentType);
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
				? Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_ROBOTS)
				: true;
		}

		return $enabled;
	}

	private static function isFeatureEnabledByFlowTrial(): bool
	{
		return FlowFeature::isFeatureEnabledByTrial();
	}
}