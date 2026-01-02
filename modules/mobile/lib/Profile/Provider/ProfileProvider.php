<?php

namespace Bitrix\Mobile\Profile\Provider;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Mobile\Profile\Enum\TabType;
use Bitrix\Mobile\Profile\Tab\ProfileTabFactory;

class ProfileProvider
{
	private int $viewerId;
	private int $ownerId;

	public function __construct(
		int $viewerId,
		int $ownerId,
	)
	{
		$this->viewerId = $viewerId;
		$this->ownerId = $ownerId;
	}

	/**
	 * @param string $selectedTabId
	 * @return array
	 */
	public function getTabs(string $selectedTabId): array
	{
		$canViewProfile = $this->canView();
		if (!$canViewProfile)
		{
			return [
				'canView' => false,
			];
		}
		$availableTabs = $this->getAvailableTabs();

		$selectedTabNotAvailable = empty(array_filter($availableTabs, function ($tab) use ($selectedTabId) {
			return $tab['id'] === $selectedTabId;
		}));

		if ($selectedTabNotAvailable && !empty($availableTabs))
		{
			$selectedTabId = $availableTabs[0]['id'];
		}

		return [
			'tabs' => $availableTabs,
			'selectedTabId' => $selectedTabId,
			'canView' => true,
		];
	}

	private function getAvailableTabs(): array
	{
		$availableTabs = [];
		$tabInstances = $this->getTabInstances();
		foreach ($tabInstances as $tab)
		{
			if ($tab->isAvailable())
			{
				$tabInfo = [
					'id' => $tab->getType()->value,
					'title' => $tab->getTitle(),
					'params' => $tab->getParams(),
				];

				if ($tab->isComponent())
				{
					$tabInfo['componentName'] = $tab->getComponentName();
					$tabInfo['component'] = $tab->getComponent();
				}
				else if ($tab->isWidget())
				{
					$tabInfo['widget'] = $tab->getWidget();
				}

				$availableTabs[] = $tabInfo;
			}
		}

		return $availableTabs;
	}

	/**
	 * @return \Bitrix\Mobile\Profile\Tab\BaseProfileTab[]
	 */
	private function getTabInstances(): array
	{
		return ProfileTabFactory::createTabs($this->viewerId, $this->ownerId);
	}

	/**
	 * @return bool
	 */
	public static function isNewProfileFeatureEnabled(): bool
	{
		return Option::get('mobile', 'profile_feature_enabled', 'N') === 'Y';
	}

	/**
	 * @return array
	 */
	public function getPermissions(): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		$access = \Bitrix\Intranet\User\Access\UserAccessController::createByDefault();
		$targetUser = \Bitrix\Intranet\User\Access\Model\TargetUserModel::createFromId($this->ownerId);

		$valuesForBatchCheck = \Bitrix\Intranet\User\Access\UserActionDictionary::valuesForBatchCheck([
			\Bitrix\Intranet\User\Access\UserActionDictionary::VIEW,
			\Bitrix\Intranet\User\Access\UserActionDictionary::UPDATE,
		]);

		return $access->batchCheck($valuesForBatchCheck, $targetUser);
	}

	/**
	 * @return bool
	 */
	public function canView(): bool
	{
		if (!Loader::includeModule('intranet'))
		{
			return false;
		}

		$access = \Bitrix\Intranet\User\Access\UserAccessController::createByDefault();
		$targetUser = \Bitrix\Intranet\User\Access\Model\TargetUserModel::createFromId($this->ownerId);

		return $access->check(\Bitrix\Intranet\User\Access\UserActionDictionary::VIEW, $targetUser);
	}

	/**
	 * @return bool
	 */
	public function canUpdate(): bool
	{
		if (!Loader::includeModule('intranet'))
		{
			return false;
		}

		$access = \Bitrix\Intranet\User\Access\UserAccessController::createByDefault();
		$targetUser = \Bitrix\Intranet\User\Access\Model\TargetUserModel::createFromId($this->ownerId);

		return $access->check(\Bitrix\Intranet\User\Access\UserActionDictionary::UPDATE, $targetUser);
	}

	public function save($fieldsToSave): Result
	{
		$result = new Result();
		$tags = $fieldsToSave['tags'] ?? null;

		if ($tags !== null)
		{
			$tagProvider = new \Bitrix\Mobile\Profile\Provider\TagProvider();
			$tagProvider->saveTags($this->ownerId, $tags);
		}

		$commonFields = $fieldsToSave['commonFields'] ?? null;
		if ($commonFields !== null)
		{
			$preparedCommonFields = [];
			foreach ($commonFields as $field)
			{
				if ($field['type'] === 'date')
				{
					if (empty($field['value']))
					{
						$preparedCommonFields[$field['id']] = null;
					}
					else
					{
						$dateTime = (new \DateTime())->setTimestamp($field['value']);
						$preparedCommonFields[$field['id']] = new \Bitrix\Main\Type\Date($dateTime->format('Y-m-d'), 'Y-m-d');
					}
				}
				else
				{
					$preparedCommonFields[$field['id']] = $field['value'] ?? null;
				}
			}
			$userFields = \Bitrix\Intranet\Public\Provider\User\UserFieldsProvider::createByDefault()->getByUserData($preparedCommonFields);

			$command = new \Bitrix\Intranet\User\Command\UpdateUserFieldsCommand($this->ownerId, $userFields);
			$saveResult = $command->run();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
		}

		$avatar = $fieldsToSave['header']['image'];
		if (!empty($avatar) && Loader::includeModule('rest'))
		{
			$base64 = $avatar['base64'];
			$avatarData = [
				'bx_mobile' => 'Y',
				'bx_mobile_background' => 'N',
				'id' => $this->ownerId,
				'PERSONAL_PHOTO' => [
					'avatar.png',
					$base64,
				],
			];
			\Bitrix\Rest\Api\User::userUpdate($avatarData);
		}

		// Here you can add other data processing logic if needed
		$result->setData(ProfileTabFactory::createTab(
			TabType::COMMON,
			$this->viewerId,
			$this->ownerId,
		)->getData());

		return $result;
	}
}
