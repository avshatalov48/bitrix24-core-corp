<?php

namespace Bitrix\HumanResources\Compatibility\Event;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Enum\EventName;
use Bitrix\HumanResources\Enum\LoggerEntityType;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;

class UserEventHandler
{
	public static function onAfterUserUpdate($fields): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()
			->isLocked('main-OnAfterUserUpdate'))
		{
			return;
		}

		if (self::isDepartmentChanged($fields))
		{
			self::updateNodeMemberLink($fields);
		}

		self::updateNodeMemberActive($fields);
	}

	public static function onAfterUserDelete($id): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()
			->isLocked('main-OnAfterUserDelete'))
		{
			return;
		}

		if (!is_int($id))
		{
			return;
		}

		$fields = [
			'ID' => $id,
			'UF_DEPARTMENT' => [],
		];

		self::updateNodeMemberLink($fields);
	}

	public static function onAfterUserAdd($fields): void
	{
		if (!Storage::instance()->isCompanyStructureConverted(false))
		{
			return;
		}

		if (Container::getSemaphoreService()
			->isLocked('main-OnAfterUserAdd'))
		{
			return;
		}

		if (self::isDepartmentChanged($fields))
		{
			self::updateNodeMemberLink($fields);
		}
	}

	private static function updateNodeMemberLink(array $fields): void
	{
		$departments = $fields['UF_DEPARTMENT'];
		if (!is_array($departments))
		{
			return;
		}

		$userId = $fields['ID'];

		$currentLinks = Container::getNodeMemberRepository()
			->findAllByEntityIdAndEntityType($userId, MemberEntityType::USER)
		;

		Container::getEventSenderService()->removeEventHandlers(
			'humanresources',
			EventName::MEMBER_DELETED->name
		);

		Container::getEventSenderService()->removeEventHandlers(
			'humanresources',
			EventName::MEMBER_ADDED->name
		);

		array_walk(
			$departments, fn(&$department) => $department = DepartmentBackwardAccessCode::makeById((int)$department)
		);

		try
		{
			$nodes = Container::getNodeRepository()
				->findAllByAccessCodes($departments)
			;
			$nodeIds = array_map(
				fn($node) => $node->id,
				iterator_to_array($nodes)
			);

			foreach ($currentLinks as $link)
			{
				if (!in_array($link->nodeId, $nodeIds))
				{
					$node = Container::getNodeRepository()
						->getById($link->nodeId)
					;

					if (!$node)
					{
						Container::getNodeMemberRepository()
							->remove($link);
						$currentLinks->remove($link);

						continue;
					}

					if (!$node->accessCode)
					{
						continue;
					}

					Container::getNodeMemberRepository()
						->remove($link)
					;
					$currentLinks->remove($link);
				}
			}

			$existingNodeIds = array_map(
				fn($link) => $link->nodeId,
				iterator_to_array($currentLinks)
			);
			$newNodeIds = array_diff($nodeIds, $existingNodeIds);

			if ($newNodeIds)
			{
				foreach ($newNodeIds as $nodeId)
				{
					$nodeMember = new NodeMember(
						entityType: MemberEntityType::USER,
						entityId: $userId,
						nodeId: $nodeId
					);

					try
					{
						Container::getNodeMemberRepository()
							->create($nodeMember)
						;
					}
					catch (CreationFailedException $exception)
					{
						$message = $exception->getErrors()->getValues()[0] ?? null;
						Container::getStructureLogger()
							->write(
								[
									'message' => 'Node member create failure: '
										. $message?->getMessage()
										. ' ' . Json::encode($nodeMember)
									,
									'entityType' => LoggerEntityType::MEMBER_USER->name,
									'entityId' => $userId,
									'userId' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
								],
							)
						;
					}
				}
			}
		}
		catch (ObjectPropertyException|ArgumentException|SystemException)
		{
		}
	}

	private static function updateNodeMemberActive(array $fields): void
	{
		$user = \CUser::GetByID($fields['ID'])->Fetch();

		$fields['ACTIVE'] = $user['ACTIVE'];
		$activeValue = $fields['ACTIVE'];
		if (!in_array($activeValue, ['Y', 'N'], true))
		{
			return;
		}

		$active = $activeValue === 'Y';

		$userId = (int)$fields['ID'];

		try
		{
			if ($active)
			{
				$userConverter = Container::getStructureUserBackwardConverter();
				if (!$userConverter->isConverted($userId))
				{
					$userConverter->convert($userId);
				}
			}

			Container::getNodeMemberRepository()
				->setActiveByEntityTypeAndEntityId(MemberEntityType::USER, $userId, $active)
			;
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
		}
	}

	private static function isDepartmentChanged(array $fields): bool
	{
		$requiredKeys = [
			'RESULT',
			'UF_DEPARTMENT',
		];

		if (!self::hasRequiredKeys($fields, $requiredKeys))
		{
			return false;
		}

		return !empty($fields['RESULT']);
	}

	private static function isActiveChanged(array $fields): bool
	{
		$requiredKeys = [
			'RESULT',
			'ACTIVE',
		];

		if (!self::hasRequiredKeys($fields, $requiredKeys))
		{
			return false;
		}

		return !empty($fields['RESULT']);
	}

	private static function hasRequiredKeys(array $fields, array $requiredKeys): bool
	{
		foreach ($requiredKeys as $key)
		{
			if (!array_key_exists($key, $fields))
			{
				return false;
			}
		}

		return true;
	}
}