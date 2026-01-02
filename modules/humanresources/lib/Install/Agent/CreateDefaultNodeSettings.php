<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Model\NodeSettingsTable;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Type\NodeSettingsAuthorityType;
use Bitrix\HumanResources\Type\NodeSettingsType;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Config\Option;

class CreateDefaultNodeSettings
{
	private const MODULE_NAME = 'humanresources';
	private const NODE_SETTINGS_DEFAULTS_CREATED_OPTION_NAME = 'node_settings_defaults_created';
	private const LIMIT = 100;

	public static function run(int $nodeOffset = 0): string
	{
		if (self::isNodeSettingsDefaultsCreated())
		{
			return '';
		}

		$nodes = NodeTable::query()
			->setSelect([
				'ID',
				'ns.SETTINGS_TYPE'
			])
			->registerRuntimeField(
				'ns',
				new Reference(
					'ns',
					NodeSettingsTable::class,
					Join::on('this.ID', 'ref.NODE_ID')
						->where('ref.SETTINGS_TYPE', NodeSettingsType::BusinessProcAuthority->value)
					,
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->whereNull('ns.SETTINGS_TYPE')
			->setOrder(['ID' => 'ASC'])
			->setLimit(self::LIMIT)
			->setOffset($nodeOffset)
			->fetchAll()
		;

		if (empty($nodes))
		{
			self::setNodeSettingsDefaultsCreated(true);

			return '';
		}

		$rows = [];
		foreach ($nodes as $node)
		{
			$rows[] = [
				'NODE_ID' => $node['ID'],
				'SETTINGS_TYPE' => NodeSettingsType::BusinessProcAuthority->value,
				'SETTINGS_VALUE' => NodeSettingsAuthorityType::DepartmentHead->value,
			];
		}

		// Usage of "addMulti" has no point because of ORM events. (see trigger_error in sysAddMultiInternal of DataManager)
		foreach ($rows as $row)
		{
			NodeSettingsTable::add($row);
		}

		$nextOffset = $nodeOffset + self::LIMIT;

		return "\\Bitrix\\HumanResources\\Install\\Agent\\CreateDefaultNodeSettings::run($nextOffset);";
	}

	private static function isNodeSettingsDefaultsCreated(): bool
	{
		return Option::get(self::MODULE_NAME, self::NODE_SETTINGS_DEFAULTS_CREATED_OPTION_NAME, false);
	}

	private static function setNodeSettingsDefaultsCreated(bool $value): void
	{
		Option::set(self::MODULE_NAME, self::NODE_SETTINGS_DEFAULTS_CREATED_OPTION_NAME, $value);
	}
}
