<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Crm;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Main;
use Bitrix\Main\Data\Cache;

/**
 * Class EntityLink
 * @package Bitrix\Crm\Requisite
 */
class EntityLink
{
	const ERR_INVALID_ENTITY_TYPE                           = 201;
	const ERR_INVALID_ENTITY_ID                             = 202;
	const ERR_ENTITY_NOT_FOUND                              = 203;
	const ERR_INVALID_REQUSIITE_ID                          = 204;
	const ERR_INVALID_BANK_DETAIL_ID                        = 205;
	const ERR_INVALID_MC_REQUSIITE_ID                       = 206;
	const ERR_INVALID_MC_BANK_DETAIL_ID                     = 207;
	const ERR_REQUISITE_NOT_FOUND                           = 208;
	const ERR_REQUISITE_TIED_TO_ENTITY_WITHOUT_CLIENT       = 209;
	const ERR_REQUISITE_NOT_ASSIGNED                        = 210;
	const ERR_BANK_DETAIL_NOT_FOUND                         = 211;
	const ERR_BANK_DETAIL_NOT_ASSIGNED_WO_REQUISITE         = 212;
	const ERR_BANK_DETAIL_NOT_ASSIGNED                      = 213;
	const ERR_MC_REQUISITE_TIED_TO_ENTITY_WITHOUT_MYCOMPANY = 214;
	const ERR_MC_REQUISITE_NOT_FOUND                        = 215;
	const ERR_MC_REQUISITE_NOT_ASSIGNED                     = 216;
	const ERR_MC_BANK_DETAIL_NOT_ASSIGNED_WO_MC_REQUISITE   = 217;
	const ERR_MC_BANK_DETAIL_NOT_FOUND                      = 218;
	const ERR_MC_BANK_DETAIL_NOT_ASSIGNED                   = 219;
	const ERR_ENTITY_CHECK_N_BUT_CLIENT_NOT_SPECIFIED       = 220;

	const ENTITY_OPERATION_FIRST = 1;
	const ENTITY_OPERATION_ADD = 1;
	const ENTITY_OPERATION_UPDATE = 2;
	const ENTITY_OPERATION_LAST = 2;

	protected const CACHE_PATH_MY_COMPNAY = '/crm/requisite/entitylink/mycompany/';
	protected const CACHE_TTL_MY_COMPANY = 86400;
	protected const CACHE_ID_MY_COMPANY_BY_ID = 'myCompanyById';
	protected const CACHE_ID_MY_COMPANY_FIRST = 'myCompanyFirst';

	private static $FIELD_INFOS = null;
	private static $parentEntityFieldMap = null;

	protected static ?Crm\Cache\RatedCache $byEntityCache = null;

	protected static function clearByEntityCache()
	{
		if (static::$byEntityCache)
		{
			static::$byEntityCache->clear();
		}
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function getByEntity($entityTypeId, $entityId)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		if (!\CCrmOwnerType::IsDefined($entityTypeId) || $entityId <= 0)
		{
			return null;
		}

		if (static::$byEntityCache === null)
		{
			static::$byEntityCache = new Crm\Cache\RatedCache();
		}

		$cacheKey = $entityTypeId . '_' . $entityId;
		$result = static::$byEntityCache->get($cacheKey);
		if ($result !== null)
		{
			return $result;
		}

		$dbResult = LinkTable::getList(
			array(
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeId, '=ENTITY_ID' => $entityId),
				'select' => array('REQUISITE_ID', 'BANK_DETAIL_ID', 'MC_REQUISITE_ID', 'MC_BANK_DETAIL_ID'),
				'limit' => 1
			)
		);
		$fields = $dbResult->fetch();

		$fields = is_array($fields) ? $fields : [];
		static::$byEntityCache->set($cacheKey, $fields);

