<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Intranet\User\Filter\ExtranetUserSettings;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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
		return $this->getSettings()->isFilterAvailable(ExtranetUserSettings::COLLABER_FIELD)
			? [
				'COLLABER' => $this->createField(
					'COLLABER',
					[
						'name' => Loc::getMessage('INTRANET_USER_FILTER_COLLABER') ?? '',
						'type' => 'checkbox',
						'partial' => true,
					]
				),
			]
			: [];
	}

	public function prepareFieldData($fieldID): array
	{
		return [];
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		if (!Loader::includeModule('extranet'))
		{
			return $rawFilterValue;
		}

		$result = $rawFilterValue;
		if (\CExtranet::getExtranetUserGroupId())
		{
			$this->checkExtranetField($result);
		}
		$this->checkCollaberField($result);

		if (
			!$this->getSettings()->isCurrentUserExtranetAdmin()
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

	private function checkCollaberField(array &$filterValue): void
	{
		if (
			!empty($filterValue[ExtranetUserSettings::COLLABER_FIELD])
			&& $this->getSettings()->isFilterAvailable(ExtranetUserSettings::COLLABER_FIELD)
			&& $filterValue[ExtranetUserSettings::COLLABER_FIELD] === 'Y'
		)
		{
			$filterValue[] = ['=EXTRANET.ROLE' => 'collaber'];
		}
		elseif (
			!$this->getSettings()->isFilterAvailable(ExtranetUserSettings::COLLABER_FIELD)
			|| (
				!empty($filterValue[ExtranetUserSettings::COLLABER_FIELD])
				&& $filterValue[ExtranetUserSettings::COLLABER_FIELD] === 'N'
			)
		)
		{
			$filterValue[] = ['!=EXTRANET.ROLE' => 'collaber'];
		}
	}

	private function checkExtranetField(array &$filterValue): void
	{
		if (
			!empty($filterValue[ExtranetUserSettings::EXTRANET_FIELD])
			&& $this->getSettings()->isFilterAvailable(ExtranetUserSettings::EXTRANET_FIELD)
			&& $filterValue[ExtranetUserSettings::EXTRANET_FIELD] === 'Y'
		)
		{
			$filterValue['UF_DEPARTMENT'] = false;
			$filterValue['!=EXTRANET.ROLE'] = 'collaber';
			$filterValue['!INTRANET_USER_EXTRANET_GROUP_GROUP_ID'] = false;
		}
		elseif (
			!$this->getSettings()->isFilterAvailable(ExtranetUserSettings::EXTRANET_FIELD)
			|| (
				!empty($filterValue[ExtranetUserSettings::EXTRANET_FIELD])
				&& $filterValue[ExtranetUserSettings::EXTRANET_FIELD] === 'N'
			)
		)
		{
			$filterValue[] =  [
				'LOGIC' => 'OR',
				[
					'!UF_DEPARTMENT' => false,
				],
				[
					'INTRANET_USER_EXTRANET_GROUP_GROUP_ID' => false,
				],
				[
					'=EXTRANET.ROLE' => 'collaber'
				],
			];
		}
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