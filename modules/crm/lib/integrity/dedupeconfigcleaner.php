<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main\Application;
use Bitrix\Main;
use CCrmOwnerType;

class DedupeConfigCleaner
{
	protected function getListByEntityTypes(array $entityTypeIds): Main\DB\Result
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$conditions = [];
		$fieldNameSql = '`NAME`';
		foreach ($entityTypeIds as $entityTypeId)
		{
			$entityTypeNameSql = mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId));
			$nameValueSql = $helper->forSql("{$entityTypeNameSql}_dedupe_wizard");
			$conditions[] = "$fieldNameSql = '$nameValueSql'";
		}
		$nameConditionSql = empty($conditions) ? '' : ' AND (' . implode(' OR ', $conditions) . ')';

		$categorySql = $helper->forSql(DedupeConfig::OPTION_KEY);

		return $connection->query(
			"SELECT ID, USER_ID, `NAME` FROM b_user_option WHERE CATEGORY = '$categorySql'$nameConditionSql"
		);
	}

	public function removeTypes(array $entityTypeIds, array $typeIds)
	{
		$res = $this->getListByEntityTypes($entityTypeIds);
		if (is_object($res))
		{
			while ($row = $res->fetch())
			{
				$optionName = $row['NAME'];
				$userId = (int)$row['USER_ID'];
				$entityTypeEndPos = mb_strpos($optionName, '_');
				if ($entityTypeEndPos !== false)
				{
					$entityTypeEndPos = mb_strpos($optionName, '_');
					if ($entityTypeEndPos !== false)
					{
						$entityTypeId = CCrmOwnerType::ResolveID(
							mb_strtoupper(mb_substr($optionName, 0, $entityTypeEndPos))
						);
						$dedupeConfig = new DedupeConfig($userId);
						$config = $dedupeConfig->get($optionName, $entityTypeId);
						$isConfigModified = false;
						foreach (['typeNames', 'typeIDs'] as $configSectionName)
						{
							if (is_array($config[$configSectionName]))
							{
								$types = [];
								$isModified = false;
								foreach ($config[$configSectionName] as $type)
								{
									$typeId =
										($configSectionName === 'typeNames')
											? DuplicateIndexType::resolveID($type)
											: $type
									;
									if (in_array($typeId, $typeIds, true))
									{
										$isModified = true;
									}
									else
									{
										$types[] = $type;
									}
								}
								if ($isModified)
								{
									$config[$configSectionName] = $types;
									$isConfigModified = true;
								}
							}
						}
						if ($isConfigModified)
						{
							$dedupeConfig->save($optionName, $config);
						}
					}
				}
			}
		}
	}
}