<?php
namespace Bitrix\Crm\Kanban;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

class SupervisorTable extends Entity\DataManager
{

	const TTL_ACTIVITY = 1200;

	protected static $pathMarkers = array('#lead_id#', '#contact_id#', '#company_id#', '#deal_id#', '#quote_id#', '#invoice_id#');
	protected static $avatarSize = array('width' => 38, 'height' => 38);

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_kanban_supervisor';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'ENTITY_TYPE_ID' => new Entity\IntegerField('ENTITY_TYPE_ID', array(
				'required' => true,
			)),
			'USER_ID' => new Entity\IntegerField('USER_ID', array(
				'required' => true,
			)),
			'USER' => new Entity\ReferenceField(
				'USER',
				'\Bitrix\Main\UserTable',
				array('=this.USER_ID' => 'ref.ID')
			),
		);
	}

	/**
	 * Become supervisor for current user.
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * @param boolean $set Set or unset.
	 * @return void
	 */
	public static function set($type, $set)
	{
		if ($type = \CCrmOwnerType::ResolveID($type))
		{
			$uid = \CCrmSecurityHelper::GetCurrentUserID();
			$exist = self::getList(array('filter' => array(
				'ENTITY_TYPE_ID' => $type,
				'USER_ID' => $uid
			)))->fetch();
			if ($set && !$exist)
			{
				self::add(array(
					'ENTITY_TYPE_ID' => $type,
					'USER_ID' => $uid
				));
			}
			elseif (!$set && $exist)
			{
				self::delete($exist['ID']);
			}
		}
	}

	/**
	 * Current user is supervisor for the type?
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * @return boolean
	 */
	public static function isSupervisor($type)
	{
		if ($type = \CCrmOwnerType::ResolveID($type))
		{
			return self::getList(array(
					'filter' => array(
						'ENTITY_TYPE_ID' => $type,
						'USER_ID' => \CCrmSecurityHelper::GetCurrentUserID()
					)))->fetch() ? true : false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get list of supervisors.
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * return array
	 */
	public static function getSupervisors($type)
	{
		$users = array();
		if ($type = \CCrmOwnerType::ResolveID($type))
		{
			$res = self::getList(array(
						'filter' => array(
							'ENTITY_TYPE_ID' => $type
						),
						'select' => array(
							'ID', 'USER_ID', 'LAST_ACTIVITY_DATE' => 'USER.LAST_ACTIVITY_DATE',
							new Entity\ExpressionField('NOW_TIME', 'UNIX_TIMESTAMP(NOW())')
						)));
			while ($row = $res->fetch())
			{
				$row['LAST_ACTIVITY_DATE'] = $row['LAST_ACTIVITY_DATE']->getTimestamp();
				if ($row['NOW_TIME'] - $row['LAST_ACTIVITY_DATE'] > self::TTL_ACTIVITY)
				{
					self::delete($row['ID']);
				}
				else
				{
					$users[] = $row['USER_ID'];
				}
			}
		}

		return $users;
	}

	/**
	 * Send item to supervisor.
	 * @param int $id Id of item.
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * @param string $command Command for pull.
	 * @return void
	 */
	public static function sendItem($id, $type, $command)
	{
		$provider = '\CCrm' . $type;
		if (
			$id>0 && class_exists($provider) && method_exists($provider, 'CheckReadPermission') &&
			(method_exists($provider, 'getListEx') || method_exists($provider, 'getList')) &&
			\Bitrix\Main\Loader::includeModule('pull')
		)
		{
			$users = self::getSupervisors($type);
			if (!empty($users))
			{
				$send = array(
					'users' => array(),
					'info' => array()
				);
				//collect allowed subscribers
				foreach ($users as $uid)
				{
					$userPerms = \CCrmPerms::GetUserPermissions($uid);
					if ($provider::CheckReadPermission($id, $userPerms))
					{
						$send['users'][] = $uid;
					}
				}
				//get info and send
				if (!empty($send['users']))
				{
					$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
					$select = array('*');
					if ($priceField = self::getRedefinedPriceField($type))
					{
						$select[] = $priceField;
					}
					$row = $provider::$method(array(), array('ID' => $id, 'CHECK_PERMISSIONS' => 'N'), false, false, $select)->fetch();
					$row = self::buildItem($row, $type);
					$timeOffset = time() + \CTimeZone::GetOffset();
					$send['info'] = array(
						'id' =>  $id,
						'name' => htmlspecialcharsbx($row['TITLE']),
						'link' => self::getUrl($id, $type),
						'columnId' => $row['STATUS_ID'],
						'columnColor' => '',
						'price' => $row['PRICE'],
						'price_formatted' => $row['PRICE_FORMATTED'],
						'date' => (
									!$row['FORMAT_TIME']
									? \FormatDate('j M Y', $row['DATE_UNIX'], $timeOffset)
									: (
										(time() - $row['DATE_UNIX']) / 3600 > 48
										? \FormatDate('j M Y, H:i', $row['DATE_UNIX'], $timeOffset)
										: \FormatDate('x', $row['DATE_UNIX'], $timeOffset)
									)
								),
						'contactId' => (int)$row['CONTACT_ID'],
						'contactType' => $row['CONTACT_TYPE'],
						'contactName' => $row['CONTACT_NAME'],
						'contactLink' => $row['CONTACT_LINK'],
						'modifyById' => $row['MODIFY_BY_ID'],
						'modifyByAvatar' => isset($row['MODIFY_PERSONAL_PHOTO']) ? $row['MODIFY_PERSONAL_PHOTO']['src'] : '',
						'activityShow' => $row['ACTIVITY_SHOW'],
						'activityProgress' => 0,
						'activityTotal' => 0,
						'page' => 0,
						'pageCount' => 0,
						'fields' => array()
					);
					if (!empty($row['FM_VALUES']))
					{
						$send['info'] = array_merge($send['info'], $row['FM_VALUES']);
					}
					\Bitrix\Pull\Event::add($send['users'], array(
						'module_id' => 'crm',
						'command' => $command,
						'params' => $send['info']
					));
				}
			}
		}
	}

	/**
	 * Get additional and standartinize one item.
	 * @param array $row Entity row.
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * @return array
	 */
	protected static function buildItem(array $row, $type)
	{
		$currency = \CCrmCurrency::GetAccountCurrencyID();
		$row['FORMAT_TIME'] = true;
		//base
		if ($type == \CCrmOwnerType::LeadName)
		{
			$row['ACTIVITY_SHOW'] = 1;
			$row['PRICE'] = $row['OPPORTUNITY'];
			$row['DATE'] = $row['DATE_CREATE'];
		}
		elseif ($type == \CCrmOwnerType::DealName)
		{
			$row['STATUS_ID'] = $row['STAGE_ID'];
			$row['ACTIVITY_SHOW'] = 1;
			$row['PRICE'] = $row['OPPORTUNITY'];
			if ($row['BEGINDATE'])
			{
				$row['FORMAT_TIME'] = false;
				$row['DATE'] = $row['BEGINDATE'];
			}
			else
			{
				$row['DATE'] = $row['DATE_CREATE'];
			}
		}
		elseif ($type == \CCrmOwnerType::QuoteName)
		{
			$row['ACTIVITY_SHOW'] = 0;
			$row['PRICE'] = $row['OPPORTUNITY'];
			if ($row['BEGINDATE'])
			{
				$row['FORMAT_TIME'] = false;
				$row['DATE'] = $row['BEGINDATE'];
			}
			else
			{
				$row['DATE'] = $row['DATE_CREATE'];
			}
		}
		elseif ($type == \CCrmOwnerType::InvoiceName)
		{
			$row['ACTIVITY_SHOW'] = 0;
			$row['TITLE'] = $row['ORDER_TOPIC'];
			$row['PRICE'] = $row['PRICE'];
			$row['FORMAT_TIME'] = false;
			$row['DATE'] = $row['DATE_BILL'] ? $row['DATE_BILL'] : $row['DATE_INSERT_FORMAT'];
			$row['CONTACT_ID'] = $row['UF_CONTACT_ID'];
			$row['COMPANY_ID'] = $row['UF_COMPANY_ID'];
			$row['CURRENCY_ID'] = $row['CURRENCY'];
		}
		if (!isset($row['MODIFY_BY_ID']))
		{
			$row['MODIFY_BY_ID'] = 0;
		}
		if ($row['MODIFY_BY_ID'] > 0)
		{
			$res = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID', 'PERSONAL_PHOTO'),
				'filter' => array('ID' => $row['MODIFY_BY_ID'])
			));
			if ($user = $res->fetch())
			{
				$row['MODIFY_PERSONAL_PHOTO'] = \CFile::ResizeImageGet($user['PERSONAL_PHOTO'], self::$avatarSize, BX_RESIZE_IMAGE_EXACT);
				if (!$row['MODIFY_PERSONAL_PHOTO'])
				{
					unset($row['MODIFY_PERSONAL_PHOTO']);
				}
			}
		}
		//redefine price
		if ($priceField = self::getRedefinedPriceField($type))
		{
			$row['PRICE'] = $row[$priceField];
		}
		elseif (isset($row['OPPORTUNITY_ACCOUNT']) && $row['OPPORTUNITY_ACCOUNT']!='')
		{
			$row['PRICE'] = $row['OPPORTUNITY_ACCOUNT'];
		}
		if (isset($row['ACCOUNT_CURRENCY_ID']) && $row['ACCOUNT_CURRENCY_ID']!='')
		{
			$row['CURRENCY_ID'] = $row['ACCOUNT_CURRENCY_ID'];
		}
		//price converted
		if ($row['CURRENCY_ID']=='' || $row['CURRENCY_ID'] == $currency)
		{
			$row['PRICE'] = doubleval($row['PRICE']);
			$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
		}
		else
		{
			$row['PRICE'] = \CCrmCurrency::ConvertMoney($row['PRICE'], $row['CURRENCY_ID'], $currency);
			$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
		}
		//contragent
		if ($row['CONTACT_ID'] > 0)
		{
			$row['CONTACT_TYPE'] = 'CONTACT';
			$contragentProvider = 'CCrmContact';
		}
		elseif ($row['COMPANY_ID'] > 0)
		{
			$row['CONTACT_TYPE'] = 'COMPANY';
			$row['CONTACT_ID'] = $row['COMPANY_ID'];
			$contragentProvider = 'CCrmCompany';
		}
		else
		{
			$row['CONTACT_TYPE'] = '';
		}
		if ($row['CONTACT_ID'] > 0)
		{
			//name
			$contragent = $contragentProvider::getListEx(array(),
														array('ID' => $row['CONTACT_ID'], 'CHECK_PERMISSIONS' => 'N'),
														false, false, array('ID', 'NAME', 'LAST_NAME', 'TITLE'))->fetch();
			if ($contragent)
			{
				if (!array_key_exists('TITLE', $contragent) && array_key_exists('NAME', $contragent) && array_key_exists('LAST_NAME', $contragent))
				{
					$row['CONTACT_NAME'] = trim($contragent['NAME'] . ' ' . $contragent['LAST_NAME']);
				}
				else
				{
					$row['CONTACT_NAME'] = $contragent['TITLE'];
				}
				$row['CONTACT_LINK'] = self::getUrl($contragent['ID'], $row['CONTACT_TYPE']);
			}
			//contacts
			$langMess = array();
			$res = \CCrmFieldMulti::GetListEx(array(), array(
															'ENTITY_ID' => $row['CONTACT_TYPE'],
															'ELEMENT_ID' => $row['CONTACT_ID']));
			while ($fm = $res->fetch())
			{
				$fm['TYPE_ID'] = strtolower($fm['TYPE_ID']);
				if (!in_array($fm['TYPE_ID'], array('phone', 'email', 'im')))
				{
					continue;
				}
				if ($fm['TYPE_ID'] == 'im' && strpos($fm['VALUE'], 'imol|') !== 0)
				{
					continue;
				}
				if (!isset($row['FM_VALUES']))
				{
					$row['FM_VALUES'] = array();
				}
				if (!isset($row['FM_VALUES'][$fm['TYPE_ID']]))
				{
					$row['FM_VALUES'][$fm['TYPE_ID']] = array();
				}
				if (empty($langMess))
				{
					Loc::loadMessages('/bitrix/components/bitrix/crm.kanban/class.php');
					$langMess = array(
						'EMAIL_WORK' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_WORK'),
						'EMAIL_HOME' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_HOME'),
						'EMAIL_OTHER' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_OTHER'),
						'PHONE_MOBILE' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_MOBILE'),
						'PHONE_WORK' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_WORK'),
						'PHONE_FAX' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_FAX'),
						'PHONE_HOME' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_HOME'),
						'PHONE_PAGER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_PAGER'),
						'PHONE_OTHER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_OTHER'),
					);
				}
				$row['FM_VALUES'][$fm['TYPE_ID']][] = array(
					'value' => htmlspecialcharsbx($fm['VALUE']),
					'title' => $langMess[$fm['COMPLEX_ID']]
				);
			}

			$row['CONTACT_TYPE'] = 'CRM_'.$row['CONTACT_TYPE'];
		}
		$row['DATE_UNIX'] = \makeTimeStamp($row['DATE']);

		return $row;
	}

	/**
	 * Get url for entity.
	 * @param int $id Entity id.
	 * @param string $type Type of entity.
	 * @return string
	 */
	protected function getUrl($id, $type)
	{
		return str_replace(self::$pathMarkers, $id, \CrmCheckPath('PATH_TO_'.strtoupper($type).'_SHOW', '', ''));
	}

	/**
	 * Get redefined field for price.
	 * @param string $type Entity type: LEAD, DEAL, QUOTE, INVOICE.
	 * @return string
	 */
	protected static function getRedefinedPriceField($type)
	{
		static $prices = array();

		if (!array_key_exists($type, $prices) && $type != \CCrmOwnerType::QuoteName)
		{
			$prices[$type] = '';
			$slots = \Bitrix\Crm\Statistics\StatisticEntryManager::prepareSlotBingingData($type .  '_SUM_STATS');
			if (is_array($slots) && isset($slots['SLOT_BINDINGS']) && is_array($slots['SLOT_BINDINGS']))
			{
				foreach ($slots['SLOT_BINDINGS'] as $slot)
				{
					if ($slot['SLOT'] == 'SUM_TOTAL')
					{
						$prices[$type] = $slot['FIELD'];
					}
				}
			}
		}

		return $prices[$type];
	}
}