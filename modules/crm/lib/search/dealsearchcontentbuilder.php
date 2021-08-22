<?php
namespace Bitrix\Crm\Search;

use Bitrix\Crm;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Binding\DealContactTable;
class DealSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmDeal::GetUserFieldEntityID();
	}
	public function isFullTextSearchEnabled()
	{
		return DealTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmDeal::GetListEx(
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
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @param array|null $options Options.
	 * @return SearchMap
	 * @throws \Bitrix\Main\ArgumentException
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
			$customerNumber = $this->parseCustomerNumber($title, \CCrmDeal::GetDefaultTitleTemplate());
			if($customerNumber != $entityID)
			{
				$map->addTextFragments($customerNumber);
			}
		}

		$map->addField($fields, 'OPPORTUNITY');

		if (!$isShortIndex)
		{
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

		//region Company
		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$map->add(
				\CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $companyID, false)
			);

			$map->addEntityMultiFields(
				\CCrmOwnerType::Company,
				$companyID,
				array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL)
			);
		}
		//endregion

		//region Contacts
		$contactIDs = DealContactTable::getDealContactIDs($entityID);
		foreach($contactIDs as $contactID)
		{
			$map->add(
				\CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $contactID, false)
			);

			$map->addEntityMultiFields(
				\CCrmOwnerType::Contact,
				$contactID,
				array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL)
			);
		}
		//endregion

		if (!$isShortIndex)
		{
			if(isset($fields['TYPE_ID']))
			{
				$map->addStatus('DEAL_TYPE', $fields['TYPE_ID']);
			}

			if(isset($fields['STAGE_ID']))
			{
				$map->add(
					Crm\Category\DealCategory::getStageName(
						$fields['STAGE_ID'],
						isset($fields['CATEGORY_ID']) ? $fields['CATEGORY_ID'] : -1
					)
				);
			}

			if(isset($fields['BEGINDATE']))
			{
				$map->add($fields['BEGINDATE']);
			}

			if(isset($fields['CLOSEDATE']))
			{
				$map->add($fields['CLOSEDATE']);
			}

			if(isset($fields['COMMENTS']))
			{
				$map->addHtml($fields['COMMENTS'], 1024);
			}

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
		$dbResult = \CCrmDeal::GetListEx(
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
		DealTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}

	protected function saveShortIndex(int $entityId, SearchMap $map, bool $checkExist = false): \Bitrix\Main\DB\Result
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$searchContent = $helper->forSql($map->getString());

		if ($checkExist)
		{
			$sql = "
				INSERT INTO b_crm_deal_index(DEAL_ID, SEARCH_CONTENT)
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
			UPDATE b_crm_deal_index SET SEARCH_CONTENT= '{$searchContent}' WHERE DEAL_ID = {$entityId}
		";
		return $connection->query($sql);
	}

	public function removeShortIndex(int $entityId): \Bitrix\Main\ORM\Data\Result
	{
		return \Bitrix\Crm\Entity\Index\DealTable::delete([
			'DEAL_ID' => $entityId,
		]);
	}
}
