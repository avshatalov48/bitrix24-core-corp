<?php

namespace Bitrix\HumanResources\Controller\Structure\Node;

use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Command\Structure\Node\SaveNodeSettingsCommand;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Exception\CommandException;
use Bitrix\HumanResources\Exception\CommandValidateException;
use Bitrix\HumanResources\Internals\Attribute\Access\LogicOr;
use Bitrix\HumanResources\Internals\Attribute\StructureActionAccess;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Repository\NodeSettingsRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\Main\Error;
use Bitrix\Main\Request;

class NodeSettings extends Controller
{
	private NodeSettingsRepository $nodeSettingsRepository;

	public function __construct(Request $request = null)
	{
		$this->nodeSettingsRepository = Container::getNodeSettingsRepository();
		parent::__construct($request);
	}

	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_VIEW,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function getAction(Item\Node $node): array
	{
		return $this->nodeSettingsRepository->getByNodeAndTypes($node->id)->getValues();
	}

	/**
	 * @param Item\Node $node
	 * @param int $parentId
	 * @param array{ NodeSettingsType: array{ values: string, replace: bool } } $settings
	 * @return void
	 */
	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
	)]
	public function createAction(Item\Node $node, int $parentId, array $settings)
	{
		try
		{
			$commandResult = (new SaveNodeSettingsCommand(
				$node,
				$settings,
			))->run();

			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}
		catch (CommandException|CommandValidateException $e)
		{
			$this->addError(new Error($e->getMessage()));
		}
	}

	/**
	 * @param Item\Node $node
	 * @param int $parentId
	 * @param array{ NodeSettingsType: array{ values: string, replace: bool } } $settings
	 * @return void
	 */
	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_SETTINGS_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_SETTINGS_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
	)]
	public function updateAction(Item\Node $node, int $parentId, array $settings)
	{
		try
		{
			$commandResult = (new SaveNodeSettingsCommand(
				$node,
				$settings,
			))->run();

			if (!$commandResult->isSuccess())
			{
				$this->addErrors($commandResult->getErrors());
			}
		}
		catch (CommandException|CommandValidateException $e)
		{
			$this->addError(new Error($e->getMessage()));
		}
	}
}
