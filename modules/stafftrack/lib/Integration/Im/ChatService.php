<?php

namespace Bitrix\StaffTrack\Integration\Im;

use Bitrix\Im\V2\Service\Locator;
use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\StaffTrack\Dictionary\Feature;
use Bitrix\Stafftrack\Integration\HumanResources\Structure;
use Bitrix\StaffTrack\Provider\UserProvider;

class ChatService
{
	private int $userId;
	private const BANNER_COMPONENT_ID = 'SupervisorEnableFeatureMessage';

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param Feature $feature
	 * @return Result
	 * @throws LoaderException
	 */
	public function createDepartmentHeadChat(Feature $feature): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		$departmentHeadId = $this->getDepartmentHeadId();
		if ($departmentHeadId === 0)
		{
			return $result->addError(new Error('Department head not found'));
		}

		$createChatResult = Locator::getMessenger()->createChat([
			'ENTITY_TYPE' => 'SUPERVISOR_CHAT',
			'TITLE' => $this->getTitle($feature),
			'SEND_GREETING_MESSAGES' => 'Y',
		]);
		if (!$createChatResult->isSuccess())
		{
			return $result->addErrors($createChatResult->getErrors());
		}

		$chatId = $createChatResult->getResult()['CHAT_ID'];

		$this->addUserToChat($chatId, $departmentHeadId);
		$this->addBannerMessage($chatId, $feature);

		return $result->setData(['chatId' => $chatId]);
	}

	/**
	 * @return int
	 */
	private function getDepartmentHeadId(): int
	{
		$departmentHeads = \CIntranetUtils::GetDepartmentManager(
			UserSubordinate::getDepartmentsByUserId($this->userId),
			$this->userId,
			true
		);

		/** @var array $departmentHead */
		foreach ($departmentHeads as $departmentHead)
		{
			if (!empty($departmentHead) && isset($departmentHead['ID']))
			{
				return (int)$departmentHead['ID'];
			}
		}

		return 0;
	}

	/**
	 * @param Feature $feature
	 * @return string
	 */
	private function getTitle(Feature $feature): string
	{
		return match ($feature)
		{
			Feature::ENABLE_CHECK_IN => Loc::getMessage('STAFFTRACK_INTEGRATION_IM_CHAT_ENABLE_FEATURE_TITLE'),
			Feature::ENABLE_CHECK_IN_GEO => Loc::getMessage('STAFFTRACK_INTEGRATION_IM_CHAT_ENABLE_GEO_FEATURE_TITLE'),
		};
	}

	/**
	 * @param Feature $feature
	 * @return string
	 */
	private function getBannerId(Feature $feature): string
	{
		return match ($feature)
		{
			Feature::ENABLE_CHECK_IN => 'checkIn',
			Feature::ENABLE_CHECK_IN_GEO => 'checkInGeo',
		};
	}

	/**
	 * @param Feature $feature
	 * @return string
	 */
	private function getBannerMessage(Feature $feature): string
	{
		return match ($feature)
		{
			Feature::ENABLE_CHECK_IN => Loc::getMessage('STAFFTRACK_INTEGRATION_IM_CHAT_ENABLE_FEATURE_BANNER_MESSAGE'),
			Feature::ENABLE_CHECK_IN_GEO => Loc::getMessage('STAFFTRACK_INTEGRATION_IM_CHAT_ENABLE_GEO_FEATURE_BANNER_MESSAGE'),
		};
	}

	/**
	 * @param int $chatId
	 * @param int $userId
	 * @return void
	 */
	private function addUserToChat(int $chatId, int $userId): void
	{
		(new \CIMChat())->AddUser($chatId, [$userId]);
	}

	/**
	 * @param int $chatId
	 * @param Feature $feature
	 * @return void
	 */
	private function addBannerMessage(int $chatId, Feature $feature): void
	{
		\CIMMessenger::Add([
			'TO_CHAT_ID' => $chatId,
			'SYSTEM' => 'Y',
			'MESSAGE_TYPE' => 'C',
			'MESSAGE' => $this->getBannerMessage($feature),
			'PARAMS' => [
				'COMPONENT_ID' => self::BANNER_COMPONENT_ID,
				'COMPONENT_PARAMS' => [
					'TOOL_ID' => $this->getBannerId($feature),
				],
			],
		]);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		return Loader::includeModule('im') && Loader::includeModule('intranet');
	}
}
