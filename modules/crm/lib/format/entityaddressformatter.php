<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
class EntityAddressFormatter
{
	const Undefined = 0;
	const EU = 1;
	const UK = 2;
	const USA = 3;
	const RUS = 4;
	const RUS2 = 5;

	const Dflt = 1;
	const First = 1;
	const Last = 5;

	const EUName = 'EU';
	const UKName = 'UK';
	const USAName = 'USA';
	const RUSName = 'RUS';
	const RUS2Name = 'RUS2';

	private static $formatID = null;
	private static $allDescriptions = null;
	private static $allExamples = null;
	private static $config = null;

	private static function getAll()
	{
		return array(self::EU, self::UK, self::USA, self::RUS, self::RUS2);
	}
	private static function resolveID($name)
	{
		switch($name)
		{
			case self::EUName:
				return self::EU;
			case self::UKName:
				return self::UK;
			case self::USAName:
				return self::USA;
			case self::RUSName:
				return self::RUS;
			case self::RUS2Name:
				return self::RUS2;
			default:
				return self::Undefined;
		}
	}
	private static function resolveName($ID)
	{
		switch($ID)
		{
			case self::EU:
				return self::EUName;
			case self::UK:
				return self::UKName;
			case self::USA:
				return self::USAName;
			case self::RUS:
				return self::RUSName;
			case self::RUS2:
				return self::RUS2Name;
			default:
				return '';
		}
	}

