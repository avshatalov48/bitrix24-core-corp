<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Web\Uri;

class EntityConversionWizard
{
	public const QUERY_PARAM_SOURCE_TYPE_ID = 'conv_source_type_id';
	public const QUERY_PARAM_SOURCE_ID = 'conv_source_id';

	/** @var EntityConverter|null  */
	public $converter = null;
	/** @var string  */
	protected $originUrl = '';
	/** @var string  */
	protected $redirectUrl = '';
	/** @var string  */
	protected $errorText = '';
	/** @var EntityConversionException|null  */
	protected $exception = null;
	/** @var bool */
	protected $enableRedirectToShow = true;
	/** @var bool */
	protected $enableSlider = false;
	/** @var bool */
	protected $isMobileContext = false;
	/** @var array|null */
	protected $eventParams = null;
	/** @var bool $skipMultipleUserFields */
	protected $skipMultipleUserFields = false;

	/**
	 * @todo remove the method and its usages after complete refactoring
	 * @deprecated will be removed soon
	 * @return bool
	 */
	public function isNewApi(): bool
	{
		foreach ($this->converter->getConfig()->getActiveItems() as $item)
		{
			//only dynamic destination types are allowed
			if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($item->getEntityTypeID()))
			{
				return false;
			}
		}

		return true;
	}

	public function __construct(EntityConverter $converter)
	{
		$this->converter = $converter;

		if (
			is_callable(['\Bitrix\MobileApp\Mobile', 'getApiVersion'])
			&& defined("BX_MOBILE") && BX_MOBILE === true
		)
		{
			$this->isMobileContext = true;
		}
	}

	public static function createFromExternalized(array $externalizedParams): ?self
	{
		$converter = EntityConverter::createFromExternalized((array)($externalizedParams['converter'] ?? null));
		if (!$converter)
		{
			return null;
		}

		$wizard = new self($converter);
		$wizard->internalize($externalizedParams);

		return static::checkInstanceIntegrity($wizard) ? $wizard : null;
	}

	/**
	 * Check if an instance of wizard was constructed completely
	 *
	 * @param EntityConversionWizard $wizard
	 * @return bool
	 */
	private static function checkInstanceIntegrity(self $wizard): bool
	{
		return ($wizard->converter instanceof EntityConverter);
	}

	public function execute(array $contextData = null)
	{
		if (is_array($contextData))
		{
			$this->converter->setContextData($contextData);
		}

		try
		{
			$this->converter->convert();
		}
		catch (EntityConversionException $conversionException)
		{
			$this->exception = $conversionException;
			$this->redirectUrl = (string)$this->getRedirectUrlByConversionException($conversionException);

			$this->save();

			return false;
		}
		catch (\Exception $generalException)
		{
			$this->errorText = $generalException->getMessage();

			$this->save();

			return false;
		}

		$this->redirectUrl = (string)$this->getRedirectUrlByResultData($this->getResultData());

		$this->save();

		return true;
	}

	final protected function getRedirectUrlByConversionException(EntityConversionException $conversionException): ?Main\Web\Uri
	{
		if ($conversionException->getTargetType() !== EntityConversionException::TARG_DST)
		{
			return null;
		}

		$router = Container::getInstance()->getRouter();
		if ($this->isMobileContext)
		{
			$url = $router->getMobileItemDetailUrl($conversionException->getDestinationEntityTypeID());
		}
		else
		{
			$categoryId = null;
			$initData = $this->converter->getConfig()->getEntityInitData($conversionException->getDestinationEntityTypeID());
			if (isset($initData['categoryId']) && (int)$initData['categoryId'] >= 0)
			{
				$categoryId = (int)$initData['categoryId'];
			}

			$url = $router->getItemDetailUrl($conversionException->getDestinationEntityTypeID(), 0, $categoryId);
		}

		if (!$url)
		{
			return null;
		}

		$analyticsEventBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\ConvertOpenEvent::createDefault(
			$conversionException->getDestinationEntityTypeID(),
		);
		$analyticsEventBuilder->setSrcEntityTypeId($this->getEntityTypeID());

		return $analyticsEventBuilder->buildUri($url)->addParams([
			self::QUERY_PARAM_SOURCE_TYPE_ID => $this->getEntityTypeID(),
			self::QUERY_PARAM_SOURCE_ID => $this->getEntityID(),
		]);
	}

	public static function getQueryParamSource(): array{
		return [
			'ENTITY_TYPE_ID' => self::QUERY_PARAM_SOURCE_TYPE_ID,
			'ENTITY_ID' => self::QUERY_PARAM_SOURCE_ID
		];
	}

	/**
	 * @param Array<string, int> $resultData
	 *
	 * @return Uri|null
	 */
	final protected function getRedirectUrlByResultData(array $resultData): ?Main\Web\Uri
	{
		// array_reverse because we prioritize last added results
		foreach (array_reverse($resultData, true) as $destinationEntityTypeName => $destinationId)
		{
			$destinationEntityTypeId = \CCrmOwnerType::ResolveID($destinationEntityTypeName);

			if (\CCrmOwnerType::IsDefined($destinationEntityTypeId) && (int)$destinationId > 0)
			{
				if ($this->isMobileContext)
				{
					return Container::getInstance()->getRouter()->getMobileItemDetailUrl(
						$destinationEntityTypeId,
						$destinationId,
					);
				}

				return Container::getInstance()->getRouter()->getItemDetailUrl($destinationEntityTypeId, $destinationId);
			}
		}

		return null;
	}

	public function save()
	{
		$storage = self::getSessionLocalStorage($this->getEntityTypeID());
		if (!$storage)
		{
			return;
		}

		$storage->set($this->getEntityID(), $this->externalize());
	}

	/**
	 * @internal
	 *
	 * @param int $srcEntityTypeId
	 * @return Main\Data\LocalStorage\SessionLocalStorage|null
	 */
	final protected static function getSessionLocalStorage(int $srcEntityTypeId): ?Main\Data\LocalStorage\SessionLocalStorage
	{
		$storageManager = Main\Application::getInstance()->getSessionLocalStorageManager();
		if (!$storageManager->isReady())
		{
			return null;
		}

		return $storageManager->get(static::getSessionStorageName($srcEntityTypeId));
	}

	/**
	 * @deprecated
	 *
	 * @abstract
	 * @param $entityID
	 *
	 * @return static|null
	 * @throws Main\NotImplementedException
	 */
	public static function load($entityID)
	{
		throw new Main\NotImplementedException('Method ' . __METHOD__ . ' should be overwritten in ' . static::class);
	}

	/**
	 * @deprecated
	 *
	 * @abstract
	 * @param $entityID
	 * @throws Main\NotImplementedException
	 */
	public static function remove($entityID)
	{
		throw new Main\NotImplementedException('Method ' . __METHOD__ . ' should be overwritten in ' . static::class);
	}

	public static function loadByIdentifier(ItemIdentifier $source): ?self
	{
		$storage = self::getSessionLocalStorage($source->getEntityTypeId());
		if (!$storage)
		{
			return null;
		}

		$externalizedWizardParams = $storage[$source->getEntityId()] ?? null;
		if (!is_array($externalizedWizardParams))
		{
			return null;
		}

		return static::createFromExternalized($externalizedWizardParams);
	}

	public static function removeByIdentifier(ItemIdentifier $source): void
	{
		$storage = self::getSessionLocalStorage($source->getEntityTypeId());
		if (!$storage)
		{
			return;
		}

		unset($storage[$source->getEntityId()]);
	}

	private static function getSessionStorageName(int $srcEntityTypeID): string
	{
		return \CCrmOwnerType::ResolveName($srcEntityTypeID) . '_CONVERTER';
	}

	/**
	 * Returns true if the wizard with current configuration converts source item to the provided $dstEntityTypeId
	 *
	 * @param int $dstEntityTypeId
	 * @return bool
	 */
	public function isConvertingTo(int $dstEntityTypeId): bool
	{
		$configItem = $this->getEntityConfig($dstEntityTypeId);

		return $configItem && $configItem->isActive();
	}

	public function fillDestinationItemWithDataFromSourceItem(Item $destinationItem): void
	{
		$this->converter->fillDestinationItemWithDataFromSourceItem($destinationItem);
	}

	public function hasOriginUrl()
	{
		return $this->originUrl !== '';
	}
	public function getOriginUrl()
	{
		return $this->originUrl;
	}
	public function setOriginUrl($url)
	{
		$this->originUrl = $url;
	}
	public function getErrorText()
	{
		return $this->exception !== null ? $this->exception->getLocalizedMessage() : $this->errorText;
	}
	public function getEntityTypeID()
	{
		return $this->converter->getEntityTypeID();
	}
	public function getEntityID()
	{
		return $this->converter->getEntityID();
	}

	/**
	 * Check if User Field check is enabled.
	 * User Field checking is performed during a creation and update operation.
	 * @return bool
	 */
	public function isUserFieldCheckEnabled()
	{
		return $this->converter->isUserFieldCheckEnabled();
	}
	/**
	 * Enable/disable User Field check.
	 * User Field checking is performed during a creation and update operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableUserFieldCheck($enable)
	{
		$this->converter->enableUserFieldCheck($enable);
	}
	/**
	 * Check if checking for parametrized business process is enabled.
	 * Checking is performed before a creation operation. It is enabled by default.
	 * @return bool
	 */
	public function isBizProcCheckEnabled()
	{
		return $this->converter->isBizProcCheckEnabled();
	}
	/**
	 * Enable/disable checking for parametrized business process.
	 * Checking is performed before a creation operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableBizProcCheck($enable)
	{
		$this->converter->enableBizProcCheck($enable);
	}

	/**
	 * Check should auto start BP after update.
	 * @return bool
	 */
	public function shouldSkipBizProcAutoStart(): bool
	{
		return $this->converter->shouldSkipBizProcAutoStart();
	}
	/**
	 * Enable/disable auto start BP after update
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function setSkipBizProcAutoStart(bool $enable)
	{
		$this->converter->setSkipBizProcAutoStart($enable);
	}

	/**
	 * Get event params that must be risen on the client
	 * @return array|null
	 *
	 */
	public function getClientEventParams()
	{
		return $this->eventParams;
	}
	/**
	 * Check if redirect to entity show page is enabled.
	 * @return bool
	 */
	public function isRedirectToShowEnabled()
	{
		return $this->enableRedirectToShow;
	}
	/**
	 * Enable or disable redirect to entity show page.
	 * @param boolean $enabled
	 */
	public function setRedirectToShowEnabled($enabled)
	{
		$this->enableRedirectToShow = (bool)$enabled;
	}
	/**
	 * Check if slider mode is enabled.
	 * @return bool
	 */
	public function isSliderEnabled()
	{
		return $this->enableSlider;
	}
	/**
	 * Enable or disable slider mode.
	 * @param boolean $enabled
	 */
	public function setSliderEnabled($enabled)
	{
		$this->enableSlider = (bool)$enabled;
	}
	/**
	 * Get converter result data.
	 * @return array
	 */
	public function getResultData()
	{
		return $this->converter->getResultData();
	}
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}

	/**
	 * Get current converter phase.
	 * @return int
	 */
	public function getCurrentPhase()
	{
		return $this->converter->getCurrentPhase();
	}

	/**
	 * Check if process is completed (converter is in final phase).
	 * @return bool
	 */
	public function isFinished()
	{
		return $this->converter->isFinished();
	}

	/**
	 * @param $entityTypeID
	 * @return EntityConversionConfigItem|null
	 */
	public function getEntityConfig($entityTypeID)
	{
		return $this->converter->getEntityConfig($entityTypeID);
	}

	public function mapEntityFields($entityTypeID, array $options)
	{
		return $this->converter->mapEntityFields($entityTypeID, $options);
	}

	public function prepareEditorContextParams($targetEntityTypeID)
	{
		$entityTypeID = $this->getEntityTypeID();
		$entityID = $this->getEntityID();

		$result = [
			'CONVERSION_SOURCE' => [
				'entityTypeId' => $entityTypeID,
				'entityId' => $entityID,
			],
		];

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$result['LEAD_ID'] = $entityID;
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$result['DEAL_ID'] = $entityID;
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			$result['QUOTE_ID'] = $entityID;
		}

		$factory = Container::getInstance()->getFactory((int)$targetEntityTypeID);
		if($factory && $factory->isCategoriesEnabled())
		{
			$config = $this->getEntityConfig($targetEntityTypeID);
			if($config)
			{
				$initData = $config->getInitData();
				if(is_array($initData) && isset($initData['categoryId']))
				{
					$result['CATEGORY_ID'] = (int)$initData['categoryId'];
				}
			}
		}

		return $result;
	}
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
	}
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
	}
	protected function prepareFileUserFieldForSave($fieldName, array $fieldInfo, array &$fields)
	{
		$fileInputUtility = Main\UI\FileInputUtility::instance();

		if(isset($fieldInfo['MULTIPLE']) && $fieldInfo['MULTIPLE'] === 'Y')
		{
			$results = array();
			if(is_array($fields[$fieldName]))
			{
				foreach($fields[$fieldName] as $data)
				{
					if(!is_array($data))
					{
						//Handle new user field file editor mail.file.input (it operates with file ID instead of file info)
						$controlID = $fileInputUtility->getUserFieldCid($fieldInfo);

						if(!in_array($data, $fileInputUtility->checkFiles($controlID, array($data))))
						{
							continue;
						}

						if(in_array($data, $fileInputUtility->checkDeletedFiles($controlID)))
						{
							continue;
						}

						$results[] = $data;
					}
					else
					{
						//HACK: Deletion flag may contain fileID or boolean value.
						$isDeleted = isset($data['del']) && ($data['del'] === true || $data['del'] === $data['old_id']);
						if($isDeleted)
						{
							continue;
						}

						if($data['tmp_name'] !== '')
						{
							$results[] = $data;
						}
						elseif($data['old_id'] !== '')
						{
							$isResolved = \CCrmFileProxy::TryResolveFile($data['old_id'], $file, array('ENABLE_ID' => true));
							if($isResolved)
							{
								$results[] = $file;
							}
						}
					}
				}
			}
			$fields[$fieldName] = $results;
		}
		else
		{
			$data = $fields[$fieldName];

			if(!is_array($data))
			{
				//Handle new user field file editor mail.file.input (it operates with file ID instead of file info array)
				$controlID = $fileInputUtility->getUserFieldCid($fieldInfo);
				$checkResult = $fileInputUtility->checkFiles($controlID, array($data));
				if(!in_array($data, $checkResult))
				{
					unset($fields[$fieldName]);
				}
				else
				{
					$delResult = $fileInputUtility->checkDeletedFiles($controlID);
					if(in_array($data, $delResult))
					{
						unset($fields[$fieldName]);
					}
				}
			}
			else
			{
				//HACK: Deletion flag may contain fileID or boolean value.
				$isDeleted = isset($data['del']) && ($data['del'] === true || $data['del'] === $data['old_id']);
				if(!$isDeleted  && $data['tmp_name'] === '' && $data['old_id'] !== '')
				{
					$isResolved = \CCrmFileProxy::TryResolveFile($fields[$fieldName]['old_id'], $file, array('ENABLE_ID' => true));
					if($isResolved)
					{
						$fields[$fieldName] = $file;
					}
				}
			}
		}
	}
	public function getEditFormLegend()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$exceptionCode = $this->exception !== null ? (int)$this->exception->getCode() : 0;
		if($exceptionCode === EntityConversionException::AUTOCREATION_DISABLED
			|| $exceptionCode === EntityConversionException::HAS_WORKFLOWS)
		{
			return GetMessage(
				"CRM_ENTITY_CONV_WIZ_CUSTOM_FORM_LEGEND",
				array('#TEXT#' => $this->exception->getLocalizedMessage())
			);
		}

		return GetMessage("CRM_ENTITY_CONV_WIZ_FORM_LEGEND");
	}

	public function attachNewlyCreatedEntity($entityTypeName, $entityID)
	{
		$contextData = array();
		EntityConverter::setDestinationEntityID($entityTypeName, $entityID, $contextData, array('isNew' => true));
		$this->execute($contextData);
	}

	public function externalize()
	{
		$result = array(
			'originUrl' => $this->originUrl,
			'redirectUrl' => $this->redirectUrl,
			'converter' => $this->converter->externalize(),
		);

		if($this->exception !== null)
		{
			$result['exception'] = $this->exception->externalize();
		}

		return $result;
	}
	public function internalize(array $params)
	{
		if(isset($params['originUrl']))
		{
			$this->originUrl = $params['originUrl'];
		}

		if(isset($params['redirectUrl']))
		{
			$this->redirectUrl = $params['redirectUrl'];
		}

		if(isset($params['converter']) && is_array($params['converter']))
		{
			$this->converter->internalize($params['converter']);
		}

		if(isset($params['exception']) && is_array($params['exception']))
		{
			$this->exception = new EntityConversionException();
			$this->exception->internalize($params['exception']);
		}
	}

	/**
	 * @return bool
	 */
	protected function isSkipMultipleUserFields(): bool
	{
		return $this->skipMultipleUserFields;
	}

	/**
	 * @param bool $skipMultipleUserFields
	 * @return EntityConversionWizard
	 */
	public function setSkipMultipleUserFields(bool $skipMultipleUserFields): EntityConversionWizard
	{
		$this->skipMultipleUserFields = $skipMultipleUserFields;
		return $this;
	}
}
