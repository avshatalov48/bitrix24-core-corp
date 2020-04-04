<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;
use Bitrix\Crm\Security\EntityAuthorization;

abstract class EntityBase
{
	abstract public function getEntityTypeID();

	abstract protected function getDbEntity();
	abstract protected function buildPermissionSql(array $params);

	abstract public function checkReadPermission($entityID = 0, $userPermissions = null);
	abstract public function checkDeletePermission($entityID = 0, $userPermissions = null);

	abstract public function getTopIDs(array $params);
	abstract public function getCount(array $params);
	abstract public function delete($entityID, array $options = array());
	public function cleanup($entityID)
	{
		throw new Main\NotImplementedException('Method cleanup must be overridden');
	}

	public function getLastID($userID = 0, $enablePermissionCheck = true)
	{
		if($userID <= 0)
		{
			$userID = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userID);

		if($enablePermissionCheck && EntityAuthorization::isAdmin($userID))
		{
			$enablePermissionCheck = false;
		}

		$query = new Main\Entity\Query($this->getDbEntity());
		if(!$enablePermissionCheck)
		{
			$query->addSelect('ID');
			$query->addOrder('ID', 'DESC');
			$query->setLimit(1);
		}
		else
		{
			$permissionSql = $this->buildPermissionSql(
				array(
					'alias' => 'L',
					'permissionType' => 'READ',
					'options' => array(
						'PERMS' => $userPermissions,
						'RAW_QUERY' => array('TOP' => 1, 'SORT_TYPE' => 'DESC')
					)
				)
			);

			$query->addSelect('ID');
			if(!is_string($permissionSql))
			{
				return 0;
			}
			elseif($permissionSql === '')
			{
				$query->addOrder('ID', 'DESC');
				$query->setLimit(1);
			}
			else
			{
				$permissionEntity = Main\Entity\Base::compileEntity(
					'user_perms',
					array('ENTITY_ID' => array('data_type' => 'integer')),
					array('table_name' => "({$permissionSql})")
				);
				$query->registerRuntimeField('',
					new Main\Entity\ReferenceField('PERMS',
						$permissionEntity,
						array('=this.ID' => 'ref.ENTITY_ID'),
						array('join_type' => 'INNER')
					)
				);
			}
		}

		$dbResult = $query->exec();
		$field = $dbResult->fetch();
		return is_array($field) ? (int)$field['ID'] : 0;
	}
	public function getNewIDs($offsetID, $order = 'DESC', $limit = 100, $userID = 0, $enablePermissionCheck = true)
	{
		if($userID <= 0)
		{
			$userID = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userID);

		if($enablePermissionCheck && EntityAuthorization::isAdmin($userID))
		{
			$enablePermissionCheck = false;
		}

		$query = new Main\Entity\Query($this->getDbEntity());
		$query->addSelect('ID');

		if($offsetID > 0)
		{
			$query->addFilter('>ID', $offsetID);
		}
		$query->addOrder('ID', $order);

		$query->setLimit($limit);

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$ID = (int)$fields['ID'];
			if($enablePermissionCheck && !$this->checkReadPermission($ID, $userPermissions))
			{
				continue;
			}

			$results[] = $ID;
		}
		return $results;
	}

	public function getEntityMultifields($entityID, array $options = null)
	{
		if($entityID <= 0)
		{
			return array();
		}

		$dbResult = \CCrmFieldMulti::GetListEx(
			array('ID' => 'asc'),
			array(
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($this->getEntityTypeID()),
				'=ELEMENT_ID' => $entityID
			)
		);

		if($options === null)
		{
			$options = array();
		}

		$skipEmpty = isset($options['skipEmpty']) && $options['skipEmpty'];

		$entityMultiFields = array();
		while($fields = $dbResult->Fetch())
		{
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			if ($skipEmpty && $value === '')
			{
				continue;
			}

			$typeID = $fields['TYPE_ID'];
			if(!isset($this->entityMutliFields[$typeID]))
			{
				$entityMultiFields[$typeID] = array();
			}

			$entityMultiFields[$typeID][] = array(
				'ID' => $fields['ID'],
				'VALUE' => $value,
				'VALUE_TYPE' => isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '',
				'COMPLEX_ID' => isset($fields['COMPLEX_ID']) ? $fields['COMPLEX_ID'] : ''
			);
		}

		return $entityMultiFields;
	}
}