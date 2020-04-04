<?php

namespace Bitrix\Crm\Rest;

use Bitrix\Crm\ExternalChannelConnectorTable;
use Bitrix\Crm\Integration\Channel\ExternalTracker;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;

Loc::loadMessages(__FILE__);

class CCrmExternalChannelConnector
{
	const Prefix = 'options_';
	const Suffix = 'ext_channel.';

	public $channel_id = null;
	public $server = null;

	private static $FIELD_INFOS = null;

	/**
	 * @param \CRestServer $server
     */
	public function setServer(\CRestServer $server)
	{
		$this->server = $server;
	}

	/**
	 * @return null
     */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * @return bool
     */
	public function isRegistered()
	{
		return $this->getChannel() !== false;
	}

	/**
	 * @param $channel_id
     */
	public function setChannelId($channel_id)
	{
		$this->channel_id = $channel_id;
	}

	/**
	 * @return string
     */
	public function getChannelId()
	{
		return $this->channel_id;
	}

	/**
	 * @return array|false
     */
	public function getChannel()
	{
		$f = $this->getList(array('filter'=>array('CHANNEL_ID'=>$this->getChannelId())));
		if($list = $f->fetch())
		{
			return $list;
		}
		return false;
	}

	/**
	 * @param $params
	 * @return array|null
     */
	public function getOption($params)
	{
		$fieldsChannel = $this->getChannel();

		if(isset($params['option']))
		{
			return isset($fieldsChannel[$params['option']]) ? $fieldsChannel[$params['option']] : null;
		}
		else
		{
			return $fieldsChannel;
		}
	}

	/**
	 * @return null|array
     */
	public function getOriginatorId()
	{
		$result = null;

		$fieldsChannel = $this->getChannel();
		if(is_array($fieldsChannel))
		{
			$result = $fieldsChannel['ORIGINATOR_ID'];
		}
		return $result;
	}

	/**
	 * @return array
     */
	public function getTypeId()
	{
		$f = ExternalChannelConnectorTable::getList(array('filter'=>array('CHANNEL_ID'=>$this->getChannelId())));
		if($list = $f->fetch())
		{
			return $list['TYPE_ID'];
		}
		return false;
	}

	/**
	 * @param string $originatorId
	 * @return array|false
     */
	public function getByOriginalId($originatorId)
	{
		$f = $this->getList(array('filter'=>array('ORIGINATOR_ID'=>$originatorId)));

		if($result = $f->fetch())
		{
			return $result;
		}

		return $result;
	}

	/**
	 * @param $fields
     */
	public function prepareFields(&$fields)
	{
		if(!is_set($fields, 'NAME') || $fields['NAME'] =='')
		{
			$fields['NAME'] = Loc::getMessage("CRM_REST_EXTERNAL_CHANNEL_CONNECTOR_DEFAULT_NAME");
		}
		else
		{
			$fields['NAME'] = Encoding::convertEncodingToCurrent($fields['NAME']);
		}

		if(!is_set($fields, 'TYPE_ID'))
		{
			if($fields['NAME'] ==  Loc::getMessage("CRM_REST_EXTERNAL_CHANNEL_CONNECTOR_DEFAULT_1C_NAME"))
			{
				$fields['TYPE_ID'] = CCrmExternalChannelType::OneCName;
			}
			else
			{
				$fields['TYPE_ID'] = CCrmExternalChannelType::CustomName;
			}
		}

		$fields['APP_ID'] = $this->server->getAppId();
	}

