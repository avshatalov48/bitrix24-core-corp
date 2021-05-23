<?php
namespace Bitrix\Intranet\Component\UserProfile;

use Bitrix\Intranet\Component\UserProfile\StressLevel\Img;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\UserWelltoryTable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;

class StressLevel implements \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;

	const TYPE_LIST = [ 'green', 'yellow', 'red', 'unknown' ];


	function __construct()
	{
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getStub()
	{
		$result = [
			'AVAILABLE' => (
				Option::get('intranet', 'stresslevel_available', 'Y') === 'Y'
				&& (
					!Loader::includeModule('bitrix24')
					|| \Bitrix\Bitrix24\Release::isAvailable('stresslevel')
				)
					? 'Y'
					: 'N'
			),
			'TYPES_LIST' => [],
			'IMAGE_SUPPORT' => ($this->getImageSupport() ? 'Y' : 'N')
		];

		foreach(self::TYPE_LIST as $type)
		{
			$result['TYPES_LIST'][$type] = '';
		}

		return $result;
	}

	protected static function getTypeCorrected($type = '', $value = 0)
	{
		switch ($type)
		{
			case 'red':
				if ($value <= 9)
				{
					$type = 'red1';
				}
				elseif ($value <= 20)
				{
					$type = 'red2';
				}
				elseif ($value <= 69)
				{
					$type = 'red3';
				}
				elseif ($value <= 79)
				{
					$type = 'red4';
				}
				elseif ($value <= 89)
				{
					$type = 'red5';
				}
				elseif ($value <= 98)
				{
					$type = 'red6';
				}
				else
				{
					$type = 'red7';
				}
				break;
			case 'yellow':
				if ($value <= 20)
				{
					$type = 'yellow1';
				}
				elseif ($value <= 60)
				{
					$type = 'yellow2';
				}
				else
				{
					$type = 'yellow3';
				}
				break;
			case 'green':
				if ($value <= 30)
				{
					$type = 'green1';
				}
				elseif ($value <= 44)
				{
					$type = 'green2';
				}
				else
				{
					$type = 'green3';
				}
				break;
			default:
		}

		return $type;
	}

	public static function getTypeDescription($type = '', $value = 0)
	{
		$type = (
			!empty($type)
			&& in_array($type, self::TYPE_LIST)
				? self::getTypeCorrected($type, $value)
				: 'unknown'
		);

		return Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_TYPE_DESCRIPTION_'.mb_strtoupper($type));
	}

	public static function getTypeTextTitle($type = '', $value = 0)
	{
		$type = (
			!empty($type)
			&& in_array($type, self::TYPE_LIST)
				? $type
				: 'gray'
		);

		return Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_TYPE_TEXT_TITLE', [
			'#LEVEL#' => self::getTypeDescription($type, $value)
		]);
	}

	public static function getValueDescription($type = '', $value = 0)
	{
		$type = (
			!empty($type)
			&& in_array($type, self::TYPE_LIST)
				? self::getTypeCorrected($type, $value)
				: 'unknown'
		);

		return Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_TYPE_TEXT_'.mb_strtoupper($type));
	}

	protected function getImageSupport()
	{
		$img = new Img();
		return $img->getImageSupport();
	}
}