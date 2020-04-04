<?php
class CCrmTemplateManager
{
	private static $ADAPTERS = null;

	private static function PrepareAdapters()
	{
		if(self::$ADAPTERS !== null)
		{
			return self::$ADAPTERS;
		}

		self::$ADAPTERS = array(
			new CCrmTemplateAdapter()
		);

		return self::$ADAPTERS;
	}

	public static function GetAllMaps()
	{
		$result = array();
		$adapters = self::PrepareAdapters();
		foreach($adapters as $adapter)
		{
			$types = $adapter->GetSupportedTypes();
			foreach($types as $typeID)
			{
				$map = $adapter->GetTypeMap($typeID);
				if($map)
				{
					$result[] = &$map;
				}
				unset($map);
			}
		}
		return $result;
	}

	private static function ResolveMapper($entityTypeID, $entityID)
	{
		$adapters = self::PrepareAdapters();
		foreach($adapters as $adapter)
		{
			if($adapter->IsTypeSupported($entityTypeID))
			{
				return $adapter->CreateMapper($entityTypeID, $entityID);
			}
		}
		return null;
	}

	public static function PrepareTemplate($template, $entityTypeID, $entityID, $contentTypeID = 0, $senderId = 0)
	{
		$template = strval($template);
		if($template === '')
		{
			return '';
		}

		$entityTypeName = \CCrmOwnerType::System == $entityTypeID ? 'SENDER' : \CCrmOwnerType::resolveName($entityTypeID);
		$entityID = intval($entityID);

		$contentTypeID = (int) $contentTypeID;
		if (!\CCrmContentType::isDefined($contentTypeID))
			$contentTypeID = \CCrmContentType::PlainText;

		if ($entityTypeName != '' && $entityID > 0)
		{
			if (preg_match_all(sprintf('/#%s\.[^#]+#/i', preg_quote($entityTypeName, '/')), $template))
			{
				$entityMapper = self::resolveMapper($entityTypeID, $entityID);
				$entityMapper->setContentType($contentTypeID);
			}
		}

		if (\CCrmOwnerType::System == $entityTypeID)
		{
			$senderMapper = $entityMapper;
		}
		else if ($senderId > 0)
		{
			if (preg_match_all('/#SENDER\.[^#]+#/i', $template))
			{
				$senderMapper = self::resolveMapper(\CCrmOwnerType::System, $senderId);
				$senderMapper->setContentType($contentTypeID);
			}
		}

		if (empty($entityMapper) && empty($senderMapper))
			return $template;

		preg_match_all(sprintf('/#(SENDER|%s)\.[^#]+#/i', preg_quote($entityTypeName, '/')), $template, $matches);

		$replacements = array();
		foreach ($matches[0] as $i => $key)
		{
			$mapper = $matches[1][$i] == 'SENDER' ? $senderMapper : $entityMapper;

			if (array_key_exists($key, $replacements) || empty($mapper))
				continue;

			$replacements[$key] = $mapper->mapPath(substr($key, 1, -1));
		}

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}
}