	/**
	 * @param $fields
	 * @param $errors
     */
	public function checkFields(&$fields, &$errors)
	{
		if(!CCrmExternalChannelType::isDefined(CCrmExternalChannelType::resolveID($fields['TYPE_ID'])))
		{
			$errors[] = "Argument TYPE_ID out of range";
		}
		else
		{
			if(!is_set($fields, 'ORIGINATOR_ID') || $fields['ORIGINATOR_ID'] =='')
			{
				$errors[] = "Argument ORIGINATOR_ID is not defined";
			}
			else
			{
				if(preg_match("/[^A-Za-z0-9_]/",  $fields['ORIGINATOR_ID']))
				{
					$errors[] = "ORIGINATOR_ID can contain only Latin letter, digits, and the underscore symbol.";
				}
				else
				{
					$r = $this->getList(array('filter'=>array('ORIGINATOR_ID'=>$fields['ORIGINATOR_ID'], 'TYPE_ID'=>$fields['TYPE_ID']<>'' ? $fields['TYPE_ID']:CCrmExternalChannelType::OneCName)));

					if($connector = $r->fetch())
					{
						$errors[] = "Type id {$connector['TYPE_ID']}, ORIGINATOR_ID {$connector['ORIGINATOR_ID']} already in use ";
					}
				}
			}
		}

		if(is_set($fields['NAME']) && strlen($fields['NAME'])>32)
		{
			$errors[] = "The length of NAME should not exceed 32 characters.";
		}
		if(is_set($fields['ORIGINATOR_ID']) && strlen($fields['ORIGINATOR_ID'])>32)
		{
			$errors[] = "The length of ORIGINATOR_ID should not exceed 32 characters.";
		}
		if(is_set($fields['EXTERNAL_SERVER_HOST']) && strlen($fields['EXTERNAL_SERVER_HOST'])>128)
		{
			$errors[] = "The length of EXTERNAL_SERVER_HOST should not exceed 128 characters.";
		}
	}

	/**
	 * @param string $typeId
	 * @param string $originatorId
	 * @param array $fields
	 * @return string
	 * @throws ArgumentException
	 * @throws ArgumentOutOfRangeException
     */
	public static function register($typeId, $originatorId, array $fields)
	{
		if(!CCrmExternalChannelType::IsDefined(CCrmExternalChannelType::resolveID($typeId)))
		{
			throw new ArgumentOutOfRangeException('typeId',
					CCrmExternalChannelType::First,
					CCrmExternalChannelType::Last
			);
		}

		if(strlen($originatorId) <= 0)
		{
			throw new ArgumentException('Originator ID must be not empty string.', 'ORIGINATOR_ID');
		}

		$channel_id = uniqid();

		$data = array(
				'TYPE_ID' => $typeId<>''? $typeId:CCrmExternalChannelType::CustomName,
				'NAME' => $fields['NAME'],
				'APP_ID' => $fields['APP_ID'],
				'CHANNEL_ID' => $channel_id,
				'ORIGINATOR_ID' => $originatorId,
				'EXTERNAL_SERVER_HOST' => $fields['EXTERNAL_SERVER_HOST']
		);

		ExternalChannelConnectorTable::upsert($data);

		return $channel_id;
	}

	/**
	 * @param string $typeId
	 * @param string $originatorId
	 * @throws ArgumentException
	 * @throws ArgumentOutOfRangeException
	 * @throws \Exception
     */
	public static function unregister($typeId, $originatorId)
	{
		if(!CCrmExternalChannelType::IsDefined(CCrmExternalChannelType::resolveID($typeId)))
		{
			throw new ArgumentOutOfRangeException('typeId',
					CCrmExternalChannelType::First,
					CCrmExternalChannelType::Last
			);
		}

		if(strlen($originatorId) <= 0)
		{
			throw new ArgumentException('Originator ID must be not empty string.', 'ORIGINATOR_ID');
		}

		ExternalChannelConnectorTable::delete(array('TYPE_ID' => $typeId, 'ORIGINATOR_ID' => $originatorId));
	}

	/**
	 * @return array|null
     */
	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
					'TYPE_ID' => array(
							'TYPE' => 'string',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::Required,
									\CCrmFieldInfoAttr::Immutable)
					),
					'ORIGINATOR_ID' => array(
							'TYPE' => 'string',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::Required,
									\CCrmFieldInfoAttr::Immutable)
					),
					'NAME' => array('TYPE' => 'string'),
					'APP_ID' => array('TYPE' => 'string'),
					'CHANNEL_ID' => array('TYPE' => 'string'),
					'EXTERNAL_SERVER_HOST' => array('TYPE' => 'string'),
			);
		}
		return self::$FIELD_INFOS;
	}

	/**
	 * @param array $params
	 * @return \Bitrix\Main\DB\Result
	 * @throws ArgumentException
     */
	public function getList($params)
	{
		return ExternalChannelConnectorTable::getList($params);
	}
}