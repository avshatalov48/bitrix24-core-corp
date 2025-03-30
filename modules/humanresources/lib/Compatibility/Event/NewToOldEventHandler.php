<?php

namespace Bitrix\HumanResources\Compatibility\Event;

use Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Compatibility\Utils\OldStructureUtils;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Enum\EventName;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Service\UserService;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Enum\LoggerEntityType;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class NewToOldEventHandler
{
	private const MODULE_NAME_PREFIX = 'humanresources-';

	/**
	 * @param \Bitrix\Main\Event $event
	 *
	 * @return void
	 */
	public static function onNodeAdded(Event $event): void
	{
		if (Container::getSemaphoreService()->isLocked(self::MODULE_NAME_PREFIX . EventName::NODE_ADDED->name))
		{
			return;
		}

		/** @var \Bitrix\HumanResources\Item\Node $node */
		$node = $event->getParameter('node');
		if (!isset($node))
		{
			return;
		}

		$companyStructureConverter = Container::getStructureBackwardConverter();

		Container::getEventSenderService()->removeEventHandlers('iblock', 'OnBeforeIBlockSectionDelete');
		Container::getEventSenderService()->removeEventHandlers('iblock', 'OnAfterIBlockSectionAdd');
		try
		{
			if ($companyStructureConverter->getCompanyStructureId() !== $node->structureId)
			{
				return;
			}

			$parent =
				$node->parentId
				? Container::getNodeRepository()
				->getById($node->parentId) : null
			;

			$parentId = DepartmentBackwardAccessCode::extractIdFromCode($parent?->accessCode);

			$newDepartmentId = OldStructureUtils::addDepartment([
				'NAME' => $node->name,
				'PARENT' => $parentId,
				'SORT' => $node->sort,
				'ACTIVE' => $node->active,
			]);

			$companyStructureConverter->createBackwardAccessCode($node, $newDepartmentId);

			Container::getNodeRepository()
				->update($node);
		}
		catch (\Exception)
		{
			Container::getStructureLogger()->write([
				'entityType' => LoggerEntityType::NODE->name,
				'entityId' => $node->id,
				'message' => 'onNodeAdded: Failed to update Node',
				'userId' => CurrentUser::get()->getId(),
			]);
		}

		self::clearCacheInBackground($node);
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 *
	 * @return void
	 */
	public static function onNodeDeleted(Event $event): void
	{
		if (Container::getSemaphoreService()->isLocked(self::MODULE_NAME_PREFIX . EventName::NODE_DELETED->name))
		{
			return;
		}

		/** @var \Bitrix\HumanResources\Item\Node $node */
		$node = $event->getParameter('node');
		if (!isset($node))
		{
			return;
		}

		try
		{
			$companyStructureConverter = Container::getStructureBackwardConverter();

			if ($companyStructureConverter->getCompanyStructureId() !== $node->structureId)
			{
				return;
			}

			Container::getEventSenderService()
				->removeEventHandlers('iblock', 'OnBeforeIBlockSectionDelete')
			;

			$id = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
			if (!$id)
			{
				return;
			}

			OldStructureUtils::deleteDepartment([
				'ID' => $id,
			]);
		}
		catch (\Exception)
		{
			Container::getStructureLogger()->write([
				'entityType' => LoggerEntityType::NODE->name,
				'entityId' => $node->id,
				'message' => 'onNodeDeleted: Failed to delete Node',
				'userId' => CurrentUser::get()->getId(),
			]);
		}

		self::clearCacheInBackground($node);
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onNodeUpdated(Event $event): void
	{
		if (Container::getSemaphoreService()->isLocked(self::MODULE_NAME_PREFIX . EventName::NODE_UPDATED->name))
		{
			return;
		}

		/** @var \Bitrix\HumanResources\Item\Node $node */
		$node = $event->getParameter('node');
		$fields = $event->getParameter('fields');
		if (!isset($node) || !isset($fields))
		{
			return;
		}

		if (!array_intersect(['name', 'parentId', 'active', 'sort'], array_keys($fields)))
		{
			return;
		}

		$companyStructureConverter = Container::getStructureBackwardConverter();

		if ($companyStructureConverter->getCompanyStructureId() !== $node->structureId)
		{
			return;
		}

		StructureBackwardAdapter::clearCache();

		$parent = $node->parentId ? Container::getNodeRepository()
			->getById($node->parentId) : null;

		$nodeId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
		$parentId = DepartmentBackwardAccessCode::extractIdFromCode($parent?->accessCode);

		if (!$nodeId)
		{
			return;
		}

		Container::getEventSenderService()
			->removeEventHandlers('iblock', 'OnBeforeIBlockSectionUpdate')
		;

		try
		{
			OldStructureUtils::updateDepartment([
				'ID' => $nodeId,
				'NAME' => $node->name,
				'PARENT' => $parentId,
				'ACTIVE' => $node->active === true ? 'Y' : 'N',
				'SORT' => $node->sort,
			]);
		}
		catch (\Exception)
		{
		}

		self::clearCacheInBackground($node);
	}

	/**
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onMemberAdded(Event $event): void
	{
		if (Container::getSemaphoreService()->isLocked(self::MODULE_NAME_PREFIX . EventName::MEMBER_ADDED->name))
		{
			return;
		}

		/** @var \Bitrix\HumanResources\Item\NodeMember $member */
		$member = $event->getParameter('member');

		if (!isset($member))
		{
			return;
		}

		if ($member->entityType !== MemberEntityType::USER)
		{
			return;
		}

		$onAfterUserUpdateEvent = 'main-OnAfterUserUpdate';
		Container::getSemaphoreService()->lock($onAfterUserUpdateEvent);
		Container::getEventSenderService()->removeEventHandlers('iblock', 'OnBeforeIBlockSectionUpdate');
		Container::getEventSenderService()->removeEventHandlers('main', 'OnAfterUserUpdate');

		// possible synchronization problem between old and new structure
		static $jobSentForUser = [];

		if (!isset($jobSentForUser[$member->entityId]))
		{
			\Bitrix\Main\Application::getInstance()->addBackgroundJob(function ($member) {
				NodeTable::cleanCache();
				$nodes = Container::getNodeRepository()
					->findAllByUserId($member->entityId);

				$departments = [];

				foreach ($nodes as $node)
				{
					$accessCode = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
					if ($accessCode !== null)
					{
						$departments[] = $accessCode;
					}
				}

				$user = new \CUser();
				$user->Update(
					$member->entityId,
					[
						'UF_DEPARTMENT' => $departments,
					],
				);
			}, [$member]);

			$jobSentForUser[$member->entityId] = true;
		}

		$node = null;
		if ($member->role === Container::getRoleHelperService()->getHeadRoleId())
		{
			$node = Container::getNodeRepository()->getById($member->nodeId);
			try
			{
				OldStructureUtils::updateDepartment([
					'ID' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
					'UF_HEAD' => $member->entityId,
				]);
			}
			catch (\Exception)
			{
			}
		}
		self::clearCacheInBackground($node, $member);

		Container::getSemaphoreService()->unlock($onAfterUserUpdateEvent);
	}

	public static function onMemberDeleted(Event $event): void
	{
		if (
			Container::getSemaphoreService()
				->isLocked(self::MODULE_NAME_PREFIX . EventName::MEMBER_DELETED->name)
		)
		{
			return;
		}

		/** @var \Bitrix\HumanResources\Item\NodeMember $member */
		$member = $event->getParameter('member');

		if (!isset($member))
		{
			return;
		}

		if ($member->entityType !== MemberEntityType::USER)
		{
			return;
		}

		StructureBackwardAdapter::clearCache();

		$onAfterUserDeleteEvent = 'main-OnAfterUserDelete';
		Container::getSemaphoreService()->lock($onAfterUserDeleteEvent);

		try
		{
			self::onMemberAdded($event);

			$node = Container::getNodeRepository()->getById($member->nodeId);

		}
		catch (ObjectPropertyException|ArgumentException|SystemException)
		{
			Container::getSemaphoreService()->unlock($onAfterUserDeleteEvent);

			return;
		}

		if (!str_starts_with($node->accessCode, 'D'))
		{
			Container::getSemaphoreService()->unlock($onAfterUserDeleteEvent);

			return;
		}

		$departmentId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
		$memberDepartment = OldStructureUtils::getOldDepartmentById($departmentId ?? 0) ?? null;

		if (!$memberDepartment || (int)$memberDepartment['UF_HEAD'] !== $member->entityId)
		{
			Container::getSemaphoreService()->unlock($onAfterUserDeleteEvent);

			return;
		}

		try
		{
			OldStructureUtils::updateDepartment([
				'ID' => $departmentId,
				'UF_HEAD' => null,
			]);
		}
		catch (\Exception)
		{
		}

		self::clearCacheInBackground($node, $member);

		Container::getSemaphoreService()->unlock($onAfterUserDeleteEvent);
	}

	public static function onMemberUpdated(Event $event): void
	{
		if (Container::getSemaphoreService()->isLocked(self::MODULE_NAME_PREFIX . EventName::MEMBER_UPDATED->name))
		{
			return;
		}

		$changedFields = $event->getParameter('fields');

		if (array_intersect($changedFields, ['role']))
		{
			$member = $event->getParameter('member');
			$node = Container::getNodeRepository()->getById($member->nodeId);

			$departmentId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
			$memberDepartment = OldStructureUtils::getOldDepartmentById($departmentId ?? 0) ?? null;
			$isHead = $member->role === Container::getRoleHelperService()->getHeadRoleId();

			if (!$isHead && $memberDepartment && (int)$memberDepartment['UF_HEAD'] === $member->entityId)
			{
				try
				{
					OldStructureUtils::updateDepartment(
						[
							'ID' => $departmentId,
							'UF_HEAD' => null,
						],
					);
				}
				catch (\Exception)
				{
				}
			}
		}

		self::onMemberAdded($event);
	}

	/**
	 * @param mixed $node
	 * @param \Bitrix\HumanResources\Item\NodeMember $member
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function clearCache(?Node $node = null, ?\Bitrix\HumanResources\Item\NodeMember $member = null): void
	{
		$taggedCacheManager = Application::getInstance()->getTaggedCache();

		if ($node)
		{
			if (Loader::includeModule('iblock'))
			{
				$iblockId = \COption::getOptionInt('intranet', 'iblock_structure');
				\CIBlockSection::ReSort($iblockId);
			}

			$taggedCacheManager->clearByTag(
				'iblock_id_' . DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode)
			);
		}

		StructureBackwardAdapter::clearCache();
		Container::getCacheManager()->cleanDir(NodeMemberRepository::NODE_MEMBER_CACHE_DIR);
		Container::getCacheManager()->clean(NodeRepository::NODE_ENTITY_RESTRICTION_CACHE);
		NodeTable::cleanCache();

		$taggedCacheManager->clearByTag('intranet_users');
		$taggedCacheManager->clearByTag('intranet_department_structure');

		if ($member)
		{
			Container::getCacheManager()->clean(sprintf(UserService::USER_DEPARTMENT_EXISTS_KEY, $member->entityId));
			Container::getCacheManager()->clean(
				$member->entityId,
				"/user_card_" . (int)($member->entityId / TAGGED_user_card_size)
			);

			if (Loader::includeModule('intranet'))
			{
				\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache($member->entityId);
			}
		}
	}

	public static function clearCacheInBackground(?Node $node = null, ?\Bitrix\HumanResources\Item\NodeMember $member = null)
	{
		static $jobPrepared = [];

		if (isset($jobPrepared[(int)$node?->id][(int)$member?->id]))
		{
			return;
		}

		$jobPrepared[(int)$node?->id][(int)$member?->id] = true;

		\Bitrix\Main\Application::getInstance()->addBackgroundJob(function ($node, $member) {
			self::clearCache($node, $member);
		}, [$node, $member]);

		static $tagGroupCache = [];
		$groupCacheKey = (int)($member?->entityId / TAGGED_user_card_size);

		if (isset($tagGroupCache[$groupCacheKey]))
		{
			return;
		}

		$tagGroupCache[$groupCacheKey] = true;

		\Bitrix\Main\Application::getInstance()->addBackgroundJob(
			function($groupCacheKey) {
				if (defined('BX_COMP_MANAGED_CACHE'))
				{
					$taggedCacheManager = Application::getInstance()->getTaggedCache();

					$taggedCacheManager->clearByTag("USER_CARD_" . $groupCacheKey);
				}
			},
			[$groupCacheKey],
		);

	}
}