		return !empty($fields) ? $fields : null;
	}

	/**
	 * @return array
	 */
	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ENTITY_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required, \CCrmFieldInfoAttr::Immutable)
				),
				'ENTITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required, \CCrmFieldInfoAttr::Immutable)
				),
				'REQUISITE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'BANK_DETAIL_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'MC_REQUISITE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'MC_BANK_DETAIL_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				)
			);
		}

		return self::$FIELD_INFOS;
	}

	public static function getFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_ENTITY_LINK_{$fieldName}_FIELD");
		return is_string($result) ? $result : '';
	}

	public static function getParentEntityFieldMap()
	{
		if (self::$parentEntityFieldMap === null)
		{
			self::$parentEntityFieldMap = [
				\CCrmOwnerType::Deal => [
					\CCrmOwnerType::Quote => 'QUOTE_ID'
				],
				\CCrmOwnerType::Quote => [
					\CCrmOwnerType::Deal => 'DEAL_ID'
				],
				\CCrmOwnerType::Invoice => [
					\CCrmOwnerType::Quote => 'UF_QUOTE_ID',
					\CCrmOwnerType::Deal => 'UF_DEAL_ID'
				],
				\CCrmOwnerType::SmartInvoice => [
					\CCrmOwnerType::Quote => Service\ParentFieldManager::getParentFieldName(\CCrmOwnerType::Quote),
					\CCrmOwnerType::Deal => Service\ParentFieldManager::getParentFieldName(\CCrmOwnerType::Deal),
				],
			];
		}

		return self::$parentEntityFieldMap;
	}

	public static function getEntityClientSellerInfo($entityTypeId, $entityId, $options = [])
	{
		$getParentEntityFields = (
			is_array($options) && isset($options['GET_PARENT_ENTITY_FILEDS'])
			&& ($options['GET_PARENT_ENTITY_FILEDS'] === 'Y' || $options['GET_PARENT_ENTITY_FILEDS'] === true)
		);

		$entityNotFound = false;
		$result = array(
			'CLIENT_ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'CLIENT_ENTITY_ID' => 0,
			'MYCOMPANY_ID' => 0
		);
		$parentFieldMap = self::getParentEntityFieldMap();
		$parentFileds = [];
		if ($getParentEntityFields && is_array($parentFieldMap[$entityTypeId] ?? null))
		{
			$parentFileds = array_values($parentFieldMap[$entityTypeId]);
		}
		if ($entityTypeId === \CCrmOwnerType::Invoice)
		{
			$select = array('ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID');
			$res = \CCrmInvoice::GetList(
				array(),
				array('ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array_merge($select, $parentFileds)
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				if (isset($row['UF_COMPANY_ID']) && $row['UF_COMPANY_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$result['CLIENT_ENTITY_ID'] = (int)$row['UF_COMPANY_ID'];
				}
				else if (isset($row['UF_CONTACT_ID']) && $row['UF_CONTACT_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
					$result['CLIENT_ENTITY_ID'] = (int)$row['UF_CONTACT_ID'];
				}

				if (isset($row['UF_MYCOMPANY_ID']) && $row['UF_MYCOMPANY_ID'] > 0)
					$result['MYCOMPANY_ID'] = (int)$row['UF_MYCOMPANY_ID'];
			}
			else
			{
				$entityNotFound = true;
			}
		}
		else
		{
			$factory = Service\Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				if (
					!$getParentEntityFields
					|| empty($parentFileds)
					|| $entityTypeId != \CCrmOwnerType::SmartInvoice
				)
				{
					[$result, $entityNotFound] = self::clientSellerInfoByOrm(
						$factory,
						$entityId,
						$result,
						$getParentEntityFields,
						$parentFileds,
						$entityNotFound
					);
				}
				else
				{
					[$result, $entityNotFound] = self::clientSellerInfoByFactory(
						$factory,
						$entityId,
						$result,
						$getParentEntityFields,
						$parentFileds,
						$entityNotFound
					);
				}
			}
			else
			{
				$entityNotFound = true;
			}
		}

		if ($entityNotFound)
			throw new Main\SystemException('Entity is not found', self::ERR_ENTITY_NOT_FOUND);

		return $result;
	}

	public static function checkConsistence($entityTypeId, $entityId,
		$requisiteId, $bankDetailId, $mcRequisiteId, $mcBankDetailId, $options = null)
	{
		$enableEntityCheck = (!is_array($options) || !isset($options['ENTITY_CHECK'])
			|| $options['ENTITY_CHECK'] === 'Y' || $options['ENTITY_CHECK'] === true);
		$skipIsMyCompanyCheck = is_array($options) && isset($options['IS_MY_COMPANY_CHECK'])
			&& $options['IS_MY_COMPANY_CHECK'] !== 'Y' && $options['IS_MY_COMPANY_CHECK'] !== true;

		if (!$enableEntityCheck
			&& (!is_array($options)
				|| !is_array($options['CLIENT_SELLER_INFO'])
				|| !isset($options['CLIENT_SELLER_INFO']['CLIENT_ENTITY_TYPE_ID'])
				|| !isset($options['CLIENT_SELLER_INFO']['CLIENT_ENTITY_ID'])
				|| !(\CCrmOwnerType::IsDefined($options['CLIENT_SELLER_INFO']['CLIENT_ENTITY_TYPE_ID'])
					|| $options['CLIENT_SELLER_INFO']['CLIENT_ENTITY_TYPE_ID'] === \CCrmOwnerType::Undefined)
				|| ($options['CLIENT_ENTITY_ID'] ?? null) < 0))
		{
			throw new Main\SystemException(
				'The entity check is disabled, but client type and id are not specified.',
				self::ERR_ENTITY_CHECK_N_BUT_CLIENT_NOT_SPECIFIED
			);
		}

		$availableEntityTypeIds = static::getAvailableEntityTypeIds();

		if ($enableEntityCheck && !isset($availableEntityTypeIds[$entityTypeId]))
		{
			throw new Main\SystemException(
				'Entity type is not defined or invalid.',
				self::ERR_INVALID_ENTITY_TYPE
			);
		}

		if($enableEntityCheck && !(is_int($entityId) && $entityId > 0))
		{
			throw new Main\SystemException(
				'Entity identifier is not defined or invalid.',
				self::ERR_INVALID_ENTITY_ID
			);
		}

		if(!is_int($requisiteId) || $requisiteId < 0)
		{
			throw new Main\SystemException(
				'Requisite identifier is not defined or invalid.',
				self::ERR_INVALID_REQUSIITE_ID
			);
		}

		if(!is_int($bankDetailId) || $bankDetailId < 0)
		{
			throw new Main\SystemException(
				'BankDetail identifier is not defined or invalid.',
				self::ERR_INVALID_BANK_DETAIL_ID
			);
		}

		if(!is_int($mcRequisiteId) || $mcRequisiteId < 0)
		{
			throw new Main\SystemException(
				'Requisite identifier of your company is not defined or invalid.',
				self::ERR_INVALID_MC_REQUSIITE_ID
			);
		}

		if(!is_int($mcBankDetailId) || $mcBankDetailId < 0)
		{
			throw new Main\SystemException(
				'BankDetail identifier of your company is not defined or invalid.',
				self::ERR_INVALID_MC_BANK_DETAIL_ID
			);
		}

		if (is_array($options) && is_array($options['CLIENT_SELLER_INFO']))
		{
			$clientSellerInfo = $options['CLIENT_SELLER_INFO'];
		}
		else
		{
			$clientSellerInfo = self::getEntityClientSellerInfo($entityTypeId, $entityId);
		}

		$requisite = new EntityRequisite();
		$bankDetail = new EntityBankDetail();
		$entityTypeName = null;

		if ($requisiteId > 0)
		{
			if ($clientSellerInfo['CLIENT_ENTITY_TYPE_ID'] === \CCrmOwnerType::Undefined
				|| $clientSellerInfo['CLIENT_ENTITY_ID'] <= 0)
			{
				if ($entityTypeName === null)
				{
					$entityTypeName = ucfirst(mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)));
				}
				throw new Main\SystemException(
					"Requisite with ID '$requisiteId' can not be tied to the $entityTypeName ".
						"in which the client is not selected.",
					self::ERR_REQUISITE_TIED_TO_ENTITY_WITHOUT_CLIENT
				);
			}

			$res = $requisite->getList(
				array(
					'filter' => array('=ID' => $requisiteId),
					'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
				)
			);
			$row = $res->fetch();
			unset($res);
			if (!is_array($row))
			{
				throw new Main\SystemException(
					"The Requisite with ID '$requisiteId' is not found.",
					self::ERR_REQUISITE_NOT_FOUND
				);
			}
			$rqEntityTypeId = isset($row['ENTITY_TYPE_ID']) ? (int)$row['ENTITY_TYPE_ID'] : 0;
			$rqEntityId = isset($row['ENTITY_ID']) ? (int)$row['ENTITY_ID'] : 0;
			$clientEntityTypeId = (int)$clientSellerInfo['CLIENT_ENTITY_TYPE_ID'];
			$clientEntityId = (int)$clientSellerInfo['CLIENT_ENTITY_ID'];
			if ($clientEntityTypeId !== $rqEntityTypeId || $clientEntityId !== $rqEntityId)
			{
				$clientEntityTypeName = ucfirst(mb_strtolower(\CCrmOwnerType::ResolveName($clientEntityTypeId)));
				throw new Main\SystemException(
					"The Requisite with ID '$requisiteId' is not assigned to $clientEntityTypeName ".
						"with ID '$clientEntityId'.",
					self::ERR_REQUISITE_NOT_ASSIGNED
				);
			}
		}

		if ($bankDetailId > 0)
		{
			if ($requisiteId <= 0)
			{
				throw new Main\SystemException(
					"The BankDetail can not be assigned without Requisite.",
					self::ERR_BANK_DETAIL_NOT_ASSIGNED_WO_REQUISITE
				);
			}

			$res = $bankDetail->getList(
				array(
					'filter' => array('=ID' => $bankDetailId),
					'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
				)
			);
			$row = $res->fetch();
			unset($res);
			if (!is_array($row))
			{
				throw new Main\SystemException(
					"The BankDetail with ID '$bankDetailId' is not found.",
					self::ERR_BANK_DETAIL_NOT_FOUND
				);
			}
			$bdEntityTypeId = isset($row['ENTITY_TYPE_ID']) ? (int)$row['ENTITY_TYPE_ID'] : 0;
			$bdEntityId = isset($row['ENTITY_ID']) ? (int)$row['ENTITY_ID'] : 0;
			if ($bdEntityTypeId !== \CCrmOwnerType::Requisite || $bdEntityId !== $requisiteId)
			{
				throw new Main\SystemException(
					"The BankDetail with ID '$bankDetailId' is not assigned to Requisite with ID '$requisiteId'.",
					self::ERR_BANK_DETAIL_NOT_ASSIGNED
				);
			}
		}

		if ($mcRequisiteId > 0)
		{
			if ($clientSellerInfo['MYCOMPANY_ID'] <= 0
				|| (!$skipIsMyCompanyCheck && $clientSellerInfo['MYCOMPANY_ID'] > 0
					&& !self::isMyCompany($clientSellerInfo['MYCOMPANY_ID'])))
			{
				if ($entityTypeName === null)
				{
					$entityTypeName = ucfirst(mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)));
				}
				throw new Main\SystemException(
					"Requisite of your company with ID '$requisiteId' can not be tied to the $entityTypeName ".
					"in which your company is not selected.",
					self::ERR_MC_REQUISITE_TIED_TO_ENTITY_WITHOUT_MYCOMPANY
				);
			}

			$myCompanyId = (int)$clientSellerInfo['MYCOMPANY_ID'];
			$res = $requisite->getList(
				array(
					'filter' => array('=ID' => $mcRequisiteId),
					'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
				)
			);
			$row = $res->fetch();
			unset($res);
			if (!is_array($row))
			{
				throw new Main\SystemException(
					"The Requisite of your company with ID '$mcRequisiteId' is not found.",
					self::ERR_MC_REQUISITE_NOT_FOUND
				);
			}
			$rqEntityTypeId = isset($row['ENTITY_TYPE_ID']) ? (int)$row['ENTITY_TYPE_ID'] : 0;
			$rqEntityId = isset($row['ENTITY_ID']) ? (int)$row['ENTITY_ID'] : 0;
			if ($rqEntityTypeId !== \CCrmOwnerType::Company || $rqEntityId !== $myCompanyId)
			{
				throw new Main\SystemException(
					"The Requisite with ID '$mcRequisiteId' is not assigned to your company with ID '$myCompanyId'.",
					self::ERR_MC_REQUISITE_NOT_ASSIGNED
				);
			}
		}

		if ($mcBankDetailId > 0)
		{
			if ($mcRequisiteId <= 0)
			{
				throw new Main\SystemException(
					"The BankDetail of your company can not be assigned without Requisite of your company.",
					self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED_WO_MC_REQUISITE
				);
			}

			$res = $bankDetail->getList(
				array(
					'filter' => array('=ID' => $mcBankDetailId),
					'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
				)
			);
			$row = $res->fetch();
			unset($res);
			if (!is_array($row))
			{
				throw new Main\SystemException(
					"The BankDetail of your company with ID '$mcBankDetailId' is not found.",
					self::ERR_MC_BANK_DETAIL_NOT_FOUND
				);
			}
			$bdEntityTypeId = isset($row['ENTITY_TYPE_ID']) ? (int)$row['ENTITY_TYPE_ID'] : 0;
			$bdEntityId = isset($row['ENTITY_ID']) ? (int)$row['ENTITY_ID'] : 0;
			if ($bdEntityTypeId !== \CCrmOwnerType::Requisite || $bdEntityId !== $mcRequisiteId)
			{
				throw new Main\SystemException(
					"The BankDetail of your company with ID '$mcBankDetailId' is not assigned to ".
						"Requisite of your company with ID '$mcRequisiteId'.",
					self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED
				);
			}
		}
	}

	/**
	 * @param $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($parameters)
	{
		return LinkTable::getList($parameters);
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @param $requisiteId
	 * @param int $bankDetailId
	 * @param int $mcRequisiteId
	 * @param int $mcBankDetailId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function register(
		$entityTypeId, $entityId,
		$requisiteId = 0, $bankDetailId = 0,
		$mcRequisiteId = 0, $mcBankDetailId = 0
	)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityTypeId = (int)$entityTypeId;
		if($entityTypeId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityTypeId');

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityId');

		$requisiteId = (int)$requisiteId;
		if($requisiteId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$bankDetailId = (int)$bankDetailId;
		if($bankDetailId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'bankDetailId');

		$mcRequisiteId = (int)$mcRequisiteId;
		if($mcRequisiteId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'mcRequisiteId');

		$mcBankDetailId = (int)$mcBankDetailId;
		if($mcBankDetailId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'mcBankDetailId');

		LinkTable::upsert(
			array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId,
				'MC_REQUISITE_ID' => $mcRequisiteId,
				'MC_BANK_DETAIL_ID' => $mcBankDetailId
			)
		);

		static::clearByEntityCache();
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function unregister($entityTypeId, $entityId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityTypeId = (int)$entityTypeId;
		if($entityTypeId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityTypeId');

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityId');

		LinkTable::delete(
			array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId
			)
		);

		static::clearByEntityCache();
	}

	/**
	 * @param $requisiteId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function unregisterByRequisite($requisiteId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$requisiteId = (int)$requisiteId;
		if ($requisiteId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$connection = Main\Application::getConnection();

		$tableName = LinkTable::getTableName();

		$connection->queryExecute(
			"DELETE FROM {$tableName} WHERE (REQUISITE_ID = {$requisiteId} AND MC_REQUISITE_ID = 0) OR ".
			"(MC_REQUISITE_ID = {$requisiteId} AND REQUISITE_ID = 0) OR ".
			"(MC_REQUISITE_ID = {$requisiteId} AND REQUISITE_ID = {$requisiteId})"
		);
		$connection->queryExecute(
			"UPDATE {$tableName} ".
			"SET REQUISITE_ID = 0, BANK_DETAIL_ID = 0 ".
			"WHERE REQUISITE_ID = {$requisiteId} AND MC_REQUISITE_ID > 0"
		);
		$connection->queryExecute(
			"UPDATE {$tableName} ".
			"SET MC_REQUISITE_ID = 0, MC_BANK_DETAIL_ID = 0 ".
			"WHERE MC_REQUISITE_ID = {$requisiteId} AND REQUISITE_ID > 0"
		);

		static::clearByEntityCache();
	}

	public static function getSelectedRequisiteLink($entityTypeId, $entityId): array
	{
		$result = [
			'REQUISITE_ID' => 0,
			'BANK_DETAIL_ID' => 0
		];

		$requisite = EntityRequisite::getSingleInstance();
		$bankDetail = EntityBankDetail::getSingleInstance();

		$settings = $requisite->loadSettings($entityTypeId, $entityId);
		if (is_array($settings))
		{
			$requisiteId = 0;

			if (isset($settings['REQUISITE_ID_SELECTED']) && $settings['REQUISITE_ID_SELECTED'] > 0)
			{
				$defRequisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
				if ($defRequisiteId > 0)
				{
					if ($requisite->exists($defRequisiteId))
					{
						$requisiteId = $defRequisiteId;
						$result['REQUISITE_ID'] = $requisiteId;
					}
				}
			}

			if ($requisiteId > 0 && isset($settings['BANK_DETAIL_ID_SELECTED']))
			{
				$defBankDetailId = (int)$settings['BANK_DETAIL_ID_SELECTED'];
				if ($defBankDetailId > 0)
				{
					if ($bankDetail->exists($defBankDetailId, \CCrmOwnerType::Requisite, $requisiteId))
					{
						$result['BANK_DETAIL_ID'] = $defBankDetailId;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return array Array of identifiers by default for seller, requisites and bank details
	 */
	public static function getDefaultMyCompanyRequisiteLink()
	{
		$mcRequisiteId = 0;
		$mcBankDetailId = 0;

		$myCompanyId = self::getDefaultMyCompanyId();

		if ($myCompanyId > 0)
		{
			[$mcRequisiteId, $mcBankDetailId] = array_values(
				static::getSelectedRequisiteLink(\CCrmOwnerType::Company, $myCompanyId)
			);

			if ($mcRequisiteId <= 0)
			{
				$mcBankDetailId = 0;
				$requisite = EntityRequisite::getSingleInstance();
				$res = $requisite->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
							'=ENTITY_ID' => $myCompanyId
						),
						'select' => array('ID'),
						'limit' => 1
					)
				);
				if ($row = $res->fetch())
				{
					$mcRequisiteId = (int)$row['ID'];
				}
			}

			if ($mcRequisiteId > 0)
			{
				if ($mcBankDetailId <= 0)
				{
					$bankDetail = EntityBankDetail::getSingleInstance();
					$res = $bankDetail->getList(
						array(
							'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
							'filter' => array(
								'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
								'=ENTITY_ID' => $mcRequisiteId
							),
							'select' => array('ID'),
							'limit' => 1
						)
					);
					if ($row = $res->fetch())
					{
						$mcBankDetailId = (int)$row['ID'];
					}
				}
			}
		}

		return array(
			'MYCOMPANY_ID' => $myCompanyId,
			'MC_REQUISITE_ID' => $mcRequisiteId,
			'MC_BANK_DETAIL_ID' => $mcBankDetailId
		);
	}

	public static function isMyCompany($companyId)
	{
		$result = false;

		if ($companyId <= 0)
		{
			return $result;
		}

		$companyId = (int)$companyId;

		$res = \CCrmCompany::GetListEx(
			[],
			[
				'=ID' => $companyId,
				'=IS_MY_COMPANY' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			['ID']
		);
		if (is_object($res) && $res->Fetch())
		{
			$result = true;
		}

		return $result;
	}

	public static function getDefaultEntityRequisiteLink($parentEntityList, $entityTypeId, $entityId, $requisiteId = 0, $isMyCompany = false)
	{
		$result = [
			'REQUISITE_ID' => 0,
			'BANK_DETAIL_ID' => 0
		];

		if ($requisiteId > 0)
		{
			$result['REQUISITE_ID'] = (int)$requisiteId;
		}

		$requisite = null;
		$bankDetail = null;

		if (!is_array($parentEntityList))
		{
			$parentEntityList = [];
		}

		$parentEntityList[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		foreach ($parentEntityList as $entityInfo)
		{
			$parentEntityTypeId = isset($entityInfo['ENTITY_TYPE_ID']) ? (int)$entityInfo['ENTITY_TYPE_ID'] : 0;
			if ($parentEntityTypeId < 0)
				$parentEntityTypeId = 0;
			$parentEntityId = isset($entityInfo['ENTITY_ID']) ? (int)$entityInfo['ENTITY_ID'] : 0;
			if ($parentEntityId < 0)
				$parentEntityId = 0;

			if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
			{
				if ($parentEntityTypeId === \CCrmOwnerType::Deal
					|| $parentEntityTypeId === \CCrmOwnerType::Quote
					|| $parentEntityTypeId === \CCrmOwnerType::Invoice)
				{
					$fieldMap = [
						0 => [
							'REQUISITE_ID' => 'REQUISITE_ID',
							'BANK_DETAIL_ID' => 'BANK_DETAIL_ID'
						],
						1 => [
							'REQUISITE_ID' => 'MC_REQUISITE_ID',
							'BANK_DETAIL_ID' => 'MC_BANK_DETAIL_ID'
						]
					];
					$fieldIndex = $isMyCompany ? 1 : 0;
					if ($row = self::getList(
						array(
							'filter' => array(
								'=ENTITY_TYPE_ID' => $parentEntityTypeId,
								'=ENTITY_ID' => $parentEntityId
							),
							'select' => [
								$fieldMap[$fieldIndex]['REQUISITE_ID'],
								$fieldMap[$fieldIndex]['BANK_DETAIL_ID']
							],
							'limit' => 1
						)
					)->fetch())
					{
						if (isset($row[$fieldMap[$fieldIndex]['REQUISITE_ID']])
							&& $row[$fieldMap[$fieldIndex]['REQUISITE_ID']] > 0
							&& $requisiteId <= 0)
						{
							$result['REQUISITE_ID'] = (int)$row[$fieldMap[$fieldIndex]['REQUISITE_ID']];
						}
						if ($result['REQUISITE_ID'] > 0
							&& isset($row[$fieldMap[$fieldIndex]['BANK_DETAIL_ID']])
							&& $row[$fieldMap[$fieldIndex]['BANK_DETAIL_ID']] > 0
							&& ($requisiteId <= 0 || $requisiteId === $result['REQUISITE_ID']))
						{
							$result['BANK_DETAIL_ID'] = (int)$row[$fieldMap[$fieldIndex]['BANK_DETAIL_ID']];
						}
					}
					unset($row);

					if ($result['REQUISITE_ID'] > 0)
					{
						try
						{
							if ($isMyCompany)
							{
								self::checkConsistence(
									\CCrmOwnerType::Undefined, 0,
									0, 0,
									$result['REQUISITE_ID'], $result['BANK_DETAIL_ID'],
									[
										'ENTITY_CHECK' => false,
										'CLIENT_SELLER_INFO' => [
											'CLIENT_ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
											'CLIENT_ENTITY_ID' => 0,
											'MYCOMPANY_ID' => $entityId
										]
									]
								);
							}
							else
							{
								self::checkConsistence(
									\CCrmOwnerType::Undefined, 0,
									$result['REQUISITE_ID'], $result['BANK_DETAIL_ID'],
									0, 0,
									[
										'ENTITY_CHECK' => false,
										'CLIENT_SELLER_INFO' => [
											'CLIENT_ENTITY_TYPE_ID' => $entityTypeId,
											'CLIENT_ENTITY_ID' => $entityId,
											'MYCOMPANY_ID' => 0
										]
									]
								);
							}
						}
						catch (Main\SystemException $e)
						{
							$resetBankDetail = false;
							switch ($e->getCode())
							{
								case self::ERR_INVALID_BANK_DETAIL_ID:
								case self::ERR_BANK_DETAIL_NOT_ASSIGNED_WO_REQUISITE:
								case self::ERR_BANK_DETAIL_NOT_FOUND:
								case self::ERR_BANK_DETAIL_NOT_ASSIGNED:
								case self::ERR_INVALID_MC_BANK_DETAIL_ID:
								case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED_WO_MC_REQUISITE:
								case self::ERR_MC_BANK_DETAIL_NOT_FOUND:
								case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED:
									$resetBankDetail = true;
							}
							if ($resetBankDetail)
							{
								$result['BANK_DETAIL_ID'] = 0;
								if ($bankDetail === null)
									$bankDetail = EntityBankDetail::getSingleInstance();
								$res = $bankDetail->getList(
									array(
										'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
										'filter' => array(
											'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
											'=ENTITY_ID' => $result['REQUISITE_ID']
										),
										'select' => array('ID'),
										'limit' => 1
									)
								);
								if ($row = $res->fetch())
									$result['BANK_DETAIL_ID'] = (int)$row['ID'];
								unset($resetBankDetail, $res, $row);

								break;
							}

							$result['REQUISITE_ID'] = 0;
							$result['BANK_DETAIL_ID'] = 0;

							continue;
						}

						if ($result['REQUISITE_ID'] === 0
							|| ($requisiteId > 0
								&& $requisiteId === $result['REQUISITE_ID']
								&& $result['BANK_DETAIL_ID'] === 0))
						{
							continue;
						}

						break;
					}
				}
				else if ($entityTypeId === \CCrmOwnerType::Company
					|| $entityTypeId === \CCrmOwnerType::Contact)
				{
					if ($requisite === null)
					{
						$requisite = EntityRequisite::getSingleInstance();
					}

					if ($result['REQUISITE_ID'] <= 0 || $result['BANK_DETAIL_ID'] <= 0)
					{
						$requisiteId = $result['REQUISITE_ID'];
						$bankDetailId = $result['BANK_DETAIL_ID'];
						$settings = $requisite->loadSettings($entityTypeId, $entityId);
						if (is_array($settings))
						{
							if ($result['REQUISITE_ID'] <= 0 && isset($settings['REQUISITE_ID_SELECTED']))
							{
								$requisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
								if ($requisiteId < 0)
									$requisiteId = 0;
							}
							if (isset($settings['BANK_DETAIL_ID_SELECTED'])
								&& ($result['REQUISITE_ID'] <= 0
									|| (isset($settings['REQUISITE_ID_SELECTED'])
										&& $result['REQUISITE_ID'] === (int)$settings['REQUISITE_ID_SELECTED'])))
							{
								$bankDetailId = (int)$settings['BANK_DETAIL_ID_SELECTED'];
								if ($bankDetailId < 0)
									$bankDetailId = 0;
							}
						}
						$result['REQUISITE_ID'] = $requisiteId;
						$result['BANK_DETAIL_ID'] = $bankDetailId;
						unset($requisiteId, $bankDetailId, $settings);
					}

					try
					{
						if ($isMyCompany)
						{
							self::checkConsistence(
								\CCrmOwnerType::Undefined, 0,
								0, 0,
								$result['REQUISITE_ID'], $result['BANK_DETAIL_ID'],
								[
									'ENTITY_CHECK' => false,
									'CLIENT_SELLER_INFO' => [
										'CLIENT_ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
										'CLIENT_ENTITY_ID' => 0,
										'MYCOMPANY_ID' => $entityId
									]
								]
							);
						}
						else
						{
							self::checkConsistence(
								\CCrmOwnerType::Undefined, 0,
								$result['REQUISITE_ID'], $result['BANK_DETAIL_ID'],
								0, 0,
								[
									'ENTITY_CHECK' => false,
									'CLIENT_SELLER_INFO' => [
										'CLIENT_ENTITY_TYPE_ID' => $entityTypeId,
										'CLIENT_ENTITY_ID' => $entityId,
										'MYCOMPANY_ID' => 0
									]
								]
							);
						}
					}
					catch (Main\SystemException $e)
					{
						$resetBankDetail = false;
						switch ($e->getCode())
						{
							case self::ERR_INVALID_BANK_DETAIL_ID:
							case self::ERR_BANK_DETAIL_NOT_ASSIGNED_WO_REQUISITE:
							case self::ERR_BANK_DETAIL_NOT_FOUND:
							case self::ERR_BANK_DETAIL_NOT_ASSIGNED:
							case self::ERR_INVALID_MC_BANK_DETAIL_ID:
							case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED_WO_MC_REQUISITE:
							case self::ERR_MC_BANK_DETAIL_NOT_FOUND:
							case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED:
								$resetBankDetail = true;
						}
						if ($resetBankDetail)
						{
							$result['BANK_DETAIL_ID'] = 0;
							if ($bankDetail === null)
								$bankDetail = EntityBankDetail::getSingleInstance();
							$res = $bankDetail->getList(
								array(
									'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
									'filter' => array(
										'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
										'=ENTITY_ID' => $result['REQUISITE_ID']
									),
									'select' => array('ID'),
									'limit' => 1
								)
							);
							if ($row = $res->fetch())
								$result['BANK_DETAIL_ID'] = (int)$row['ID'];
							unset($resetBankDetail, $res, $row);
						}
						else
						{
							$result['REQUISITE_ID'] = 0;
							$result['BANK_DETAIL_ID'] = 0;
						}
					}

					if ($result['REQUISITE_ID'] === 0)
					{
						if ($requisite === null)
						{
							$requisite = EntityRequisite::getSingleInstance();
						}
						$res = $requisite->getList(
							array(
								'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
								'filter' => array(
									'=ENTITY_TYPE_ID' => $entityTypeId,
									'=ENTITY_ID' => $entityId
								),
								'select' => array('ID'),
								'limit' => 1
							)
						);
						if ($row = $res->fetch())
							$result['REQUISITE_ID'] = (int)$row['ID'];
						unset($res, $row);
					}

					if ($result['REQUISITE_ID'] > 0)
					{
						if ($result['BANK_DETAIL_ID'] === 0)
						{
							if ($bankDetail === null)
								$bankDetail = EntityBankDetail::getSingleInstance();
							$res = $bankDetail->getList(
								array(
									'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
									'filter' => array(
										'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
										'=ENTITY_ID' => $result['REQUISITE_ID']
									),
									'select' => array('ID'),
									'limit' => 1
								)
							);
							if ($row = $res->fetch())
								$result['BANK_DETAIL_ID'] = (int)$row['ID'];
							unset($res, $row);
						}

						break;
					}
				}
			}
		}

		return $result;
	}

	public static function prepareParrentEntityMap($entityTypeId, $entityFields)
	{
		$result = [];

		$parentEntityFieldMap = self::getParentEntityFieldMap();
		if (is_array($parentEntityFieldMap[$entityTypeId] ?? null))
		{
			foreach ($parentEntityFieldMap[$entityTypeId] as $parentEntityTypeId => $fieldName)
			{
				if (isset($entityFields[$fieldName])
					&& $entityFields[$fieldName] !== ''
					&& $entityFields[$fieldName] > 0)
				{
					$result[$parentEntityTypeId] = (int)$entityFields[$fieldName];
				}
			}
		}

		return $result;
	}

	public static function prepareClientSellerParamsByEntityFields ($entityTypeId, $entityFields)
	{
		$result = [
			'CLIENT_ENTITY_TYPE_ID' => null,
			'CLIENT_ENTITY_ID' => null,
			'SELLER_ENTITY_TYPE_ID' => null,
			'SELLER_ENTITY_ID' => null,
		];

		if (is_array($entityFields))
		{
			if ($entityTypeId === \CCrmOwnerType::Invoice)
			{
				if (isset($entityFields['UF_COMPANY_ID']) && $entityFields['UF_COMPANY_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$result['CLIENT_ENTITY_ID'] = (int)$entityFields['UF_COMPANY_ID'];
				}
				else if (isset($entityFields['UF_CONTACT_ID']) && $entityFields['UF_CONTACT_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
					$result['CLIENT_ENTITY_ID'] = (int)$entityFields['UF_CONTACT_ID'];
				}
				if (isset($entityFields['UF_MYCOMPANY_ID']) && $entityFields['UF_MYCOMPANY_ID'] > 0)
				{
					$result['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$result['SELLER_ENTITY_ID'] = (int)$entityFields['UF_MYCOMPANY_ID'];
				}
			}
			else
			{
				if (isset($entityFields['COMPANY_ID']) && $entityFields['COMPANY_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$result['CLIENT_ENTITY_ID'] = (int)$entityFields['COMPANY_ID'];
				}
				else if (isset($entityFields['CONTACT_ID']) && $entityFields['CONTACT_ID'] > 0)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
					$result['CLIENT_ENTITY_ID'] = (int)$entityFields['CONTACT_ID'];
				}
				elseif (
					is_array($entityFields['CONTACT_IDS'] ?? null)
					&& !empty($entityFields['CONTACT_IDS'])
					&& $entityFields['CONTACT_IDS'][0] > 0
				)
				{
					$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
					$result['CLIENT_ENTITY_ID'] = (int)$entityFields['CONTACT_IDS'][0];
				}
				if (isset($entityFields['MYCOMPANY_ID']) && $entityFields['MYCOMPANY_ID'] > 0)
				{
					$result['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$result['SELLER_ENTITY_ID'] = (int)$entityFields['MYCOMPANY_ID'];
				}
			}
		}

		return $result;
	}

	public static function determineRequisiteLinkBeforeSave($entityTypeId, $entityId, $operation, &$entityFields,
		$modifyFields = true, $requisiteId = null, $bankDetailId = null, $mcRequisiteId = null, $mcBankDetailId = null)
	{
		$modifyFields = (bool)$modifyFields;
		$resultLink = [
			'CLIENT_ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'CLIENT_ENTITY_ID' => 0,
			'REQUISITE_ID' => 0,
			'BANK_DETAIL_ID' => 0,
			'SELLER_ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'SELLER_ENTITY_ID' => 0,
			'MC_REQUISITE_ID' => 0,
			'MC_BANK_DETAIL_ID' => 0
		];

		$availableEntityIds = static::getAvailableEntityTypeIds();
		if (!isset($availableEntityIds[$entityTypeId]))
		{
			return $resultLink;
		}

		$skipSeller = ($entityTypeId === \CCrmOwnerType::Deal/* || $entityTypeId === \CCrmOwnerType::Order*/);

		$entityId = (int)$entityId;

		$operation = (int)$operation;
		if ($operation !== self::ENTITY_OPERATION_ADD && $operation !== self::ENTITY_OPERATION_UPDATE)
		{
			return $resultLink;
		}

		$params = self::prepareClientSellerParamsByEntityFields($entityTypeId, $entityFields);
		$clientEntityTypeId = $params['CLIENT_ENTITY_TYPE_ID'] === null ? null : (int)$params['CLIENT_ENTITY_TYPE_ID'];
		$clientEntityId = $params['CLIENT_ENTITY_ID'] === null ? null : (int)$params['CLIENT_ENTITY_ID'];
		$requisiteId = $requisiteId === null ? null : (int)$requisiteId;
		$bankDetailId = $bankDetailId === null ? null : (int)$bankDetailId;
		$sellerEntityTypeId = $params['SELLER_ENTITY_TYPE_ID'] === null ? null : (int)$params['SELLER_ENTITY_TYPE_ID'];
		$sellerEntityId = $params['SELLER_ENTITY_ID'] === null ? null : (int)$params['SELLER_ENTITY_ID'];
		$mcRequisiteId = $mcRequisiteId === null ? null : (int)$mcRequisiteId;
		$mcBankDetailId = $mcBankDetailId === null ? null : (int)$mcBankDetailId;
		unset($params);

		$resetClientRequisite = false;
		$resetClientBankDetail = false;
		$resetSellerRequisite = false;
		$resetSellerBankDetail = false;

		$parentEntityMap = self::prepareParrentEntityMap($entityTypeId, $entityFields);

		$currentLink = $resultLink;
		$entityExists = ($operation === self::ENTITY_OPERATION_UPDATE && $entityId > 0);
		if ($entityExists)
		{
			try
			{
				$csInfo = self::getEntityClientSellerInfo($entityTypeId, $entityId, ['GET_PARENT_ENTITY_FILEDS' => true]);
			}
			catch (Main\SystemException $e)
			{
				switch ($e->getCode())
				{
					case self::ERR_ENTITY_NOT_FOUND:
						$entityExists = false;
						break;
				}
			}

			if (!$entityExists)
			{
				return $resultLink;
			}

			$currentParrentEntityMap = self::prepareParrentEntityMap($entityTypeId, $csInfo);
			foreach (array_keys($parentEntityMap) as $parentEntityTypeId)
			{
				$currentParrentEntityMap[$parentEntityTypeId] = $parentEntityMap[$parentEntityTypeId];
			}
			$parentEntityMap = $currentParrentEntityMap;
			unset($currentParrentEntityMap, $parentEntityTypeId);

			$currentLink['CLIENT_ENTITY_TYPE_ID'] = (int)$csInfo['CLIENT_ENTITY_TYPE_ID'];
			$currentLink['CLIENT_ENTITY_ID'] = (int)$csInfo['CLIENT_ENTITY_ID'];
			$currentLink['SELLER_ENTITY_TYPE_ID'] = $csInfo['MYCOMPANY_ID'] > 0 ?
				\CCrmOwnerType::Company : \CCrmOwnerType::Undefined;
			$currentLink['SELLER_ENTITY_ID'] = (int)$csInfo['MYCOMPANY_ID'];

			$isClientDefined = ($currentLink['CLIENT_ENTITY_TYPE_ID'] !== \CCrmOwnerType::Undefined
				&& $currentLink['CLIENT_ENTITY_ID'] > 0);
			$isSellerDefined = ($currentLink['SELLER_ENTITY_TYPE_ID'] === \CCrmOwnerType::Company
				&& $currentLink['SELLER_ENTITY_ID'] > 0);

			if ($isClientDefined || $isSellerDefined)
			{
				if ($row = self::getList(
					array(
						'filter' => array(
							'=ENTITY_TYPE_ID' => $entityTypeId,
							'=ENTITY_ID' => $entityId
						),
						'select' => array('REQUISITE_ID', 'BANK_DETAIL_ID', 'MC_REQUISITE_ID', 'MC_BANK_DETAIL_ID'),
						'limit' => 1
					)
				)->fetch())
				{
					if ($isClientDefined && isset($row['REQUISITE_ID']) && $row['REQUISITE_ID'] > 0)
					{
						$currentLink['REQUISITE_ID'] = (int)$row['REQUISITE_ID'];

						if (isset($row['BANK_DETAIL_ID']) && $row['BANK_DETAIL_ID'] > 0)
						{
							$currentLink['BANK_DETAIL_ID'] = (int)$row['BANK_DETAIL_ID'];
						}
					}
					if ($isSellerDefined && isset($row['MC_REQUISITE_ID']) && $row['MC_REQUISITE_ID'] > 0)
					{
						$currentLink['MC_REQUISITE_ID'] = (int)$row['MC_REQUISITE_ID'];

						if (isset($row['MC_BANK_DETAIL_ID']) && $row['MC_BANK_DETAIL_ID'] > 0)
						{
							$currentLink['MC_BANK_DETAIL_ID'] = (int)$row['MC_BANK_DETAIL_ID'];
						}
					}
				}
				unset($row);
			}
			unset($isClientDefined, $isSellerDefined);

			$resultLink = $currentLink;
		}

		$parentEntityList = [];
		foreach ($parentEntityMap as $parentEntityTypeId => $parentEntityId)
		{
			$parentEntityList[] = [
				'ENTITY_TYPE_ID' => $parentEntityTypeId,
				'ENTITY_ID' => $parentEntityId
			];
		}
		unset($parentEntityMap, $parentEntityTypeId, $parentEntityId);

		if (($clientEntityTypeId === \CCrmOwnerType::Company || $clientEntityTypeId === \CCrmOwnerType::Contact)
			&& $clientEntityId !== null && $clientEntityId >= 0)
		{
			$resultLink['CLIENT_ENTITY_TYPE_ID'] = $clientEntityTypeId;
			$resultLink['CLIENT_ENTITY_ID'] = $clientEntityId;

			if ($clientEntityTypeId !== $currentLink['CLIENT_ENTITY_TYPE_ID']
				|| $clientEntityId !== $currentLink['CLIENT_ENTITY_ID'])
			{
				$resultLink['REQUISITE_ID'] = 0;
				$resultLink['BANK_DETAIL_ID'] = 0;
			}
		}

		if ($resultLink['CLIENT_ENTITY_TYPE_ID'] === \CCrmOwnerType::Company && $resultLink['CLIENT_ENTITY_ID'] > 0)
		{
			if (self::isMyCompany($resultLink['CLIENT_ENTITY_ID']))
			{
				$resultLink['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
				$resultLink['CLIENT_ENTITY_ID'] = 0;
				$resultLink['REQUISITE_ID'] = 0;
				$resultLink['BANK_DETAIL_ID'] = 0;
				$resetClientRequisite = true;
				$resetClientBankDetail = true;
			}
		}

		if ($skipSeller)
		{
			$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
			$resultLink['SELLER_ENTITY_ID'] = 0;
			$resultLink['MC_REQUISITE_ID'] = 0;
			$resultLink['MC_BANK_DETAIL_ID'] = 0;
		}
		else
		{
			if ($sellerEntityTypeId === \CCrmOwnerType::Company && $sellerEntityId !== null && $sellerEntityId >= 0)
			{
				$resultLink['SELLER_ENTITY_TYPE_ID'] = $sellerEntityTypeId;
				$resultLink['SELLER_ENTITY_ID'] = $sellerEntityId;

				if ($sellerEntityTypeId !== $currentLink['SELLER_ENTITY_TYPE_ID']
					|| $sellerEntityId !== $currentLink['SELLER_ENTITY_ID'])
				{
					$resultLink['MC_REQUISITE_ID'] = 0;
					$resultLink['MC_BANK_DETAIL_ID'] = 0;
				}
			}
		}

		if ($resultLink['SELLER_ENTITY_TYPE_ID'] === \CCrmOwnerType::Company && $resultLink['SELLER_ENTITY_ID'] > 0)
		{
			if (!self::isMyCompany($resultLink['SELLER_ENTITY_ID']))
			{
				$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
				$resultLink['SELLER_ENTITY_ID'] = 0;
				$resultLink['MC_REQUISITE_ID'] = 0;
				$resultLink['MC_BANK_DETAIL_ID'] = 0;
				$resetSellerRequisite = true;
				$resetSellerBankDetail = true;
			}
		}

		$defaultMyCompanyId = null;

		$needBankDetailId = true;
		if ($resetClientRequisite || $requisiteId === null)
		{
			if ($resultLink['REQUISITE_ID'] <= 0)
			{
				$link = self::getDefaultEntityRequisiteLink(
					$parentEntityList,
					$resultLink['CLIENT_ENTITY_TYPE_ID'],
					$resultLink['CLIENT_ENTITY_ID']
				);
				$resultLink['REQUISITE_ID'] = $link['REQUISITE_ID'];
				if ($resetClientBankDetail || $bankDetailId === null)
				{
					$resultLink['BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
					$needBankDetailId = false;
				}
			}
		}
		else
		{
			$resultLink['REQUISITE_ID'] = $requisiteId;
		}
		if ($resetClientBankDetail || $bankDetailId === null)
		{
			if ($needBankDetailId && $resultLink['REQUISITE_ID'] > 0 && $resultLink['BANK_DETAIL_ID'] <= 0)
			{
				$link = self::getDefaultEntityRequisiteLink(
					$parentEntityList,
					$resultLink['CLIENT_ENTITY_TYPE_ID'],
					$resultLink['CLIENT_ENTITY_ID'],
					$resultLink['REQUISITE_ID']
				);
				$resultLink['REQUISITE_ID'] = $link['REQUISITE_ID'];
				$resultLink['BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
			}
		}
		else
		{
			$resultLink['BANK_DETAIL_ID'] = $bankDetailId;
		}
		if (!$skipSeller)
		{

			if (($resultLink['SELLER_ENTITY_TYPE_ID'] !== \CCrmOwnerType::Company
					|| $resultLink['SELLER_ENTITY_ID'] <= 0)
				&& $sellerEntityId !== 0)
			{
				if ($defaultMyCompanyId === null)
				{
					$defaultMyCompanyId = self::getDefaultMyCompanyId();
				}
				if ($defaultMyCompanyId > 0)
				{
					$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
					$resultLink['SELLER_ENTITY_ID'] = $defaultMyCompanyId;
					$resultLink['MC_REQUISITE_ID'] = 0;
					$resultLink['MC_BANK_DETAIL_ID'] = 0;
					$resetSellerRequisite = true;
					$resetSellerBankDetail = true;
				}
				else
				{
					$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
					$resultLink['SELLER_ENTITY_ID'] = 0;
					$resultLink['MC_REQUISITE_ID'] = 0;
					$resultLink['MC_BANK_DETAIL_ID'] = 0;
				}
			}

			$needBankDetailId = true;
			if ($resetSellerRequisite || $mcRequisiteId === null)
			{
				if ($resultLink['MC_REQUISITE_ID'] <= 0)
				{
					$link = self::getDefaultEntityRequisiteLink(
						$parentEntityList,
						$resultLink['SELLER_ENTITY_TYPE_ID'],
						$resultLink['SELLER_ENTITY_ID'],
						0,
						true
					);
					$resultLink['MC_REQUISITE_ID'] = $link['REQUISITE_ID'];
					if ($resetSellerBankDetail || $mcBankDetailId === null)
					{
						$resultLink['MC_BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
						$needBankDetailId = false;
					}
				}
			}
			else
			{
				$resultLink['MC_REQUISITE_ID'] = $mcRequisiteId;
			}
			if ($resetSellerBankDetail || $mcBankDetailId === null)
			{
				if ($needBankDetailId && $resultLink['MC_REQUISITE_ID'] > 0 && $resultLink['MC_BANK_DETAIL_ID'] <= 0)
				{
					$link = self::getDefaultEntityRequisiteLink(
						$parentEntityList,
						$resultLink['SELLER_ENTITY_TYPE_ID'],
						$resultLink['SELLER_ENTITY_ID'],
						$resultLink['MC_REQUISITE_ID'],
						true
					);
					$resultLink['MC_REQUISITE_ID'] = $link['REQUISITE_ID'];
					$resultLink['MC_BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
				}
			}
			else
			{
				$resultLink['MC_BANK_DETAIL_ID'] = $mcBankDetailId;
			}
		}

		$continueCheck = true;
		while ($continueCheck)
		{
			$continueCheck = false;
			try
			{
				self::checkConsistence(
					$entityTypeId, $entityId,
					$resultLink['REQUISITE_ID'], $resultLink['BANK_DETAIL_ID'],
					$resultLink['MC_REQUISITE_ID'], $resultLink['MC_BANK_DETAIL_ID'],
					[
						'ENTITY_CHECK' => $entityExists,
						'IS_MY_COMPANY_CHECK' => false,
						'CLIENT_SELLER_INFO' => [
							'CLIENT_ENTITY_TYPE_ID' => $resultLink['CLIENT_ENTITY_TYPE_ID'],
							'CLIENT_ENTITY_ID' => $resultLink['CLIENT_ENTITY_ID'],
							'MYCOMPANY_ID' => $resultLink['SELLER_ENTITY_ID']
						]
					]
				);
			}
			catch (Main\SystemException $e)
			{
				$continueCheck = true;
				switch ($e->getCode())
				{
					case self::ERR_ENTITY_NOT_FOUND:
					case self::ERR_INVALID_ENTITY_TYPE:
					case self::ERR_INVALID_ENTITY_ID:
						$resultLink['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
						$resultLink['CLIENT_ENTITY_ID'] = 0;
						$resultLink['REQUISITE_ID'] = 0;
						$resultLink['BANK_DETAIL_ID'] = 0;
						$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
						$resultLink['SELLER_ENTITY_ID'] = 0;
						$resultLink['MC_REQUISITE_ID'] = 0;
						$resultLink['MC_BANK_DETAIL_ID'] = 0;
						$continueCheck = false;
						break;
					case self::ERR_REQUISITE_TIED_TO_ENTITY_WITHOUT_CLIENT:
						$resultLink['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
						$resultLink['CLIENT_ENTITY_ID'] = 0;
						$resultLink['REQUISITE_ID'] = 0;
						$resultLink['BANK_DETAIL_ID'] = 0;
						break;
					case self::ERR_INVALID_REQUSIITE_ID:
					case self::ERR_REQUISITE_NOT_FOUND:
					case self::ERR_REQUISITE_NOT_ASSIGNED:
						$link = self::getDefaultEntityRequisiteLink(
							$parentEntityList,
							$resultLink['CLIENT_ENTITY_TYPE_ID'],
							$resultLink['CLIENT_ENTITY_ID']
						);
						$resultLink['REQUISITE_ID'] = $link['REQUISITE_ID'];
						$resultLink['BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
						unset($link);
						break;
					case self::ERR_INVALID_BANK_DETAIL_ID:
					case self::ERR_BANK_DETAIL_NOT_ASSIGNED_WO_REQUISITE:
					case self::ERR_BANK_DETAIL_NOT_FOUND:
					case self::ERR_BANK_DETAIL_NOT_ASSIGNED:
						if ($requisiteId === 0)
						{
							$resultLink['BANK_DETAIL_ID'] = 0;
						}
						else
						{
							$link = self::getDefaultEntityRequisiteLink(
								$parentEntityList,
								$resultLink['CLIENT_ENTITY_TYPE_ID'],
								$resultLink['CLIENT_ENTITY_ID'],
								$resultLink['REQUISITE_ID']
							);
							$resultLink['REQUISITE_ID'] = $link['REQUISITE_ID'];
							$resultLink['BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
						}
						break;
					case self::ERR_MC_REQUISITE_TIED_TO_ENTITY_WITHOUT_MYCOMPANY:
						if ($defaultMyCompanyId === null)
						{
							$defaultMyCompanyId = self::getDefaultMyCompanyId();
						}
						if ($defaultMyCompanyId > 0)
						{
							$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
							$resultLink['SELLER_ENTITY_ID'] = $defaultMyCompanyId;

							if ($resultLink['SELLER_ENTITY_TYPE_ID'] !== $currentLink['SELLER_ENTITY_TYPE_ID']
								|| $resultLink['SELLER_ENTITY_ID'] !== $currentLink['SELLER_ENTITY_ID'])
							{
								$resultLink['MC_REQUISITE_ID'] = 0;
								$resultLink['MC_BANK_DETAIL_ID'] = 0;
							}
						}
						else
						{
							$resultLink['SELLER_ENTITY_TYPE_ID'] = \CCrmOwnerType::Undefined;
							$resultLink['SELLER_ENTITY_ID'] = 0;
							$resultLink['MC_REQUISITE_ID'] = 0;
							$resultLink['MC_BANK_DETAIL_ID'] = 0;
						}
						unset($defaultMyCompanyId);
						break;
					case self::ERR_INVALID_MC_REQUSIITE_ID:
					case self::ERR_MC_REQUISITE_NOT_FOUND:
					case self::ERR_MC_REQUISITE_NOT_ASSIGNED:
						$link = self::getDefaultEntityRequisiteLink(
							$parentEntityList,
							$resultLink['SELLER_ENTITY_TYPE_ID'],
							$resultLink['SELLER_ENTITY_ID'],
							0,
							true
						);
						$resultLink['MC_REQUISITE_ID'] = $link['REQUISITE_ID'];
						$resultLink['MC_BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
						unset($link);
						break;
					case self::ERR_INVALID_MC_BANK_DETAIL_ID:
					case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED_WO_MC_REQUISITE:
					case self::ERR_MC_BANK_DETAIL_NOT_FOUND:
					case self::ERR_MC_BANK_DETAIL_NOT_ASSIGNED:
						if ($mcRequisiteId === 0)
						{
							$resultLink['MC_BANK_DETAIL_ID'] = 0;
						}
						else
						{
							$link = self::getDefaultEntityRequisiteLink(
								$parentEntityList,
								$resultLink['SELLER_ENTITY_TYPE_ID'],
								$resultLink['SELLER_ENTITY_ID'],
								$resultLink['MC_REQUISITE_ID'],
								true
							);
							$resultLink['MC_REQUISITE_ID'] = $link['REQUISITE_ID'];
							$resultLink['MC_BANK_DETAIL_ID'] = $link['BANK_DETAIL_ID'];
							unset($link);
						}
						break;
					default:
						throw $e;
				}
			}
		}

		if ($modifyFields && is_array($entityFields))
		{
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Quote:
					$entityFields['MYCOMPANY_ID'] = $resultLink['SELLER_ENTITY_ID'];
					break;
				case \CCrmOwnerType::Invoice:
					$entityFields['UF_MYCOMPANY_ID'] = $resultLink['SELLER_ENTITY_ID'];
					break;
			}
		}

		return $resultLink;
	}

	public static function clearMyCompanyCache(): void
	{
		Cache::clearCache(true, static::CACHE_PATH_MY_COMPNAY);
	}

	/**
	 * @return int ID of default seller company.
	 */
	public static function getDefaultMyCompanyId()
	{
		$optionValue = Main\Config\Option::get('crm', 'def_mycompany_id', null);
		$isOptionSet = ($optionValue !== null);
		$myCompanyId = $isOptionSet ? (int)$optionValue : 0;

		if ($myCompanyId > 0)
		{
			$cache = Cache::createInstance();
			if (
				$cache->initCache(
					static::CACHE_TTL_MY_COMPANY,
					static::CACHE_ID_MY_COMPANY_BY_ID,
					static::CACHE_PATH_MY_COMPNAY
				)
			)
			{
				$cacheVars = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$res = \CCrmCompany::GetListEx(
					array(),
					array('ID' => $myCompanyId, 'IS_MY_COMPANY' => 'Y', 'CHECK_PERMISSIONS' => 'N'),
					false,
					array('nTopCount' => 1),
					array('ID')
				);
				$cacheVars = (is_object($res) && ($row = $res->Fetch()) && is_array($row)) ? [$myCompanyId => 'Y'] : [];
				$cache->endDataCache($cacheVars);
			}

			if (!isset($cacheVars[$myCompanyId]))
			{
				$myCompanyId = 0;
			}
		}

		if ($myCompanyId <= 0)
		{
			$myCompanyId = 0;

			$cache = Cache::createInstance();
			if (
				$cache->initCache(
					static::CACHE_TTL_MY_COMPANY,
					static::CACHE_ID_MY_COMPANY_FIRST,
					static::CACHE_PATH_MY_COMPNAY
				)
			)
			{
				$cacheVars = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$res = \CCrmCompany::GetListEx(
					array('ID' => 'ASC'),
					array('IS_MY_COMPANY' => 'Y', 'CHECK_PERMISSIONS' => 'N'),
					false,
					array('nTopCount' => 1),
					array('ID')
				);
				$cacheVars = [
					'myCompanyId' =>
						(($row = $res->Fetch()) && is_array($row) && isset($row['ID']))
							? (int)$row['ID']
							: 0
				];
				$cache->endDataCache($cacheVars);
			}

			if (isset($cacheVars['myCompanyId']))
			{
				$myCompanyId = $cacheVars['myCompanyId'];
			}

			if (!$isOptionSet || $myCompanyId > 0)
			{
				self::setDefaultMyCompanyId($myCompanyId);
			}
		}

		return $myCompanyId;
	}

	public static function setDefaultMyCompanyId($defMyCompanyId)
	{
		Main\Config\Option::set('crm', 'def_mycompany_id', (int)$defMyCompanyId);
	}

	public static function getAvailableEntityTypeIds(): array
	{
		$types = [
			\CCrmOwnerType::Deal => \CCrmOwnerType::Deal,
			\CCrmOwnerType::Invoice => \CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote => \CCrmOwnerType::Quote,
		];
		$dynamicTypesMap = Service\Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => false,
			'isLoadStages' => false,
		]);

		foreach ($dynamicTypesMap->getTypesCollection() as $type)
		{
			$types[$type->getEntityTypeId()] = $type->getEntityTypeId();
		}

		return $types;
	}

	/**
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @return bool
	 */
	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		$userPermissions = Service\Container::getInstance()->getUserPermissions();

		if(intval($entityTypeID) <= 0 && intval($entityID) <= 0)
		{
			foreach (static::getAvailableEntityTypeIds() as $entityTypeId)
			{
				if ($userPermissions->canReadType($entityTypeId))
				{
					return true;
				}
			}

			return false;
		}

		return $userPermissions->checkReadPermissions($entityTypeID, $entityID);
	}

	/**
	 * @param int $entityTypeID
	 * @param int $entityID
	 * @return bool
	 */
	public static function checkUpdatePermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		return Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions((int)$entityTypeID, (int)$entityID);
	}

	public static function moveDependencies(
		$targEntityTypeId = 0, $targEntityId = 0,
		$seedEntityTypeId = 0, $seedEntityId = 0,
		$targRequisiteId = 0, $seedRequisiteId = 0,
		$targBankDetailId = 0, $seedBankDetailId = 0
	)
	{
		$targRequisiteId = (int)$targRequisiteId;
		$targBankDetailId = (int)$targBankDetailId;
		$seedRequisiteId = (int)$seedRequisiteId;
		$seedBankDetailId = (int)$seedBankDetailId;

		if (!(\CCrmOwnerType::IsDefined($targEntityTypeId) && \CCrmOwnerType::IsDefined($seedEntityTypeId)
			&& $targEntityId > 0 && $seedEntityId > 0 && $targRequisiteId > 0 && $seedRequisiteId > 0))
		{
			return false;
		}

		if ($targBankDetailId > 0 && $seedBankDetailId > 0)
		{
			LinkTable::updateDependencies(
				array('REQUISITE_ID' => $targRequisiteId, 'BANK_DETAIL_ID' => $targBankDetailId),
				array('REQUISITE_ID' => $seedRequisiteId, 'BANK_DETAIL_ID' => $seedBankDetailId)
			);
			LinkTable::updateDependencies(
				array('MC_REQUISITE_ID' => $targRequisiteId, 'MC_BANK_DETAIL_ID' => $targBankDetailId),
				array('MC_REQUISITE_ID' => $seedRequisiteId, 'MC_BANK_DETAIL_ID' => $seedBankDetailId)
			);
		}
		else
		{
			LinkTable::updateDependencies(
				array('REQUISITE_ID' => $targRequisiteId),
				array('REQUISITE_ID' => $seedRequisiteId)
			);
			LinkTable::updateDependencies(
				array('MC_REQUISITE_ID' => $targRequisiteId),
				array('MC_REQUISITE_ID' => $seedRequisiteId)
			);
		}

		static::clearByEntityCache();

		$event = new Main\Event(
			'crm',
			'OnAfterRequisiteLinkMoveDependencies',
			array(
				'targEntityTypeId' => $targEntityTypeId,
				'targEntityId' => $targEntityId,
				'seedEntityTypeId' => $seedEntityTypeId,
				'seedEntityId' => $seedEntityId,
				'targRequisiteId' => $targRequisiteId,
				'targBankDetailId' => $targBankDetailId,
				'seedRequisiteId' => $seedRequisiteId,
				'seedBankDetailId' => $seedBankDetailId
			)
		);
		$event->send();

		return true;
	}

	public static function copyRequisiteLink(Item $source, Item $destination): void
	{
		$companyId = null;
		if ($source->hasField(Item::FIELD_NAME_COMPANY_ID))
		{
			$companyId = $source->getCompanyId();
		}

		$contactId = null;
		if ($source->hasField(Item::FIELD_NAME_CONTACTS))
		{
			$contactId = $source->getPrimaryContact() ? $source->getPrimaryContact()->getId() : null;
		}

		$requisiteBindings = EntityRequisite::getSingleInstance()->getEntityRequisiteBindings(
			$source->getEntityTypeId(),
			$source->getId(),
			$companyId,
			$contactId,
		);
		$requisiteId = isset($requisiteBindings['REQUISITE_ID']) ? (int)$requisiteBindings['REQUISITE_ID'] : 0;
		$bankDetailId = isset($requisiteBindings['BANK_DETAIL_ID']) ? (int)$requisiteBindings['BANK_DETAIL_ID'] : 0;

		[$mcRequisiteId, $mcBankDetailId] = self::getMcRequisiteLink($source);

		if ($requisiteId > 0 || $mcRequisiteId > 0)
		{
			self::register(
				$destination->getEntityTypeId(),
				$destination->getId(),
				$requisiteId,
				$bankDetailId,
				$mcRequisiteId,
				$mcBankDetailId,
			);
		}
	}

	/**
	 * @param Item $item
	 * @return int[] [MC_REQUISITE_ID, MC_BANK_DETAIL_ID]
	 */
	private static function getMcRequisiteLink(Item $item): array
	{
		if (!$item->hasField(Item::FIELD_NAME_MYCOMPANY_ID))
		{
			return [
				0,
				0,
			];
		}

		if ($item->getMycompanyId() > 0)
		{
			$mcRequisiteBindings = EntityRequisite::getSingleInstance()->getDefaultMyCompanyEntityRequisiteBindings(
				$item->getEntityTypeId(),
				$item->getId(),
				$item->getMycompanyId(),
			);
		}
		else
		{
			$mcRequisiteBindings = self::getDefaultMyCompanyRequisiteLink();
		}

		$mcRequisiteId = isset($mcRequisiteBindings['MC_REQUISITE_ID']) ? (int)$mcRequisiteBindings['MC_REQUISITE_ID'] : 0;
		$mcBankDetailId = isset($mcRequisiteBindings['MC_BANK_DETAIL_ID']) ? (int)$mcRequisiteBindings['MC_BANK_DETAIL_ID'] : 0;

		return [
			$mcRequisiteId,
			$mcBankDetailId,
		];
	}

	private static function clientSellerInfoByFactory(
		Service\Factory $factory,
		int $entityId,
		array $result,
		bool $getParentEntityFields,
		array $parentFields,
		bool $entityNotFound
	): array
	{
		$item = $factory->getItem($entityId);
		if ($item)
		{
			$companyId = $item->getCompanyId();
			$contactId = $item->getContactId();
			if ($companyId > 0)
			{
				$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
				$result['CLIENT_ENTITY_ID'] = $companyId;
			}
			elseif ($contactId > 0)
			{
				$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
				$result['CLIENT_ENTITY_ID'] = $contactId;
			}

			if ($item->hasField(Crm\Item::FIELD_NAME_MYCOMPANY_ID))
			{
				$myCompanyId = $item->getMycompanyId();
				if ($myCompanyId > 0)
				{
					$result['MYCOMPANY_ID'] = $myCompanyId;
				}
			}

			if ($getParentEntityFields)
			{
				foreach ($parentFields as $fieldName)
				{
					if ($item->hasField($fieldName) && $item->get($fieldName) > 0)
					{
						$result[$fieldName] = $item->get($fieldName);
					}
				}
			}
		}
		else
		{
			$entityNotFound = true;
		}
		return array($result, $entityNotFound);
	}

	private static function clientSellerInfoByOrm(
		Service\Factory $factory,
		int $entityId,
		array $result,
		bool $getParentEntityFields,
		array $parentFields,
		bool $entityNotFound
	): array
	{
		$select = [
			Item::FIELD_NAME_ID,
		];

		$companyIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_COMPANY_ID);
		if ($factory->isFieldExists($companyIdFieldName))
		{
			$select[] = $companyIdFieldName;
		}

		$contactIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CONTACT_ID);
		if ($factory->isFieldExists($contactIdFieldName))
		{
			$select[] = $contactIdFieldName;
		}

		$myCompanyFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_MYCOMPANY_ID);
		if ($factory->isFieldExists($myCompanyFieldName))
		{
			$select[] = $myCompanyFieldName;
		}

		foreach ($parentFields as $parentField)
		{
			if ($factory->isFieldExists($parentField))
			{
				$select[] = $parentField;
			}
		}

		$item = $factory->getDataClass()::query()
			->setSelect($select)
			->where('ID', $entityId)
			->fetch();

		if ($item)
		{
			$companyId = $item[$companyIdFieldName] ?? 0;
			$contactId = $item[$contactIdFieldName] ?? 0;
			if ($companyId > 0)
			{
				$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;
				$result['CLIENT_ENTITY_ID'] = $companyId;
			}
			elseif ($contactId > 0)
			{
				$result['CLIENT_ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
				$result['CLIENT_ENTITY_ID'] = $contactId;
			}

			$myCompanyId = $item[$myCompanyFieldName] ?? 0;
			if ($myCompanyId > 0)
			{
				$result['MYCOMPANY_ID'] = $myCompanyId;
			}

			if ($getParentEntityFields) {
				foreach ($parentFields as $fieldName) {
					if (isset($item[$fieldName]) && $item[$fieldName] > 0)
					{
						$result[$fieldName] = $item[$fieldName];
					}
				}
			}
		}
		else {
			$entityNotFound = true;
		}

		return array($result, $entityNotFound);
	}
}
