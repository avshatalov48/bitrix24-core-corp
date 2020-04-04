<?php
namespace Bitrix\Crm\Data;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Format;

class DataGenerator
{
	private $id = '';
	public function __construct($id)
	{
		$this->setId($id);
	}
	public function getId()
	{
		return $this->id;
	}
	public function setId($id)
	{
		$this->id = $id;
	}
	protected static function getRandomItem(array $ary, $default = null)
	{
		$qty = count($ary);
		if($qty === 0)
		{
			return $default;
		}
		if($qty === 1)
		{
			return $ary[0];
		}

		return $ary[mt_rand(0, $qty - 1)];
	}
	protected static function getEntityMultifieldValues($entityTypeID, $entityID, $typeID)
	{
		$results = array();
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'TYPE_ID' => $typeID,
				'ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID
			)
		);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[] = $fields['VALUE'];
			}
		}
		return $results;
	}
	protected static function getCompanyInfo($companyID)
	{
		if($companyID <= 0)
		{
			return array();
		}

		$result = array(
			'TITLE' => '',
			'FULL_ADDRESS' => '',
			'PHONE' => '',
			'EMAIL' => ''
		);
		$dbRes = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $companyID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('TITLE', 'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_POSTAL_CODE', 'ADDRESS_REGION', 'ADDRESS_PROVINCE', 'ADDRESS_COUNTRY')
		);

		$fields = is_object($dbRes) ? $dbRes->Fetch() : null;
		if(is_array($fields))
		{
			$result['TITLE'] = isset($fields['TITLE']) ? $fields['TITLE'] : '';
			$result['FULL_ADDRESS'] = Format\CompanyAddressFormatter::format(
				$fields,
				array('SEPARATOR' => Format\AddressSeparator::NewLine)
			);

			$dbRes = \CCrmFieldMulti::GetListEx(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyID, '@TYPE_ID' => array('PHONE', 'EMAIL')),
				false,
				false,
				array('TYPE_ID', 'VALUE')
			);
			while($multiFields = $dbRes->Fetch())
			{
				if($result['PHONE'] === '' && $multiFields['TYPE_ID'] === 'PHONE')
				{
					$result['PHONE'] = $multiFields['VALUE'];
				}
				elseif($result['EMAIL'] === '' && $multiFields['TYPE_ID'] === 'EMAIL')
				{
					$result['EMAIL'] = $multiFields['VALUE'];
				}

				if($result['PHONE'] !== '' && $result['EMAIL'] !== '')
				{
					break;
				}
			}
		}
		return $result;
	}
	protected static function getContactInfo($contactID)
	{
		if($contactID <= 0)
		{
			return array();
		}

		$result = array(
			'FULL_NAME' => '',
			'FULL_ADDRESS' => '',
			'PHONE' => '',
			'EMAIL' => ''
		);
		$dbRes = \CCrmContact::GetListEx(
			array(),
			array('=ID' => $contactID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('NAME', 'SECOND_NAME', 'LAST_NAME', 'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_POSTAL_CODE', 'ADDRESS_REGION', 'ADDRESS_PROVINCE', 'ADDRESS_COUNTRY')
		);
		$fields = is_object($dbRes) ? $dbRes->Fetch() : null;
		if(is_array($fields))
		{
			$result['FULL_NAME'] = \CCrmContact::PrepareFormattedName($fields);
			$result['FULL_ADDRESS'] = Format\ContactAddressFormatter::format(
				$fields,
				array('SEPARATOR' => Format\AddressSeparator::NewLine)
			);

			$dbRes = \CCrmFieldMulti::GetListEx(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactID, '@TYPE_ID' => array('PHONE', 'EMAIL')),
				false,
				false,
				array('TYPE_ID', 'VALUE')
			);
			while($multiFields = $dbRes->Fetch())
			{
				if($result['PHONE'] === '' && $multiFields['TYPE_ID'] === 'PHONE')
				{
					$result['PHONE'] = $multiFields['VALUE'];
				}
				elseif($result['EMAIL'] === '' && $multiFields['TYPE_ID'] === 'EMAIL')
				{
					$result['EMAIL'] = $multiFields['VALUE'];
				}

				if($result['PHONE'] !== '' && $result['EMAIL'] !== '')
				{
					break;
				}
			}
		}
		return $result;
	}
	public function createDeals(array $params)
	{
		$date = isset($params['DATE']) ? $params['DATE'] : null;
		if(!$date)
		{
			$date = $date = new Date();
		}
		$dateFormat = Date::convertFormatToPhp(FORMAT_DATE);

		$count = isset($params['COUNT']) ? (int)$params['COUNT'] : 0;
		if($count <= 0)
		{
			return;
		}

		$duration = isset($params['DURATION']) ? (int)$params['DURATION'] : 0;
		if($duration <= 0)
		{
			$duration = 7;
		}

		$probability = isset($params['PROBABILITY']) ? (int)$params['PROBABILITY'] : 0;
		if($probability <= 0)
		{
			$probability = 10;
		}

		$typeID = isset($params['TYPE_ID']) ? $params['TYPE_ID'] : '';
		if($typeID === '')
		{
			$typeID = 'SALE';
		}

		$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
		if($prefix === '')
		{
			$prefix = $this->id;
		}

		$stageIDs = isset($params['STAGE_IDS']) && is_array($params['STAGE_IDS']) ? $params['STAGE_IDS'] : array();
		if(empty($stageIDs))
		{
			$stageIDs[] = 'NEW';
		}
		$stageCount = count($stageIDs);

		$userIDs = isset($params['USER_IDS']) && is_array($params['USER_IDS']) ? $params['USER_IDS'] : array();
		if(empty($userIDs))
		{
			$userIDs[] = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$currencyID = isset($params['CURRENCY_ID']) ? $params['CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		}

		$minSum = isset($params['MIN_SUM']) ? (int)$params['MIN_SUM'] : 0;
		if($minSum <= 0)
		{
			$minSum = 100;
		}

		$maxSum = isset($params['MAX_SUM']) ? (int)$params['MAX_SUM'] : 0;
		if($maxSum <= 0)
		{
			$maxSum = 1000;
		}

		$clientInfos = isset($params['CLIENT_INFOS']) && is_array($params['CLIENT_INFOS']) ? $params['CLIENT_INFOS'] : array();

		$callCount = isset($params['CALL_COUNT']) ? (int)$params['CALL_COUNT'] : 0;
		$meetingCount = isset($params['MEETING_COUNT']) ? (int)$params['MEETING_COUNT'] : 0;
		$invoiceCount = isset($params['INVOICE_COUNT']) ? (int)$params['INVOICE_COUNT'] : 0;

		$number = isset($params['NUMBER']) ? (int)$params['NUMBER'] : 0;
		if($number <= 0)
		{
			$number = 0;
		}

		$dealEntity = new \CCrmDeal(false);
		for($i = 0; $i < $count; $i++)
		{
			$number++;
			$title = "{$prefix} deal # {$number}";
			$beginDate = clone $date;
			$opportunity =  mt_rand($minSum, $maxSum);
			$fields = array(
				'TITLE' => $title,
				'TYPE_ID' => $typeID,
				'STAGE_ID' => $stageIDs[0],
				'PROBABILITY' => $probability,
				'CURRENCY_ID' => $currencyID,
				'OPPORTUNITY' => $opportunity,
				'ASSIGNED_BY_ID' => self::getRandomItem($userIDs),
				'BEGINDATE' => $beginDate->format($dateFormat),
				'CLOSEDATE' => $beginDate->add("{$duration} days")->format($dateFormat),
				'ORIGINATOR_ID' => $this->id
			);

			$contactID = 0;
			$companyID = 0;

			$clientInfo = self::getRandomItem($clientInfos);
			if(is_array($clientInfo))
			{
				if(isset($clientInfo['CONTACT_ID']))
				{
					$contactID = $clientInfo['CONTACT_ID'];
				}

				if(isset($clientInfo['COMPANY_ID']))
				{
					$companyID = $clientInfo['COMPANY_ID'];
				}
			}

			if($contactID > 0)
			{
				$fields['CONTACT_ID'] = $contactID;
			}

			if($companyID > 0)
			{
				$fields['COMPANY_ID'] = $companyID;
			}

			$ID = $dealEntity->Add($fields, true, array('ENABLE_CLOSE_DATE_SYNC' => false));

			$lastStage = $stageIDs[$stageCount - 1];
			if($stageCount > 1)
			{
				$lastStageIndex = mt_rand(1, ($stageCount - 1));
				$lastStage = $stageIDs[$lastStageIndex];
				for($j = 1; $j <= $lastStageIndex; $j++)
				{
					$fields = array('STAGE_ID' => $stageIDs[$j]);
					$dealEntity->Update($ID, $fields, true, true, array('ENABLE_CLOSE_DATE_SYNC' => false));
				}
			}

			$clientTypeID = \CCrmOwnerType::Undefined;
			$clientID = 0;
			if($contactID > 0)
			{
				$clientTypeID = \CCrmOwnerType::Contact;
				$clientID = $contactID;
			}
			elseif($companyID > 0)
			{
				$clientTypeID = \CCrmOwnerType::Company;
				$clientID = $companyID;
			}

			if($clientID > 0)
			{
				if($callCount > 0)
				{
					$this->createActivities(
						array(
							'COUNT' => $callCount,
							'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
							'OWNER_ID' => $ID,
							'SUBJECT_PREFIX' => "Call for {$title}",
							'DATE' => $date,
							'MAX_DATE_OFFSET' => $duration,
							'CLIENT_TYPE_ID' => $clientTypeID,
							'CLIENT_ID' => $clientID,
							'TYPE_ID' => \CCrmActivityType::Call,
							'USER_IDS' => $userIDs
						)
					);
				}

				if($meetingCount > 0)
				{
					$this->createActivities(
						array(
							'COUNT' => $meetingCount,
							'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
							'OWNER_ID' => $ID,
							'SUBJECT_PREFIX' => "Meeting for {$title}",
							'DATE' => $date,
							'MAX_DATE_OFFSET' => $duration,
							'CLIENT_TYPE_ID' => $clientTypeID,
							'CLIENT_ID' => $clientID,
							'TYPE_ID' => \CCrmActivityType::Meeting,
							'USER_IDS' => $userIDs
						)
					);
				}
			}

			if($invoiceCount > 0)
			{
				$this->createInvoices(
					array(
						'COUNT' => $invoiceCount,
						'SUM' => $opportunity,
						'DEAL_ID' => $ID,
						'CONTACT_ID' => $contactID,
						'COMPANY_ID' => $companyID,
						'USER_IDS' => $userIDs,
						'PREFIX' => $prefix,
						'DATE' => $date,
						'MAX_DATE_OFFSET' => $duration,
						'IS_WON' => $lastStage === 'WON'
					)
				);
			}
		}
	}
	public function createActivities(array $params)
	{
		$count = isset($params['COUNT']) ? (int)$params['COUNT'] : 0;
		if($count <= 0)
		{
			return;
		}

		$typeID = isset($params['TYPE_ID']) ? (int)$params['TYPE_ID'] : \CCrmActivityType::Undefined;
		if(!\CCrmActivityType::IsDefined($typeID))
		{
			return;
		}

		$ownerTypeID = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		if(!\CCrmOwnerType::IsDefined($ownerTypeID))
		{
			return;
		}

		$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			return;
		}

		$clientTypeID = isset($params['CLIENT_TYPE_ID']) ? (int)$params['CLIENT_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$clientID = isset($params['CLIENT_ID']) ? (int)$params['CLIENT_ID'] : 0;
		if(!\CCrmOwnerType::IsDefined($clientTypeID) || $clientID <= 0)
		{
			$clientTypeID = $ownerTypeID;
			$clientID = $ownerID;
		}

		$commType = $typeID === \CCrmActivityType::Call ? 'PHONE' : '';
		$values = array();
		if($commType === 'PHONE' || $commType === 'EMAIL')
		{
			$values = self::getEntityMultifieldValues($clientTypeID, $clientID, $commType);
			if(empty($values))
			{
				return;
			}
		}

		$userIDs = isset($params['USER_IDS']) && is_array($params['USER_IDS']) ? $params['USER_IDS'] : array();
		if(empty($userIDs))
		{
			$userIDs[] = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$date = isset($params['DATE']) ? $params['DATE'] : null;
		if(!$date)
		{
			$date = $date = new Date();
		}
		$maxDateOffset = isset($params['MAX_DATE_OFFSET']) ? (int)$params['MAX_DATE_OFFSET'] : 0;
		$dateTimeFormat = Date::convertFormatToPhp(FORMAT_DATETIME);

		$subjectPrefix = isset($params['SUBJECT_PREFIX']) ? $params['SUBJECT_PREFIX'] : '';

		for($i = 0; $i < $count; $i++)
		{
			$time = DateTime::createFromTimestamp($date->getTimestamp());
			if($maxDateOffset > 0)
			{
				$time->add(mt_rand(0, $maxDateOffset) . ' days');
			}
			$time->setTime(mt_rand(8, 20), mt_rand(0, 59), 0);
			$siteTime = $time->format($dateTimeFormat);

			$fields = array(
				'TYPE_ID' =>  $typeID,
				'START_TIME' => $siteTime,
				'END_TIME' => $siteTime,
				'SUBJECT' => "{$subjectPrefix} ({$siteTime})",
				'COMPLETED' => (mt_rand(1, 10) % 2) !== 0 ? 'Y' : 'N',
				'PRIORITY' => \CCrmActivityPriority::Medium,
				'DESCRIPTION' => '',
				'DESCRIPTION_TYPE' => \CCrmContentType::PlainText,
				'LOCATION' => '',
				'DIRECTION' =>  \CCrmActivityDirection::Outgoing,
				'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
				'RESPONSIBLE_ID' => self::getRandomItem($userIDs),
				'OWNER_ID' => $ownerID,
				'OWNER_TYPE_ID' => $ownerTypeID,
				'BINDINGS' => array(
					array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID)
				)
			);

			$ID = \CCrmActivity::Add($fields, false, true, array('REGISTER_SONET_EVENT' => true));
			$comms = array(
				array(
					'TYPE' => $commType,
					'VALUE' => self::getRandomItem($values, ''),
					'ENTITY_ID' => $clientID,
					'ENTITY_TYPE_ID' => $clientTypeID
				)
			);
			\CCrmActivity::SaveCommunications($ID, $comms, $fields, false, false);
		}
	}
	public function createInvoices(array $params)
	{
		$count = isset($params['COUNT']) ? (int)$params['COUNT'] : 0;

		if($count <= 0)
		{
			return;
		}

		$sum = isset($params['SUM']) ? (int)$params['SUM'] : 0;
		if($sum <= 0)
		{
			return;
		}

		$dealID = isset($params['DEAL_ID']) ? (int)$params['DEAL_ID'] : 0;
		$companyID = isset($params['COMPANY_ID']) ? (int)$params['COMPANY_ID'] : 0;
		$contactID = isset($params['CONTACT_ID']) ? (int)$params['CONTACT_ID'] : 0;

		$userIDs = isset($params['USER_IDS']) && is_array($params['USER_IDS']) ? $params['USER_IDS'] : array();
		if(empty($userIDs))
		{
			$userIDs[] = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
		if($prefix === '')
		{
			$prefix = $this->id;
		}

		$date = isset($params['DATE']) ? $params['DATE'] : null;
		if(!$date)
		{
			$date = $date = new Date();
		}
		$maxDateOffset = isset($params['MAX_DATE_OFFSET']) ? (int)$params['MAX_DATE_OFFSET'] : 0;
		$dateFormat = Date::convertFormatToPhp(FORMAT_DATE);
		$dateTimeFormat = Date::convertFormatToPhp(FORMAT_DATETIME);

		$isWon = isset($params['IS_WON']) ? $params['IS_WON'] : false;
		if($isWon)
		{
			$totalSum = $sum;
		}
		else
		{
			$totalSum = $sum - mt_rand((int)($sum/3), $sum);
		}

		$entity = new \CCrmInvoice(false);
		$invoiceSum = (int)$totalSum/$count;
		$totalInvoiceSum = 0;
		for($i = 1; $i <= $count; $i++)
		{
			if($i == $count)
			{
				$invoiceSum = $totalSum - $totalInvoiceSum;
			}
			$totalInvoiceSum += $invoiceSum;

			$time = DateTime::createFromTimestamp($date->getTimestamp());
			if($maxDateOffset > 0)
			{
				$time->add(mt_rand(0, $maxDateOffset) . ' days');
			}
			$time->setTime(mt_rand(8, 20), mt_rand(0, 59), 0);
			$siteTime = $time->format($dateTimeFormat);
			$siteDate = $time->format($dateFormat);

			\CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $companyID, false);
			$companyInfo = self::getCompanyInfo($companyID);
			$contactInfo = self::getContactInfo($contactID);

			$fields = array(
				'ORDER_TOPIC' => "{$prefix} invoice # {$i}",
				'STATUS_ID' => $isWon ? 'P' : 'N',
				'DATE_INSERT' => $siteTime,
				'DATE_BILL' => $siteDate,
				'RESPONSIBLE_ID' => self::getRandomItem($userIDs),
				'UF_DEAL_ID' => $dealID,
				'UF_COMPANY_ID' => $companyID,
				'UF_CONTACT_ID' => $contactID,
				'PERSON_TYPE_ID' => 1,
				'PAY_SYSTEM_ID' => 1,
				'INVOICE_PROPERTIES' => array(
					10 => $companyInfo['TITLE'],
					11 => $companyInfo['FULL_ADDRESS'],
					12 => $contactInfo['FULL_NAME'],
					13 => $contactInfo['EMAIL'],
					14 => $contactInfo['PHONE'],
				),
				'PRODUCT_ROWS' => array(
					array(
						'ID' => 0,
						'PRODUCT_NAME' => "{$prefix} product",
						'QUANTITY' => 1,
						'PRICE' => $invoiceSum,
						'PRODUCT_ID' => 0,
						'CUSTOMIZED' => 'Y'
					)
				),
			);
			$ID = $entity->Add($fields);
		}
	}
}