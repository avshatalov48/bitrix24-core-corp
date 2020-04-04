<?php

namespace Bitrix\SalesCenter\Fields\Entity;

use Bitrix\Main\UserTable;
use Bitrix\SalesCenter\Fields\Entity;

abstract class User extends Entity
{
	protected function getUserFieldEntity(): ?string
	{
		return 'USER';
	}

	protected function getTableClassName(): ?string
	{
		return UserTable::class;
	}

	public function getHiddenFields(): ?array
	{
		$settings = new \Bitrix\Main\Filter\UserSettings(['ID' => 0]);
		$userFilterDataProvider = new \Bitrix\Main\Filter\UserUFDataProvider($settings);
		if(is_callable([$userFilterDataProvider, 'getUfReserved']))
		{
			$hiddenFields = $userFilterDataProvider->getUfReserved();
		}
		else
		{
			$hiddenFields = [
				'UF_DEPARTMENT',
				'UF_USER_CRM_ENTITY',
				'UF_PUBLIC',
				'UF_TIMEMAN',
				'UF_TM_REPORT_REQ',
				'UF_TM_FREE',
				'UF_REPORT_PERIOD',
				'UF_1C',
				'UF_TM_ALLOWED_DELTA',
				'UF_SETTING_DATE',
				'UF_LAST_REPORT_DATE',
				'UF_DELAY_TIME',
				'UF_TM_REPORT_DATE',
				'UF_TM_DAY',
				'UF_TM_TIME',
				'UF_TM_REPORT_TPL',
				'UF_TM_MIN_DURATION',
				'UF_TM_MIN_FINISH',
				'UF_TM_MAX_START',
				'UF_CONNECTOR_MD5',
				'UF_WORK_BINDING',
				'UF_IM_SEARCH',
				'UF_BXDAVEX_CALSYNC',
				'UF_BXDAVEX_MLSYNC',
				'UF_UNREAD_MAIL_COUNT',
				'UF_BXDAVEX_CNTSYNC',
				'UF_BXDAVEX_MAILBOX',
				'UF_VI_PASSWORD',
				'UF_VI_BACKPHONE',
				'UF_VI_PHONE',
				'UF_VI_PHONE_PASSWORD',
			];
		}

		return array_merge($hiddenFields, parent::getHiddenFields());
	}
}