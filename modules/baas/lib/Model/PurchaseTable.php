<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\BooleanField;

/**
 * Class PurchaseTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Purchase_Query query()
 * @method static EO_Purchase_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Purchase_Result getById($id)
 * @method static EO_Purchase_Result getList(array $parameters = [])
 * @method static EO_Purchase_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_Purchase createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_Purchase_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_Purchase wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_Purchase_Collection wakeUpCollection($rows)
 */
class PurchaseTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;
	use Traits\InsertIgnore;
	use Traits\InsertUpdate;
	use Traits\UpdateBatch;

	public static function getTableName(): string
	{
		return 'b_baas_purchase';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('CODE'))
				->configurePrimary()
				->configureTitle('Purchase string ID')
			,
			(new ORM\Fields\StringField('PURCHASE_URL'))
				->configureTitle('Purchase url to the store')
			,
			new Main\ORM\Fields\Relations\Reference(
				'PURCHASED_PACKAGE',
				PurchasedPackageTable::class,
				['=this.CODE' => 'ref.PURCHASE_CODE'],
				['join_type' => 'LEFT'],
			),
			(new BooleanField('PURGED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
			,
			(new BooleanField('NOTIFIED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
			,
		];
	}
}
