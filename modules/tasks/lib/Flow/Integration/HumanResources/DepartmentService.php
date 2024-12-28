<?php

namespace Bitrix\Tasks\Flow\Integration\HumanResources;

use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Util\NodeMemberCounterHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class DepartmentService
{
	private NodeMemberCounterHelper $nodeMemberCounterHelper;
	private NodeRepository $nodeRepository;

	/**
	 * @throws LoaderException
	 */
	public function __construct()
	{
		if (!Loader::includeModule('humanresources'))
		{
			throw new LoaderException('Humanresources is not loaded');
		}

		$this->nodeMemberCounterHelper = Container::getNodeMemberCounterHelper();
		$this->nodeRepository = Container::getNodeRepository();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getDepartmentUsersCountByAccessCode(string $code): int
	{
		$findRecursive = false;
		$codeWithoutRecursive = $code;
		if (str_contains($code, 'DR'))
		{
			$findRecursive = true;
			$codeWithoutRecursive = str_replace('DR', 'D', $code);
		}

		$nodeCollection = $this->nodeRepository->findAllByAccessCodes([$codeWithoutRecursive]);
		$items = $nodeCollection->getItemMap();

		return $this->nodeMemberCounterHelper->countByNodeId(array_shift($items)->id, $findRecursive);
	}
}