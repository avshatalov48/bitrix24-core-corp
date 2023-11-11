<?php

namespace Bitrix\Crm\Entity\MessageBuilder;

use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

abstract class BaseBuilder
{
	protected const MESSAGE_BASE_PREFIX = 'CRM_PROCESS_ENTITY';

	/**
	 * Entity type ID (@see CCrmOwnerType class)
	 *
	 * @var int
	 */
	protected int $entityTypeId;

	/**
	 * Entity ID (optional)
	 *
	 * @var int|null
	 */
	protected int $entityId;

	/**
	 * Parameters to replace in a phrase
	 *
	 * @var array
	 */
	protected array $replaceList = [];

	/**
	 * Method to build phrase code by set parameters
	 *
	 * @return string
	 */
	abstract public function buildCode(): string;

	/**
	 * Method to build default phrase code
	 *
	 * @return string
	 */
	abstract protected function buildDefaultCode(): string;

	public function __construct(int $entityTypeId, int $entityId = null)
	{
		$this->entityTypeId = $entityTypeId;

		if (isset($entityId))
		{
			$this->entityId = $entityId;
		}

		// fill replace list
		$this->replaceList['#ENTITY_TYPE_CAPTION#'] = htmlspecialcharsbx(CCrmOwnerType::GetDescription($this->entityTypeId));
	}

	public function getMessage(array $replace = []): string
	{
		$replaceList = array_merge($this->replaceList, $replace);
		$result = Loc::getMessage($this->buildCode(), $replaceList) ?? '';
		if (empty($result))
		{
			$result = Loc::getMessage($this->buildCode() . '_MSGVER_1', $replaceList) ?? '';
		}
		if (empty($result))
		{
			return Loc::getMessage($this->buildDefaultCode(), $replaceList) ?? '';
		}

		return $result;
	}

	final protected function fetchEntityTypeName(): string
	{
		return CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId)
			? CCrmOwnerType::CommonDynamicName
			: CCrmOwnerType::ResolveName($this->entityTypeId);
	}
}
