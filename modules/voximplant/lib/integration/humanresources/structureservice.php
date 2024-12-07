<?php

namespace Bitrix\Voximplant\Integration\HumanResources;

use Bitrix\Main\Loader;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;

class StructureService
{
	protected static $instance;
	protected NodeRepository $nodeRepository;
	protected NodeMemberService $nodeMemberService;

	private array $colleagues = [];

	protected function __construct()
	{
		if ($this->isCompanyStructureConverted())
		{
			$this->nodeRepository = Container::getNodeRepository();
			$this->nodeMemberService = Container::getNodeMemberService();
		}
	}

	/**
	 * @return self
	 */
	public static function getInstance(): self
	{
		self::$instance ??= new static();

		return self::$instance;
	}

	public function isCompanyStructureConverted(): bool
	{
		return
			Loader::includeModule('humanresources')
			&& Storage::instance()->isCompanyStructureConverted();
	}

	public function getRootDepartmentId(): int
	{
		$rootDepartment = 0;
		if ($this->isCompanyStructureConverted())
		{
			$structure = Container::getStructureRepository()->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
			$rootNode = $this->nodeRepository->getRootNodeByStructureId($structure->id);
			$rootDepartment = DepartmentBackwardAccessCode::extractIdFromCode($rootNode->accessCode);
		}
		elseif (\Bitrix\Main\Loader::includeModule('intranet'))
		{
			$departmentTree = \CIntranetUtils::GetDeparmentsTree();
			$rootDepartment = (int)$departmentTree[0][0];
		}

		return $rootDepartment;
	}

	public function getUserColleagues(int $userId): array
	{
		if (!isset($this->colleagues[$userId]))
		{
			$this->colleagues[$userId] = [];

			if ($this->isCompanyStructureConverted())
			{
				$colleagues = [];
				$userNodes = $this->nodeRepository->findAllByUserId($userId);
				foreach ($userNodes as $userNode)
				{
					$nodes = $this->nodeMemberService->getAllEmployees($userNode->id, true);
					foreach ($nodes as $employee)
					{
						$colleagues[] = $employee->entityId;
					}
				}
				$this->colleagues[$userId] = array_unique($colleagues);
			}
			elseif (Loader::includeModule('intranet'))
			{
				$colleagues = [];
				$cursor = \CIntranetUtils::getDepartmentColleagues($userId, true);
				while ($row = $cursor->Fetch())
				{
					$colleagues[] = (int)$row['ID'];
				}

				$subordinateEmployees = [];
				$cursor = \CIntranetUtils::getSubordinateEmployees($userId, true);
				while ($row = $cursor->Fetch())
				{
					$subordinateEmployees[] = (int)$row['ID'];
				}

				$this->colleagues[$userId] = array_unique(array_merge($colleagues, $subordinateEmployees));
			}
		}

		return $this->colleagues[$userId];
	}
}