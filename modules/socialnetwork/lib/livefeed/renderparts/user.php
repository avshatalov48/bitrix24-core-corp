<?php
namespace Bitrix\Socialnetwork\Livefeed\RenderParts;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

final class User extends Base
{
	public function getData($entityId = 0)
	{
		static $userPath = null;
		static $userNameTemplate = null;
		static $intranetInstalled = null;
		static $extranetInstalled = null;

		$result = $this->getMetaResult();
		$options = $this->getOptions();

		if ($intranetInstalled === null)
		{
			$intranetInstalled = ModuleManager::isModuleInstalled("intranet");
		}

		if ($extranetInstalled === null)
		{
			$extranetInstalled = ($intranetInstalled && ModuleManager::isModuleInstalled("extranet"));
		}

		$extranetSite = (
			isset($options['extranetSite'])
				? $options['extranetSite']
				: false
		);

		if (intval($entityId) == 0)
		{
			$result['name'] = (
				$intranetInstalled
					? Loc::getMessage("SONET_LIVEFEED_RENDERPARTS_USER_ALL")
					: Loc::getMessage("SONET_LIVEFEED_RENDERPARTS_USER_ALL_BUS")
			);

			if (
				(!isset($options['mobile']) || !$options['mobile'])
				&& (!isset($options['im']) || !$options['im'])
				&& ($extranetSite != SITE_ID)
				&& defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL")
			)
			{
				$result['link'] = BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL;
			}
		}
		elseif (
			($res = \CUser::getByID($entityId))
			&& ($fields = $res->fetch())
		)
		{
			$result['id'] = $entityId;

			if ($userNameTemplate === null)
			{
				$userNameTemplate = \CSite::getNameFormat();
			}

			$result['name'] = \CUser::formatName($userNameTemplate, $fields, true, false);
			$result['type'] = '';
			if ($fields['EXTERNAL_AUTH_ID'] == 'email')
			{
				$result['type'] = 'email';
			}
			elseif (
				$extranetInstalled
				&& isset($fields['UF_DEPARTMENT'])
				&& empty($fields['UF_DEPARTMENT'])
			)
			{
				$result['type'] = 'extranet';
			}

			if (
				empty($options['skipLink'])
				|| !$options['skipLink']
			)
			{
				if ($userPath === null)
				{
					$userPath = (
						(!isset($options['im']) || !$options['im'])
							? (
								(!isset($options['mobile']) || !$options['mobile'])
									? Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/').'user/#user_id#/'
									: SITE_DIR.'mobile/users/?user_id=#user_id#'
							)
							: ''
					);
				}
				if (!empty($userPath))
				{
					$result['link'] = \CComponentEngine::makePathFromTemplate(
						$userPath,
						array(
							"user_id" => $entityId
						)
					);
				}
			}
		}

		return $result;
	}
}