<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\Permissions;

class PermissionsLayoutV2 extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('PERMISSIONS_LAYOUT_V2_NAME');
	}

	public function getCategory(): Category\BaseCategory
	{
		return Permissions::getInstance();
	}

	protected function getOptionName(): string
	{
		return 'use_v2_version_config_perms';
	}

	public function enable(): void
	{
		if (!$this->isEnabled())
		{
			parent::enable();

			\CAgent::AddAgent(
				'\Bitrix\Crm\Agent\Security\SeparateRolesAgent::doRun();',
				'crm'
			);
		}
	}

	public function enableWithoutAgent(): void
	{
		if (!$this->isEnabled())
		{
			parent::enable();
		}
	}
}
