<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Intranet\User\Filter\ExtranetUserSettings;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\UserToGroupTable;

class ExtranetUserDataProvider extends EntityDataProvider
{
	private ExtranetUserSettings $settings;

	public function __construct(ExtranetUserSettings $settings)
	{
		$this->settings = $settings;
	}

	public function getSettings(): ExtranetUserSettings
	{
		return $this->settings;
	}

	public function prepareFields(): array
	{
		return [];
	}

	public function prepareFieldData($fieldID): array
	{
		return [];
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$result = $rawFilterValue;

		if (
			Loader::includeModule('extranet')
			&& !$this->getSettings()->isCurrentUserExtranetAdmin()
			&& (
				(
					isset($result['=UF_DEPARTMENT'])
					&& $result['=UF_DEPARTMENT']
				)
				|| $this->getSettings()->isCurrentUserExtranet()
				|| !isset($result['=UF_DEPARTMENT'])
			)
			&& (
				!isset($result['!UF_DEPARTMENT'])
				|| $result['!UF_DEPARTMENT'] !== false
				|| $this->getSettings()->isCurrentUserExtranet()
			)
			&& Loader::includeModule('socialnetwork')
		)
		{
			$workgroupIdList = $this->getSettings()->getWorkgroupIdList();

			if (
				!isset($filter['UF_DEPARTMENT'])
				&& !$this->getSettings()->isCurrentUserExtranet()
			)
			{
				if (!empty($workgroupIdList))
				{
					$result[] = [
						'LOGIC' => 'OR',
						[
							'!UF_DEPARTMENT' => false
						],
						[
							'@ID' => new SqlExpression($this->getWorkgroupUsersSubQuery($workgroupIdList))
						],
					];
				}
				else
				{
					$result[] = ['!UF_DEPARTMENT' => false];
				}
			}
			else
			{
				$publicUserIdList = $this->getSettings()->getPublicUserIdList();

				if (
					empty($workgroupIdList)
					&& empty($publicUserIdList)
				)
				{
					$result[] = ['ID' => $this->getSettings()->getCurrentUserId()];
				}
				else if (!empty($workgroupIdList))
				{
					if (!empty($publicUserIdList))
					{
						$result[] = [
							'LOGIC' => 'OR',
							[
								'<=UG.ROLE' => UserToGroupTable::ROLE_USER,
								'@UG.GROUP_ID' => $workgroupIdList
							],
							[
								'@ID' => $publicUserIdList
							],
						];
					}
					else
					{
						$result[] = ['<=UG.ROLE' => UserToGroupTable::ROLE_USER];
						$result[] = ['@UG.GROUP_ID' => $workgroupIdList];
					}
				}
				else
				{
					$result[] = ['@ID' => $publicUserIdList];
				}
			}
		}

		return $result;
	}

	public function getWorkgroupUsersSubQuery(array $workgroupIdList): string
	{
		$subQuery = new \Bitrix\Main\Entity\Query(UserToGroupTable::getEntity());
		$subQuery->addSelect('USER_ID');
		$subQuery->addFilter('@ROLE', [UserToGroupTable::ROLE_REQUEST, UserToGroupTable::ROLE_USER]);
		$subQuery->addFilter('@GROUP_ID', $workgroupIdList);
		$subQuery->addGroup('USER_ID');

		return $subQuery->getQuery();
	}
}