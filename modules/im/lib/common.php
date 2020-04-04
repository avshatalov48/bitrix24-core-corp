<?php
namespace Bitrix\Im;

class Common
{
	public static function getPublicDomain()
	{
		$schema = \Bitrix\Main\Context::getCurrent()->getRequest()->isHttps()? "https" : "http";

		if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
		{
			$domain = SITE_SERVER_NAME;
		}
		else
		{
			$domain = \Bitrix\Main\Config\Option::get("main", "server_name", '');
			if (!$domain)
			{
				$domain = $_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
			}
		}

		return $schema."://".$domain;
	}

	public static function objectEncode($params)
	{
		if (is_array($params))
		{
			array_walk_recursive($params, function(&$value, $key)
			{
				if ($value instanceof \Bitrix\Main\Type\DateTime)
				{
					$value = date('c', $value->getTimestamp());
				}
				else if (is_string($key) && in_array($key, ['AVATAR', 'AVATAR_HR']) && is_string($value) && $value && strpos($value, 'http') !== 0)
				{
					$value = \Bitrix\Im\Common::getPublicDomain().$value;
				}
			});
		}

		return \CUtil::PhpToJSObject($params);
	}

	public static function getCacheUserPostfix($id)
	{
		return '/'.substr(md5($id),2,2).'/'.intval($id);
	}

	public static function isChatId($id)
	{
		return $id && preg_match('/^chat[0-9]{1,}$/i', $id);
	}

	public static function isDialogId($id)
	{
		return $id && preg_match('/^[0-9]{1,}|chat[0-9]{1,}$/i', $id);
	}

	public static function getUserId($userId = null)
	{
		if (is_null($userId) && is_object($GLOBALS['USER']))
		{
			$userId = $GLOBALS['USER']->getId();
		}

		$userId = intval($userId);
		if (!$userId)
		{
			return false;
		}

		return $userId;
	}

	public static function getPullExtra()
	{
		return [
			'revision_im_web' => \Bitrix\Im\Revision::getWeb(),
			'revision_im_mobile' => \Bitrix\Im\Revision::getMobile(),
			'revision_im_rest' => \Bitrix\Im\Revision::getRest(),
			// deprecated
			'im_revision' => \Bitrix\Im\Revision::getWeb(),
			'im_revision_mobile' => \Bitrix\Im\Revision::getMobile(),
		];
	}
}

