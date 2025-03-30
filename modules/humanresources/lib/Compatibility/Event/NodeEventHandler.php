<?php

namespace Bitrix\HumanResources\Compatibility\Event;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Enum\EventName;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;

use Bitrix\Main\Engine\CurrentUser;

class NodeEventHandler
{
	private const NECESSARY_NODE_FIELDS = ['NAME', 'ACTIVE','GLOBAL_ACTIVE', 'SORT', 'LEFT_MARGIN', 'RIGHT_MARGIN'];

	public static function onBeforeIBlockSectionUpdate($fields): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()->isLocked('iblock-OnBeforeIBlockSectionUpdate'))
		{
			return;
		}

		if (!self::validateFields($fields))
		{
			return;
		}

		self::provideNode(
			fields: $fields,
			provideChildrenForNewNode: true
		);
	}

	/**
	 * @param $sectionId
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\DeleteFailedException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Throwable
	 */
	public static function onBeforeIBlockSectionDelete($sectionId): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()->isLocked('iblock-OnBeforeIBlockSectionDelete'))
		{
			return;
		}

		Container::getEventSenderService()->removeEventHandlers('humanresources', EventName::NODE_DELETED->name);

		$sectionId = (int) $sectionId;
		if ($sectionId < 1)
		{
			return;
		}

		$node = Container::getNodeRepository()->getByAccessCode(
			DepartmentBackwardAccessCode::makeById($sectionId)
		);

		if ($node)
		{
			Container::getNodeService()->removeNode($node);
		}
	}

	/**
	 * @param $fields
	 *
	 * @return void
	 */
	public static function onAfterIBlockSectionAdd($fields): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()->isLocked('iblock-OnAfterIBlockSectionAdd'))
		{
			return;
		}

		if (!self::validateFields($fields))
		{
			return;
		}

		self::provideNode($fields);
	}

	private static function getStructureId(): ?int
	{
		$structure = Container::getStructureRepository()->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

		return $structure?->id;
	}

	private static function getParentId(array $oldDepartment): ?int
	{
		$oldStructParentId = (int) $oldDepartment['IBLOCK_SECTION_ID'] ?? null;

		if ($oldStructParentId)
		{
			$found = null;
			try
			{
				$found = Container::getNodeRepository()->getByAccessCode(
					DepartmentBackwardAccessCode::makeById($oldStructParentId)
				);
			}
			catch (\Exception)
			{
			}

			return $found?->id;

		}

		return null;
	}

	private static function provideNode(array $fields, bool $provideChildrenForNewNode = false): void
	{
		$structureId = self::getStructureId();
		$parentId = self::getParentId($fields);
		if (!$structureId)
		{
			Container::getStructureLogger()->write([
				'message' => 'Failed to provide Node. StructureId is null or 0',
				'userId' => CurrentUser::get()->getId(),
			]);

			return;
		}

		self::removeEventHandlersForNodeEvents();

		$node =
			Container::getNodeRepository()
				->getByAccessCode(
					DepartmentBackwardAccessCode::makeById((int)$fields['ID'])
				)
		;

		if ($node && $parentId !== null)
		{
			if ($node->parentId !== $parentId)
			{
				$parentNode = Container::getNodeRepository()->getById($parentId);
				Container::getNodeService()->moveNode($node, $parentNode);
			}

			$node->parentId = $parentId;
		}

		if ($node)
		{
			if (!empty($fields['NAME']))
			{
				$node->name = $fields['NAME'];
			}

			if (!empty($fields['SORT']))
			{
				$node->sort = (int)$fields['SORT'];
			}

			if (!empty($fields['ACTIVE']))
			{
				$node->active = $fields['ACTIVE'] === 'Y';
			}

			Container::getNodeRepository()->update($node);
		}

		if (!$node)
		{
			if (!self::checkNecessaryNodeFields($fields))
			{
				$section = \CIBlockSection::GetByID($fields['ID'])->Fetch();
				foreach (self::NECESSARY_NODE_FIELDS as $key)
				{
					$fields[$key] = $section[$key] ?? null;
				}
			}

			$node = Container::getNodeService()
				->insertAndMoveNode(
					new Node(
						name: $fields['NAME'],
						type: NodeEntityType::DEPARTMENT,
						structureId: self::getStructureId(),
						parentId: $parentId,
						xmlId: $fields['XML_ID'],
						active: $fields['ACTIVE'] === 'Y',
						globalActive: $fields['GLOBAL_ACTIVE'] === 'Y',
						sort: (int)$fields['SORT'],
					),
				);
			Container::getStructureBackwardConverter()
				->createBackwardAccessCode(
					$node,
					$fields['ID']
				);

			if (
				$provideChildrenForNewNode
				&& !is_null($fields['LEFT_MARGIN'])
				&& !is_null($fields['RIGHT_MARGIN'])
			)
			{
				$result = \CIBlockSection::GetList(
					arFilter: [
						'IBLOCK_ID' => self::getOldDepartmentIblockId(),
						'>LEFT_MARGIN' => $fields["LEFT_MARGIN"],
						'<RIGHT_MARGIN' => $fields["RIGHT_MARGIN"],
					],
					arSelect: ['*']
				);

				while ($section = $result->fetch())
				{
					self::provideNode($section);
				}
			}
		}

		self::updateHead($node, $fields);
		self::restoreEventHandlersForNodeEvents();
	}

	private static function validateFields(array &$fields): bool
	{
		if (isset($fields['NAME']) && $fields['NAME'] === '')
		{
			return false;
		}

		if (isset($fields['RESULT']) && !$fields['RESULT'])
		{
			return false;
		}

		$requiredKeys = [
			'ID',
			'IBLOCK_ID',
		];

		$ibDept = \COption::GetOptionInt('intranet', 'iblock_structure', false);
		$currentIbId = (int)($fields['IBLOCK_ID'] ?? null);
		if ($ibDept !== $currentIbId || !$currentIbId)
		{
			return false;
		}

		foreach ($requiredKeys as $key)
		{
			if (!array_key_exists($key, $fields))
			{
				return false;
			}
		}

		return true;
	}

	private static function checkNecessaryNodeFields(array $fields): bool
	{
		foreach (self::NECESSARY_NODE_FIELDS as $key)
		{
			if (!$fields[$key])
			{
				return false;
			}
		}
		return true;
	}

	private static function getRole(string $role): ?int
	{
		return Container::getRoleRepository()
			->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID[$role])
			?->id
		;
	}

	/**
	 * @return void
	 */
	private static function removeEventHandlersForNodeEvents(): void
	{
		Container::getSemaphoreService()->lock('iblock-OnAfterIBlockSectionAdd');
		Container::getSemaphoreService()->lock('iblock-OnBeforeIBlockSectionAdd');
		Container::getEventSenderService()->removeEventHandlers('humanresources', EventName::NODE_ADDED->name);
		Container::getEventSenderService()->removeEventHandlers('humanresources', EventName::NODE_UPDATED->name);
	}

	private static function restoreEventHandlersForNodeEvents(): void
	{
		Container::getSemaphoreService()->unlock('iblock-OnAfterIBlockSectionAdd');
		Container::getSemaphoreService()->unlock('iblock-OnBeforeIBlockSectionAdd');
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 * @param array $fields
	 *
	 * @return void
	 */
	protected static function updateHead(Node $node, array $fields): void
	{
		if (!array_key_exists('UF_HEAD', $fields))
		{
			return;
		}

		$headRole = self::getRole('HEAD');
		$employeeRole = self::getRole('EMPLOYEE');

		$heads =
			Container::getNodeMemberRepository()
				->findAllByRoleIdAndNodeId($headRole, $node->id)
		;

		$currentHead = $fields['UF_HEAD'] ?? null;
		$alreadyExisted = false;

		Container::getEventSenderService()->removeEventHandlers(
			'humanresources',
			EventName::MEMBER_UPDATED->name
		);

		try
		{
			foreach ($heads as $head)
			{
				if ($head->entityId === (int)$currentHead)
				{
					$alreadyExisted = true;
					continue;
				}

				$head->role = $employeeRole;
				Container::getNodeMemberRepository()->update($head);
			}

			if (!$alreadyExisted && $currentHead)
			{
				Container::getNodeMemberRepository()
					->create(
						new NodeMember(
							entityType: MemberEntityType::USER,
							entityId: (int)$currentHead,
							nodeId: $node->id,
							active: true,
							role: $headRole,
						),
					)
				;
			}
		}
		catch (\Exception)
		{
		}
	}

	private static function getOldDepartmentIblockId(): int
	{
		return \COption::GetOptionInt('intranet', 'iblock_structure', 0);
	}
}