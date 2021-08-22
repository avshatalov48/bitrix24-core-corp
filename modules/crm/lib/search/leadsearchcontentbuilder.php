<?php
namespace Bitrix\Crm\Search;

use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\LeadTable;

class LeadSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmLead::GetUserFieldEntityID();
	}
	public function isFullTextSearchEnabled()
	{
		return LeadTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*'/*, 'UF_*'*/)
		);

		$fields = $dbResult->Fetch();
		return is_array($fields) ? $fields : null;
	}
	public function prepareEntityFilter(array $params)
	{
		$value = isset($params['SEARCH_CONTENT']) ? $params['SEARCH_CONTENT'] : '';
		if(!is_string($value) || $value === '')
		{
			return array();
		}

		$operation = $this->isFullTextSearchEnabled() ? '*' : '*%';
		return array("{$operation}SEARCH_CONTENT" => SearchEnvironment::prepareToken($value));
	}
	/**
	 * Convert entity list filter values.
	 * @param array $filter List Filter.
	 * @return void
	 */
	public function convertEntityFilterValues(array &$filter)
	{
		$this->transferEntityFilterKeys(array('FIND', 'PHONE'), $filter);
	}
	/**
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @param array|null $options Options.
	 * @return SearchMap
	 */
	protected function prepareSearchMap(array $fields, array $options = null)
	{
		$map = new SearchMap();

		$entityID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($entityID <= 0)
		{
			return $map;
		}

		$isShortIndex = ($options['isShortIndex'] ?? false);

		if (!$isShortIndex)
		{
			$map->add($entityID);
		}

		$title = isset($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title !== '')
		{
			$map->addText($title);
			$map->addText(SearchEnvironment::prepareSearchContent($title));

			$customerNumber = $this->parseCustomerNumber($title, \CCrmLead::GetDefaultTitleTemplate());
			if($customerNumber != $entityID)
			{
				$map->addTextFragments($customerNumber);
			}
		}

		$map->addField($fields, 'LAST_NAME');
		$map->addField($fields, 'NAME');
		$map->addField($fields, 'SECOND_NAME');

		if (!$isShortIndex)
		{
			$map->addField($fields, 'COMPANY_TITLE');
			$map->addField($fields, 'OPPORTUNITY');
			$map->add(
				\CCrmCurrency::GetCurrencyName(
					isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
				)
			);

			if(isset($fields['ASSIGNED_BY_ID']))
			{
				$map->addUserByID($fields['ASSIGNED_BY_ID']);
			}
		}

		$multiFields = $this->getEntityMultiFields($entityID);
		if(isset($multiFields[\CCrmFieldMulti::PHONE]))
		{
			foreach($multiFields[\CCrmFieldMulti::PHONE] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addPhone($multiField['VALUE']);
				}
			}
		}
		if(isset($multiFields[\CCrmFieldMulti::EMAIL]))
		{
			foreach($multiFields[\CCrmFieldMulti::EMAIL] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addEmail($multiField['VALUE']);
				}
			}
		}

		if (!$isShortIndex)
		{
			//region Status
			if(isset($fields['STATUS_ID']))
			{
				$map->addStatus('STATUS', $fields['STATUS_ID']);
			}

			if(isset($fields['STATUS_DESCRIPTION']))
			{
				$map->addText($fields['STATUS_DESCRIPTION'], 1024);
			}
			//endregion

			//region Source
			if(isset($fields['SOURCE_ID']))
			{
				$map->addStatus('SOURCE', $fields['SOURCE_ID']);
			}

			if(isset($fields['SOURCE_DESCRIPTION']))
			{
				$map->addText($fields['SOURCE_DESCRIPTION'], 1024);
			}
			//endregion

			//region Address
            $address = preg_replace(
                '/[,.]/', '',
                AddressFormatter::getSingleInstance()->formatTextComma(
                    LeadAddress::mapEntityFields($fields)
                )
            );
			if($address !== '')
			{
				$map->add($address);
			}
			//endregion

			if(isset($fields['COMMENTS']))
			{
				$map->addHtml($fields['COMMENTS'], 1024);
			}

			if(isset($fields['IS_RETURN_CUSTOMER']) && $fields['IS_RETURN_CUSTOMER'] === 'Y')
			{
				$map->add(\CCrmLead::GetFieldCaption('IS_RETURN_CUSTOMER'));
			}

			//region UserFields
			foreach($this->getUserFields($entityID) as $userField)
			{
				$map->addUserField($userField);
			}
			//endregion
		}

		return $map;
	}
	/**
	 * Prepare required data for bulk build.
	 * @param array $entityIDs Entity IDs.
	 */
	protected function prepareForBulkBuild(array $entityIDs)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			array('ASSIGNED_BY_ID'),
			false,
			array('ASSIGNED_BY_ID')
		);

		$userIDs = array();
		while($fields = $dbResult->Fetch())
		{
			$userIDs[] = (int)$fields['ASSIGNED_BY_ID'];
		}

		if(!empty($userIDs))
		{
			SearchMap::cacheUsers($userIDs);
		}
	}
	protected function save($entityID, SearchMap $map)
	{
		LeadTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}

	protected function saveShortIndex(int $entityId, SearchMap $map, bool $checkExist = false): \Bitrix\Main\DB\Result
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$searchContent = $helper->forSql($map->getString());

		if ($checkExist)
		{
			$sql = "
				INSERT INTO b_crm_lead_index(LEAD_ID, SEARCH_CONTENT)
				VALUES({$entityId}, '{$searchContent}')
				ON DUPLICATE KEY UPDATE SEARCH_CONTENT= '{$searchContent}'
			";
			try
			{
				return $connection->query($sql);
			}
			catch (\Exception $exception)
			{
				return $connection->query($sql);
			}
		}

		$sql = "
			UPDATE b_crm_lead_index SET SEARCH_CONTENT= '{$searchContent}' WHERE LEAD_ID = {$entityId}
		";
		return $connection->query($sql);
	}

	public function removeShortIndex(int $entityId): \Bitrix\Main\ORM\Data\Result
	{
		return \Bitrix\Crm\Entity\Index\LeadTable::delete([
			'LEAD_ID' => $entityId,
		]);
	}
}
