<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\Intranet\Contract\Repository\DepartmentRepository as DepartmentRepositoryContract;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Enum\DepartmentActiveFilter;
use Bitrix\Intranet\Enum\DepthLevel;
use Bitrix\Intranet\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class IblockDepartmentRepository implements DepartmentRepositoryContract
{
	/**
	 * @throws LoaderException
	 */
	public function __construct()
	{
		if(!Loader::includeModule('iblock'))
		{
			throw new \Bitrix\Main\LoaderException('Module "iblock" not loaded.');
		}
	}

	/**
	 * @throws ObjectException
	 */
	public function getById(int $departmentId): ?Department
	{
		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			['ID' => $departmentId],
			['ID', 'NAME', 'XML_ID', 'MODIFIED_BY', 'CREATED_BY', 'TIMESTAMP_X', 'DATE_CREATE', 'IBLOCK_SECTION_ID'],
			['nTopCount' => 1]
		);
		if ($departmentData = $departmentResult->Fetch())
		{
			return $this->makeDepartmentFromIBlockArray($departmentData);
		}

		return null;
	}

	/**
	 * @throws ObjectException
	 * @throws ArgumentException
	 */
	public function getAllTree(
		Department $rootDepartment = null,
		DepthLevel $depthLevel = DepthLevel::FULL,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection
	{
		$activeFilter = $this->convertDepartmentActiveFilter($activeFilter);
		if (!$rootDepartment)
		{
			$rootDepartment = $this->getRootDepartment();
		}
		if (!$rootDepartment)
		{
			return new DepartmentCollection();
		}
		$depthLevelFilter = $this->convertDepartmentDepthFilter($depthLevel, $rootDepartment);
		$departmentResult = \CIBlockSection::GetTreeList(
			array_merge([
				"IBLOCK_ID" => $this->getIblockId(),
				],
				$depthLevelFilter,
				$activeFilter
			)
		);

		return $this->makeDepartmentCollectionFromIBlockResult($departmentResult);
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function getDepartmentHead(int $departmentId): ?User
	{
		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			[
				'ID' => $departmentId,
				'IBLOCK_ID' => $this->getIblockId()
			],
			false,
			['UF_HEAD'],
			['nTopCount' => 1]
		);
		if (($departmentData = $departmentResult->Fetch()) && (int)$departmentData['UF_HEAD'] > 0)
		{
			return new User((int)$departmentData['UF_HEAD']);
		}

		return null;
	}

	/**
	 * @throws SystemException
	 */
	public function getRootDepartment(): ?Department
	{
		$departmentResult = \CIBlockSection::GetList(
			[],
			[
				"SECTION_ID" => 0,
				"IBLOCK_ID" => $this->getIblockId()
			]
		);
		if ($departmentData = $departmentResult->Fetch())
		{
			return $this->makeDepartmentFromIBlockArray($departmentData);
		}

		return null;
	}

	/**
	 * @throws ObjectException
	 * @throws ArgumentException
	 */
	public function getDepartmentsByName(?string $name = null, int $limit = 100): DepartmentCollection
	{
		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			[
				'IBLOCK_ID' => $this->getIblockId(),
				'%NAME' => $name
			],
			false,
			['ID', 'NAME'],
			['nTopCount' => $limit]
		);

		return $this->makeDepartmentCollectionFromIBlockResult($departmentResult);
	}

	/**
	 * @throws \Exception
	 */
	public function setHead(int $departmentId, int $userId): void
	{
		$iBlockSection = new \CIBlockSection();

		if ($iBlockSection->Update($departmentId, array('UF_HEAD' => $userId)) === false)
		{
			throw new ArgumentException($iBlockSection->LAST_ERROR);
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function findAllByIds(array $ids): DepartmentCollection
	{
		if (empty($ids))
		{
			return new DepartmentCollection();
		}

		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			[
				'IBLOCK_ID' => $this->getIblockId(),
				'ID' => $ids
			],
			false,
			['ID', 'NAME'],
		);

		return $this->makeDepartmentCollectionFromIBlockResult($departmentResult);
	}

	/**
	 * @throws ObjectException
	 * @throws ArgumentException
	 */
	public function findAllByXmlId(string $xmlId): DepartmentCollection
	{
		$departmentResult = \CIBlockSection::GetList(
			[],
			[
				"IBLOCK_ID" => $this->getIblockId(),
				"EXTERNAL_ID"=>$xmlId
			],
		);

		return $this->makeDepartmentCollectionFromIBlockResult($departmentResult);
	}

	public function getDepartmentByHeadId(
		int $headId,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection
	{
		$filter = $this->convertDepartmentActiveFilter($activeFilter);
		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			array_merge([
				'IBLOCK_ID' => $this->getIblockId(),
				'UF_HEAD' => $headId
			], $filter),
			false,
			['ID', 'NAME', 'XML_ID', 'MODIFIED_BY', 'CREATED_BY', 'TIMESTAMP_X', 'DATE_CREATE', 'IBLOCK_SECTION_ID'],
		);

		return $this->makeDepartmentCollectionFromIBlockResult($departmentResult);
	}

	public function unsetHead(int $departmentId): void
	{
		(new \CIBlockSection())->Update($departmentId, array('UF_HEAD' => null));
	}

	/**
	 * @throws ArgumentException
	 */
	protected function create(Department $department): Department
	{
		$currentUserId = CurrentUser::get()?->getId() ?? 0;
		$department->setCreatedBy($currentUserId);
		$departmentFields = [
			"NAME" => $department->getName(),
			"IBLOCK_ID" => $this->getIblockId(),
			'IBLOCK_SECTION_ID' => $department->getParentId() ?? null,
			'XML_ID' => $department->getXmlId() ?? null,
			'ACTIVE' => $department->isActive() ? 'Y' : 'N',
			'SORT' => $department->getSort(),
			'CREATED_BY' => $department->getCreatedBy(),
		];
		$iBlockSection = new \CIBlockSection();
		$id = $iBlockSection->Add($departmentFields);
		if ($id === false)
		{
			throw new ArgumentException($iBlockSection->LAST_ERROR);
		}
		$department->setId((int)$id);

		return $department;
	}

	/**
	 * @throws ArgumentException
	 */
	protected function update(Department $department): Department
	{
		if (!$department->getId())
		{
			return $department;
		}

		$departmentResult = \CIBlockSection::GetList(
			['ID' => 'ASC'],
			[
				'IBLOCK_ID' => $this->getIblockId(),
				'ID' => $department->getId()
			],
			false,
			['ID', 'NAME', 'IBLOCK_ID', 'XML_ID', 'IBLOCK_SECTION_ID'],
		);
		$departmentData = $departmentResult->Fetch();

		if ($departmentData === false)
		{
			return $department;
		}
		$updateData = [];
		if ($department->getName() && $departmentData['NAME'] !== $department->getName())
		{
			$updateData['NAME'] = $department->getName();
		}

		if (!is_null($department->getParentId()) && $departmentData['IBLOCK_SECTION_ID'] !== $department->getParentId())
		{
			$updateData['IBLOCK_SECTION_ID'] = $department->getParentId();
		}

		if ($department->getXmlId() && $departmentData['XML_ID'] !== $department->getXmlId())
		{
			$updateData['XML_ID'] = $department->getXmlId();
		}

		if ($department->getSort() && $departmentData['SORT'] !== $department->getSort())
		{
			$updateData['SORT'] = $department->getSort();
		}

		if (!is_null($department->isActive()) && $departmentData['ACTIVE'] !== ($department->isActive() ? 'Y' : 'N') )
		{
			$updateData['ACTIVE'] = $department->isActive() ? 'Y' : 'N';
		}

		if (empty($updateData))
		{
			return $department;
		}

		$iBlockSection = new \CIBlockSection();
		$result = $iBlockSection->Update($department->getId(), $updateData);
		if ($result === false)
		{
			throw new ArgumentException($iBlockSection->LAST_ERROR);
		}

		return $department;
	}

	/**
	 * @throws ArgumentException
	 */
	public function save(Department $department): Department
	{
		if ($department->getId() > 0)
		{
			return $this->update($department);
		}

		return $this->create($department);
	}

	public function delete(int $departmentId): void
	{
		$result = \CIBlockSection::Delete($departmentId);
		if ($result === false)
		{
			global $APPLICATION;
			$message = '';
			if($exception = $APPLICATION->GetException())
			{
				$message = $exception->GetString();
			}
			throw new ArgumentException($message);
		}
	}

	/**
	 * @throws ObjectException
	 */
	protected function makeDepartmentFromIBlockArray(array $data): Department
	{
		$active = null;
		if (isset($data['ACTIVE']))
		{
			$active = $data['ACTIVE'] === 'Y';
		}
		$globalActive = null;
		if (isset($data['GLOBAL_ACTIVE']))
		{
			$globalActive = $data['GLOBAL_ACTIVE'] === 'Y';
		}
		return new Department(
			name: $data['NAME'] ?? null,
			id: $data['ID'],
			parentId: $data['IBLOCK_SECTION_ID'],
			createdBy: $data['CREATED_BY'] ?? null,
			createdAt: $data['DATE_CREATE'] ? new DateTime($data['DATE_CREATE']) : null,
			updatedAt: $data['TIMESTAMP_X'] ? new DateTime($data['TIMESTAMP_X']) : null,
			xmlId: $data['XML_ID'] ?? null,
			sort: $data['SORT'] ?? null,
			isActive: $active,
			isGlobalActive: $globalActive,
			depth: $data['DEPTH_LEVEL'] ?? null,
		);
	}

	/**
	 * @throws ObjectException
	 * @throws ArgumentException
	 */
	protected function makeDepartmentCollectionFromIBlockResult(\CIBlockResult $result): DepartmentCollection
	{
		$departmentCollection = new DepartmentCollection();
		while ($departmentData = $result->Fetch())
		{
			$departmentCollection->add($this->makeDepartmentFromIBlockArray($departmentData));
		}

		return $departmentCollection;
	}

	protected function getIblockId(): int
	{
		$iblockId = \COption::GetOptionInt('intranet', 'iblock_structure');
		if ($iblockId <= 0)
		{
			$result = \CIBlock::GetList(array(), array("CODE" => "departments"));
			$departmentData = $result->Fetch();

			$iblockId = (int)$departmentData["ID"];
		}

		return $iblockId;
	}

	protected function convertDepartmentActiveFilter(DepartmentActiveFilter $activeFilter): array
	{
		$filter = [];
		switch ($activeFilter)
		{
			case DepartmentActiveFilter::ALL:
				return $filter;
				break;
			case DepartmentActiveFilter::ONLY_ACTIVE:
				$filter['ACTIVE'] = 'Y';
				return $filter;
				break;
			case DepartmentActiveFilter::ONLY_GLOBAL_ACTIVE:
				$filter['GLOBAL_ACTIVE'] = 'Y';
				return $filter;
				break;
		}

		throw new ArgumentException("Unknown active filter");
	}

	protected function convertDepartmentDepthFilter(DepthLevel $depthLevel, Department $department): array
	{
		$filter = [];
		switch ($depthLevel)
		{
			case DepthLevel::FULL:
				$section = \CIBlockSection::GetByID($department->getId())->Fetch();
				$filter = [
					"LEFT_MARGIN" => $section["LEFT_MARGIN"],
					"RIGHT_MARGIN" => $section["RIGHT_MARGIN"],
				];
				return $filter;
				break;
			case DepthLevel::FIRST:
				$filter = [
					'SECTION_ID' => $department?->getId() ?? 0,
				];
				return $filter;
				break;
			default:
				throw new ArgumentException("Unknown depth level");
		}

		return $filter;
	}
}