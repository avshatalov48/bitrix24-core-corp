<?php

namespace Bitrix\Crm\Rest;
use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Faceid\AgreementTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmExternalChannelImport
{
	const BATCH = 'BATCH';
	const AGENT = 'AGENT';
	const ACTIVITY = 'ACTIVITY';

	const FIELDS = 'FIELDS';
	const EXTERNAL_FIELDS = 'EXTERNAL_FIELDS';
	const FIELDS_REQUISITE = 'REQUISITE';
	const FIELDS_BANK = 'BANK_DETAILS';
	const FIELDS_ADDRESS = 'RQ_ADDR';

	private $connector = null;
	private $preset = null;
	private $rawData = null;
	private $base = null;

	function __construct(CCrmExternalChannelConnector $connector, CCrmExternalChannelImportPreset $preset)
	{
		$this->connector = $connector;
		$this->preset = $preset;
	}

	public function getConnector()
	{
		return $this->connector;
	}

	public function getPreset()
	{
		return $this->preset;
	}

	public function setRawData($rawData)
	{
		$this->rawData = $rawData;
	}

	public function getRawData()
	{
		return $this->rawData;
	}

	public function resolveParamsBatch($params)
	{
		$result = array();
		$fields = array();

		if(is_array($params))
		{
			$fields[self::BATCH] = \CCrmRestHelper::resolveArrayParam($params, self::BATCH, array());
			foreach($fields[self::BATCH] as $num => $param)
			{
				if(is_array($param))
				{
					if(($activity = \CCrmRestHelper::resolveArrayParam($param, self::ACTIVITY, array())) && count($activity)>0)
					{
						$result[$num][self::ACTIVITY][self::FIELDS] = \CCrmRestHelper::resolveArrayParam($activity, self::FIELDS, array());
						$result[$num][self::ACTIVITY][self::EXTERNAL_FIELDS] = \CCrmRestHelper::resolveArrayParam($activity, self::EXTERNAL_FIELDS, array());
					}

					if(($agent = \CCrmRestHelper::resolveArrayParam($param, self::AGENT, array())) && count($agent)>0)
					{
						$result[$num][self::AGENT][self::FIELDS] = \CCrmRestHelper::resolveArrayParam($agent, self::FIELDS, array());
						$result[$num][self::AGENT][self::EXTERNAL_FIELDS] = \CCrmRestHelper::resolveArrayParam($agent, self::EXTERNAL_FIELDS, array());
						$result[$num][self::AGENT][self::FIELDS_REQUISITE] = \CCrmRestHelper::resolveArrayParam($agent, self::FIELDS_REQUISITE, array());
					}
				}
			}
		}

		return $result;
	}

	public function formatErrorsPackage($errors, $num)
	{
		$result = '';
		if(is_string($errors))
			$result .= 'Batch row '.$num.':'.$errors;
		elseif(count($errors)>0)
		{
			foreach($errors as $error)
			{
				if($error instanceof Main\Error)
				{
					$result .= 'Batch row '.$num.':'.$error->getMessage();
				}
				else
				{
					$result .= 'Batch row '.$num.':'.$error;
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $rawFields
	 * @return int
	 * @internal
	 */
	static public function resolveTypeIdActivityByFields(array $rawFields)
	{
		$result = CCrmExternalChannelActivityType::Undefined;

		if(isset($rawFields[self::EXTERNAL_FIELDS]) && is_set($rawFields[self::EXTERNAL_FIELDS]['TYPE_ID']))
		{
			$typeId = $rawFields[self::EXTERNAL_FIELDS]['TYPE_ID'];

			if(CCrmExternalChannelActivityType::isDefined(CCrmExternalChannelActivityType::resolveID($typeId)))
			{
				$result = CCrmExternalChannelActivityType::resolveID($typeId);
			}
		}
		else
		{
			// backward compatibility
			$result = CCrmExternalChannelActivityType::Activity;
		}

		return $result;
	}

	/**
	 * @return bool
	 * @internal
	 */
	public function checkLicense($type='')
	{
		$result = true;

		$fields = $this->getRawData();

		if(($activityInfo = $fields[self::ACTIVITY]) && count($activityInfo)>0)
		{
			$result = false;

			$typeId = self::resolveTypeIdActivityByFields($activityInfo);

			if($typeId>0)
			{
				switch ($typeId)
				{
					case CCrmExternalChannelActivityType::ActivityFaceCard:

						switch ($type)
						{
							case 'tariff':
								$result = $this->licenceIsRestricted()? false:true;
								break;
							case 'accepted':
								$result = $this->agreementIsAccepted();
								break;
						}

						break;
					case CCrmExternalChannelActivityType::Activity:
						$result = true;
						break;
				}
			}
		}

		return $result;
	}

	protected function licenceIsRestricted()
	{
		$r = false;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$r = in_array(\CBitrix24::getLicenseType(), array('project', 'tf'));
		}
		return $r;
	}

	protected function agreementIsAccepted()
	{
		global $USER;

		$result = false;
		if(\Bitrix\Main\Loader::includeModule('faceid') &&
			\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
		{
			$result = true;
		}

		return $result;
	}
}