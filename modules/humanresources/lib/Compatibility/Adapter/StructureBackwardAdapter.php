<?php

namespace Bitrix\HumanResources\Compatibility\Adapter;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Config;

class StructureBackwardAdapter
{
	private static ?int $headRole = null;
	private static array $nodeHeads = [];
	private const STRUCTURE_EMPLOYEE_CACHE_KEY = 'humanresources/employee/structure/%s/%s';
	private const STRUCTURE_CACHE_KEY = 'humanresources/structure/%s/%s';

	private const CACHE_SUB_DIR = 'structure';

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getStructure(?int $fromIblockSectionId = null, ?int $depth = 0): array
	{
		if (!Config\Storage::instance()->isIntranetUtilsDisabled())
		{
			return [];
		}

		if (\COption::GetOptionInt("humanresources", "check_user_existence") !== 1)
		{
			\CAgent::AddAgent(
				name: '\Bitrix\HumanResources\Install\Agent\CheckUserExistence::run();',
				module: 'humanresources',
				interval: 60,
				next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 600, 'FULL'),
				existError: false,
			);

			\COption::SetOptionInt("humanresources", "check_user_existence", 1);
		}

		if (\COption::GetOptionInt("humanresources", "re_sync_user_structure") !== 1)
		{
			\CAgent::AddAgent(
				name: '\Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::startDefaultConverting();',
				module: 'humanresources',
				interval: 60,
				next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 600, 'FULL'),
				existError: false,
			);

			\COption::SetOptionInt("humanresources", "re_sync_user_structure", 1);
		}

		$depthKey = $depth === null ? 'full' : $depth;

		$cacheManager = self::getCacheManager();

		$employeeCacheKey = sprintf(self::STRUCTURE_EMPLOYEE_CACHE_KEY, (int)$fromIblockSectionId, $depthKey);
		$structureCache = $cacheManager->getData($employeeCacheKey, self::CACHE_SUB_DIR);

		if ($structureCache !== null)
		{
			return $structureCache;
		}

		$structure = self::getStructureWithoutEmployee($fromIblockSectionId, $depth);

		if (empty($structure))
		{
			return [];
		}

		$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])->id;
		$employees = Container::getNodeMemberService()->getAllEmployees($structure['ROOT']['ID'], true);

		foreach ($employees as $employee)
		{
			$department = $structure['DATA'][$structure['COMPATIBILITY'][$employee->nodeId]] ?? false;
			if (!$department)
			{
				continue;
			}
			if (!$department['EMPLOYEES'])
			{
				$structure['DATA'][$structure['COMPATIBILITY'][$employee->nodeId]]['EMPLOYEES'] = [];
			}

			$structure['DATA'][$structure['COMPATIBILITY'][$employee->nodeId]]['EMPLOYEES'][] = $employee->entityId;

			if (in_array($headRole, $employee->roles))
			{
				$structure['DATA'][$structure['COMPATIBILITY'][$employee->nodeId]]['UF_HEAD'] = $employee->entityId;
			}
		}

		$cacheManager->setData($employeeCacheKey, $structure, self::CACHE_SUB_DIR);

		return $structure;
	}

	/**
	 * @param int|null $fromIblockSectionId
	 * @param int|null $depth
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStructureWithoutEmployee(?int $fromIblockSectionId = null, ?int $depth = null): array
	{
		if (!Config\Storage::instance()->isIntranetUtilsDisabled())
		{
			return [];
		}

		if (!Config\Storage::instance()->isCompanyStructureConverted(false))
		{
			return [];
		}
		$depthKey = $depth === null ? 'full' : $depth;

		$cacheManager = self::getCacheManager();
		$cacheKey = sprintf(
			self::STRUCTURE_CACHE_KEY,
			(int)$fromIblockSectionId,
			$depthKey
		);

		$structureCache = $cacheManager->getData($cacheKey, self::CACHE_SUB_DIR);

		if ($structureCache !== null)
		{
			return $structureCache;
		}

		$nodeRepository = Container::getNodeRepository();
		$structureRepository = Container::getStructureRepository();

		$structure = $structureRepository->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
		if (!$structure)
		{
			return [];
		}

		try
		{
			if (!$fromIblockSectionId)
			{
				$rootNode = $nodeRepository
					->getRootNodeByStructureId($structure->id)
				;
			}
			else
			{
				$rootNode = $nodeRepository->getByAccessCode(
					DepartmentBackwardAccessCode::makeById($fromIblockSectionId),
				);
			}
		}
		catch (\Exception)
		{
			return [];
		}

		if (!$rootNode)
		{
			return [];
		}

		$children = $nodeRepository->getChildOf($rootNode, $depth === null ? DepthLevel::FULL : ($depth - 1));

		$structureArray = [
			'TREE' => [],
			'DATA' => [],
			'ROOT' => ['ID' => $rootNode->id,],
			'COMPATIBILITY' => [],
		];

		$parentNodes = [];
		foreach ($children as $child)
		{
			if (isset($parentNodes[$child->parentId]))
			{
				$parentId = $parentNodes[$child->parentId];
			}
			else
			{
				$parent = $children->getItemById($child->parentId);
				$parentId = DepartmentBackwardAccessCode::extractIdFromCode(
					$parent !== null
						? $parent->accessCode
						: $nodeRepository->getById($child->parentId)?->accessCode,
				);
			}

			if ($parentId === null && $child->depth !== 0)
			{
				continue;
			}

			$id = DepartmentBackwardAccessCode::extractIdFromCode($child->accessCode);

			if ($id === null)
			{
				continue;
			}

			$structureArray['DATA'][$id] =  [
				'ID' => $id,
				'NAME' => $child->name,
				'IBLOCK_SECTION_ID' => $parentId ?? 0,
				'UF_HEAD' => self::getHeadPersonValue($child),
				'SECTION_PAGE_URL' => '#SITE_DIR#company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
				'DEPTH_LEVEL' => $child->depth + 1,
				'EMPLOYEES' => [],
				'STRUCTURE_NODE_ID' => $child->id,
			];

			$structureArray['COMPATIBILITY'][$child->id] = $id;

			$structureArray['TREE'][$parentId ?? 0][] = $id;
			if ($parentId === null)
			{
				continue;
			}

			$parentNodes[$child->parentId] ??= $parentId;
		}

		$cacheManager->setData($cacheKey, $structureArray, self::CACHE_SUB_DIR);

		return $structureArray;
	}

	private static function getHeadPersonValue(Node $node): ?int
	{
		if (!static::$headRole)
		{
			static::$headRole = Container::getRoleRepository()
				->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])->id;
		}

		if (!empty(static::$nodeHeads))
		{
			return static::$nodeHeads[$node->id] ?? null;
		}
		$headMembers = Container::getNodeMemberRepository()
			->findAllByRoleIdAndStructureId(static::$headRole, $node->structureId)
		;

		foreach ($headMembers as $headMember)
		{
			static::$nodeHeads[$headMember->nodeId] = $headMember->entityId;
		}

		if (empty(static::$nodeHeads))
		{
			static::$nodeHeads[] = 0;
		}

		return static::$nodeHeads[$node->id] ?? null;
	}

	/**
	 * @return \Bitrix\HumanResources\Contract\Util\CacheManager
	 */
	private static function getCacheManager(): \Bitrix\HumanResources\Contract\Util\CacheManager
	{
		return Container::getCacheManager()
			->setTtl(86400)
		;
	}

	/**
	 * @return void
	 */
	public static function clearCache(): void
	{
		self::$nodeHeads = [];
		self::$headRole = null;

		self::getCacheManager()->cleanDir(self::CACHE_SUB_DIR);
	}
}