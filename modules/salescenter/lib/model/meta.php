<?php

namespace Bitrix\SalesCenter\Model;

class Meta extends EO_Meta
{
	/**
	 * @param $hash
	 * @return Meta|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getByHash($hash)
	{
		$list = MetaTable::getList(['filter' => [
			'=HASH_CRC' => MetaTable::getCrc($hash),
		]]);
		while($meta = $list->fetchObject())
		{
			if($meta->getHash() == $hash)
			{
				return $meta;
			}
		}

		return null;
	}

	/**
	 * @param $userId
	 * @param array $data
	 * @return Meta|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getForData($userId, array $data)
	{
		$meta = MetaTable::getList(['filter' => [
			'=META_CRC' => MetaTable::getCrc(serialize($data)),
			'=USER_ID' => $userId,
		]])->fetchObject();

		if(!$meta)
		{
			$meta = new static();
			$result = $meta->setUserId($userId)->setMeta($data)->save();
			if($result->isSuccess())
			{
				$meta->fillHash();
				return $meta;
			}
			else
			{
				return false;
			}
		}

		return $meta;
	}

	public function setMeta(array $data)
	{
		return parent::setMeta(serialize($data));
	}

	/**
	 * @param null $name
	 * @return array
	 */
	public function getMeta($name = null)
	{
		$meta = parent::getMeta();
		if(!is_array($meta))
		{
			try
			{
				$meta = unserialize($meta, ['allowed_classes' => false]);
			}
			finally
			{
				if(!is_array($meta))
				{
					$meta = [];
				}
			}
		}

		if($name)
		{
			return $meta[$name];
		}

		return $meta;
	}
}