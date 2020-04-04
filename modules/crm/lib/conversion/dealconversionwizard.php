<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class DealConversionWizard extends EntityConversionWizard
{
	/**
	 * @param int $entityID Entity ID.
	 * @param DealConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, DealConversionConfig $config = null)
	{
		$converter = new DealConverter($config);
		$converter->setEntityID($entityID);
		parent::__construct($converter);

	}
	/**
	 * Execute wizard.
	 * @param array|null $contextData Conversion context data.
	 * @return bool
	 */
	public function execute(array $contextData = null)
	{
		/** @var DealConverter $converter */
		$converter = $this->converter;

		if(is_array($contextData) && !empty($contextData))
		{
			$converter->setContextData(array_merge($converter->getContextData(), $contextData));
		}

		$result = false;
		try
		{
			$converter->initialize();
			do
			{
				$converter->executePhase();
			}
			while($converter->moveToNextPhase());

			$resultData = $converter->getResultData();

			if(isset($resultData[\CCrmOwnerType::InvoiceName]))
			{
				if ($this->isMobileContext)
				{
					$this->redirectUrl = "/mobile/crm/invoice/?page=view&invoice_id=".$resultData[\CCrmOwnerType::InvoiceName];
				}
				else
				{
					$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Invoice, $resultData[\CCrmOwnerType::InvoiceName], false);
				}
			}
			elseif(isset($resultData[\CCrmOwnerType::QuoteName]))
			{
				if ($this->isMobileContext)
				{
					$this->redirectUrl = "/mobile/crm/quote/?page=view&quote_id=".$resultData[\CCrmOwnerType::QuoteName];
				}
				else
				{
					$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(
						\CCrmOwnerType::Quote,
						$resultData[\CCrmOwnerType::QuoteName],
						false,
						array('ENABLE_SLIDER' => $this->enableSlider)
					);
				}
			}
			$result = true;
		}
		catch(EntityConversionException $e)
		{
			$this->exception = $e;
			if($e->getTargetType() === EntityConversionException::TARG_DST)
			{
				if ($this->isMobileContext)
				{
					switch($e->getDestinationEntityTypeID())
					{
						case (\CCrmOwnerType::Invoice):
						{
							$this->redirectUrl = "/mobile/crm/invoice/?page=edit&conv_deal_id=".$converter->getEntityID();
							break;
						}
						case (\CCrmOwnerType::Quote):
						{
							$this->redirectUrl = "/mobile/crm/quote/?page=edit&conv_deal_id=".$converter->getEntityID();
							break;
						}
					}
				}
				else
				{
					$config = $converter->getConfig();
					$options = array(
						'ENTITY_SETTINGS' => $config->getEntityInitData($e->getDestinationEntityTypeID()),
						'ENABLE_SLIDER' => $this->enableSlider
					);

					$this->redirectUrl = \CCrmUrlUtil::AddUrlParams(
						\CCrmOwnerType::GetEntityEditPath(
							$e->getDestinationEntityTypeID(),
							0,
							false,
							$options
						),
						array('conv_deal_id' => $converter->getEntityID())
					);
				}
			}
		}
		catch(\Exception $e)
		{
			$this->errorText = $e->getMessage();
		}

		$this->save();
		return $result;
	}
	/**
	 * Prepare entity fields for edit.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @param bool|true $encode Encode fields flag.
	 * @return void
	 */
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
		/** @var DealConverter $converter */
		$converter = $this->converter;

		$userFields = DealConversionMapper::getUserFields($entityTypeID);
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('ENABLE_FILES' => false));

		foreach($mappedFields as $k => $v)
		{
			if($k === 'PRODUCT_ROWS' || $k === 'CONTACT_BINDINGS')
			{
				$fields[$k] = $v;
				continue;
			}
			elseif(strpos($k, 'UF_CRM') === 0)
			{
				$userField = isset($userFields[$k]) ? $userFields[$k] : null;
				if(is_array($userField))
				{
					// hack for UF
					if($userField['USER_TYPE_ID'] === 'file')
					{
						$GLOBALS["{$k}_old_id"] = $v;
					}
					elseif(!isset($GLOBALS[$k]))
					{
						$GLOBALS[$k] = $_REQUEST[$k] = $v;
					}
				}
			}
			elseif($encode)
			{
				$fields["~{$k}"] = $v;
				if(!is_array($v))
				{
					$fields[$k] = htmlspecialcharsbx($v);
				}
			}
		}
	}
	/**
	 * Prepare entity fields for save.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @return void
	 */
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
		$dstUserFields = DealConversionMapper::getUserFields($entityTypeID);
		foreach($dstUserFields as $dstName => $dstField)
		{
			if($dstField['USER_TYPE_ID'] === 'file')
			{
				$this->prepareFileUserFieldForSave($dstName, $dstField, $fields);
			}
		}

		$mappedFields = $this->converter->mapEntityFields($entityTypeID, array('DISABLE_USER_FIELD_INIT' => true));
		foreach($mappedFields as $k => $v)
		{
			if(!isset($fields[$k]))
			{
				$fields[$k] = $v;
			}
		}
	}
	/**
	 * Save wizard settings in session.
	 * @return void
	 */
	public function save()
	{
		if(!isset($_SESSION['DEAL_CONVERTER']))
		{
			$_SESSION['DEAL_CONVERTER'] = array();
		}

		$_SESSION['DEAL_CONVERTER'][$this->getEntityID()] = $this->externalize();
	}

	/**
	 * Load wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return DealConversionWizard|null
	 */
	public static function load($entityID)
	{
		if(!(isset($_SESSION['DEAL_CONVERTER']) && $_SESSION['DEAL_CONVERTER'][$entityID]))
		{
			return null;
		}

		$item = new DealConversionWizard($entityID);
		$item->internalize($_SESSION['DEAL_CONVERTER'][$entityID]);
		return $item;
	}
	/**
	 * Remove wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public static function remove($entityID)
	{
		if(isset($_SESSION['DEAL_CONVERTER']) && $_SESSION['DEAL_CONVERTER'][$entityID])
		{
			unset($_SESSION['DEAL_CONVERTER'][$entityID]);
		}
	}
}