<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField;
use Bitrix\Main\Web\Json;

/**
 * Class Item
 * @method setTitle(string $title)
 * @method int getCreatedBy()
 * @method setCreatedBy(int $userId)
 * @method int|null getUpdatedBy()
 * @method setUpdatedBy(int $userId)
 * @method int|null getMovedBy()
 * @method setMovedBy(int $userId)
 * @method DateTime getCreatedTime()
 * @method setCreatedTime(DateTime $dateTime)
 * @method DateTime|null getUpdatedTime()
 * @method setUpdatedTime(DateTime $dateTime)
 * @method DateTime|null getMovedTime()
 * @method setMovedTime(DateTime $dateTime)
 * @method int|null getCategoryId()
 * @method setCategoryId(int $categoryId)
 * @method bool getOpened()
 * @method setOpened(bool $isOpened)
 * @method string|null getStageId()
 * @method setStageId(string $stageId)
 * @method string|null getPreviousStageId()
 * @method setPreviousStageId(string $stageId)
 * @method Date getBegindate()
 * @method setBegindate(Date $date)
 * @method Date getClosedate()
 * @method setClosedate(Date $date)
 * @method int|null getCompanyId()
 * @method setCompanyId(int $companyId)
 * @method int|null getContactId()
 * @method setContactId(int $contactId)
 * @method float|null getOpportunity()
 * @method setOpportunity(float $opportunity)
 * @method bool getIsManualOpportunity()
 * @method setIsManualOpportunity(bool $isManualOpportunity)
 * @method float|null getTaxValue()
 * @method setTaxValue(float $taxValue)
 * @method string|null getCurrencyId()
 * @method setCurrencyId(string $currencyId)
 * @method float|null getOpportunityAccount()
 * @method setOpportunityAccount(float $opportunityAccount)
 * @method float|null getTaxValueAccount()
 * @method setTaxValueAccount(float $taxValueAccount)
 * @method string|null getAccountCurrencyId()
 * @method setAccountCurrencyId(string $accountCurrencyId)
 * @method int|null getMycompanyId()
 * @method setMycompanyId(int $mycompanyId)
 * @method string|null getSourceId()
 * @method setSourceId(string $sourceId)
 * @method string|null getSourceDescription()
 * @method setSourceDescription(string $sourceDescription)
 */
class Item extends UserField\Internal\Item implements \JsonSerializable
{
	public function getEntityTypeId(): int
	{
		return (int) static::$dataClass::getType()['ENTITY_TYPE_ID'];
	}

	public function getFactory(): \Bitrix\Crm\Service\Factory
	{
		return Container::getInstance()->getFactory($this->getEntityTypeId());
	}

	public function remindActualTitle(): ?string
	{
		return $this->resolveTitle(parent::remindActualTitle());
	}

	public function getTitle(): ?string
	{
		return $this->resolveTitle(parent::getTitle());
	}

	protected function resolveTitle(?string $title): ?string
	{
		if (empty($title) && $this->state !== State::RAW)
		{
			return $this->getDefaultTitle();
		}

		return $title;
	}

	public function getDefaultTitle(): string
	{
		$typeTitle = static::$dataClass::getType()['TITLE'];
		$defaultItemTitle = $typeTitle.' #';
		if ($this->getId() > 0)
		{
			$defaultItemTitle .= $this->getId();
		}

		return $defaultItemTitle;
	}

	/**
	 * @return array|int
	 */
	public function getAssignedById()
	{
		$value = parent::getAssignedById();
		if(mb_strpos($value, '[') === 0)
		{
			$value = Json::decode($value);
			return reset($value);
		}

		return $value;
	}

	/**
	 * @param array|int $value
	 */
	public function setAssignedById($value): void
	{
		if(is_array($value) && !$this->getFactory()->isMultipleAssignedEnabled())
		{
			$value = reset($value);
		}

		parent::setAssignedById($value);
	}

	public function jsonSerialize(): array
	{
		return Container::getInstance()->getOrmObjectConverter()->toJson($this);
	}
}
