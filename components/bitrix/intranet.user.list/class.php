<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Component\UserList;
use Bitrix\Main\Loader;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Filter;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Main\PhoneNumber;

Loc::loadMessages(__FILE__);

Loader::includeModule('intranet');

class CIntranetUserListComponent extends UserList
{
	protected $gridId = 'INTRANET_USER_GRID_'.SITE_ID;
	protected $filterId = 'INTRANET_USER_LIST_'.SITE_ID;
	protected $explicitFieldsList = [
		'ID',
		'FULL_NAME',
		'PERSONAL_PHOTO',
		'UF_DEPARTMENT',
		'UF_DEPARTMENT_FLAT'
	];
	protected $availableEntityFields = [];

	private function extranetSite()
	{
		static $result = null;

		if ($result === null)
		{
			$result = (
				Loader::includeModule('extranet')
				&& \CExtranet::isExtranetSite()
			);
		}

		return $result;
	}

	private function getGridHeaders()
	{
		static $result = null;
		if ($result === null)
		{
			$result = [];

			$columns = [
				[
					'id' => 'ID',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_ID'),
					'sort' => 'ID',
//					'default' => true,
					'editable' => false
				],
				[
					'id' => 'PHOTO',
					'fieldId' => 'PERSONAL_PHOTO',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PHOTO'),
					'sort' => false,
					'default' => true,
					'editable' => false
				],
				[
					'id' => 'FULL_NAME',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_FULL_NAME'),
					'sort' => 'FULL_NAME',
					'default' => true,
					'editable' => false
				],
				[
					'id' => 'NAME',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_NAME'),
					'sort' => 'NAME',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'LAST_NAME',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_LAST_NAME'),
					'sort' => 'LAST_NAME',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'SECOND_NAME',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_SECOND_NAME'),
					'sort' => 'SECOND_NAME',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'LOGIN',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_LOGIN'),
					'sort' => 'LOGIN',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'EMAIL',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_EMAIL'),
					'sort' => 'EMAIL',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'DATE_REGISTER',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_DATE_REGISTER'),
					'sort' => 'DATE_REGISTER',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'LAST_ACTIVITY_DATE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_LAST_ACTIVITY_DATE'),
					'sort' => 'LAST_ACTIVITY_DATE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'BIRTHDAY',
					'fieldId' => 'PERSONAL_BIRTHDAY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_BIRTHDAY'),
//					'sort' => 'BIRTHDAY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WWW',
					'fieldId' => 'PERSONAL_WWW',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WWW_MSGVER_1'),
					'sort' => 'WWW',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'GENDER',
					'fieldId' => 'PERSONAL_GENDER',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_GENDER'),
					'sort' => 'GENDER',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PHONE_MOBILE',
					'fieldId' => 'PERSONAL_MOBILE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PHONE_MOBILE'),
					'sort' => 'PHONE_MOBILE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PHONE',
					'fieldId' => 'PERSONAL_PHONE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PHONE'),
					'sort' => 'PHONE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_CITY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_CITY'),
					'sort' => 'PERSONAL_CITY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_STREET',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_STREET'),
					'sort' => 'PERSONAL_STREET',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_STATE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_STATE'),
					'sort' => 'PERSONAL_STATE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_ZIP',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_ZIP'),
					'sort' => 'PERSONAL_ZIP',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_MAILBOX',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_MAILBOX'),
					'sort' => 'PERSONAL_MAILBOX',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'PERSONAL_COUNTRY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_COUNTRY'),
					'sort' => 'PERSONAL_COUNTRY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_CITY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_CITY'),
					'sort' => 'WORK_CITY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_STREET',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_STREET'),
					'sort' => 'WORK_STREET',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_STATE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_STATE'),
					'sort' => 'WORK_STATE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_ZIP',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_ZIP'),
					'sort' => 'WORK_ZIP',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_MAILBOX',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_MAILBOX'),
					'sort' => 'WORK_MAILBOX',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_COUNTRY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_COUNTRY'),
					'sort' => 'WORK_COUNTRY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_PHONE',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_PHONE'),
					'sort' => 'WORK_PHONE',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'POSITION',
					'fieldId' => 'WORK_POSITION',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_POSITION'),
					'sort' => 'POSITION',
					'default' => true,
					'editable' => false
				],
				[
					'id' => 'COMPANY',
					'fieldId' => 'WORK_COMPANY',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_COMPANY'),
					'sort' => 'COMPANY',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'WORK_DEPARTMENT',
					'fieldId' => 'WORK_DEPARTMENT',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_DEPARTMENT'),
					'sort' => 'WORK_DEPARTMENT',
					'default' => false,
					'editable' => false
				],
				[
					'id' => 'DEPARTMENT',
					'fieldId' => 'UF_DEPARTMENT',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_DEPARTMENT'),
					'sort' => false,
					'default' => true,
					'editable' => false
				],
				[
					'id' => 'TAGS',
					'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_TAGS'),
					'sort' => false,
					'default' => false,
					'editable' => false
				],
			];

			$gridUFManager = new \Bitrix\Main\Grid\Uf\User();
			$gridUFManager->addUFHeaders($columns);

			foreach ($columns as $column)
			{
				$fieldId = (isset($column['fieldId']) ? $column['fieldId'] : $column['id']);
				if (
					in_array($fieldId, $this->explicitFieldsList)
					|| in_array($fieldId, $this->arParams['USER_PROPERTY_LIST'])
				)
				{
					$result[] = $column;
				}
			}

			$defaultSelectedGridHeaders = $this->getDefaultGridSelectedHeaders();

			foreach ($result as $key => $column)
			{
				if (
					!($column['default'] ?? null)
					&& in_array($column['id'], $defaultSelectedGridHeaders)
				)
				{
					$result[$key]['default'] = true;
				}
			}
		}

		return $result;
	}

	private function getDefaultGridHeaders()
	{
		$result = [];
		$gridHeaders = $this->getGridHeaders();
		foreach ($gridHeaders as $header)
		{
			if (!empty($header['default']))
			{
				$result[] = $header['id'];
			}
		}

		return $result;
	}

	private function getDefaultGridSelectedHeaders()
	{
		$result = [
			'EMAIL',
			'PERSONAL_MOBILE',
			'UF_SKYPE',
			'WORK_PHONE',
			'PERSONAL_PHOTO',
			'FULL_NAME',
		];

		if (
			Loader::includeModule('extranet')
			&& \CExtranet::isExtranetSite()
		)
		{
			$result[] = 'WORK_COMPANY';
		}
		else
		{
			$result[] = 'UF_PHONE_INNER';
			$result[] = 'UF_DEPARTMENT';
		}

		return $result;
	}

	private function getFilterFields(\Bitrix\Main\Filter\Filter $entityFilter, array $usedFields = [])
	{
		$result = [];

		$fields = $entityFilter->getFields();

		foreach ($fields as $fieldName => $field)
		{
			if (!in_array($fieldName, $usedFields))
			{
				continue;
			}

			$result[] = $field->toArray();
		}

		return $result;
	}

	private function getFilterPresets(\Bitrix\Main\Filter\Filter $entityFilter)
	{
		$result = [];
		$fields = $entityFilter->getFields();

		$defaultFilterIds = $entityFilter->getDefaultFieldIDs();
		$defaultFieldsValues = [];
		foreach ($defaultFilterIds as $fieldId)
		{
			switch ($fields[$fieldId])
			{
				case 'dest_selector':
					$value = false;
					break;
				default:
					$value = '';
			}
			$defaultFieldsValues[$fieldId] = $value;
		}

		if (!Filter\UserDataProvider::extranetSite())
		{
			$result['company'] = [
				'name' => Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_COMPANY'),
				'fields' => array_merge($defaultFieldsValues, [
					'FIRED' => 'N'
				]),
				'default' => true
			];
		}
		else
		{
			$result['employees'] = [
				'name' => Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_EMPLOYEES'),
				'fields' => array_merge($defaultFieldsValues, [
					'EXTRANET' => 'N'
				]),
				'default' => true
			];
		}

		if (Filter\UserDataProvider::getExtranetAvailability())
		{
			$result['extranet'] = [
				'name' => Loc::getMessage(!Filter\UserDataProvider::extranetSite() ? 'INTRANET_USER_LIST_FILTER_PRESET_EXTRANET' : 'INTRANET_USER_LIST_FILTER_PRESET_CONTACTS'),
				'fields' => array_merge($defaultFieldsValues, [
					'EXTRANET' => 'Y',
					'FIRED' => 'N'
				])
			];
		}

		if (Filter\UserDataProvider::getInvitedAvailability())
		{
			$result['invited'] = [
				'name' => Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_INVITED'),
				'fields' => array_merge($defaultFieldsValues, [
					'INVITED' => 'Y',
					'FIRED' => 'N'
				])
			];
		}

		if (Filter\UserDataProvider::getFiredAvailability())
		{
			$result['fired'] = [
				'name' => Loc::getMessage('INTRANET_USER_LIST_FILTER_PRESET_FIRED'),
				'fields' => array_merge($defaultFieldsValues, [
					'FIRED' => 'Y'
				])
			];
		}

		return $result;
	}

	private function getOrder(\Bitrix\Main\Grid\Options $gridOptions)
	{
		$result = [];
		$gridSort = $gridOptions->getSorting();

		if (!empty($gridSort['sort']))
		{
			foreach ($gridSort['sort'] as $by => $order)
			{
				switch($by)
				{
					case 'FULL_NAME':
						$by = 'LAST_NAME';
						break;
					case 'BIRTHDAY':
						$by = 'PERSONAL_BIRTHDAY';
						break;
					case 'GENDER':
						$by = 'PERSONAL_GENDER';
						break;
					case 'WWW':
						$by = 'PERSONAL_WWW';
						break;
					case 'PHONE_MOBILE':
						$by = 'PERSONAL_MOBILE';
						break;
					case 'PHONE':
						$by = 'PERSONAL_PHONE';
						break;
					case 'POSITION':
						$by = 'WORK_POSITION';
						break;
					case 'COMPANY':
						$by = 'WORK_COMPANY';
						break;
					default:
				}
				$result[$by] = mb_strtoupper($order);
			}
		}
		else
		{
			$result = [
				'LAST_NAME' => 'ASC',
				'LOGIN' => 'ASC'
			];
		}

		return $result;
	}

	private function initAvailableEntityFields()
	{
		$entityFields = \Bitrix\Intranet\UserTable::getEntity()->getFields();
		foreach ($entityFields as $entityField)
		{
			$this->availableEntityFields[] = $entityField->getName();
		}
	}

	private function checkQueryFieldName($fieldName = '')
	{
		$fieldName = trim($fieldName, '!=<>%*');
		return (
			in_array($fieldName, $this->availableEntityFields)
			|| mb_strpos($fieldName, '.') !== false
		);
	}


	private function addQueryOrder(\Bitrix\Main\Entity\Query $query, \Bitrix\Main\Grid\Options $gridOptions)
	{
		$orderFields = $this->getOrder($gridOptions);
		foreach ($orderFields as $fieldName => $value)
		{
			if (!$this->checkQueryFieldName($fieldName))
			{
				continue;
			}
			$query->addOrder($fieldName, $value);
		}
	}

	private function addQueryFilter(\Bitrix\Main\Entity\Query $query, array $gridFilter)
	{
		global $USER;

		$filter = $this->getFilter($gridFilter);

		$departmentId = $flatDepartmentId = false;

		foreach ($filter as $fieldName => $value)
		{
			if ($fieldName === '=UF_DEPARTMENT')
			{
				$departmentId = (int)$filter[$fieldName];
				unset($filter[$fieldName]);
				continue;
			}
			elseif ($fieldName === '=UF_DEPARTMENT_FLAT')
			{
				$query->addFilter('=UF_DEPARTMENT', $value);
				unset($filter[$fieldName]);
				continue;
			}

			if (!$this->checkQueryFieldName($fieldName))
			{
				continue;
			}

			$query->addFilter($fieldName, $value);
		}

		if (
			Loader::includeModule('extranet')
			&& !\CExtranet::isExtranetAdmin()
			&& (
				(
					isset($filter['UF_DEPARTMENT'])
					&& $filter['UF_DEPARTMENT'] == false
				)
				|| !\CExtranet::isIntranetUser()
				|| !isset($filter['UF_DEPARTMENT'])
			)
			&& (
				!isset($filter['!UF_DEPARTMENT'])
				|| $filter['!UF_DEPARTMENT'] !== false
				|| !\CExtranet::isIntranetUser()
			)
			&& Loader::includeModule('socialnetwork')
		)
		{
			$workgroupIdList = [];
			$res = UserToGroupTable::getList([
				'filter' => [
					'=USER_ID' => $USER->getId(),
					'@ROLE' => UserToGroupTable::getRolesMember(),
					'=GROUP.ACTIVE' => 'Y'
				],
				'select' => [ 'GROUP_ID' ]
			]);

			while ($userToGroupFields = $res->fetch())
			{
				$workgroupIdList[] = $userToGroupFields['GROUP_ID'];
			}
			$workgroupIdList = array_unique($workgroupIdList);

			if (
				!isset($filter['UF_DEPARTMENT'])
				&& \CExtranet::isIntranetUser()
			)
			{
				if (!empty($workgroupIdList))
				{
					$subQuery = new \Bitrix\Main\Entity\Query(UserToGroupTable::getEntity());
					$subQuery->addSelect('USER_ID');
					$subQuery->addFilter('=ROLE', UserToGroupTable::ROLE_USER);
					$subQuery->addFilter('@GROUP_ID', $workgroupIdList);
					$subQuery->addGroup('USER_ID');

					$query->addFilter(null, [
						'LOGIC' => 'OR',
						[
							'!UF_DEPARTMENT' => false
						],
						[
							'@ID' => new Bitrix\Main\DB\SqlExpression($subQuery->getQuery())
						],
					]);
				}
				else
				{
					$query->addFilter('!UF_DEPARTMENT', false);
				}
			}
			else
			{
				$res = \Bitrix\Main\UserTable::getList([
					'filter' => [
						'!UF_DEPARTMENT' => false,
						'=UF_PUBLIC' => true,
					],
					'select' => [ 'ID' ]
				]);

				$publicUserIdList = [];
				while($userFields = $res->fetch())
				{
					$publicUserIdList[] = (int)$userFields['ID'];
				}

				if (
					empty($workgroupIdList)
					&& empty($publicUserIdList)
				)
				{
					return false;
				}

				if (!empty($workgroupIdList))
				{
					$query->registerRuntimeField(
						'',
						new \Bitrix\Main\Entity\ReferenceField(
							'UG',
							\Bitrix\Socialnetwork\UserToGroupTable::getEntity(),
							array(
								'=ref.USER_ID' => 'this.ID',
							),
							array('join_type' => 'INNER')
						)
					);

					if (!empty($publicUserIdList))
					{
						$query->addFilter(null, [
							'LOGIC' => 'OR',
							[
								'<=UG.ROLE' => UserToGroupTable::ROLE_USER,
								'@UG.GROUP_ID' => $workgroupIdList
							],
							[
								'@ID' => $publicUserIdList
							],
						]);
					}
					else
					{
						$query->addFilter('<=UG.ROLE', UserToGroupTable::ROLE_USER);
						$query->addFilter('@UG.GROUP_ID', $workgroupIdList);
					}
				}
				else
				{
					$query->addFilter('@ID', $publicUserIdList);
				}
			}
		}

		if ($departmentId)
		{
			$iblockId = (int) Option::get('intranet', 'iblock_structure', 0);
			if (
				$iblockId > 0
				&& Loader::includeModule('iblock')
			)
			{
				$section = \Bitrix\Iblock\SectionTable::getList([
					'filter' => [
						'=IBLOCK_ID' => $iblockId,
						'=ID' => $departmentId
					],
					'select' => [ 'LEFT_MARGIN', 'RIGHT_MARGIN' ],
					'limit' => 1
				])->fetch();

				if ($section)
				{
					$query->registerRuntimeField(
						'',
						new \Bitrix\Main\Entity\ReferenceField(
							'DEPARTMENT',
							\Bitrix\Iblock\SectionTable::getEntity(),
							array(
								'=ref.ID' => 'this.UF_DEPARTMENT_SINGLE',
							),
							array('join_type' => 'INNER')
						)
					);
					$query->addFilter('=DEPARTMENT.IBLOCK_ID', $iblockId);
					$query->addFilter('>=DEPARTMENT.LEFT_MARGIN', $section['LEFT_MARGIN']);
					$query->addFilter('<=DEPARTMENT.RIGHT_MARGIN', $section['RIGHT_MARGIN']);
				}
			}
		}

		return true;
	}

	private function addQuerySelect(\Bitrix\Main\Entity\Query $query, \Bitrix\Main\Grid\Options $gridOptions)
	{
		$selectFields = $this->getSelect($gridOptions);
		foreach ($selectFields as $fieldName)
		{
			if (!$this->checkQueryFieldName($fieldName))
			{
				continue;
			}

			$query->addSelect($fieldName);
		}
	}

	private function addFilterInteger(&$filter, array $params = [])
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$value = ($params['VALUE'] ?? '');

		if (
			$filterFieldName == ''
			|| (int)$value <= 0
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);
		$operation = ($params['OPERATION'] ?? '=');

		if (
			in_array($fieldName, $this->arParams['USER_PROPERTY_LIST'])
			|| in_array($fieldName, $this->explicitFieldsList)
		)
		{
			$filter[$operation.$fieldName] = $value;
		}
	}

	private function addFilterString(&$filter, array $params = [])
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$value = ($params['VALUE'] ?? '');

		if (
			$filterFieldName == ''
			|| trim($value, '%') == ''
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);
		$operation = ($params['OPERATION'] ?? '%=');

		if (in_array($fieldName, $this->arParams['USER_PROPERTY_LIST']))
		{
			$filter[$operation.$fieldName] = $value;
		}
	}

	private function addFilterDateTime(&$filter, array $params = [])
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$valueFrom = ($params['VALUE_FROM'] ?? '');
		$valueTo = ($params['VALUE_TO'] ?? '');

		if (
			$filterFieldName == ''
			|| (
				$valueFrom == ''
				&& $valueTo == ''
			)
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);

		if (in_array($fieldName, $this->arParams['USER_PROPERTY_LIST']))
		{
			if ($valueFrom <> '')
			{
				$filter['>='.$fieldName] = $valueFrom;
			}
			if ($valueTo <> '')
			{
				$filter['<='.$fieldName] = $valueTo;
			}
		}
	}

	/**
	 * Get filter for getList.
	 * @param array $gridFilter
	 * @return array|boolean
	 */
	private function getFilter(array $gridFilter)
	{
		global $USER_FIELD_MANAGER;

		$result = [
			'IS_REAL_USER' => 'Y'
		];

		if (!Filter\UserDataProvider::getFiredAvailability())
		{
			$result['=ACTIVE'] = 'Y';
		}
		elseif (!empty($gridFilter['FIRED']))
		{
			if ($gridFilter['FIRED'] === 'Y')
			{
				$result['=ACTIVE'] = 'N';
			}
			elseif ($gridFilter['FIRED'] === 'N')
			{
				$result['=ACTIVE'] = 'Y';
			}
		}

		if (
			!empty($gridFilter['EXTRANET'])
			&& Filter\UserDataProvider::getExtranetAvailability()
		)
		{
			if ($gridFilter['EXTRANET'] === 'Y')
			{
				$result['UF_DEPARTMENT'] = false;
				if (
					Loader::includeModule('extranet')
					&& ($extranetGroupId = \CExtranet::getExtranetUserGroupId())
				)
				{
					$result['=GROUPS.GROUP_ID'] = $extranetGroupId;
				}
			}
			elseif ($gridFilter['EXTRANET'] === 'N')
			{
				$result['!UF_DEPARTMENT'] = false;
			}
		}
		elseif (isset($gridFilter['PRESET_ID']) && in_array($gridFilter['PRESET_ID'], ['company', 'employees']))
		{
			$result['!UF_DEPARTMENT'] = false;
		}

		if (
			!empty($gridFilter['INVITED'])
			&& Filter\UserDataProvider::getInvitedAvailability()
		)
		{
			if ($gridFilter['INVITED'] === 'Y')
			{
				$result['!CONFIRM_CODE'] = false;
			}
			else
			{
				$result['CONFIRM_CODE'] = false;
			}
		}
		elseif (
			(isset($gridFilter['PRESET_ID'])
				&& $gridFilter['PRESET_ID'] === 'company')
			|| !Filter\UserDataProvider::getInvitedAvailability()
		)
		{
			$result['CONFIRM_CODE'] = false;
		}

		if (
			!empty($gridFilter['INTEGRATOR'])
			&& $gridFilter['INTEGRATOR'] === 'Y'
			&& method_exists('Bitrix\Main\Filter\UserDataProvider', 'getIntegratorAvailability')
			&& Filter\UserDataProvider::getIntegratorAvailability()
			&& Loader::includeModule('bitrix24')
		)
		{
			$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
			if ($integratorGroupId)
			{
				$result['=GROUPS.GROUP_ID'] = $integratorGroupId;
			}
		}

		if (
			!empty($gridFilter['ADMIN'])
			&& $gridFilter['ADMIN'] === 'Y'
			&& method_exists('Bitrix\Main\Filter\UserDataProvider', 'getAdminAvailability')
			&& Filter\UserDataProvider::getAdminAvailability()
		)
		{
			$result['=GROUPS.GROUP_ID'] = 1;
			if (Loader::includeModule('bitrix24'))
			{
				$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
				if ($integratorGroupId)
				{
					$result['!=GROUPS.GROUP_ID'] = $integratorGroupId;
				}
			}
		}

		if (
			!empty($gridFilter['IS_ONLINE'])
			&& in_array($gridFilter['IS_ONLINE'], [ 'Y', 'N' ])
		)
		{
			$result['IS_ONLINE'] = (
				$gridFilter['IS_ONLINE'] === 'Y'
					? 'Y'
					: 'N'
			);
		}

		if (isset($gridFilter['TAGS']))
		{
			$tagsSearchValue = trim($gridFilter['TAGS']);
			if ($tagsSearchValue <> '')
			{
				$result['%=TAGS.NAME'] = $tagsSearchValue.'%';
			}
		}
		$gridFilter['DEPARTMENT'] ??= null;
		$integerFieldsList = [
			[
				'FILTER_FIELD_NAME' => 'ID',
				'FIELD_NAME' => 'ID',
				'OPERATION' => '=',
				'VALUE' => $gridFilter['ID'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_COUNTRY',
				'FIELD_NAME' => 'PERSONAL_COUNTRY',
				'OPERATION' => '@',
				'VALUE' => $gridFilter['PERSONAL_COUNTRY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_COUNTRY',
				'FIELD_NAME' => 'WORK_COUNTRY',
				'OPERATION' => '@',
				'VALUE' => $gridFilter['WORK_COUNTRY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'DEPARTMENT',
				'FIELD_NAME' => 'UF_DEPARTMENT',
				'OPERATION' => '=',
				'VALUE' => (
					$gridFilter['DEPARTMENT']
					&& preg_match('/^DR(\d+)$/', $gridFilter['DEPARTMENT'], $matches)
						? $matches[1]
						: ($gridFilter['DEPARTMENT'] && (int)($gridFilter['DEPARTMENT'] > 0) ? $gridFilter['DEPARTMENT'] : false)
				)
			],
			[
				'FILTER_FIELD_NAME' => 'DEPARTMENT',
				'FIELD_NAME' => 'UF_DEPARTMENT_FLAT',
				'OPERATION' => '=',
				'VALUE' => (
					$gridFilter['DEPARTMENT'] && preg_match('/^D(\d+)$/', $gridFilter['DEPARTMENT'], $matches)
						? $matches[1]
						: false
				)
			]
		];

		$stringFieldsList = [
			[
				'FILTER_FIELD_NAME' => 'NAME',
				'FIELD_NAME' => 'NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'LAST_NAME',
				'FIELD_NAME' => 'LAST_NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['LAST_NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'SECOND_NAME',
				'FIELD_NAME' => 'SECOND_NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['SECOND_NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'LOGIN',
				'FIELD_NAME' => 'LOGIN',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['LOGIN'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'EMAIL',
				'FIELD_NAME' => 'EMAIL',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['EMAIL'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'GENDER',
				'FIELD_NAME' => 'PERSONAL_GENDER',
				'OPERATION' => '=',
				'VALUE' => $gridFilter['GENDER'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PHONE_MOBILE',
				'FIELD_NAME' => 'PERSONAL_MOBILE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PHONE_MOBILE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PHONE',
				'FIELD_NAME' => 'PERSONAL_PHONE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PHONE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_CITY',
				'FIELD_NAME' => 'PERSONAL_CITY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_CITY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_STREET',
				'FIELD_NAME' => 'PERSONAL_STREET',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_STREET'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_STATE',
				'FIELD_NAME' => 'PERSONAL_STATE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_STATE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_ZIP',
				'FIELD_NAME' => 'PERSONAL_ZIP',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_ZIP'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_MAILBOX',
				'FIELD_NAME' => 'PERSONAL_MAILBOX',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_MAILBOX'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_CITY',
				'FIELD_NAME' => 'WORK_CITY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_CITY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_STREET',
				'FIELD_NAME' => 'WORK_STREET',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_STREET'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_STATE',
				'FIELD_NAME' => 'WORK_STATE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_STATE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_ZIP',
				'FIELD_NAME' => 'WORK_ZIP',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_ZIP'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_MAILBOX',
				'FIELD_NAME' => 'WORK_MAILBOX',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_MAILBOX'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_PHONE',
				'FIELD_NAME' => 'WORK_PHONE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_PHONE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'POSITION',
				'FIELD_NAME' => 'WORK_POSITION',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['POSITION'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'COMPANY',
				'FIELD_NAME' => 'WORK_COMPANY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['COMPANY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_DEPARTMENT',
				'FIELD_NAME' => 'WORK_DEPARTMENT',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_DEPARTMENT'] ?? null
			],
		];

		$dateFieldsList = [
			[
				'FILTER_FIELD_NAME' => 'DATE_REGISTER',
				'FIELD_NAME' => 'DATE_REGISTER',
				'VALUE_FROM' => ($gridFilter['DATE_REGISTER_from'] ?? false),
				'VALUE_TO' => ($gridFilter['DATE_REGISTER_to'] ?? false)
			],
			[
				'FILTER_FIELD_NAME' => 'LAST_ACTIVITY_DATE',
				'FIELD_NAME' => 'LAST_ACTIVITY_DATE',
				'VALUE_FROM' => ($gridFilter['LAST_ACTIVITY_DATE_from'] ?? false),
				'VALUE_TO' => ($gridFilter['LAST_ACTIVITY_DATE_to'] ?? false)
			],
			[
				'FILTER_FIELD_NAME' => 'BIRTHDAY',
				'FIELD_NAME' => 'PERSONAL_BIRTHDAY',
				'VALUE_FROM' => ($gridFilter['BIRTHDAY_from'] ?? false),
				'VALUE_TO' => ($gridFilter['BIRTHDAY_to'] ?? false)
			]
		];

		foreach ($integerFieldsList as $field)
		{
			$value = false;

			if (
				is_array($field['VALUE'])
				&& !empty($field['VALUE'])
			)
			{
				$value = $field['VALUE'];
			}
			elseif (
				!is_array($field['VALUE'])
				&& $field['VALUE'] <> ''
			)
			{
				$value = (int)$field['VALUE'];
			}

			if ($value !== false)
			{
				$this->addFilterInteger($result, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => ($field['OPERATION'] ?? '='),
					'VALUE' => $value
				]);
			}
		}

		foreach ($stringFieldsList as $field)
		{
			if ($field['VALUE'] <> '')
			{
				$this->addFilterString($result, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => ($field['OPERATION'] ?? '%='),
					'VALUE' => $field['VALUE']
				]);
			}
		}

		foreach ($dateFieldsList as $field)
		{
			if (
				!empty($field['VALUE_FROM'])
				|| !empty($field['VALUE_TO'])
			)
			{
				$this->addFilterDateTime($result, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'VALUE_FROM' => ($field['VALUE_FROM'] ?? $gridFilter[$field['FILTER_FIELD_NAME']]),
					'VALUE_TO' => ($field['VALUE_TO'] ?? $gridFilter[$field['FILTER_FIELD_NAME']])
				]);
			}
		}

		$ufList = $USER_FIELD_MANAGER->getUserFields(\Bitrix\Main\UserTable::getUfId(), 0, LANGUAGE_ID, false);
		$ufCodesList = array_keys($ufList);

		foreach ($gridFilter as $key => $value)
		{
			if (
				preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $key, $match)
				&& in_array($match[1], $ufCodesList)
			)
			{
				Filter\Range::prepareFrom($result, $match[1], $value);
			}
			elseif (
				preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $key, $match)
				&& in_array($match[1], $ufCodesList)
			)
			{
				Filter\Range::prepareTo($result, $match[1], $value);
			}
			elseif (!in_array($key, $ufCodesList))
			{
				continue;
			}
			elseif (
				!empty($ufList[$key])
				&& !empty($ufList[$key]['SHOW_FILTER'])
				&& !empty($ufList[$key]['USER_TYPE_ID'])
				&& $ufList[$key]['USER_TYPE_ID'] === 'string'
				&& $ufList[$key]['SHOW_FILTER'] === 'E'
			)
			{
				$result[$key] = $value.'%';
			}
			else
			{
				$result[$key] = $value;
			}
		}

		if (
			isset($gridFilter['FIND'])
			&& $gridFilter['FIND']
		)
		{
			$matchesPhones = [];
			$phoneParserManager = PhoneNumber\Parser::getInstance();
			preg_match_all('/'.$phoneParserManager->getValidNumberPattern().'/i', $gridFilter['FIND'], $matchesPhones);

			if (
				!empty($matchesPhones)
				&& !empty($matchesPhones[0])
			)
			{
				foreach ($matchesPhones[0] as $phone)
				{
					$convertedPhone = PhoneNumber\Parser::getInstance()
						->parse($phone)
						->format(PhoneNumber\Format::E164);
					$gridFilter['FIND'] = str_replace($phone, $convertedPhone, $gridFilter['FIND']);
				}
			}

			$findFilter = \Bitrix\Main\UserUtils::getAdminSearchFilter([
				'FIND' => $gridFilter['FIND']
			]);
			if (!empty($findFilter))
			{
				$result = array_merge($result, $findFilter);
			}
		}

		return $result;
	}

	protected function getSelect(\Bitrix\Main\Grid\Options $gridOptions)
	{
		$result = [ 'ID', 'ACTIVE', 'IS_ONLINE', 'CONFIRM_CODE', 'USER_TYPE' ];
		$gridColumns = $gridOptions->getVisibleColumns();

		if (empty($gridColumns))
		{
			$gridColumns = $this->getDefaultGridHeaders();
		}

		foreach ($gridColumns as $column)
		{
			switch($column)
			{
				case 'EMAIL':
					$result[] = 'EMAIL';
					break;
				case 'FULL_NAME':
					$result[] = 'NAME';
					$result[] = 'LAST_NAME';
					$result[] = 'SECOND_NAME';
					$result[] = 'LOGIN';
					break;
				case 'PHOTO':
					$result[] = 'PERSONAL_PHOTO';
					break;
				case 'WWW':
					$result[] = 'PERSONAL_WWW';
					break;
				case 'BIRTHDAY':
					$result[] = 'PERSONAL_BIRTHDAY';
					$result[] = 'PERSONAL_GENDER';
					break;
				case 'GENDER':
					$result[] = 'PERSONAL_GENDER';
					break;
				case 'PHONE_MOBILE':
					$result[] = 'PERSONAL_MOBILE';
					break;
				case 'PHONE':
					$result[] = 'PERSONAL_PHONE';
					break;
				case 'POSITION':
					$result[] = 'WORK_POSITION';
					break;
				case 'COMPANY':
					$result[] = 'WORK_COMPANY';
					break;
				case 'DEPARTMENT':
					$result[] = 'UF_DEPARTMENT';
					break;
				case 'TAGS':
					// don't delete this empty block
					break;
				default:
					$result[] = $column;
			}
		}

		return $result;
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		if (empty($params['PATH_TO_DEPARTMENT']))
		{
			$params['PATH_TO_DEPARTMENT'] = SITE_DIR.'company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#';
		}
		if (empty($params['PATH_TO_USER']))
		{
			$params['PATH_TO_USER'] = Option::get('intranet', 'search_user_url', SITE_DIR.'company/personal/user/#ID#/');
		}

		if (!empty($params['LIST_URL']))
		{
			$oldValue = Option::get('intranet', 'list_user_url', '', SITE_ID);
			if ($params['LIST_URL'] != $oldValue)
			{
				Option::set('intranet', 'list_user_url', $params['LIST_URL'], SITE_ID);
			}
		}

		if (
			!isset($params['EXPORT_MODE'])
			|| $params['EXPORT_MODE'] !== 'Y'
		)
		{
			$params['EXPORT_MODE'] = 'N';
		}
		else
		{
			if (
				!isset($params['EXPORT_TYPE'])
				|| !in_array($params['EXPORT_TYPE'], ['excel'])
			)
			{
				$params['EXPORT_TYPE'] = 'excel';
			}
		}

		if (
			!isset($params['USER_PROPERTY_LIST'])
			|| !is_array($params['USER_PROPERTY_LIST'])
		)
		{
			$params['USER_PROPERTY_LIST'] = $this->getUserPropertyList();
		}
		elseif ($params['EXPORT_MODE'] !== 'Y')
		{
			$this->setUserPropertyList($params['USER_PROPERTY_LIST']);
		}

		return $params;
	}

	protected function prepareData()
	{
		$result = [];

		$this->initAvailableEntityFields();

		$entityFilter = Filter\Factory::createEntityFilter(
			\Bitrix\Main\UserTable::getUfId(),
			[
				'ID' => $this->filterId,
				'WHITE_LIST' => $this->arParams['USER_PROPERTY_LIST']
			]
		);

		$result['EXCEL_EXPORT_LIMITED'] = (
			Loader::includeModule('bitrix24')
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('intranet_user_export_excel')
		);

		if (
			$result['EXCEL_EXPORT_LIMITED']
			&& $this->arParams['EXPORT_TYPE'] === 'excel'
		)
		{
			$this->arParams['EXPORT_MODE'] = 'N';
		}

		$result['EXTRANET_SITE'] = ($this->extranetSite() ? 'Y' : 'N');
		$result['GRID_ID'] = $this->gridId;
		$result['FILTER_ID'] = $this->filterId;
		$result['FILTER_PRESETS'] = $this->getFilterPresets($entityFilter);

		$gridOptions = new Bitrix\Main\Grid\Options($result['GRID_ID']);
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($result['FILTER_ID'], $result['FILTER_PRESETS']);

		$usedFields = $filterOptions->getUsedFields();
		$usedFields = array_filter(
			$usedFields,
			function ($value)
			{
				return !empty($value);
			}
		);

		if (empty($usedFields))
		{
			$usedFields = $entityFilter->getDefaultFieldIDs();
		}

		$navParams = $gridOptions->getNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation('page');

		if ($this->arParams['EXPORT_MODE'] === 'Y')
		{
			$nav->allowAllRecords(true)->setPageSize(0);
		}
		else
		{
			$nav->allowAllRecords(false)
				->setPageSize($pageSize)
				->initFromUri();
		}

		$result['HEADERS'] = $this->getGridHeaders();
		//TODO: replace to new \Bitrix\Main\Filter\Filter::getFieldArrays after main will be stable
		$result['FILTER'] = $this->getFilterFields($entityFilter, $usedFields);
		$result['ROWS'] = [];

		$gridFilter = $filterOptions->getFilter($result['FILTER']);
		$gridFilter['EXTRANET'] = $gridFilter['EXTRANET'] ?? null;
		$gridFilter['DEPARTMENT'] = $gridFilter['DEPARTMENT'] ?? null;
		$gridFilter['PRESET_ID'] = $gridFilter['PRESET_ID'] ?? null;

		$result['PROCESS_EXTRANET'] = (
			in_array($gridFilter['EXTRANET'], ['Y', 'N'])
			|| !empty($gridFilter['DEPARTMENT'])
			|| in_array($gridFilter['PRESET_ID'], ['company', 'employees'])
				? 'N'
				: 'Y'
		);

		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Intranet\UserTable::getEntity());
		$this->addQueryOrder($query, $gridOptions);
		if (!$this->addQueryFilter($query, $gridFilter))
		{
			return $result;
		}
		$this->addQuerySelect($query, $gridOptions);
		$query->countTotal(true);
		$query->setOffset($nav->getOffset());
		$query->setLimit($nav->getLimit());
		$query->disableDataDoubling();

		$res = $query->exec();
		$users = $res->fetchCollection();

		$gridColumns = $gridOptions->getVisibleColumns();
		if (empty($gridColumns))
		{
			$gridColumns = $this->getDefaultGridHeaders();
		}

		if (in_array('TAGS', $gridColumns))
		{
			$users->fillTags();
		}

		$rowsList = [];

		foreach ($users as $user)
		{
			$row['USER_FIELDS'] = $user;

			$row['CAN_EDIT'] = $row['CAN_DELETE'] = false;

			$rowsList[] = [
				'id' => $user['ID'],
				'data' => $row,
				'columns' => [],
				'editable' => true,
				'actions' => \Bitrix\Intranet\Component\UserList::getActions([
					'USER_FIELDS' => $user,
					'PATH_TO_USER' => $this->arParams['PATH_TO_USER']
				]),
				'columnClasses' => (
					$user['USER_TYPE'] === 'extranet'
						? [
						'FULL_NAME' => 'intranet-user-list-full-name-extranet'
					]
					: []
				)
			];
		}

		$result['ROWS'] = $rowsList;
		$result['ROWS_COUNT'] = $res->getCount();

		$nav->setRecordCount($result['ROWS_COUNT']);
		$result['NAV_OBJECT'] = $nav;

		$result['GRID_COLUMNS'] = $gridColumns;

		return $result;
	}

	public function executeComponent()
	{
		$this->arResult = $this->prepareData();

		if ($this->arParams['EXPORT_MODE'] !== 'Y')
		{
			$this->includeComponentTemplate();
		}
		else
		{
			while(ob_get_clean());

			header('HTTP/1.1 200 OK');
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Expires: 0');
			header('Pragma: public');

			$this->IncludeComponentTemplate($this->arParams['EXPORT_TYPE']);

			die();
		}

	}
}
