<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Join;

// load language file that was created before this file renaming
Loc::loadMessages(Path::combine(__DIR__, 'productrow.php'));

/**
 * Base prices:
 * PRICE_EXCLUSIVE - base price with discount;
 * PRICE_NETTO - base price.
 *
 * Prices with tax/vat:
 * PRICE - base price with discount and vat.
 * PRICE_BRUTTO - base price with vat.
 */
class ProductRowTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_product_row';
	}

	public static function getObjectClass(): string
	{
		return ProductRow::class;
	}

	public static function getCollectionClass(): string
	{
		return ProductRowCollection::class;
	}

	public static function getMap(): array
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('OWNER_ID'))
				->configureRequired(),
			(new StringField('OWNER_TYPE'))
				->configureRequired()
				->configureSize(3),
			(new Reference(
				'OWNER',
				DealTable::class,
				Join::on('this.OWNER_ID', 'ref.ID')
			)),
			(new Reference(
				'DEAL_OWNER',
				DealTable::class,
				Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE', \CCrmOwnerTypeAbbr::Deal)
			)),
			(new Reference(
				'LEAD_OWNER',
				LeadTable::class,
				Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE', \CCrmOwnerTypeAbbr::Lead)
			)),
			(new Reference(
				'QUOTE_OWNER',
				QuoteTable::class,
				Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE', \CCrmOwnerTypeAbbr::Quote)
			)),
			(new IntegerField('PRODUCT_ID'))
				->configureRequired()
				->configureDefaultValue(0),
			(new StringField('PRODUCT_NAME'))
				->configureSize(256)
				->configureDefaultValue(''),
			(new Reference(
				'IBLOCK_ELEMENT',
				IBlockElementProxyTable::class,
				Join::on('this.PRODUCT_ID', 'ref.ID')
			)),
			(new Reference(
				'IBLOCK_ELEMENT_GRC',
				IBlockElementGrcProxyTable::class,
				Join::on('this.PRODUCT_ID', 'ref.ID')
			)),
			(new ExpressionField(
				'CP_PRODUCT_NAME',
				'CASE WHEN %s IS NOT NULL AND %s != \'\' THEN %s ELSE %s END',
				['PRODUCT_NAME', 'PRODUCT_NAME', 'PRODUCT_NAME', 'IBLOCK_ELEMENT.NAME']
			))
				->configureValueType(StringField::class),
			(new FloatField('PRICE'))
				->configureRequired()
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('PRICE_ACCOUNT'))
				->configureRequired()
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('PRICE_EXCLUSIVE'))
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('PRICE_NETTO'))
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('PRICE_BRUTTO'))
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('QUANTITY'))
				->configureRequired()
				->configureScale(4)
				->configureDefaultValue(1),
			(new ExpressionField(
				'SUM_ACCOUNT',
				'%s * %s',
				['PRICE_ACCOUNT', 'QUANTITY']
			))
				->configureValueType(FloatField::class),
			(new IntegerField('DISCOUNT_TYPE_ID'))
				->configureDefaultValue(Discount::UNDEFINED),
			(new FloatField('DISCOUNT_RATE'))
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('DISCOUNT_SUM'))
				->configureScale(2)
				->configureDefaultValue(0.00),
			(new FloatField('TAX_RATE'))
				->configureScale(2)
				->configureNullable(),
			(new BooleanField('TAX_INCLUDED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false),
			(new BooleanField('CUSTOMIZED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(true),
			(new IntegerField('MEASURE_CODE'))
				->configureDefaultValue(0),
			(new StringField('MEASURE_NAME'))
				->configureSize(50)
				->configureDefaultValue(''),
			(new IntegerField('SORT'))
				->configureDefaultValue(0),
			(new StringField('XML_ID'))
				->configureDefaultValue(''),
			(new IntegerField('TYPE'))
				->configureDefaultValue(ProductType::TYPE_PRODUCT)
				->configureRequired()
				->addValidator([static::class, 'validateSupportedProductType']),
			$fieldRepository->getProductRowReservation(),
		];
	}

	public static function deleteByItem(int $entityTypeId, int $entityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$ownerType = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE ROW_ID IN (
				SELECT ID FROM %s WHERE OWNER_TYPE = %s AND OWNER_ID = %d
			)',
			$helper->quote(Reservation\Internals\ProductRowReservationTable::getTableName()),
			$helper->quote(static::getTableName()),
			$helper->convertToDbString($ownerType),
			$helper->convertToDbInteger($entityId)
		));

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE OWNER_TYPE = %s AND OWNER_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbString($ownerType),
			$helper->convertToDbInteger($entityId)
		));

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE OWNER_TYPE = %s AND OWNER_ID = %d',
			$helper->quote(\CCrmProductRow::CONFIG_TABLE_NAME),
			$helper->convertToDbString($ownerType),
			$helper->convertToDbInteger($entityId)
		));
	}

	/**
	 * Deletes product row with reserve data
	 *
	 * @param mixed $primary
	 *
	 * @return DeleteResult
	 *
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		Reservation\Internals\ProductRowReservationTable::deleteByRowId($primary);
		return parent::delete($primary);
	}

	/**
	 * Used in OwnerDataManager::onAfterUpdate to delete unbound products
	 *
	 * Basically, simulates behaviour of @see \Bitrix\Main\ORM\Fields\Relations\CascadePolicy::FOLLOW
	 * But because of recycle bin, we can't use it with products. An owner deletion cause products deletion, but when we
	 * move the owner to recycle bin, it's an unwanted behaviour.
	 *
	 * Therefore, rather that using CascadePolicy::FOLLOW, we delete unbound products on an item update in this method and
	 * delete all bound (and not suspended) products on an item deletion (@see deleteByItem)
	 *
	 * @param EntityObject $item Owner of products
	 * @param EventResult $result A collecting parameter, which is used to collect errors
	 */
	public static function handleOwnerUpdate(EntityObject $item, EventResult $result): void
	{
		if (!$item->entity->hasField(Item::FIELD_NAME_PRODUCTS))
		{
			return;
		}

		/** @var Collection|null $products */
		$products = $item->get(Item::FIELD_NAME_PRODUCTS);
		if (!$products || !$item->isChanged(Item::FIELD_NAME_PRODUCTS))
		{
			return;
		}

		/** @var ProductRow|EntityObject $changedObject */
		/** @var int $changeType */
		foreach ($products->sysGetChanges() as [$changedObject, $changeType])
		{
			if ($changeType === Collection::OBJECT_REMOVED)
			{
				$deleteResult = $changedObject->delete();

				if (!$deleteResult->isSuccess())
				{
					foreach ($deleteResult->getErrors() as $error)
					{
						$result->addError(new EntityError($error->getMessage()));
					}
				}
			}
		}
	}

	/**
	 * Validates product type before save
	 *
	 * @param $value
	 * @param $primary
	 * @param array $row
	 * @param \Bitrix\Main\ORM\Fields\Field $field
	 * @return bool|string
	 */
	public static function validateSupportedProductType($value, $primary, array $row, \Bitrix\Main\ORM\Fields\Field $field)
	{
		$value = (int)$value;
		if (in_array($value, static::getSupportedProductTypes(), true))
		{
			return true;
		}

		return 'Type is not supported';
	}

	private static function getSupportedProductTypes(): array
	{
		return [
			ProductType::TYPE_PRODUCT,
			ProductType::TYPE_SET,
			ProductType::TYPE_SKU,
			ProductType::TYPE_OFFER,
			ProductType::TYPE_FREE_OFFER,
			ProductType::TYPE_EMPTY_SKU,
			ProductType::TYPE_SERVICE,
		];
	}
}
