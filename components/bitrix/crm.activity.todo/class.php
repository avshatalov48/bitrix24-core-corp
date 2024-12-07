<?php

use Bitrix\Main\Type\DateTime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

class CrmActivityTodoComponent extends \CBitrixComponent
{
	private array $ids = [];
	private int $typeId = 0;

	/**
	 * Init class' vars.
	 */
	private function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		if (
			!isset($this->arParams['OWNER_TYPE_ID'], $this->arParams['OWNER_ID'])
			|| empty($this->arParams['OWNER_ID'])
		)
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

		if (!isset($this->arParams['IS_AJAX']) || $this->arParams['IS_AJAX'] !== 'Y')
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

		$pathKey = 'PATH_TO_'.mb_strtoupper($type).'_SHOW';

		return!array_key_exists($pathKey, $params)
			? \CrmCheckPath($pathKey, '', '')
			: $params[$pathKey];
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

		if ((int)$activity['TYPE_ID'] === \CCrmActivityType::Call)
		{
			return (int)$activity['DIRECTION'] === \CCrmActivityDirection::Outgoing ? 'call-outgoing' : 'call';
		}

		if ((int)$activity['TYPE_ID'] === \CCrmActivityType::Meeting)
		{
			return 'meet';
		}

		if ((int)$activity['TYPE_ID'] === \CCrmActivityType::Email)
		{
			return (int)$activity['DIRECTION'] === \CCrmActivityDirection::Outgoing ? 'mail' : 'mail-send';
		}

		if ($activity['PROVIDER_ID'] === 'CRM_EXTERNAL_CHANNEL')
		{
			return 'onec';
		}

		if ($activity['PROVIDER_ID'] === 'CRM_LF_MESSAGE')
		{
			return 'live-feed';
		}

		if ($activity['PROVIDER_ID'] === 'CRM_WEBFORM')
		{
			return 'form';
		}

		if ($activity['PROVIDER_ID'] === 'IMOPENLINES_SESSION')
		{
			return 'chat';
		}

		if ($activity['PROVIDER_ID'] !== '')
		{
			return mb_strtolower($activity['PROVIDER_ID']);
		}

