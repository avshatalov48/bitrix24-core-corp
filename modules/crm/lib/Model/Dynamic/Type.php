<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\UserField;

/**
 * Class Type
 * @see TypeTable
 * @method string|null getName()
 * @method setName(string $name)
 * @method string|null getTitle()
 * @method setTitle(string $title)
 * @method bool|null getIsCategoriesEnabled()
 * @method setIsCategoriesEnabled(bool $isCategoriesEnabled)
 * @method bool|null getIsStagesEnabled()
 * @method setIsStagesEnabled(bool $isStagesEnabled)
 * @method bool|null getIsBeginCloseDatesEnabled()
 * @method setIsBeginCloseDatesEnabled(bool $isBeginCloseDatesEnabled)
 * @method bool|null getIsClientEnabled()
 * @method bool|null getIsUseInUserfieldEnabled()
 * @method setIsClientEnabled(bool $isClientEnabled)
 * @method bool|null getIsLinkWithProductsEnabled()
 * @method setIsLinkWithProductsEnabled(bool $isLinkWithProductsEnabled)
 * @method bool|null getIsCrmTrackingEnabled()
 * @method setIsCrmTrackingEnabled(bool $isCrmTrackingEnabled)
 * @method bool|null getIsMycompanyEnabled()
 * @method setIsMycompanyEnabled(bool $isMycompanyEnabled)
 * @method bool|null getIsDocumentsEnabled()
 * @method setIsDocumentsEnabled(bool $isDocumentsEnabled)
 * @method bool|null getIsSourceEnabled()
 * @method setIsSourceEnabled(bool $isSourceEnabled)
 * @method bool|null getIsObserversEnabled()
 * @method setIsObserversEnabled(bool $isObserversEnabled)
 * @method bool|null getIsRecyclebinEnabled()
 * @method setIsRecyclebinEnabled(bool $isRecyclebinEnabled)
 * @method bool|null getIsAutomationEnabled()
 * @method setIsAutomationEnabled(bool $isAutomationEnabled)
 * @method bool|null getIsBizProcEnabled()
 * @method setIsBizProcEnabled(bool $isBizProcEnabled)
 * @method bool|null getIsSetOpenPermissions()
 * @method setIsSetOpenPermissions(bool $isSetOpenPermissions)
 * @method bool|null getIsPaymentsEnabled()
 * @method setIsPaymentsEnabled(bool $isPaymentsEnabled)
 * @method int|null getEntityTypeId()
 * @method setEntityTypeId(int $entityTypeId)
 * @method bool|null getIsCountersEnabled()
 * @method bool|null setIsCountersEnabled(bool $isCountersEnabled)
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
