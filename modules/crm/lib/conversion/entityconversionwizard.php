<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
abstract class EntityConversionWizard
{
	/** @var EntityConverter|null  */
	protected $converter = null;
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

	public function __construct(EntityConverter $converter)
	{
		$this->converter = $converter;

		if (
			is_callable(array('\Bitrix\MobileApp\Mobile', 'getApiVersion'))
			&& defined("BX_MOBILE") && BX_MOBILE === true
		)
			$this->isMobileContext = true;
	}
	abstract public function execute(array $contextData = null);
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
		$result = array();

		$entityTypeID = $this->getEntityTypeID();
		$entityID = $this->getEntityID();

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

		if($targetEntityTypeID === \CCrmOwnerType::Deal)
		{
			$config = $this->getEntityConfig(\CCrmOwnerType::Deal);
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
			'converter' => $this->converter->externalize()
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
}