<?php
namespace Bitrix\Crm\Filter;
class TimelineSettings extends \Bitrix\Main\Filter\Settings
{
	private int $entityTypeId;
	private int $entityId;

	public function __construct(array $params)
	{
		parent::__construct($params);

		$this->entityTypeId = $params['entityTypeId'] ?? 0;
		$this->entityId = $params['entityId'] ?? 0;
	}

	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Undefined;
	}

	public function getOwnerTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getOwnerId(): int
	{
		return $this->entityId;
	}
}
