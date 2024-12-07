<?php

namespace Bitrix\HumanResources\Install\Stepper;

use Bitrix\HumanResources;
use Bitrix\HumanResources\Config;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Iblock\SectionTable;

final class UpdateSortAndActiveFieldsStepper extends Stepper
{
	private const ACTIVE_UPDATE_STAGE = 1;
	private const SORT_UPDATE_STAGE = 2;
	private const DEFAULT_SORT_VALUE = 500;
	private const LIMIT = 100;
	protected static $moduleId = 'humanresources';

	public function execute(array &$option): bool
	{
		if (
			!Loader::includeModule('intranet')
			|| !Loader::includeModule('iblock')
		)
		{
			return self::FINISH_EXECUTION;
		}

		$option['lastId'] = $option['lastId'] ?? 0;
		$option['departmentTypeId'] = $option['departmentTypeId'] ?? self::getOldDepartmentTypeId();
		$option['maxId'] = $option['maxId'] ?? self::getOldDepartmentMaxId($option['departmentTypeId']);
		if ($option['maxId'] === 0)
		{
			return self::FINISH_EXECUTION;
		}

		$nodeRepository = Container::getNodeRepository();
		$option['updateStage'] = $option['updateStage'] ?? self::ACTIVE_UPDATE_STAGE;

		if (
			$option['updateStage'] === self::ACTIVE_UPDATE_STAGE
			&& $option['lastId'] >= $option['maxId']
		)
		{
			$option['updateStage'] = self::SORT_UPDATE_STAGE;
			$option['lastId'] = 0;
		}

		if ($option['updateStage'] === self::ACTIVE_UPDATE_STAGE)
		{
			$firstId = $option['lastId'];
			$result = SectionTable::query()
				->setSelect(['ID', 'ACTIVE'])
				->where('IBLOCK_ID', $option['departmentTypeId'])
				->where('ID', '>', $option['lastId'])
				->where('ACTIVE', 'N')
				->setOrder(['ID' => 'ASC'])
				->setLimit(self::LIMIT)
				->exec()
			;

			while ($row = $result->fetch())
			{
				$node = $nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById((int)$row['ID']));

				if ($node)
				{
					$node->active = false;
					self::disableUpdateEvent();
					$nodeRepository->update($node);
				}
				$option['lastId'] = (int)$row['ID'];
			}

			if ($option['lastId'] === $firstId)
			{
				$option['lastId'] = $option['maxId'];
			}

			return self::CONTINUE_EXECUTION;
		}

		if ($option['lastId'] >= $option['maxId'])
		{
			return self::FINISH_EXECUTION;
		}

		$firstId = $option['lastId'];
		$result = SectionTable::query()
			->setSelect(['ID', 'SORT'])
			->where('IBLOCK_ID', $option['departmentTypeId'])
			->where('ID', '>', $option['lastId'])
			->where('SORT', '!=', self::DEFAULT_SORT_VALUE)
			->setOrder(['ID' => 'ASC'])
			->setLimit(self::LIMIT)
			->exec()
		;

		while ($row = $result->fetch())
		{
			$node = $nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById((int)$row['ID']));
			if ($node)
			{
				$node->sort = (int)$row['SORT'];
				self::disableUpdateEvent();
				$nodeRepository->update($node);
			}
			$option['lastId'] = $row['ID'];
		}

		if ($option['lastId'] === $firstId)
		{
			$option['lastId'] = $option['maxId'];
		}

		return self::CONTINUE_EXECUTION;
	}

	private static function getOldDepartmentTypeId(): int
	{
		return (int)(Main\Config\Option::get('intranet', 'iblock_structure', 0));
	}

	private static function getOldDepartmentMaxId(int $departmentTypeId): int
	{
		if ($departmentTypeId === 0)
		{
			return 0;
		}

		$result = SectionTable::query()
			->setSelect(['ID'])
			->where('IBLOCK_ID', $departmentTypeId)
			->setOrder(['ID' => 'DESC'])
			->setLimit(self::LIMIT)
			->exec()
		;

		if ($row = $result->fetch())
		{
			return (int)$row['ID'];
		}

		return 0;
	}

	private static function disableUpdateEvent(): void
	{
		Container::getEventSenderService()->removeEventHandlers(
			self::$moduleId,
			HumanResources\Enum\EventName::NODE_UPDATED->name,
		);
	}

	public static function checkDefaultConverting(): string
	{
		if (!Config\Storage::instance()->isCompanyStructureConverted(checkIsEmployeesTransferred: false))
		{
			return 'Bitrix\HumanResources\Install\Stepper\UpdateSortAndActiveFieldsStepper::checkDefaultConverting();';
		}

		\Bitrix\Main\Update\Stepper::bindClass(
			'\Bitrix\HumanResources\Install\Stepper\UpdateSortAndActiveFieldsStepper',
			'humanresources',
		);

		return '';
	}
}