<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\ItemIdentifier;

class DealConversionWizard extends EntityConversionWizard
{
	public const QUERY_PARAM_SRC_ID = 'conv_deal_id';

	/**
	 * @param int $entityID Entity ID.
	 * @param DealConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, EntityConversionConfig $config = null)
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
		if ($this->isNewApi())
		{
			return parent::execute($contextData);
		}

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
						array(static::QUERY_PARAM_SRC_ID => $converter->getEntityID())
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
			elseif(mb_strpos($k, 'UF_CRM') === 0)
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
	 * Load wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return DealConversionWizard|null
	 */
	public static function load($entityID)
	{
		$storage = self::getSessionLocalStorage(\CCrmOwnerType::Deal);
		if (!$storage)
		{
			return null;
		}

		$externalizedWizardParams = $storage[$entityID] ?? null;
		if (!is_array($externalizedWizardParams))
		{
			return null;
		}

		$item = new DealConversionWizard($entityID);
		$item->internalize($externalizedWizardParams);
		return $item;
	}

	/**
	 * @inheritDoc
	 */
	public static function remove($entityID)
	{
		if ($entityID > 0)
		{
			static::removeByIdentifier(new ItemIdentifier(\CCrmOwnerType::Deal, (int)$entityID));
		}
	}
}
