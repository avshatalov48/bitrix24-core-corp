<?php

namespace Bitrix\Crm\ItemMiniCard\Factory\Layout\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemMiniCard\Builder\Layout\ClientBuilder;
use Bitrix\Crm\ItemMiniCard\Layout\Field\ClientField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\CommonField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\EmailField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\LinkField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\MoneyField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\PhoneField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\ProductField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\StageField;
use Bitrix\Crm\ItemMiniCard\Layout\Field\Value;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Multifield\Type\Web;
use Bitrix\Crm\Multifield\TypeRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\PhoneNumber\Parser;
use CCrmCallToUrl;
use CCrmFieldMulti;
use CCrmOwnerType;

final class FieldFactory
{
	private readonly Factory $factory;
	private readonly Router $router;

	private readonly ClientBuilder $clientBuilder;

	private const PRODUCT_LIMIT = 3;

	public function __construct(
		private readonly Item $item,
	)
	{
		$this->factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		$this->router = Container::getInstance()->getRouter();

		$this->clientBuilder = new ClientBuilder($this->item->getEntityTypeId(), $this->item->getId());
	}

	public function getStage(): ?StageField
	{
		if (!$this->isAvailable(Item::FIELD_NAME_STAGE_ID))
		{
			return null;
		}

		$title = $this->factory->getFieldCaption(Item::FIELD_NAME_STAGE_ID);
		$stage = $this->factory->getStage($this->item->getStageId());
		if ($stage === null)
		{
			return null;
		}

		return new StageField($title, $stage->getName(), $stage->getColor());
	}

	public function getProducts(): ?ProductField
	{
		if (!$this->isAvailable(Item::FIELD_NAME_PRODUCTS))
		{
			return null;
		}

		$productRows = $this->item->getProductRows();
		if ($productRows === null || $productRows->isEmpty())
		{
			return null;
		}

		$field = new ProductField($this->factory->getFieldCaption(Item::FIELD_NAME_PRODUCTS));
		$counter = 0;
		foreach ($productRows->getAll() as $productRow)
		{
			if ($counter++ >= self::PRODUCT_LIMIT)
			{
				$productsLeftCount = $productRows->count() - self::PRODUCT_LIMIT;
				$productsLeftUrl = $this->router
					->getItemDetailUrl($this->item->getEntityTypeId(), $this->item->getId())
					?->addParams([
						'active_tab' => 'tab_products',
					]);

				$field
					->setProductsLeftCount($productsLeftCount)
					->setProductsLeftUrl($productsLeftUrl);

				break;
			}

			$field->addValue(
				new Value\Product(
					$productRow->getProductName(),
					$this->router->getProductDetailUrl($productRow->getProductId()),
				),
			);
		}

		return $field;
	}

	public function getFm(?array $availableTypeIds = null): array
	{
		if (!$this->isAvailable(Item::FIELD_NAME_FM))
		{
			return [];
		}

		$multiFields = $this->item->getFm();
		if ($multiFields->isEmpty())
		{
			return [];
		}

		$fields = [];
		foreach ($multiFields->getAll() as $multiField)
		{
			$typeId = $multiField->getTypeId();
			if ($availableTypeIds !== null && !in_array($typeId, $availableTypeIds, true))
			{
				continue;
			}

			$caption = TypeRepository::getTypeCaption($typeId);

			if ($typeId === Phone::ID)
			{
				$fields[$typeId] ??= new PhoneField($caption);

				$sipConfig =  [
					'ENABLE_SIP' => true,
					'SIP_PARAMS' => [
						'ENTITY_TYPE' => "CRM_{$this->item->getEntityTypeId()}",
						'ENTITY_ID' => $this->item->getId(),
					],
				];

				$phone = Parser::getInstance()?->parse($multiField->getValue())->format();
				$linkAttrs = CCrmCallToUrl::PrepareLinkAttributes($phone, $sipConfig);

				$phone = new Value\Phone(
					$phone,
					$linkAttrs['HREF'] ?? '',
					$linkAttrs['ONCLICK'] ?? '',
				);

				$fields[$typeId]->addValue($phone);

				continue;
			}

			if ($typeId === Email::ID)
			{
				$fields[$typeId] ??= new EmailField($caption);
				$fields[$typeId]->addValue(
					new Value\Email(
						$multiField->getValue(),
						$this->item->getEntityTypeId(),
						$this->item->getId(),
					),
				);

				continue;
			}

			if ($typeId === Web::ID)
			{
				$hrefTemplate = CCrmFieldMulti::GetEntityTypes()[$typeId][$multiField->getValueType()]['LINK'] ?? null;
				if ($hrefTemplate === null)
				{
					continue;
				}

				$hrefValue = preg_replace('#^\s*https?://#i', '', $multiField->getValue());
				$href = strtr($hrefTemplate, [
					'#VALUE_URL#' => $hrefValue,
				]);

				$fields[$typeId] ??= new LinkField($caption);
				$fields[$typeId]->addValue(
					new Value\Link(
						$href,
						$multiField->getValue(),
						'_blank',
					),
				);

				continue;
			}

			$fields[$typeId] ??= new CommonField($caption);
			$fields[$typeId]->addValue($multiField->getValue());
		}

		return array_values($fields);
	}

	public function getOpportunity(): ?MoneyField
	{
		if (!$this->isAvailable(Item::FIELD_NAME_OPPORTUNITY))
		{
			return null;
		}

		$title = $this->factory->getFieldCaption(Item::FIELD_NAME_OPPORTUNITY);

		return (new MoneyField($title))
			->addValue($this->item->getOpportunity(), $this->item->getCurrencyId());
	}

	public function getCompany(): ?ClientField
	{
		if (!$this->isAvailable(Item::FIELD_NAME_COMPANY_ID))
		{
			return null;
		}

		$companyId = $this->item->getCompanyId();
		$company = Container::getInstance()->getFactory(CCrmOwnerType::Company)?->getItem($companyId);
		if ($company === null)
		{
			return null;
		}

		$client = $this->clientBuilder->buildClient($company);
		if ($client === null)
		{
			return null;
		}

		return (new ClientField($this->factory->getFieldCaption(Item::FIELD_NAME_COMPANY_ID)))
			->addValue($client);
	}

	public function getContact(): ?ClientField
	{
		if (!$this->isAvailable(Item::FIELD_NAME_CONTACT_IDS))
		{
			return null;
		}

		$contactIds = $this->item->getContactIds();
		$contacts = Container::getInstance()->getFactory(CCrmOwnerType::Contact)?->getItems([
			'filter' => [
				'@ID' => $contactIds,
			],
		]);

		if (empty($contacts))
		{
			return null;
		}

		$field = new ClientField($this->factory->getFieldCaption(Item::FIELD_NAME_CONTACT_ID));
		foreach ($contacts as $contact)
		{
			$client = $this->clientBuilder->buildClient($contact);
			if ($client === null)
			{
				continue;
			}

			$field->addValue($client);
		}

		return $field;
	}

	public function get(string $fieldId): ?CommonField
	{
		if (!$this->isAvailable($fieldId))
		{
			return null;
		}

		$title = $this->factory->getFieldCaption($fieldId);
		$value = $this->factory->getFieldValueCaption($fieldId, $this->item->get($fieldId));

		return (new CommonField($title))
			->addValue($value);
	}

	private function isAvailable(string $fieldId): bool
	{
		if (!$this->item->hasField($fieldId))
		{
			return false;
		}

		$field = $this->factory->getFieldsCollection()->getField($fieldId);

		return $field !== null && !$field->isItemValueEmpty($this->item);
	}
}
