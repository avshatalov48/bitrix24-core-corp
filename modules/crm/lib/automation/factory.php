<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Bitrix24\Feature;
use Bitrix\Bizproc;
use Bitrix\Crm\Automation\Target;
use Bitrix\Crm\Automation\Trigger\BaseTrigger;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Activity\AutocompleteRule;
use Bitrix\Crm\ActivityTable;

class Factory
{
	private static $supportedEntityTypes = null;

	private static $limitedEntityTypes = [
		\CCrmOwnerType::Lead,
		\CCrmOwnerType::Deal,
	];

	private static $triggerRegistry;

	private static $targets = [];

	private static $newActivities = [];
	private static $conversionResults = [];

	private static $limitationCache = [];

	private static function getSupportedEntityTypes()
	{
		if (is_null(static::$supportedEntityTypes))
		{
			static::$supportedEntityTypes = [
					\CCrmOwnerType::Lead,
					\CCrmOwnerType::Deal,
					\CCrmOwnerType::Order,
			];

			if (QuoteSettings::getCurrent()->isFactoryEnabled())
			{
				static::$supportedEntityTypes[] = \CCrmOwnerType::Quote;
			}
		}

		return static::$supportedEntityTypes;
	}

	public static function isAutomationAvailable($entityTypeId, $ignoreLicense = false)
	{
		if (!Helper::isBizprocEnabled() || !static::isSupported($entityTypeId))
			return false;

		if (!$ignoreLicense && Loader::includeModule('bitrix24'))
		{
			$feature = 'crm_automation_'.mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
			$is = Feature::isFeatureEnabled($feature);

			if (!$is && self::isLimitationSupported() && in_array($entityTypeId, self::$limitedEntityTypes))
			{
				$is = Feature::isFeatureEnabled($feature.'_limited');
			}

			return $is;
		}

		return true;
	}

	public static function isAutomationRunnable(int $entityTypeId): bool
	{
		if (static::isAutomationAvailable($entityTypeId))
		{
			if (self::isAutomationLimited($entityTypeId))
			{
				return !self::isOverLimited($entityTypeId);
			}

			return true;
		}

		return false;
	}

	public static function isAutomationLimited(int $entityTypeId): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			$feature = 'crm_automation_'.mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
			$is = Feature::isFeatureEnabled($feature);

			if ($is)
			{
				return false;
			}

