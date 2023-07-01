<?php

namespace Bitrix\Disk\Volume\Storage;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;


/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Common extends Volume\Storage\Storage
{
	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		$entityTypes = array_merge(
			[Disk\ProxyType\Common::className()],
			Volume\Module\Im::getEntityType(),
			Volume\Module\Mail::getEntityType(),
			Volume\Module\Documentgenerator::getEntityType()
		);

		return $entityTypes;
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE, self::UNNECESSARY_VERSION]): self
	{
		$this->addFilter('@ENTITY_TYPE', self::getEntityType());

		parent::measure($collectData);

		return $this;
	}

	/**
	 * @param Volume\Fragment $fragment Storage entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): ?string
	{
		$storage = $fragment->getStorage();
		if (!$storage instanceof Disk\Storage)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Storage::className());
		}

		$url = $storage->getProxyType()->getStorageBaseUrl();

		$testUrl = trim($url, '/');
		if (
			$testUrl == '' ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_DISK
		)
		{
			return null;
		}

		return $url;
	}
}

