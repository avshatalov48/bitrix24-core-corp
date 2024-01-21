<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\ActivityTable;
use CCrmActivity;
use CCrmContentType;
use CCrmFieldMulti;
use CCrmOwnerType;
use CTextParser;

final class ActivitySearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmActivity::class;

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Activity;
	}

	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		if (!is_array($options))
		{
			$options = [];
		}

		if (!(isset($options['skipEntityId']) && $options['skipEntityId']))
		{
			$map->add($entityId);
		}

		$map->addField($fields, 'SUBJECT');

		$description = isset($fields['DESCRIPTION']) ? trim($fields['DESCRIPTION']) : '';
		if ($description !== '')
		{
			$descriptionType = (int)($fields['DESCRIPTION_TYPE'] ?? CCrmContentType::PlainText);
			if ($descriptionType === CCrmContentType::Html)
			{
				$description = strip_tags(preg_replace("/<br(\s*\/\s*)?>/i", ' ', $description));
			}
			elseif ($descriptionType === CCrmContentType::BBCode)
			{
				$parser = new CTextParser();
				$parser->allow['SMILES'] = 'N';
				$description = strip_tags(
					preg_replace(
						"/<br(\s*\/\s*)?>/i",
						' ',
						$parser->convertText($description)
					)
				);
			}

			$map->addText($description, 256);
		}

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
		}

		//region Bindings
		$bindings = CCrmActivity::GetBindings($entityId);
		if (is_array($bindings))
		{
			foreach ($bindings as $binding)
			{
				$ownerId = (int)($binding['OWNER_ID'] ?? 0);
				$ownerTypeId = (int)($binding['OWNER_TYPE_ID'] ?? CCrmOwnerType::Undefined);
				if ($ownerId > 0 && CCrmOwnerType::IsDefined($ownerTypeId))
				{
					$map->add(CCrmOwnerType::GetCaption($ownerTypeId, $ownerId, false));
				}
			}
		}
		//endregion

		//region Communications
		$communications = CCrmActivity::GetCommunications($entityId);
		if (is_array($communications))
		{
			foreach ($communications as $communication)
			{
				$value = $communication['VALUE'] ?? '';
				if ($value === '')
				{
					continue;
				}

				$typeId = $communication['TYPE'] ?? '';
				if ($typeId === CCrmFieldMulti::EMAIL)
				{
					$map->addEmail($value);
				}
				elseif ($typeId === CCrmFieldMulti::PHONE)
				{
					$map->addPhone($value);
				}
			}
		}
		//endregion

		return $map;
	}

	protected function prepareForBulkBuild(array $entityIds): void
	{
		$dbResult = CCrmActivity::GetList(
			[],
			['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'],
			['RESPONSIBLE_ID'],
			false,
			['RESPONSIBLE_ID']
		);

		$userIds = [];
		while ($fields = $dbResult->Fetch())
		{
			$userIds[] = (int)$fields['RESPONSIBLE_ID'];
		}

		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		ActivityTable::update($entityId, ['SEARCH_CONTENT' => $map->getString()]);
	}
}
