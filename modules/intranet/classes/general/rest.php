<?php
if(!CModule::IncludeModule('rest'))
	return;

use Bitrix\Intranet\Repository;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\Exceptions\ObjectNotFoundException;
use Bitrix\Rest\RestException;

class CIntranetRestService extends IRestService
{
	const CONTACT_CENTER_PLACEMENT = 'CONTACT_CENTER';
	public const PAGE_BACKGROUND_WORKER_PLACEMENT = 'PAGE_BACKGROUND_WORKER';
	public const LEFT_MENU = 'LEFT_MENU';

	protected static $arAllowedDepartmentFields = array(
		"ID", "NAME", "SORT", "PARENT", "UF_HEAD"
	);
	protected static $arSelectDepartmentFields = array(
		"ID", "NAME", "SORT", "IBLOCK_SECTION_ID", "UF_HEAD"
	);

	private static bool $checkAccess = true;

	public static function OnRestServiceBuildDescription()
	{
		$result = array(
			'department' => array(
				'department.fields' => array('CIntranetRestService', 'departmentFields'),
				'department.get' => array('CIntranetRestService', 'departmentGet'),
				'department.add' => array('CIntranetRestService', 'departmentAdd'),
				'department.update' => array('CIntranetRestService', 'departmentUpdate'),
				'department.delete' => array('CIntranetRestService', 'departmentDelete'),
			),
			'contact_center' => array(
				\CRestUtil::PLACEMENTS => array(
					self::CONTACT_CENTER_PLACEMENT => array()
				),
			),
			\CRestUtil::GLOBAL_SCOPE => [
				\CRestUtil::PLACEMENTS => [
					self::PAGE_BACKGROUND_WORKER_PLACEMENT => [
						'max_count' => 1,
						'user_mode' => true,
						'options' => [
							'errorHandlerUrl' => 'string',
						],
						'registerCallback' => [
							'moduleId' => 'intranet',
							'callback' => [
								'CIntranetRestService',
								'onRegisterPlacementPageBackground',
							],
						],
					],
					self::LEFT_MENU => [],
				],
			],
		);

		$placementMap = \Bitrix\Intranet\Binding\Menu::getRestMap();
		foreach ($placementMap as $scope => $placementList)
		{
			if (!empty($result[$scope][\CRestUtil::PLACEMENTS]))
			{
				$result[$scope][\CRestUtil::PLACEMENTS] = array_merge($placementList, $result[$scope][\CRestUtil::PLACEMENTS]);
			}
			else
			{
				$result[$scope][\CRestUtil::PLACEMENTS] = $placementList;
			}
		}

		return $result;
	}

	/**
	 * Check params on register INTRANET_PAGE_BACKGROUND.
	 * @param array $placementBind
	 * @param array $placementInfo
	 * @return array
	 */
	public static function onRegisterPlacementPageBackground(array $placementBind, array $placementInfo): array
	{
		if (empty($placementBind['OPTIONS']['errorHandlerUrl']))
		{
			$placementBind = [
				'error' => 'EMPTY_ERROR_HANDLER_URL',
				'error_description' => 'Field errorHandlerUrl is empty.',
			];
		}

		return $placementBind;
	}

	public static function departmentFields($arParams)
	{
		IncludeModuleLangFile(__FILE__);

		$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('IBLOCK_'.self::getDeptIblock().'_SECTION', 0, LANGUAGE_ID);

		$res = array(
			"ID" => "ID",
			"NAME" => GetMessage('INTR_STRUCTURE_NAME'),
			'SORT' => GetMessage('INTR_STRUCTURE_SORT'),
			'PARENT' => GetMessage('INTR_STRUCTURE_DEPARTMENT'),
			'UF_HEAD' => $arFields['UF_HEAD']['EDIT_FORM_LABEL'],
		);

		return $res;
	}