			return (
				in_array($entityTypeId, self::$limitedEntityTypes)
				&& Feature::isFeatureEnabled($feature.'_limited')
			);
		}

		return false;
	}

	public static function getRobotsLimit(int $entityTypeId): int
	{
		if (static::isAutomationLimited($entityTypeId))
		{
			return (int) Feature::getVariable('crm_automation_robots_limit');
		}
		return 0;
	}

	private static function isOverLimited($entityTypeId): bool
	{
		$limit = static::getRobotsLimit($entityTypeId);

		if ($limit <= 0)
		{
			return false;
		}

		if (isset(self::$limitationCache[$entityTypeId]))
		{
			return self::$limitationCache[$entityTypeId];
		}

		$target = self::createTarget($entityTypeId);
		$statuses = $target->getEntityStatuses();

		$triggersCnt = count($target->getTriggers($statuses));

		if ($triggersCnt > $limit)
		{
			return self::$limitationCache[$entityTypeId] = true;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
		$robotsCnt = \Bitrix\Bizproc\Automation\Helper::countAllRobots($documentType, $statuses);

		return self::$limitationCache[$entityTypeId] = ($triggersCnt + $robotsCnt > $limit);
	}

	private static function isLimitationSupported()
	{
		return method_exists(\Bitrix\Bizproc\Automation\Helper::class, 'countAllRobots');
	}

	public static function canUseBizprocDesigner()
	{
		if (Loader::includeModule('bitrix24'))
		{
			$feature = 'crm_automation_designer';
			return Feature::isFeatureEnabled($feature);
		}

		return true;
	}

	public static function isBizprocDesignerSupported(int $entityTypeId): bool
	{
		return (
			$entityTypeId === \CCrmOwnerType::Lead
			|| $entityTypeId === \CCrmOwnerType::Deal
			|| $entityTypeId === \CCrmOwnerType::Contact
			|| $entityTypeId === \CCrmOwnerType::Company
			|| $entityTypeId === \CCrmOwnerType::Order
			|| $entityTypeId === \CCrmOwnerType::Quote
			|| \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
		);
	}

	public static function isBizprocDesignerEnabled(int $entityTypeId): bool
	{
		$isSupported = static::isBizprocDesignerSupported($entityTypeId);
		$factory = Container::getInstance()->getFactory($entityTypeId);

		return isset($factory) ? $factory->isBizProcEnabled() : $isSupported;
	}

	public static function canUseAutomation()
	{
		foreach (static::getSupportedEntityTypes() as $entityTypeId)
		{
			if (static::isAutomationAvailable($entityTypeId))
				return true;
		}
		return false;
	}

	public static function isSupported($entityTypeId)
	{
		$isSupported = in_array((int)$entityTypeId, static::getSupportedEntityTypes(), true);
		if (!$isSupported && \CCrmOwnerType::isPossibleDynamicTypeId((int)$entityTypeId))
		{
			$factory = Container::getInstance()->getFactory((int)$entityTypeId);
			$isSupported = $factory && $factory->isAutomationEnabled();
		}

		return $isSupported;
	}

	public static function runOnAdd($entityTypeId, $entityId)
	{
		if ($entityTypeId === \CCrmOwnerType::Lead && !LeadSettings::isEnabled())
		{
			return self::runLeadFreeScenario($entityId);
		}

		$result = new Result();

		if (empty($entityId) || !static::isAutomationRunnable($entityTypeId))
		{
			$result->addError(new Error('not available'));
			return $result;
		}

		$automationTarget = static::getTarget($entityTypeId, $entityId);
		$automationTarget->getRuntime()->onDocumentAdd();

		if ($conversionResult = self::shiftConversionResult($entityTypeId, $entityId))
		{
			$result->setConversionResult($conversionResult);
		}

		return $result;
	}

	public static function runOnStatusChanged($entityTypeId, $entityId)
	{
		$result = new Result();

		if (empty($entityId) || !static::isAutomationRunnable($entityTypeId))
		{
			$result->addError(new Error('not available'));
			return $result;
		}

		static::doAutocompleteActivities($entityTypeId, $entityId);

		$automationTarget = static::getTarget($entityTypeId, $entityId);
		//refresh target entity fields
		$automationTarget->setEntityById($entityId);
		$automationTarget->getRuntime()->onDocumentStatusChanged();

		if ($conversionResult = self::shiftConversionResult($entityTypeId, $entityId))
		{
			$result->setConversionResult($conversionResult);
		}

		return $result;
	}

	/**
	 * Create Target instance by entity type.
	 * @param int $entityTypeId Entity type id from \CCrmOwnerType.
	 * @return Target\BaseTarget Target instance, child of BaseTarget.
	 * @throws NotSupportedException
	 */
	public static function createTarget($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;

		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return new Target\DealTarget();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return new Target\LeadTarget();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			return new Target\OrderTarget();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Invoice)
		{
			return new Target\InvoiceTarget();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return new Target\ItemTarget($entityTypeId);
		}
		elseif (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId) || $entityTypeId === \CCrmOwnerType::Quote)
		{
			return new Target\ItemTarget($entityTypeId);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			throw new NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
		}
	}

	public static function getTarget($entityTypeId, $entityId)
	{
		if (isset(self::$targets[$entityTypeId]) && isset(self::$targets[$entityTypeId][$entityId]))
		{
			return self::$targets[$entityTypeId][$entityId];
		}
		$target = self::createTarget($entityTypeId);
		$target->setEntityById($entityId);

		self::setTarget($target);

		return $target;
	}

	private static function setTarget(Target\BaseTarget $target)
	{
		if (!isset(self::$targets[$target->getEntityTypeId()]))
		{
			self::$targets[$target->getEntityTypeId()] = [];
		}
		self::$targets[$target->getEntityTypeId()][$target->getEntityId()] = $target;
	}

	/**
	 * Create Runtime instance.
	 * @return Engine\Runtime Runtime instance.
	 * @deprecated
	 * @see Bizproc\Automation\Engine\Runtime
	 */
	public static function createRuntime()
	{
		return new Engine\Runtime();
	}

	/**
	 * @return Trigger\BaseTrigger[] Registered triggers array.
	 */
	private static function getTriggerRegistry()
	{
		if (self::$triggerRegistry === null)
		{
			self::$triggerRegistry = [];
			foreach ([
					Trigger\ResponsibleChangedTrigger::className(),
					Trigger\FieldChangedTrigger::className(),
					Trigger\EmailTrigger::className(),
					Trigger\EmailSentTrigger::className(),
					Trigger\EmailReadTrigger::className(),
					Trigger\EmailLinkTrigger::className(),
					Trigger\CallTrigger::className(),
					Trigger\MissedCallTrigger::className(),
					Trigger\WebFormTrigger::className(),
					Trigger\CallBackTrigger::className(),
					Trigger\InvoiceTrigger::className(),
					Trigger\PaymentTrigger::className(),
					Trigger\AllowDeliveryTrigger::className(),
					Trigger\FillTrackingNumberTrigger::className(),
					Trigger\ShipmentChangedTrigger::className(),
					Trigger\DeductedTrigger::className(),
					Trigger\OrderCanceledTrigger::className(),
					Trigger\OrderPaidTrigger::className(),
					Trigger\DeliveryFinishedTrigger::className(),
					Trigger\WebHookTrigger::className(),
					Trigger\VisitTrigger::className(),
					Trigger\GuestReturnTrigger::className(),
					Trigger\OpenLineTrigger::className(),
					Trigger\OpenLineMessageTrigger::className(),
					Trigger\OpenLineAnswerControlTrigger::className(),
					Trigger\OpenLineAnswerTrigger::className(),
					Trigger\ResourceBookingTrigger::className(),
					Trigger\DocumentCreateTrigger::className(),
					Trigger\DocumentViewTrigger::className(),
					Trigger\TaskStatusTrigger::className(),
					Trigger\AppTrigger::className(),
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
	 * @param int $entityTypeId Entity type id.
	 * @return array
	 */
	public static function getAvailableTriggers($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		$description = array();
		/**
		 * @var BaseTrigger $triggerClass
		 */
		foreach (self::getTriggerRegistry() as $triggerClass)
		{
			if ($triggerClass::isSupported($entityTypeId))
			{
				$description[] = $triggerClass::toArray($entityTypeId);
			}
		}

		return $description;
	}

	/**
	 * @param $code Trigger string code.
	 * @return bool|Trigger\BaseTrigger Trigger class name or false.
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

	/**
	 * @param int $entityTypeId Entity type id.
	 * @param string $entityStatus Entity status for check.
	 * @return bool
	 */
	public static function hasRobotsForStatus($entityTypeId, $entityStatus)
	{
		if (!Helper::isBizprocEnabled() || !static::isSupported($entityTypeId))
			return false;

		$documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

		$template = new Bizproc\Automation\Engine\Template($documentType, $entityStatus);

		return ($template->getId() > 0 && ($template->isExternalModified() || count($template->getRobots()) > 0));
	}

	public static function registerActivity($id)
	{
		static::$newActivities[$id] = true;
	}

	public static function registerConversionResult($entityTypeId, $entityId, Converter\Result $result)
	{
		$key = $entityTypeId.'_'.$entityId;
		self::$conversionResults[$key] = $result;
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return Converter\Result|null
	 */
	private static function shiftConversionResult($entityTypeId, $entityId)
	{
		$key = $entityTypeId.'_'.$entityId;
		$result = isset(self::$conversionResults[$key]) ? self::$conversionResults[$key] : null;
		unset(self::$conversionResults[$key]);

		return $result;
	}

	private static function doAutocompleteActivities($entityTypeId, $entityId)
	{
		$result = ActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=COMPLETED' => 'N',
				'=AUTOCOMPLETE_RULE' => AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED,
				'=BINDINGS.OWNER_TYPE_ID' => $entityTypeId,
				'=BINDINGS.OWNER_ID' => $entityId,
			),
			'order' => array('ID' => 'ASC')
		));

		while ($row = $result->fetch())
		{
			if (!isset(static::$newActivities[$row['ID']]))
			{
				\CCrmActivity::SetAutoCompleted($row['ID']);
			}
			else
			{
				unset(static::$newActivities[$row['ID']]);
			}
		}
	}

	private static function runLeadFreeScenario($entityId)
	{
		$result = new Result();

		$converter = Converter\Factory::create(\CCrmOwnerType::Lead, $entityId);
		$config = LeadSettings::getCurrent()->getFreeModeConverterConfig();

		if (!$config['completeActivities'])
		{
			$converter->enableActivityCompletion(false);
		}

		$itemOptions = ['categoryId' => $config['dealCategoryId'] ?: 0];
		$items = $config['items'] ? $config['items'] : [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact];

		foreach ($items as $itemTypeId)
		{
			$converter->setTargetItem($itemTypeId, $itemOptions);
		}

		$result->setConversionResult($converter->execute());

		return $result;
	}
}
