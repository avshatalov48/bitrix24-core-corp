<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter\Model;

use Bitrix\Main\Entity;

/**
 * Class BaasPackageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BaasPackage_Query query()
 * @method static EO_BaasPackage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BaasPackage_Result getById($id)
 * @method static EO_BaasPackage_Result getList(array $parameters = [])
 * @method static EO_BaasPackage_Entity getEntity()
 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection createCollection()
 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage wakeUpObject($row)
 * @method static \Bitrix\AI\Limiter\Model\EO_BaasPackage_Collection wakeUpCollection($rows)
 */
class BaasPackageTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_baas_package';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Entity\DateField('DATE_START', [
				'required' => true,
			]),
			new Entity\DateField('DATE_EXPIRED', [
				'required' => true,
			]),
		];
	}
}
