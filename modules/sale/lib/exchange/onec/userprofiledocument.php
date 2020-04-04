<?php
namespace Bitrix\Sale\Exchange\OneC;

use Bitrix\Main;

class UserProfileDocument extends DocumentBase
{
	protected static $FIELD_INFOS = null;

	/**
	 * @return array
	 */
	protected static function getMessage()
	{
		return Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sale/general/export.php',self::CML_LANG_ID);
	}

	/**
	 * @return int
	 */
	public function getTypeId()
	{
		return DocumentType::USER_PROFILE;
	}

	/**
	 * @return array
	 */
	static public function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'XML_ID' => array(
					'TYPE' => 'string'
				),
				'VERSION' => array(
					'TYPE' => 'string'
				),
				'ITEM_NAME' => array(
					'TYPE' => 'string'
				),
				'OFICIAL_NAME' => array(
					'TYPE' => 'string'
				),
				'FULL_NAME' => array(
					'TYPE' => 'string'
				),
				'INN' => array(
					'TYPE' => 'string'
				),
				'KPP' => array(
					'TYPE' => 'string'
				),
				'OKPO_CODE' => array(
					'TYPE' => 'string'
				),
				'EGRPO' => array(
					'TYPE' => 'string'
				),
				'OKVED' => array(
					'TYPE' => 'string'
				),
				'OKDP' => array(
					'TYPE' => 'string'
				),
				'OKOPF' => array(
					'TYPE' => 'string'
				),
				'OKFC' => array(
					'TYPE' => 'string'
				),
				'OKPO' => array(
					'TYPE' => 'string'
				),
				//region export fields
				'SURNAME' => array(
					'TYPE' => 'string'
				),
				'NAME' => array(
					'TYPE' => 'string'
				),
				'MIDDLE_NAME' => array(
					'TYPE' => 'string'
				),
				'BIRTHDAY' => array(
					'TYPE' => 'string'
				),
				'SEX' => array(
					'TYPE' => 'string'
				),
				'MONEY_ACCOUNTS' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'ACCOUNT_NUMBER' => array(
							'TYPE' => 'string'
						),
						'BANK' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'ITEM_NAME' => array(
									'TYPE' => 'string'
								),
								'ADDRESS' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'PRESENTATION' => array(
											'TYPE' => 'string'
										),
										'ADDRESS_FIELD' => array(
											'TYPE' => 'array',
											'FIELDS' => array(
												'POST_CODE' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'COUNTRY' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'REGION' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'STATE' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'SMALL_CITY' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'CITY' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'STREET' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'HOUSE' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'BUILDING' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												),
												'FLAT' => array(
													'TYPE' => 'array',
													'FIELDS' => array(
														'TYPE' => array(
															'TYPE' => 'string'
														),
														'VALUE' => array(
															'TYPE' => 'string'
														)
													)
												)
											)
										)
									)
								)
							)
						)
					)
				),
				//endregion
				'REGISTRATION_ADDRESS' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'PRESENTATION' => array(
							'TYPE' => 'string'
						),
						'ADDRESS_FIELD' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'POST_CODE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'COUNTRY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'REGION' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STATE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'SMALL_CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STREET' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'HOUSE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'BUILDING' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'FLAT' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								)
							)
						)
					)
				),
				'UR_ADDRESS' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'PRESENTATION' => array(
							'TYPE' => 'string'
						),
						'ADDRESS_FIELD' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'POST_CODE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'COUNTRY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'REGION' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STATE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'SMALL_CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STREET' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'HOUSE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'BUILDING' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'FLAT' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								)
							)
						)
					)
				),
				'ADDRESS' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'PRESENTATION' => array(
							'TYPE' => 'string'
						),
						'ADDRESS_FIELD' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'POST_CODE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'COUNTRY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'REGION' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STATE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'SMALL_CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'CITY' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'STREET' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'HOUSE' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'BUILDING' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'FLAT' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								)
							)
						)
					)
				),
				'CONTACTS' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'CONTACT' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'WORK_PHONE_NEW' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								),
								'MAIL_NEW' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'TYPE' => array(
											'TYPE' => 'string'
										),
										'VALUE' => array(
											'TYPE' => 'string'
										)
									)
								)
							)
						)
					)
				),
				'REPRESENTATIVES' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'REPRESENTATIVE'=>array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'CONTACT_PERSON' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'RELATION' => array(
											'TYPE' => 'string'
										),
										'ITEM_NAME' => array(
											'TYPE' => 'string'
										)
									)
								),
								//region export representatives.fields
								'CONTRAGENT' => array(
									'TYPE' => 'array',
									'FIELDS' => array(
										'RELATION' => array(
											'TYPE' => 'string'
										),
										'ID' => array(
											'TYPE' => 'string'
										),
										'ITEM_NAME' => array(
											'TYPE' => 'string'
										)
									)
								)
								//endregion
							)
						)
					)
				),
				'ROLE' => array(
					'TYPE' => 'string'
				),
				'REK_VALUES' => array(
					'TYPE' => 'array',
					'FIELDS' => array(
						'DELIVERY_ADDRESS' => array(
							'TYPE' => 'array',
							'FIELDS' => array(
								'NAME' => array(
									'TYPE' => 'string'
								),
								'VALUE' => array(
									'TYPE' => 'string'
								)
							)
						)
					)
				)

			);
		}
		return self::$FIELD_INFOS;
	}


	/**
	 * @param array $document
	 * @return array
	 */
	static public function prepareFieldsData(array $document)
	{
		$message = static::getMessage();
		$fields = array();

		foreach(static::getFieldsInfo() as $k=>$v)
		{
			$value = $document[$message["SALE_EXPORT_".$k]][0]["#"];

			switch($k)
			{
				case 'XML_ID':
					$value = $document[$message["SALE_EXPORT_ID"]][0]["#"];
					self::internalizeFields($value, $v);
					$fields[$k] = $value;
					break;
				case 'VERSION':
				case 'ITEM_NAME':
				case 'OFICIAL_NAME':
				case 'FULL_NAME':
				case 'INN':
				case 'KPP':
				case 'OKPO_CODE':
				case 'EGRPO':
				case 'OKVED':
				case 'OKDP':
				case 'OKOPF':
				case 'OKFC':
				case 'OKPO':
					if(!empty($value))
					{
						static::internalizeFields($value, $v);
						$fields[$k] = $value;
					}
					break;
				case 'REGISTRATION_ADDRESS':
				case 'UR_ADDRESS':
				case 'ADDRESS':
				case 'CONTACTS':
				case 'REPRESENTATIVES':
					if(!empty($value))
					{
						$value = static::resolveFields($value, $v);
						$fields[$k] = $value;
					}
					break;
			}
		}
		return $fields;
	}

	/**
	 * @param $value
	 * @param $fieldsInfo
	 * @return array
	 */
	static protected function resolveRelationArrayParams($value, $fieldsInfo)
	{
		$fields = array();
		$message = self::getMessage();

		foreach($value as $item)
		{
			foreach($fieldsInfo['FIELDS'] as $name => $info)
			{
				if($message["SALE_EXPORT_".$name] == $item['#'][$message["SALE_EXPORT_RELATION"]][0]['#'])
				{
					$fields[$name] = self::resolveFields($item['#'], $info);
				}
			}
		}
		return $fields;
	}

	/**
	 * @param $value
	 * @param $fieldsInfo
	 * @return array
	 */
	static protected function resolveArrayParams($value, $fieldsInfo)
	{
		$fields = array();
		$message = self::getMessage();

		foreach($value as $item)
		{
			foreach($fieldsInfo['FIELDS'] as $name=>$info)
			{
				if($message["SALE_EXPORT_".$name] == $item['#'][$message["SALE_EXPORT_TYPE"]][0]['#'])
				{
					$fields[$name] = self::resolveFields($item['#'], $info);
				}
			}
		}
		return $fields;
	}

	/**
	 * @param array $document
	 * @param array $fieldsInfo
	 * @return null
	 */
	static protected function resolveFields(array $document, array $fieldsInfo)
	{
		$fields = null;
		$message = self::getMessage();

		foreach($fieldsInfo['FIELDS'] as $name => $info)
		{
			$value = '';
			$val = $document[$message["SALE_EXPORT_".$name]];
			if(!empty($val))
			{
				if($info['TYPE'] == 'array')
				{
					if($name == 'ADDRESS_FIELD' || $name == 'CONTACT')
					{
						$value = self::resolveArrayParams($val, $info);
					}
					elseif($name == 'REPRESENTATIVE')
					{
						$value = self::resolveRelationArrayParams($val, $info);
					}
				}
				else
				{
					$value = $val[0]["#"];
					self::internalizeFields($value, $info);
				}
				$fields[$name] = $value;
			}
		}
		return $fields;
	}

	/**
	 * @return null|string
	 */
	function getExternalId()
	{
		if(isset($this->fields['XML_ID']))
		{
			return $this->fields['XML_ID'];
		}

		return null;
	}
}