	public static function departmentGet($arQuery, $nav = 0)
	{
		CModule::IncludeModule('iblock');

		if(isset($arQuery['FILTER']) && is_array($arQuery['FILTER']))
		{
			$arQuery = $arQuery['FILTER'];
		}

		$dbRes = CIBlockSection::GetList(
			self::prepareSort($arQuery),
			self::prepareDeptData($arQuery),
			false,
			self::$arSelectDepartmentFields,
			self::getNavData($nav)
		);

		$result = array();
		$arFiles = array();
		while($arDept = $dbRes->NavNext(false))
		{
			$result[] = self::getDeptData($arDept);
		}

		return self::setNavData($result, $dbRes);
	}

	public static function departmentAdd($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			/** @var Repository\HrDepartmentRepository | Repository\IblockDepartmentRepository $departmentRepository */
			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository();
			$sort = (int)($params['SORT'] ?? null);
			$department = new \Bitrix\Intranet\Entity\Department(
				$params['NAME'] ?? '',
				parentId: $params['PARENT'] ?? null,
				sort: $sort > 0 ? $sort : null,
			);
			$conn = Application::getConnection();
			$conn->startTransaction();
			try
			{
				$department = $departmentRepository->save($department);
				if (isset($params['UF_HEAD']) && (int)$params['UF_HEAD'] > 0)
				{
					$departmentRepository->setHead($department->getId(), (int)$params['UF_HEAD']);
				}
				$conn->commitTransaction();

				return $department->getId();
			}
			catch (SqlQueryException)
			{
				$conn->rollbackTransaction();
				throw new RestException('Internal error adding department. Try adding again.');
			}
			catch (\Exception $exception)
			{
				$conn->rollbackTransaction();
				throw new RestException($exception->getMessage());
			}
		}
		else
		{
			throw new AccessException();
		}
	}

	public static function departmentUpdate($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			/** @var Repository\HrDepartmentRepository | Repository\IblockDepartmentRepository $departmentRepository */
			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository();
			$oldDepartment = $departmentRepository->getById((int)$params['ID']);

			if($oldDepartment)
			{
				$sort = (int)($params['SORT'] ?? null);
				$department = new \Bitrix\Intranet\Entity\Department(
					$params['NAME'] ?? $oldDepartment->getName(),
					id: $oldDepartment->getId(),
					parentId: $params['PARENT'] ?? $oldDepartment->getParentId(),
					sort: $sort > 0 ? $sort : null,
				);
				/** @var Repository\HrDepartmentRepository | Repository\IblockDepartmentRepository $departmentRepository */
				$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
					->departmentRepository();

				$conn = Application::getConnection();
				$conn->startTransaction();
				try
				{
					$departmentRepository->save($department);
					if (isset($params['UF_HEAD']))
					{
						$departmentRepository->setHead($department->getId(), (int)$params['UF_HEAD']);
					}
					$conn->commitTransaction();

					return true;
				}
				catch (SqlQueryException)
				{
					$conn->rollbackTransaction();
					throw new RestException('Internal error updating department. Try updating again.');
				}
				catch (\Exception $exception)
				{
					$conn->rollbackTransaction();
					throw new RestException($exception->getMessage());
				}
			}
			else
			{
				throw new ObjectNotFoundException('Department not found');
			}
		}
		else
		{
			throw new AccessException();
		}
	}

	public static function departmentDelete($params)
	{
		if(self::canEdit())
		{
			CModule::IncludeModule('iblock');

			$params = array_change_key_case($params, CASE_UPPER);

			$arDept = self::getDepartment($params['ID']);
			if(is_array($arDept))
			{
				/** @var Repository\HrDepartmentRepository | Repository\IblockDepartmentRepository $departmentRepository */
				$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
					->departmentRepository();

				$conn = Application::getConnection();
				$conn->startTransaction();
				try
				{
					$departmentRepository->delete((int)$arDept['ID']);
					$conn->commitTransaction();

					return true;
				}
				catch (SqlQueryException)
				{
					$conn->rollbackTransaction();
					throw new RestException('Internal error deleting department. Try deleting again.');
				}
				catch (\Exception $exception)
				{
					$conn->rollbackTransaction();
					throw new RestException($exception->getMessage());
				}
			}
			else
			{
				throw new ObjectNotFoundException('Department not found');
			}
		}
		else
		{
			throw new AccessException();
		}
	}

	protected static function getDeptIblock()
	{
		return COption::GetOptionInt('intranet', 'iblock_structure', 0);
	}

	protected static function getDeptData($arDept)
	{
		$res = array();
		foreach(self::$arSelectDepartmentFields as $key)
		{
			if(isset($arDept[$key]))
			{
				switch($key)
				{
					case 'SORT':
						$res[$key] = intval($arDept[$key]);
					break;
					case 'IBLOCK_SECTION_ID':
						$res['PARENT'] = $arDept[$key];
					break;
					default:
						$res[$key] = $arDept[$key];
				}
			}
		}

		return $res;
	}

	protected static function prepareSort($query): array
	{
		$query = array_change_key_case($query, CASE_UPPER);
		$sort = ['LEFT_MARGIN' => 'ASC'];

		if (isset($query['SORT']) && is_string($query['SORT']))
		{
			$sortField = mb_strtoupper($query['SORT']);
			if (in_array($sortField, self::$arAllowedDepartmentFields))
			{
				$order = isset($query['ORDER']) && is_string($query['ORDER']) ? mb_strtoupper($query['ORDER']) : '';
				if ($order != 'DESC')
					$order = 'ASC';

				$sort = [$sortField => $order];
			}
		}
		return $sort;
	}

	protected static function prepareDeptData($arData)
	{
		$arDept = array(
			'IBLOCK_ID' => self::getDeptIblock(),
			'GLOBAL_ACTIVE' => 'Y'
		);

		foreach($arData as $key => $value)
		{
			if(in_array($key, self::$arAllowedDepartmentFields))
			{
				$dkey = $key == 'PARENT' ? 'SECTION_ID' : $key;
				$arDept[$dkey] = $value;
			}
		}

		if(isset($arDept['ID']))
		{
			if(is_array($arDept['ID']))
				$arDept['ID'] = array_map("intval", $arDept['ID']);
			else
				$arDept['ID'] = intval($arDept['ID']);
		}

		if(isset($arDept['SORT']))
		{
			$arDept['SORT'] = intval($arDept['SORT']);
		}

		if(isset($arDept['SECTION_ID']))
		{
			if(is_array($arDept['SECTION_ID']))
				$arDept['SECTION_ID'] = array_map("intval", $arDept['SECTION_ID']);
			else
				$arDept['SECTION_ID'] = intval($arDept['SECTION_ID']);
		}

		if(isset($arDept['UF_HEAD']))
		{
			if(is_array($arDept['UF_HEAD']))
				$arDept['UF_HEAD'] = array_map("intval", $arDept['UF_HEAD']);
			else
				$arDept['UF_HEAD'] = intval($arDept['UF_HEAD']);
		}

		return $arDept;
	}

	protected static function getDepartment($ID)
	{
		$ID = intval($ID);
		if($ID > 0)
		{
			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository();
			$department = $departmentRepository->getById($ID);

			return $department ? ['ID' => $department->getId()] : false;
		}

		return false;
	}

	protected static function canEdit()
	{
		if (!self::isCheckAccess())
		{
			return true;
		}

		CModule::IncludeModule('iblock');
		$perm = CIBlock::GetPermission(self::getDeptIblock());
		return $perm  >= 'U';
	}

	public static function isCheckAccess(): bool
	{
		return self::$checkAccess;
	}

	public static function setCheckAccess(bool $checkAccess): void
	{
		self::$checkAccess = $checkAccess;
	}
}