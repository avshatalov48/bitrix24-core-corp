<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main;
use Bitrix\ImConnector;
use Bitrix\Crm\Tracking;

/**
 * Class Imol
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Imol extends Base implements Features\TraceDetectable, Features\SingleChannelSourceDetectable
{
	protected $code = self::Imol;

	/**
	 * Imol constructor.
	 *
	 * @param string $connectorCode Connector code.
	 */
	public function __construct($connectorCode)
	{
		$this->value = $connectorCode;
	}

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		if (!$this->isSourceDirectlyRequiredSingleChannel())
		{
			return null;
		}

		$map = static::getMapConnectorsToSources();
		$sourceCode = isset($map[$this->value]) ? $map[$this->value] : null;
		return $sourceCode ? Tracking\Internals\SourceTable::getSourceByCode($sourceCode) : null;
	}

	/**
	 * Return true if supports detecting trace.
	 *
	 * @return bool
	 */
	public function isSupportTraceDetecting()
	{
		switch ($this->getValue())
		{
			case 'livechat':
				return false;

			default:
				return true;
		}
	}

	/**
	 * Return true if source directly required single channel.
	 *
	 * @return bool
	 */
	public function isSourceDirectlyRequiredSingleChannel()
	{
		return in_array(
			$this->getValue(),
			array_keys(static::getMapConnectorsToSources())
		);
	}

	protected static function getMapConnectorsToSources()
	{
		return [
			'yandex' => Tracking\Source\Base::Ya,
			'vkgroup' => Tracking\Source\Base::Vk,
			'vkgrouporder' => Tracking\Source\Base::Vk,
			'facebook' => Tracking\Source\Base::Fb,
			'facebookcomments' => Tracking\Source\Base::Fb,
			'facebookmessenger' => Tracking\Source\Base::Fb,
			'fbinstagram' => Tracking\Source\Base::Ig,
			'instagram' => Tracking\Source\Base::Ig,
		];
	}

	/**
	 * Return true if can use.
	 *
	 * @return bool
	 */
	public function canUse()
	{
		if (!Main\Loader::includeModule('imconnector'))
		{
			return false;
		}

		return parent::canUse();
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName()
	{
		if ($this->canUse())
		{
			$names = ImConnector\Connector::getListConnectorReal(20);
			if (isset($names[$this->getValue()]))
			{
				return $names[$this->getValue()];
			}
		}

		return parent::getName();
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return parent::getName();
	}

	/**
	 * Make uri trackable for imol channel.
	 *
	 * @param Main\Web\Uri $uri Uri.
	 * @param string $connectorCode Connector code.
	 * @return Main\Web\Uri
	 */
	public static function makeUriTrackable(Main\Web\Uri $uri, $connectorCode)
	{
		return $uri;
	}
}