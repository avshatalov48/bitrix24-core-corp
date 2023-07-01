<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\ItemIdentifier;

class QuoteConversionWizard extends EntityConversionWizard
{
	public const QUERY_PARAM_SRC_ID = 'conv_quote_id';

	/**
	 * @param int $entityID Entity ID.
	 * @param QuoteConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, EntityConversionConfig $config = null)
	{
		$converter = new QuoteConverter($config);
		$converter->setEntityID($entityID);

		parent::__construct($converter);
	}

	/**
	 * Execute wizard.
	 *
	 * @param array|null $contextData Conversion context data.
	 * @return bool
	 */
	public function execute(array $contextData = null)
	{
		if ($this->isNewApi())
		{
			return parent::execute($contextData);
		}

		/** @var QuoteConverter $converter */
		$converter = $this->converter;

		if (is_array($contextData) && !empty($contextData))
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
			while ($converter->moveToNextPhase());

			$resultData = $converter->getResultData();

			if (isset($resultData[\CCrmOwnerType::DealName]))
			{
				if ($this->isMobileContext)
				{
					$this->redirectUrl = "/mobile/crm/deal/?page=view&deal_id=" . $resultData[\CCrmOwnerType::DealName];
				}
				else
				{
					$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(
						\CCrmOwnerType::Deal,
						$resultData[\CCrmOwnerType::DealName],
						false,
						['ENABLE_SLIDER' => $this->enableSlider]
					);
				}
			}
			elseif (isset($resultData[\CCrmOwnerType::InvoiceName]))
			{
				if ($this->isMobileContext)
				{
					$this->redirectUrl = "/mobile/crm/invoice/?page=view&invoice_id="
						. $resultData[\CCrmOwnerType::InvoiceName];
				}
				else
				{
					$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Invoice,
						$resultData[\CCrmOwnerType::InvoiceName], false);
				}
			}
			$result = true;
		}
		catch (EntityConversionException $e)
		{
			$this->exception = $e;
			if ($e->getTargetType() === EntityConversionException::TARG_DST)
			{
				if ($this->isMobileContext)
				{
					switch ($e->getDestinationEntityTypeID())
					{
						case (\CCrmOwnerType::Invoice):
						{
							$this->redirectUrl = "/mobile/crm/invoice/?page=edit&" . self::QUERY_PARAM_SRC_ID . "="
								. $converter->getEntityID();
							break;
						}
						case (\CCrmOwnerType::Quote):
						{
							$this->redirectUrl = "/mobile/crm/quote/?page=edit&" . self::QUERY_PARAM_SRC_ID . "="
								. $converter->getEntityID();
							break;
						}
					}
				}
				else
				{
					$config = $converter->getConfig();
					$options = [
						'ENTITY_SETTINGS' => $config->getEntityInitData($e->getDestinationEntityTypeID()),
						'ENABLE_SLIDER' => $this->enableSlider,
					];

					$this->redirectUrl = \CCrmUrlUtil::AddUrlParams(
						\CCrmOwnerType::GetEntityEditPath(
							$e->getDestinationEntityTypeID(),
							0,
							false,
							$options
						),
						[self::QUERY_PARAM_SRC_ID => $converter->getEntityID()]
					);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->errorText = $e->getMessage();
		}

		$this->save();
		return $result;
	}

	/**
	 * Prepare entity fields for edit.
	 *
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @param bool|true $encode Encode fields flag.
	 * @return void
	 */
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
		/** @var QuoteConverter $converter */
		$converter = $this->converter;

		$userFields = QuoteConversionMapper::getUserFields($entityTypeID);
		$mappedFields = $converter->mapEntityFields($entityTypeID, ['ENABLE_FILES' => false]);

		foreach ($mappedFields as $k => $v)
		{
			if ($k === 'PRODUCT_ROWS' || $k === 'CONTACT_BINDINGS')
			{
				$fields[$k] = $v;
				continue;
			}
			elseif (mb_strpos($k, 'UF_CRM') === 0)
			{
				$userField = isset($userFields[$k]) ? $userFields[$k] : null;
				if (is_array($userField))
				{
					// hack for UF
					if ($userField['USER_TYPE_ID'] === 'file')
					{
						$GLOBALS["{$k}_old_id"] = $v;
					}
					elseif (!isset($GLOBALS[$k]))
					{
						$GLOBALS[$k] = $_REQUEST[$k] = $v;
					}
				}
			}
			elseif ($encode)
			{
				$fields["~{$k}"] = $v;
				if (!is_array($v))
				{
					$fields[$k] = htmlspecialcharsbx($v);
				}
			}
		}
	}

	/**
	 * Prepare entity fields for save.
	 *
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @return void
	 */
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
		$dstUserFields = QuoteConversionMapper::getUserFields($entityTypeID);
		foreach ($dstUserFields as $dstName => $dstField)
		{
			if ($dstField['USER_TYPE_ID'] === 'file')
			{
				$this->prepareFileUserFieldForSave($dstName, $dstField, $fields);
			}
		}

		$mappedFields = $this->converter->mapEntityFields($entityTypeID, ['DISABLE_USER_FIELD_INIT' => true]);
		foreach ($mappedFields as $k => $v)
		{
			if (!isset($fields[$k]))
			{
				$fields[$k] = $v;
			}
		}
	}

	/**
	 * Load wizard related to entity from session.
	 *
	 * @param int $entityID Entity ID.
	 * @return QuoteConversionWizard|null
	 */
	public static function load($entityID)
	{
		$storage = self::getSessionLocalStorage(\CCrmOwnerType::Quote);
		if (!$storage)
		{
			return null;
		}

		$externalizedWizardParams = $storage[$entityID] ?? null;
		if (!is_array($externalizedWizardParams))
		{
			return null;
		}

		$item = new QuoteConversionWizard($entityID);
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
			static::removeByIdentifier(new ItemIdentifier(\CCrmOwnerType::Quote, (int)$entityID));
		}
	}
}
