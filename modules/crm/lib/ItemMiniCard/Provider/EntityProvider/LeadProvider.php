<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\Item\Lead;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Field\CommonField;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use CCrmLead;

final class LeadProvider extends AbstractEntityProvider
{
	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('o-lead');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->getStage(),
			$this->fieldFactory->getProducts(),
			$this->getLeadFormattedNameField(),
			...$this->fieldFactory->getFm([
				Phone::ID,
				Email::ID,
			]),
		];
	}

	private function getLeadFormattedNameField(): ?CommonField
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		$fieldNameTitle = $factory?->getFieldCaption(Lead::FIELD_NAME_NAME);
		$fieldNameValue = CCrmLead::PrepareFormattedName([
			'HONORIFIC' => $this->item->getHonorific(),
			'NAME' => $this->item->getName(),
			'LAST_NAME' => $this->item->getLastName(),
			'SECOND_NAME' => $this->item->getSecondName(),
		]);

		return (new CommonField($fieldNameTitle))
			->addValue($fieldNameValue);
	}
}
