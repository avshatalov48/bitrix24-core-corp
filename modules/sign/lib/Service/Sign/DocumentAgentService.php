<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\ArgumentException;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Operation\Result\ConfigureResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type;

use Bitrix\Main;
use Bitrix\Sign\Type\DocumentStatus;

class DocumentAgentService
{
	private const ERROR_RETRY_DELAY = 60;

	/**
	 * Add agent for start signing
	 * @param string $uid
	 *
	 * @return void
	 */
	public function addConfigureAndStartAgent(string $uid): void
	{
		$agentName = $this->getConfigureAndStartAgentName($uid);
		if (!$this->agentExists($agentName))
		{
			$this->addAgent($agentName);
		}
	}

	/**
	 * Add agent for start signing
	 * @param string $uid
	 *
	 * @return void
	 */
	public function removeConfigureAndStartAgent(string $uid, int $retryCount = 0): void
	{
		$agentName = $this->getConfigureAndStartAgentName($uid, $retryCount);
		if ($this->agentExists($agentName))
		{
			$this->removeAgent($agentName);
		}
	}

	/**
	 * Configure and start signing
	 * @param string $uid
	 *
	 * @return string
	 */
	public static function configureAndStart(string $uid, $retryCount = 0): string
	{
		$agentService = Container::instance()->getDocumentAgentService();
		$result = Container::instance()->getDocumentService()->configureAndStart($uid);
		if (!$result->isSuccess())
		{
			if ($retryCount >= 4)
			{
				self::handleSigningConfigureErrors($uid, $result->getErrorCollection());
			}
			else
			{
				$agentService->addAgent(
					agentName: $agentService->getConfigureAndStartAgentName($uid, ++$retryCount),
					nextDateExec: \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+self::ERROR_RETRY_DELAY, "FULL")
				);
			}

			return '';
		}

		if ($result instanceof ConfigureResult && !$result->completed)
		{
			return $agentService->getConfigureAndStartAgentName($uid, $retryCount);
		}

		return '';
	}

	private static function handleSigningConfigureErrors(string $documentUid, Main\ErrorCollection $errors): void
	{
		$documentRepository = Container::instance()->getDocumentRepository();
		$memberRepository = Container::instance()->getMemberRepository();
		$documentItem = $documentRepository->getByUid($documentUid);

		if (!$documentItem)
		{
			return;
		}
		/** @var Main\Error $error */
		foreach ($errors as $error)
		{

			if ($error->getCode() === 'SIGN_DOCUMENT_INCORRECT_STATUS')
			{
				//This is normal case for automated b2b signing, should not go to timeline
				continue;
			}


			$memberItem = null;
			$phone = null;
			if (in_array($error->getCode(), ['MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE', 'MEMBER_INVALID_PHONE'], true))
			{
				$customData = $error->getCustomData();
				$phone = $customData['phone'] ?? null;

				if ($phone)
				{
					$members = $memberRepository->listByDocumentId($documentItem->id);
					$memberItem = $members->findFirst(static function (Member $member) use ($phone) {
						return
							$member->channelType === Type\Member\ChannelType::PHONE
							&&
							(
								$phone === $member->channelValue
								|| $phone === Main\PhoneNumber\Parser::getInstance()
									->parse($member->channelValue)
									->format(Main\PhoneNumber\Format::E164)
							)
							;
					});
				}
			}

			if (!$memberItem)
			{
				$memberItem = $memberRepository->getAssigneeByDocumentId($documentItem->id);
			}

			$eventData = (new EventData())
				->setEventType(EventData::TYPE_ON_CONFIGURATION_ERROR)
				->setDocumentItem($documentItem)
				->setMemberItem($memberItem)
				->setError($error)
			;

			try
			{
				Container::instance()->getEventHandlerService()->createTimelineEvent($eventData);
			}
			catch (ArgumentException|Main\ArgumentOutOfRangeException $e)
			{
			}

			if (Type\DocumentScenario::isB2EScenario($documentItem->scenario))
			{
				$documentItem->status = DocumentStatus::UPLOADED;
				$documentRepository->update($documentItem);
			}
		}
	}
	private function addAgent(string $agentName, int $interval = 1, string $nextDateExec = '')
	{
		$agent = new \CAgent();
			$agent->AddAgent(
				name: $agentName,
				module: "sign",
				period: "Y",
				interval: $interval,
				next_exec: $nextDateExec ?: \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+$interval, "FULL")
			);
	}

	private function removeAgent(string $agentName)
	{
		$agent = new \CAgent();
		$list = $agent->getList(
			["ID" => "DESC"],
			["MODULE_ID" => "sign", "NAME" => $agentName]
		);
		while ($row = $list->fetch())
		{
			$agent->delete($row["ID"]);
		}
	}

	private function agentExists(string $agentName)
	{
		$agent = new \CAgent();
		return (bool)$agent->getList(
			["ID" => "DESC"],
			["MODULE_ID" => "sign", "NAME" => $agentName]
		)->fetch();
	}

	public function getConfigureAndStartAgentName(string $uid, $retryCount = 0)
	{
		return "\\" . __CLASS__ . "::configureAndStart('$uid', $retryCount);";
	}
}
