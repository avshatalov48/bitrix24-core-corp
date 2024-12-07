<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField;

/**
 * Class Type
 * @see TypeTable
 * @method string|null getName()
 * @method Type setName(string $name)
 * @method string|null getTitle()
 * @method Type setTitle(string $title)
 * @method bool|null getIsCategoriesEnabled()
 * @method Type setIsCategoriesEnabled(bool $isCategoriesEnabled)
 * @method bool|null getIsStagesEnabled()
 * @method Type setIsStagesEnabled(bool $isStagesEnabled)
 * @method bool|null getIsBeginCloseDatesEnabled()
 * @method Type setIsBeginCloseDatesEnabled(bool $isBeginCloseDatesEnabled)
 * @method bool|null getIsClientEnabled()
 * @method bool|null getIsUseInUserfieldEnabled()
 * @method Type setIsClientEnabled(bool $isClientEnabled)
 * @method bool|null getIsLinkWithProductsEnabled()
 * @method Type setIsLinkWithProductsEnabled(bool $isLinkWithProductsEnabled)
 * @method bool|null getIsCrmTrackingEnabled()
 * @method Type setIsCrmTrackingEnabled(bool $isCrmTrackingEnabled)
 * @method bool|null getIsMycompanyEnabled()
 * @method Type setIsMycompanyEnabled(bool $isMycompanyEnabled)
 * @method bool|null getIsDocumentsEnabled()
 * @method Type setIsDocumentsEnabled(bool $isDocumentsEnabled)
 * @method bool|null getIsSourceEnabled()
 * @method Type setIsSourceEnabled(bool $isSourceEnabled)
 * @method bool|null getIsObserversEnabled()
 * @method Type setIsObserversEnabled(bool $isObserversEnabled)
 * @method bool|null getIsRecyclebinEnabled()
 * @method Type setIsRecyclebinEnabled(bool $isRecyclebinEnabled)
 * @method bool|null getIsAutomationEnabled()
 * @method Type setIsAutomationEnabled(bool $isAutomationEnabled)
 * @method bool|null getIsBizProcEnabled()
 * @method Type setIsBizProcEnabled(bool $isBizProcEnabled)
 * @method bool|null getIsSetOpenPermissions()
 * @method Type setIsSetOpenPermissions(bool $isSetOpenPermissions)
 * @method bool|null getIsPaymentsEnabled()
 * @method Type setIsPaymentsEnabled(bool $isPaymentsEnabled)
 * @method int|null getEntityTypeId()
 * @method Type setEntityTypeId(int $entityTypeId)
 * @method int|null getCustomSectionId()
 * @method Type setCustomSectionId(?int $customSectionId)
 * @method bool|null getIsCountersEnabled()
 * @method Type  setIsCountersEnabled(bool $isCountersEnabled)
 * @method DateTime|null getCreatedTime()
 * @method Type setCreatedTime(DateTime $createdTime)
 * @method int|null getCreatedBy()
 * @method Type setCreatedBy(int $createdById)
 * @method int|null getUpdatedBy()
 * @method Type setUpdatedBy(int $createdById)
 * @method DateTime|null getUpdatedTime()
 * @method Type setUpdatedTime(DateTime $updatedTime)
 */
class Type extends UserField\Internal\Type implements \JsonSerializable
{
	public static $dataClass = TypeTable::class;

	public function jsonSerialize(?bool $allData = true): array
	{
		return Container::getInstance()->getTypeConverter()->toJson($this, $allData);
	}

	/**
	 * Returns true if this object has been created recently and is not saved yet
	 *
	 * @return bool
	 */
	public function isNew(): bool
	{
		return ($this->getId() <= 0);
	}
}
