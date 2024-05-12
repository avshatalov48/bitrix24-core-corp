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

			$this->redirectUrl = (string)$this->getRedirectUrlByResultData($resultData);

			$result = true;
		}
		catch(EntityConversionException $e)
		{
			$this->exception = $e;

			$uri = $this->getRedirectUrlByConversionException($e)?->addParams([
				static::QUERY_PARAM_SRC_ID => $converter->getEntityID()
			]);

			$this->redirectUrl = (string)$uri;
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
