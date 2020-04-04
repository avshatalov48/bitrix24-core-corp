<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmActivityTodoComponent extends \CBitrixComponent
{
	private $ids = 0;
	private $typeId = 0;

	/**
	 * Init class' vars.
	 */
	private function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		if (!isset($this->arParams['OWNER_TYPE_ID']) || !isset($this->arParams['OWNER_ID']) || empty($this->arParams['OWNER_ID']))
		{
			return false;
		}
		else
		{
			$this->ids = is_array($this->arParams['OWNER_ID']) ? $this->arParams['OWNER_ID'] : array($this->arParams['OWNER_ID']);
			$this->typeId = \CCrmOwnerType::ResolveID(trim($this->arParams['OWNER_TYPE_ID']));
			if (!$this->typeId)
			{
				return false;
			}
		}

		if (!isset($this->arParams['IS_AJAX']) || $this->arParams['IS_AJAX'] != 'Y')
		{
			$this->arParams['IS_AJAX'] = 'N';
		}

		return true;
	}

	/**
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	private function getEntityPath($type)
	{
		$params = $this->arParams;

		$pathKey = 'PATH_TO_'.strtoupper($type).'_SHOW';
		$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];

		return $url;
	}

	/**
	 * Get icon for activity.
	 * @param array $activity
	 * @return string
	 */
	private function getTypeIcon($activity)
	{
		if (
			!isset($activity['TYPE_ID']) &&
			!isset($activity['PROVIDER_ID'])
		)
		{
			return 'no';
		}
		if ($activity['TYPE_ID'] == \CCrmActivityType::Call)
		{
			return $activity['DIRECTION'] == \CCrmActivityDirection::Outgoing ? 'call-outgoing' : 'call';
		}
		if ($activity['TYPE_ID'] == \CCrmActivityType::Meeting)
		{
			return 'meet';
		}
		if ($activity['TYPE_ID'] == \CCrmActivityType::Email)
		{
			return $activity['DIRECTION'] == \CCrmActivityDirection::Outgoing ? 'mail' : 'mail-send';
		}
		if ($activity['PROVIDER_ID'] == 'CRM_EXTERNAL_CHANNEL')
		{
			return 'onec';
		}
		if ($activity['PROVIDER_ID'] == 'CRM_LF_MESSAGE')
		{
			return 'live-feed';
		}
		if ($activity['PROVIDER_ID'] == 'CRM_WEBFORM')
		{
			return 'form';
		}
		if ($activity['PROVIDER_ID'] == 'IMOPENLINES_SESSION')
		{
			return 'chat';
		}
		if ($activity['PROVIDER_ID'] != '')
		{
			return strtolower($activity['PROVIDER_ID']);
		}
		return 'no';
	}

	/**
	 * Get all activities.
	 * @return array
	 */
	private function getActivity()
	{
		$return = array();
		$high = \CCrmActivityPriority::High;
		$contactTypeId = \CCrmOwnerType::Contact;

		//make filter
		$filter = array(
			'BINDINGS' => array()
		);
		if (isset($this->arParams['RESPONSIBLE_ID']))
		{
			$filter['RESPONSIBLE_ID'] = $this->arParams['RESPONSIBLE_ID'];
		}
		if (isset($this->arParams['COMPLETED']))
		{
			$filter['COMPLETED'] = $this->arParams['COMPLETED'];
		}
		foreach ($this->ids as $id)
		{
			if ($id > 0)
			{
				$filter['BINDINGS'][] = array(
					'OWNER_ID' => intval($id),
					'OWNER_TYPE_ID' => $this->typeId,
				);
			}
		}
		if (empty($filter['BINDINGS']))
		{
			return $return;
		}

		//get activities
		$contacts = array();
		$select = array();
		$sort = array('COMPLETED' => 'ASC', 'PRIORITY' => 'DESC', 'DEADLINE' => 'ASC', 'ID' => 'DESC');
		$res = \CCrmActivity::GetList($sort, $filter, false, false, $select);
		while ($activity = $res->getNext())
		{
			if (!($activity['PROVIDER'] = \CCrmActivity::GetActivityProvider($activity)))
			{
				continue;
			}
			if (isset($activity['DEADLINE']) && \CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
			{
				$activity['DEADLINE'] = '';
			}
			if ($activity['COMPLETED'] === 'N' && $activity['PROVIDER']::canCompleteOnView($activity['PROVIDER_TYPE_ID']))
			{
				if (\CCrmActivity::Complete($activity['ID']))
				{
					$activity['COMPLETED'] = 'Y';
				}
			}
			if ($activity['DEADLINE'] && $activity['COMPLETED'] != 'Y')
			{
				$dateTime = \makeTimeStamp($activity['DEADLINE']);
				$date = \Bitrix\Main\Type\DateTime::createFromTimestamp($dateTime);
				//$date->add('-'.date('G', $dateTime).' hours')->add('-'.date('i', $dateTime).' minutes');
				$activity['DEADLINED'] = $date->getTimeStamp() < time() + \CTimeZone::getOffset();
			}
			else
			{
				$activity['DEADLINED'] = false;
			}
			$activity['CONTACTS'] = array();
			foreach (\CCrmActivity::GetBindings($activity['ID']) as $binding)
			{
				if ($binding['OWNER_TYPE_ID'] == $contactTypeId)
				{
					if (!isset($contacts[$binding['OWNER_ID']]))
					{
						$contacts[$binding['OWNER_ID']] = array();
					}
					$contacts[$binding['OWNER_ID']][] = $activity['ID'];
				}
			}
			$activity['PROVIDER_TITLE'] = $activity['PROVIDER']::getTypeName($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION']);
			$activity['PROVIDER_ANCHOR'] = (array)$activity['PROVIDER']::getStatusAnchor();
			$activity['ICON'] = $this->getTypeIcon($activity);
			$activity['HIGH'] = ($activity['PRIORITY'] == $high) ? 'Y' : 'N';
			$activity['DETAIL_EXIST'] = true;
			$return[$activity['ID']] = $activity;
		}
		//waiter
		$wait = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentByOwner(
			$this->typeId,
			$id
		);
		if ($wait)
		{
			$wait['ID'] = 'w' . $wait['ID'];
			$wait['HIGH'] = 'N';
			$wait['DETAIL_EXIST'] = false;
			$wait['CONTACTS'] = array();
			$wait['SUBJECT'] = nl2br(\htmlspecialcharsbx($wait['DESCRIPTION']));
			$wait['ICON'] = $this->getTypeIcon($wait);
			$return[$wait['ID']] = $wait;
		}
		//get contacts for activities
		if (!empty($contacts))
		{
			$contactsVals = array();
			$path = $this->getEntityPath('contact');
			$select = array('ID', 'NAME', 'LAST_NAME');
			$res = \CCrmContact::getListEx(array(), array('ID' => array_keys($contacts)), false, false, $select);
			while ($row = $res->getNext())
			{
				$row['TITLE'] = trim($row['NAME'] . ' ' . $row['LAST_NAME']);
				$row['URL'] = str_replace('#contact_id#', $row['ID'], $path);
				$contactsVals[$row['ID']] = $row;
			}
			//fill activities with contacts
			if (!empty($contactsVals))
			{
				foreach ($contacts as $cid => $aIds)
				{
					if (isset($contactsVals[$cid]))
					{
						foreach ($aIds as $aid)
						{
							$return[$aid]['CONTACTS'][] = $contactsVals[$cid];
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$this->arResult['ITEMS'] = $this->getActivity();

		$this->IncludeComponentTemplate();
	}
}