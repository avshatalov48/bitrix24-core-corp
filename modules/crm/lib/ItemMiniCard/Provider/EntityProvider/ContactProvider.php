<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\Item;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\ItemMiniCard\Builder\Layout\ClientBuilder;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Field\ClientField;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\Trait\HasFileImageAvatar;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use CCrmContact;
use CCrmOwnerType;

final class ContactProvider extends AbstractEntityProvider
{
	use HasFileImageAvatar;

	public function provideAvatar(): AbstractAvatar
	{
		return $this->getImageAvatar()
			?? new IconAvatar('o-person');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->get(Contact::FIELD_NAME_TYPE_ID),
			$this->getCompaniesField(),
			...$this->fieldFactory->getFm([
				Phone::ID,
				Email::ID,
			]),
		];
	}

	protected function getImageAvatarFileId(): ?int
	{
		if (!$this->item->hasField(Contact::FIELD_NAME_PHOTO))
		{
			return null;
		}

		return $this->item->get(Contact::FIELD_NAME_PHOTO);
	}

	private function getCompaniesField(): ?ClientField
	{
		$companyIds = $this->item->get(Contact::FIELD_NAME_COMPANY_IDS);
		if (empty($companyIds))
		{
			return null;
		}

		$companies = Container::getInstance()
			->getFactory(CCrmOwnerType::Company)
			?->getItems([
				'filter' => [
					'@ID' => $companyIds,
				],
			])
		;

		if (empty($companies))
		{
			return null;
		}

		$clientBuilder = new ClientBuilder(CCrmOwnerType::Contact, $this->item->getId());

		$fieldCaption = Container::getInstance()->getFactory(CCrmOwnerType::Contact)->getFieldCaption(Item::FIELD_NAME_COMPANY_ID);
		$field = new ClientField($fieldCaption);

		foreach ($companies as $company)
		{
			$client = $clientBuilder->buildClient($company);
			if ($client === null)
			{
				continue;
			}

			$field->addValue($client);
		}

		return $field;
	}
}
