<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class PhaseSemantics
{
	public const UNDEFINED = '';
	public const PROCESS = 'P';
	public const SUCCESS = 'S';
	public const FAILURE = 'F';
	//const APOLOGY = 'A';
	private static $messagesLoaded = false;
	private static $descriptions = null;
	/**
	* @return boolean
	*/
	public static function isDefined($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = mb_strtoupper($semanticID);
		return $semanticID === self::PROCESS
			|| $semanticID === self::SUCCESS
			|| $semanticID === self::FAILURE;
	}
	/**
	* @return array Array of strings
	*/
	public static function getProcessSemantis()
	{
		return array(self::PROCESS);
	}
	/**
	* @return array Array of strings
	*/
	public static function getFinalSemantis()
	{
		return array(self::SUCCESS, self::FAILURE);
	}
	/**
	* @return boolean
	*/
	public static function isFinal($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = mb_strtoupper($semanticID);
		return $semanticID === self::SUCCESS || $semanticID === self::FAILURE;
	}

	/**
	 * @return boolean
	 */
	public static function isSuccess($semanticID): bool
	{
		if (!is_string($semanticID))
		{
			return false;
		}

		$semanticID = mb_strtoupper($semanticID);
		return $semanticID === self::SUCCESS;
	}

	/**
	* @return boolean
	*/
	public static function isLost($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = mb_strtoupper($semanticID);
		return $semanticID === self::FAILURE;
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions = array(
				self::UNDEFINED => GetMessage('CRM_PHASE_SEMANTICS_UNDEFINED'),
				self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_PROCESS'),
				self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_SUCCESS'),
				self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_FAILURE')
			);
		}
		return self::$descriptions;
	}

	public static function getListFilterInfo($entityTypeID, array $params = null, $useCommonNames = false)
	{
		if($params === null)
		{
			$params = array();
		}

		self::includeModuleFile();

		if($useCommonNames)
		{
			return array_merge(
				array(
					'type' => 'list',
					'items' => array(
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_FAILURE')
					)
				),
				$params
			);
		}

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			return array_merge(
				array(
					'type' => 'list',
					'items' => array(
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_DEAL_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_DEAL_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_DEAL_FAILURE')
					)
				),
				$params
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Lead)
		{
			return array_merge(
				array(
					'type' => 'list',
					'items' => array(
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_LEAD_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_LEAD_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_LEAD_FAILURE')
					)
				),
				$params
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return array_merge(
				array(
					'type' => 'list',
					'items' => array(
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_QUOTE_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_QUOTE_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_QUOTE_FAILURE')
					)
				),
				$params
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return array_merge(
				array(
					'type' => 'list',
					'items' => array(
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_ORDER_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_ORDER_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_ORDER_FAILURE')
					)
				),
				$params
			);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}
	}

	public static function getEntityDetailInfos(array $entityTypeNames)
	{
		self::includeModuleFile();

		$result = array();
		foreach($entityTypeNames as $entityTypeName)
		{
			if($entityTypeName === \CCrmOwnerType::DealName)
			{
				$result[\CCrmOwnerType::DealName] = array(
					'groupTitle' => GetMessage('CRM_PHASE_SEMANTICS_DEAL_GROUP_TITLE'),
					'selectorTitle' => GetMessage('CRM_PHASE_SEMANTICS_DEAL_SELECTOR_TITLE'),
					'caption' => GetMessage('CRM_PHASE_SEMANTICS_DEAL_CAPTION'),
					'descriptions' => array(
						self::UNDEFINED => GetMessage('CRM_PHASE_SEMANTICS_DEAL_UNDEFINED'),
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_DEAL_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_DEAL_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_DEAL_FAILURE')
					)
				);
			}
			elseif($entityTypeName === \CCrmOwnerType::LeadName)
			{
				$result[\CCrmOwnerType::LeadName] = array(
					'groupTitle' => GetMessage('CRM_PHASE_SEMANTICS_LEAD_GROUP_TITLE'),
					'selectorTitle' => GetMessage('CRM_PHASE_SEMANTICS_LEAD_SELECTOR_TITLE'),
					'caption' => GetMessage('CRM_PHASE_SEMANTICS_LEAD_CAPTION'),
					'descriptions' => array(
						self::UNDEFINED => GetMessage('CRM_PHASE_SEMANTICS_LEAD_UNDEFINED'),
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_LEAD_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_LEAD_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_LEAD_FAILURE')
					)
				);
			}
			elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
			{
				$result[\CCrmOwnerType::InvoiceName] = array(
					'groupTitle' => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_GROUP_TITLE'),
					'selectorTitle' => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_SELECTOR_TITLE'),
					'caption' => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_CAPTION'),
					'descriptions' => array(
						self::UNDEFINED => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_UNDEFINED'),
						self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_PROCESS'),
						self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_SUCCESS'),
						self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_INVOICE_FAILURE')
					)
				);
			}
		}
		return $result;
	}

	/**
	* @return void
	*/
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}