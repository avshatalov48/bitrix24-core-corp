<?php
namespace Bitrix\Location\Repository\Format;

use Bitrix\Location\Entity\Address\FieldType;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DataCollection
{
	/**
	 * @param string $languageId
	 * @return array|array[]
	 */
	public static function getAll(string $languageId): array
	{
		return [
			'RU' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_RUS_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_RUS_DESCR', null, $languageId),
				'delimiter' => ', ',
				'sort' => 100,
				'template' => "{{#ADDRESS_LINE_1#, }}{{#ADDRESS_LINE_2#}}\n{{#LOCALITY#, }}{{#ADM_LEVEL_1#}}\n{{#COUNTRY#, }}{{#POSTAL_CODE#}}",
				'code' => 'RU',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 600,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 500,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 350,
						'type' => FieldType::ADM_LEVEL_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADM_LEVEL_2', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 300,
						'type' => FieldType::ADM_LEVEL_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_REGION', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 200,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					]
				]
			],
			'EU' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_EU_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_EU_DESCR', null, $languageId),
				'delimiter' => ' ',
				'sort' => 200,
				'template' => "{{#ADDRESS_LINE_1# }}{{#ADDRESS_LINE_2#}}\n{{#POSTAL_CODE# }}{{#LOCALITY#}}\n{{#COUNTRY#}}",
				'code' => 'EU',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 600,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 500,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 200,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					]
				]
			],
			//todo: state
			'US' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_US_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_US_DESCR', null, $languageId),
				'delimiter' => ' ',
				'sort' => 300,
				'template' => "{{#ADDRESS_LINE_1#, }}{{#ADDRESS_LINE_2#}}\n{{#LOCALITY#}}{{ #ADM_LEVEL_1#}}{{ #POSTAL_CODE#}}\n{{#COUNTRY#}}",
				'code' => 'US',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 400,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 300,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 250,
						'type' => FieldType::ADM_LEVEL_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_REGION', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 200,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					]
				]
			],
			'UK' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_UK_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_UK_DESCR', null, $languageId),
				'delimiter' => ' ',
				'sort' => 400,
				'template' => "{{#ADDRESS_LINE_1#, }}{{#ADDRESS_LINE_2#}}\n{{#LOCALITY#\n}}{{#POSTAL_CODE#\n}}{{#COUNTRY#}}",
				'code' => 'UK',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 400,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 300,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 200,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					]
				]
			],
			'RU_2' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_RUS_2_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_RUS_2_DESCR', null, $languageId),
				'delimiter' => ', ',
				'sort' => 500,
				'template' => "{{#POSTAL_CODE#\n}}{{#COUNTRY#\n}}{{#LOCALITY#\n}}{{#ADDRESS_LINE_1# }}{{#ADDRESS_LINE_2#}}",
				'code' => 'RU_2',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 600,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 500,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 350,
						'type' => FieldType::ADM_LEVEL_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADM_LEVEL_2', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 300,
						'type' => FieldType::ADM_LEVEL_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_REGION', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 200,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					]
				]
			],
			'BR' => [
				'name' => Loc::getMessage('LOCATION_REPO_FRMT_BR_NAME', null, $languageId),
				'description' => Loc::getMessage('LOCATION_REPO_FRMT_BR_DESCR', null, $languageId),
				'delimiter' => ', ',
				'sort' => 600,
				'template' => "{{#ADDRESS_LINE_1#}}{{, #ADDRESS_LINE_2#}}\n{{#SUB_LOCALITY_LEVEL_1# }}\n{{#LOCALITY# }} {{#ADM_LEVEL_2# - }} {{#ADM_LEVEL_1#}}\n{{#POSTAL_CODE#}}\n{{#COUNTRY#}}",
				'code' => 'BR',
				'fieldForUnRecognized' => FieldType::ADDRESS_LINE_2,
				'fieldCollection' => [
					[
						'sort' => 800,
						'type' => FieldType::ADDRESS_LINE_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_2', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 700,
						'type' => FieldType::ADDRESS_LINE_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADDR_1', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 600,
						'type' => FieldType::LOCALITY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_LOCALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 500,
						'type' => FieldType::SUB_LOCALITY_LEVEL_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_SUB_LOCALITY_LEVEL_1_NEIGHBORHOOD', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 400,
						'type' => FieldType::ADM_LEVEL_2,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_ADM_LEVEL_2_MUNICIPALITY', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 300,
						'type' => FieldType::ADM_LEVEL_1,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_STATE', null, $languageId),
						'description' =>''
					],
					[
						'sort' => 200,
						'type' => FieldType::POSTAL_CODE,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_POSTAL_CODE', null, $languageId),
						'description' => ''
					],
					[
						'sort' => 100,
						'type' => FieldType::COUNTRY,
						'name' => Loc::getMessage('LOCATION_REPO_FRMT_COUNTRY', null, $languageId),
						'description' => ''
					],
				]
			],
		];
	}

	/**
	 * @param string $code
	 * @param string $languageId
	 * @return array|null
	 */
	public static function getByCode(string $code, string $languageId): ?array
	{
		$data = self::getAll($languageId);
		return isset($data[$code]) ? $data[$code] : null;
	}
}