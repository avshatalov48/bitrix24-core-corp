<?php

namespace Bitrix\HumanResources\Compatibility\Converter;

use Bitrix\HumanResources\Compatibility\Event\UserEventHandler;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class ExtranetUserCleaning
{
	public static function clear(int $offset = 0): string
	{
		$agentName = 'Bitrix\HumanResources\Compatibility\Converter\ExtranetUserCleaning::clear(%d);';
		$limit = 300;
		$structure = Container::getStructureRepository()
			->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

		if (!Storage::instance()->isCompanyStructureConverted() || !$structure)
		{
			return sprintf($agentName, $offset);
		}

		try
		{
			$rootNode =
				Container::getNodeRepository()
					->getRootNodeByStructureId($structure->id)
			;

		}
		catch (WrongStructureItemException | ObjectPropertyException | ArgumentException | SystemException $e)
		{
			return sprintf($agentName, $offset);
		}

		if (!$rootNode)
		{
			return '';
		}

		$employees = Container::getNodeMemberService()->getPagedEmployees(
			$rootNode->id,
			true,
			$offset,
			$limit
		);

		if (!$employees->count())
		{
			return '';
		}

		$handledEmployees = [];

		foreach ($employees as $employee)
		{
			if (isset($handledEmployees[$employee->entityId]))
			{
				continue;
			}

			if (self::isExtranetUser($employee->entityId))
			{
				UserEventHandler::onAfterUserUpdate(
					[
						'ID' => $employee->entityId,
						'RESULT' => $employee->entityId,
						'UF_DEPARTMENT' => [],
					]
				);
			}
			$handledEmployees[$employee->entityId] = $employee->entityId;
		}

		$offset += $limit;
		\CAgent::AddAgent(
			name: sprintf($agentName, $offset),
			module: 'humanresources',
			interval: 60,
			existError: false,
		);

		return '';
	}

	private static function isExtranetUser(int $userId): bool
	{
		$users = \CUser::GetList(
			by: 'ID',
			arFilter: ["ID_EQUAL_EXACT" => $userId],
			arParams: [
				'FIELDS' => ["UF_DEPARTMENT"],
				"SELECT" => ["UF_DEPARTMENT"],
		]);

		while ($user = $users->Fetch())
		{
			if (!empty($user['UF_DEPARTMENT'][0]))
			{
				return false;
			}
			else
			{
				return true;
			}
		}

		return true;
	}

	private static function restartAgent()
	{

	}
}