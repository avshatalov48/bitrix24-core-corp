<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class SendEvent extends Action
{
	public const ERROR_CODE_TERMINATED_BY_EVENT_COMPATIBLE = 'TERMINATED_BY_EVENT_COMPATIBLE';
	public const DEFAULT_CANCELED_MESSAGE_CODE = 'CRM_ACTION_TERMINATED';

	private static ?\SplObjectStorage $providedFields = null;

	protected $eventName;

	public function __construct(string $eventName)
	{
		parent::__construct();

		//load messages that were created before this class move
		Loc::loadMessages(Path::combine(__DIR__, '..', 'SendEventCompatible.php'));

		$this->eventName = $eventName;
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		foreach (GetModuleEvents('crm', $this->eventName, true) as $event)
		{
			$processResult = $this->executeEvent($event, $item);
			if(!$processResult->isSuccess())
			{
				return $processResult;
			}
		}

		return $result;

	}

	protected function executeEvent(array $event, Item $item): Result
	{
		$fields = $item->getCompatibleData();
		$fields += self::getProvidedFields($item);

		ExecuteModuleEventEx($event, [&$fields]);

		return new Result();
	}

	/**
	 * @internal
	 */
	final public static function attachProvidedFields(Item $item, array $providedFields): void
	{
		if (!self::$providedFields)
		{
			self::$providedFields = new \SplObjectStorage();
		}

		self::$providedFields[$item] = $providedFields;
	}

	/**
	 * @internal
	 */
	final public static function detachProvidedFields(Item $item): void
	{
		if (self::$providedFields)
		{
			unset(self::$providedFields[$item]);
		}
	}

	/**
	 * @internal
	 */
	final protected static function getProvidedFields(Item $item): array
	{
		if (self::$providedFields)
		{
			return self::$providedFields[$item] ?? [];
		}

		return [];
	}
}
