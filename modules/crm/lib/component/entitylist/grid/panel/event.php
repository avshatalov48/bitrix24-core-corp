<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel;

final class Event
{
	private array $params = [];

	public function __construct(private string $eventName)
	{
	}

	public function addEntityTypeId(int $entityTypeId): self
	{
		return $this->addParam('entityTypeId', $entityTypeId);
	}

	public function addValueElementId(string $valueElementId): self
	{
		return $this->addParam('valueElementId', $valueElementId);
	}

	public function addParam(string $paramName, int|float|string|bool|null $value): self
	{
		$this->params[$paramName] = $value;

		return $this;
	}

	public function buildJsCallback(): string
	{
		return self::buildEmitEventJs(
			$this->eventName,
			$this->params
		);
	}

	private static function buildEmitEventJs(string $eventName, array $params): string
	{
		$escapedEventName = \CUtil::JSEscape($eventName);

		$eventParams = \CUtil::PhpToJSObject($params, false, false, true);

		return "BX.Event.EventEmitter.emit('BX.Crm.EntityList.Panel:{$escapedEventName}', {$eventParams});";
	}
}