	public static function isDefined($formatID)
	{
		if(!is_int($formatID))
		{
			$formatID = intval($formatID);
		}
		return $formatID >= self::First && $formatID <= self::Last;
	}
	public static function getFormatID()
	{
		if(self::$formatID !== null)
		{
			return self::$formatID;
		}

		$formatID = (int)\COption::GetOptionString('crm', 'ent_addr_frmt_id', 0);
		if(!self::isDefined($formatID))
		{
			$formatID = self::Dflt;
		}
		self::$formatID = $formatID;
		return self::$formatID;
	}
	public static function setFormatID($formatID)
	{
		if(!is_int($formatID))
		{
			$formatID = (int)$formatID;
		}

		if(!self::isDefined($formatID))
		{
			throw new Main\ArgumentOutOfRangeException('formatID', self::First, self::Last);
		}

		self::$formatID = $formatID;
		if($formatID !== self::Dflt)
		{
			return \COption::SetOptionString('crm', 'ent_addr_frmt_id', $formatID);
		}

		// Do not store default format ID
		\COption::RemoveOption('crm', 'ent_addr_frmt_id');
		return true;
	}
	public static function getConfig()
	{
		if(self::$config !== null)
		{
			return self::$config;
		}

		$str = \COption::GetOptionString('crm', 'ent_addr_frmt_cfg', '');
		if($str !== '')
		{
			self::$config = unserialize($str);
		}

		if(!is_array(self::$config))
		{
			self::$config = array();
		}

		$all = self::getAll();
		$qty = count($all);
		for($i = 0; $i < $qty; $i++)
		{
			$ID = $all[$i];
			$name = self::resolveName($ID);

			if($name === '' || isset(self::$config[$name]))
			{
				continue;
			}

			self::$config[$name] = array('SORT' => (($i + 1) * 100), 'ENABLED' => true);
		}

		Main\Type\Collection::sortByColumn(self::$config, array('SORT' => SORT_ASC));

		return self::$config;
	}
	public static function setConfig(array $config)
	{
		//For not RU: \COption::SetOptionString('crm', 'ent_addr_frmt_cfg', serialize(array('RUS'=>array('ENABLED' => false))));
		//For RU only: \COption::SetOptionString('crm', 'ent_addr_frmt_cfg', serialize(array('RUS'=>array('ENABLED' => true, 'SORT' => 10))));

		foreach($config as $name => $v)
		{
			$ID = self::resolveID($name);
			if($ID === self::Undefined)
			{
				unset($config[$name]);
			}
		}

		self::$config = $config;
		\COption::SetOptionString('crm', 'ent_addr_frmt_cfg', serialize(self::$config));

		self::$allDescriptions = null;
		self::$allExamples = null;
	}
	public static function resetConfig()
	{
		self::$config = null;
		self::$allDescriptions = null;
		self::$allExamples = null;

		\COption::RemoveOption('crm', 'ent_addr_frmt_cfg');
	}
	public static function getAllDescriptions($showDisabled = false)
	{
		if(!is_bool($showDisabled))
		{
			$showDisabled = (bool)$showDisabled;
		}

		if(!self::$allDescriptions)
		{
			IncludeModuleLangFile(__FILE__);

			$cfg = self::getConfig();
			foreach($cfg as $name => $data)
			{
				$ID = self::resolveID($name);
				if($ID === self::Undefined)
				{
					continue;
				}

				if(!$showDisabled && (isset($data['ENABLED']) && $data['ENABLED'] === false))
				{
					continue;
				}

				/* Using of messages:
				 * CRM_ENTITY_ADDR_FRMT_EUROPEAN
				 * CRM_ENTITY_ADDR_FRMT_UK
				 * CRM_ENTITY_ADDR_FRMT_USA
				 * CRM_ENTITY_ADDR_FRMT_RUS
				 * CRM_ENTITY_ADDR_FRMT_RUS2
				 */
				self::$allDescriptions[$ID] = GetMessage("CRM_ENTITY_ADDR_FRMT_{$name}");
			}
		}
		return self::$allDescriptions;
	}
	public static function getAllExamples($showDisabled = false)
	{
		if(!is_bool($showDisabled))
		{
			$showDisabled = (bool)$showDisabled;
		}

		if(!self::$allExamples)
		{
			IncludeModuleLangFile(__FILE__);

			$cfg = self::getConfig();
			foreach($cfg as $name => $data)
			{
				$ID = self::resolveID($name);
				if($ID === self::Undefined)
				{
					continue;
				}

				if(!$showDisabled && (isset($data['ENABLED']) && $data['ENABLED'] === false))
				{
					continue;
				}

				/* Using of messages:
				 * CRM_ENTITY_ADDR_FRMT_SMPL_EUROPEAN
				 * CRM_ENTITY_ADDR_FRMT_SMPL_UK
				 * CRM_ENTITY_ADDR_FRMT_SMPL_USA
				 * CRM_ENTITY_ADDR_FRMT_SMPL_RUS
				 * CRM_ENTITY_ADDR_FRMT_SMPL_RUS2
				 */
				self::$allExamples[$ID] = GetMessage("CRM_ENTITY_ADDR_FRMT_SMPL_{$name}");
			}
		}
		return self::$allExamples;
	}
	public static function prepareLines(array $data, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$formatID = isset($options['FORMAT']) ? (int)$options['FORMAT'] : self::Undefined;
		if($formatID !== self::Undefined && !self::isDefined($formatID))
		{
			$formatID = self::Undefined;
		}
		if($formatID === self::Undefined)
		{
			$formatID = self::getFormatID();
		}

		$lines = array();
		$address1 = isset($data['ADDRESS_1']) ? $data['ADDRESS_1'] : '';
		if($address1 !== '')
		{
			if(isset($options['NL2BR']) && $options['NL2BR'] === true)
			{
				$address1 = nl2br($address1);
			}
			$lines[] = $address1;
		}

		$address2 = isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '';
		if($address2 !== '')
		{
			$lines[] = $address2;
		}

		$city = isset($data['CITY']) ? $data['CITY'] : '';
		$region = isset($data['REGION']) ? $data['REGION'] : '';
		$province = isset($data['PROVINCE']) ? $data['PROVINCE'] : '';
		$postalCode = isset($data['POSTAL_CODE']) ? $data['POSTAL_CODE'] : '';
		$country = isset($data['COUNTRY']) ? $data['COUNTRY'] : '';

		if($formatID === self::RUS)
		{
			if($city !== '')
			{
				$lines[] = $city;
			}

			if($region !== '')
			{
				$lines[] = $region;
			}

			if($province !== '')
			{
				$lines[] = $province;
			}

			if($country !== '')
			{
				$lines[] = $country;
			}

			if($postalCode !== '')
			{
				$lines[] = $postalCode;
			}
		}
		elseif($formatID === self::RUS2)
		{
			$tempLines = array();
			
			if($postalCode !== '')
			{
				$tempLines[] = $postalCode;
			}

			if($country !== '')
			{
				$tempLines[] = $country;
			}

			if($province !== '')
			{
				$tempLines[] = $province;
			}

			if($region !== '')
			{
				$tempLines[] = $region;
			}

			if($city !== '')
			{
				$tempLines[] = $city;
			}

			if (!empty($tempLines))
				$lines = array_merge($tempLines, $lines);

			unset($tempLines);
		}
		elseif($formatID === self::EU)
		{
			$localities = array();
			if($postalCode !== '')
			{
				$localities[] = $postalCode;
			}

			if($city !== '')
			{
				$localities[] = $city;
			}

			if($region !== '')
			{
				$localities[] = $region;
			}

			if($province !== '')
			{
				$localities[] = $province;
			}

			$lines[] = implode(' ', $localities);

			if($country !== '')
			{
				$lines[] = $country;
			}
		}
		elseif($formatID === self::UK)
		{
			if($city !== '')
			{
				$lines[] = strtoupper($city);
			}

			if($region !== '')
			{
				$lines[] = strtoupper($region);
			}

			if($province !== '')
			{
				$lines[] = strtoupper($province);
			}

			if($postalCode !== '')
			{
				$lines[] = strtoupper($postalCode);
			}

			if($country !== '')
			{
				$lines[] = $country;
			}
		}
		else //if($formatID === self::USA)
		{
			$localities = array();
			if($city !== '')
			{
				$localities[] = $city;
			}

			if($region !== '')
			{
				$localities[] = $region;
			}

			if($province !== '')
			{
				$localities[] = $province;
			}

			if($postalCode !== '')
			{
				$localities[] = strtoupper($postalCode);
			}

			$lines[] = implode(' ', $localities);

			if($country !== '')
			{
				$lines[] = strtoupper($country);
			}
		}

		if(isset($options['HTML_ENCODE']) && $options['HTML_ENCODE'] === true)
		{
			array_walk($lines, create_function("&\$v", "\$v=htmlspecialcharsbx(\$v);"));
		}

		return $lines;
	}
	protected static function formatLines(array $lines, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$separatorType = isset($options['SEPARATOR']) ? (int)$options['SEPARATOR'] : AddressSeparator::Undefined;
		return implode(AddressSeparator::getSeparator($separatorType), $lines);
	}
	public static function format(array $data, array $options = null)
	{
		return self::formatLines(self::prepareLines($data, $options), $options);
	}
}