		return 'no';
	}

	/**
	 * Get all activities.
	 * @return array
	 */
	private function getActivity(): array
	{
		$filter = $this->getFilter();
		if (empty($filter['BINDINGS']))
		{
			return [];
		}

		$return = [];
		$contacts = [];

		$this->prepareActivities($return, $contacts, $filter);
		if ($waiter = $this->getWaiter())
		{
			$return[$waiter['ID']] = $waiter;
		}

		$this->prepareContacts($return, $contacts);

		return $return;
	}

	private function prepareActivities(array &$activities, array &$contacts, array $filter): void
	{
		$sort = [
			'COMPLETED' => 'ASC',
			'PRIORITY' => 'DESC',
			'DEADLINE' => 'ASC',
			'ID' => 'DESC',
		];

		$select = [
			'COMPLETED',
			'ID',
			'OWNER_ID',
			'OWNER_TYPE_ID',
			'ASSOCIATED_ENTITY_ID',
			'COMPLETED',
			'CREATED',
			'DEADLINE',
			'START_TIME',
			'SUBJECT',
			'PROVIDER_ID',
			'PROVIDER_TYPE_ID',
			'TYPE_ID',
			'DIRECTION',
			'IS_INCOMING_CHANNEL',
			'ORIGIN_ID',
			'LIGHT_COUNTER_AT',
		];

		$activityListRaw = \CCrmActivity::GetList($sort, $filter, false, false, $select);
		$activitiesList = [];
		while ($activity = $activityListRaw->getNext()) {
			$activitiesList[] = $activity;
		}
		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$dateFormat = $culture->getShortDateFormat();
		$timeFormat = $culture->getShortTimeFormat();
		$datetimeFormat = $dateFormat . ' ' . $timeFormat;

		foreach ($activitiesList as $activity)
		{
			if (!($activity['PROVIDER'] = \CCrmActivity::GetActivityProvider($activity)))
			{
				continue;
			}
			$activity['SORT'] = [];
			if ($activity['IS_INCOMING_CHANNEL'] === 'Y')
			{
				$activity['SORT'][] = -\Bitrix\Main\Type\DateTime::createFromUserTime($activity['CREATED'])->getTimestamp();
			}
			else
			{
				if (isset($activity['DEADLINE']))
				{
					if (\CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
					{
						$activity['SORT'][] = PHP_INT_MAX;
					}
					else
					{
						$activity['SORT'][] = \Bitrix\Main\Type\DateTime::createFromUserTime($activity['DEADLINE'])->getTimestamp();
					}
				}
				else
				{
					$activity['SORT'][] = PHP_INT_MAX;
				}
			}
			$activity['SORT'][] = (int)$activity['ID'];

			if (isset($activity['DEADLINE']) && \CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
			{
				$activity['DEADLINE'] = '';
			}
			if (($activity['DEADLINE'] ?? '') !== '')
			{
				$activity['DEADLINE'] = DateTime::createFromUserTime($activity['DEADLINE'])->toUserTime()->format($datetimeFormat);
			}

			if (
				$activity['COMPLETED'] === 'N'
				&& $activity['PROVIDER']::canCompleteOnView($activity['PROVIDER_TYPE_ID'])
				&& \CCrmActivity::Complete($activity['ID'])
			)
			{
				$activity['COMPLETED'] = 'Y';
			}

			if ($activity['LIGHT_COUNTER_AT'] && $activity['COMPLETED'] !== 'Y')
			{
				/** @var DateTime $lightCounterAt */
				$lightCounterAt = $activity['LIGHT_COUNTER_AT'];
				$activity['DEADLINED'] = (new DateTime())->getTimestamp() > $lightCounterAt->getTimestamp();
			}
			else
			{
				$activity['DEADLINED'] = false;
			}

			$activity['CONTACTS'] = [];
			$this->appendContactsIds($contacts, $activity['ID']);

			$activity['PROVIDER_TITLE'] = $activity['PROVIDER']::getTypeName(
				$activity['PROVIDER_TYPE_ID'],
				$activity['DIRECTION']
			);

			$priority = (int)($activity['PRIORITY'] ?? null);

			$activity['PROVIDER_ANCHOR'] = (array)$activity['PROVIDER']::getStatusAnchor();
			$activity['ICON'] = $this->getTypeIcon($activity);
			$activity['HIGH'] = $priority === \CCrmActivityPriority::High ? 'Y' : 'N';
			$activity['DETAIL_EXIST'] = $activity['PROVIDER']::hasPlanner($activity);
			$activity['IS_INCOMING_CHANNEL'] = ($activity['IS_INCOMING_CHANNEL'] === 'Y');

			if (
				(int)$activity['TYPE_ID'] === \CCrmActivityType::Provider
			)
			{
				$activity['SUBJECT'] = $activity['PROVIDER']::getActivityTitle($activity);
			}

			$activities[$activity['ID']] = $activity;
		}

		$this->sortActivities($activities);
	}

	private function sortActivities(&$activities): void
	{
		uasort($activities, function ($a, $b) {
			$aSort = $a['SORT'];
			$bSort = $b['SORT'];

			foreach ($aSort as $index => $aValue)
			{
				$bValue = $bSort[$index] ?? 0;

				if ($aValue === $bValue)
				{
					continue;
				}

				return $aValue - $bValue;
			}

			return 0;
		});
	}

	private function getFilter(): array
	{
		$filter = [
			'BINDINGS' => [],
		];

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
				$filter['BINDINGS'][] = [
					'OWNER_ID' => (int)$id,
					'OWNER_TYPE_ID' => $this->typeId,
				];
			}
		}

		return $filter;
	}

	private function appendContactsIds(array &$contacts, int $activityId): void
	{
		$contactTypeId = \CCrmOwnerType::Contact;

		foreach (\CCrmActivity::GetBindings($activityId) as $binding)
		{
			if ((int)$binding['OWNER_TYPE_ID'] === $contactTypeId)
			{
				if (!isset($contacts[$binding['OWNER_ID']]))
				{
					$contacts[$binding['OWNER_ID']] = [];
				}

				$contacts[$binding['OWNER_ID']][] = $activityId;
			}
		}
	}

	private function getWaiter(): ?array
	{
		$id = current($this->ids);
		$waiter = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentByOwner($this->typeId, $id);
		if (!$waiter)
		{
			return null;
		}

		$waiter['ID'] = 'w' . $waiter['ID'];
		$waiter['HIGH'] = 'N';
		$waiter['DETAIL_EXIST'] = false;
		$waiter['CONTACTS'] = [];
		$waiter['SUBJECT'] = nl2br(\htmlspecialcharsbx($waiter['DESCRIPTION']));
		$waiter['ICON'] = $this->getTypeIcon($waiter);

		return $waiter;
	}

	private function prepareContacts(array &$activities, array $contacts): void
	{
		if (empty($contacts))
		{
			return;
		}

		$contactValues = [];
		$path = $this->getEntityPath('contact');
		$select = [
			'ID',
			'NAME',
			'LAST_NAME',
		];

		$contactsList = \CCrmContact::getListEx(
			[],
			['ID' => array_keys($contacts)],
			false,
			false,
			$select
		);

		while ($contact = $contactsList->getNext())
		{
			$contact['TITLE'] = trim($contact['NAME'] . ' ' . $contact['LAST_NAME']);
			$contact['URL'] = str_replace('#contact_id#', $contact['ID'], $path);
			$contactValues[$contact['ID']] = $contact;
		}

		if (!empty($contactValues))
		{
			foreach ($contacts as $cid => $aIds)
			{
				if (isset($contactValues[$cid]))
				{
					foreach ($aIds as $aid)
					{
						$activities[$aid]['CONTACTS'][] = $contactValues[$cid];
					}
				}
			}
		}
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
