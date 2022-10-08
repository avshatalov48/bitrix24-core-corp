<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\NumberTable;
use Bitrix\Voximplant\Security\Permissions;

class Subscription extends Controller
{
	public function getWithNumberAction($number)
	{
		if (!Permissions::createWithCurrentUser()->canModifyLines())
		{
			$this->addError(new Error("Permission denied", "permission_denied"));
			return null;
		}

		$row = NumberTable::getRow([
			"select" => ["SUBSCRIPTION_ID"],
			"filter" => [
				"=NUMBER" => $number
			]
		]);

		if(!$row)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_SUBSCRIPTION_NUMBER_NOT_FOUND"), "not_found");
			return null;
		}

		$subscriptionId = $row["SUBSCRIPTION_ID"];
		$numbers = [];

		$cursor = NumberTable::getList([
			"select" => ["NUMBER"],
			"filter" => ["=SUBSCRIPTION_ID" => $subscriptionId]
		]);
		while ($row = $cursor->fetch())
		{
			$numbers[] = $row["NUMBER"];
		}

		return [
			"subscriptionId" => $subscriptionId,
			"numbers" => $numbers
		];
	}

	public function enqueueDisconnectAction($subscriptionId)
	{
		if (!Permissions::createWithCurrentUser()->canModifyLines())
		{
			$this->addError(new Error("Permission denied", "permission_denied"));
			return null;
		}

		$numbersInSubscription = NumberTable::getList([
			'filter' => [
				'=SUBSCRIPTION_ID' => $subscriptionId
			]
		])->fetchAll();

		if(empty($numbersInSubscription))
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_SUBSCRIPTION_NUMBER_NOT_FOUND"), "not_found");
			return null;
		}

		$viHttp = new \CVoxImplantHttp();
		if(count($numbersInSubscription) == 1)
		{
			$number = $numbersInSubscription[0]['NUMBER'];
			$result = $viHttp->DeactivatePhoneNumber($number);
		}
		else
		{
			$result = $viHttp->DeactivateSubscription($subscriptionId);
		}

		if (!$result)
		{
			$this->errorCollection[] = new Error($viHttp->GetError()->msg);
			return null;
		}

		$date = new DateTime();
		$date->add('23 HOUR');

		foreach ($numbersInSubscription as $item)
		{
			NumberTable::update($item["ID"], [
				'TO_DELETE' => 'Y',
				'DATE_DELETE' => $date
			]);
		}

		return $result;
	}

	public function cancelDisconnectAction($number)
	{
		if (!Permissions::createWithCurrentUser()->canModifyLines())
		{
			$this->addError(new Error("Permission denied", "permission_denied"));
			return null;
		}

		$row = NumberTable::getRow([
			'filter' => [
				'=NUMBER' => $number
			]
		]);

		if(!$row)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_SUBSCRIPTION_NUMBER_NOT_FOUND"), "not_found");
			return null;
		}

		$subscriptionId = $row["SUBSCRIPTION_ID"];

		$viHttp = new \CVoxImplantHttp();
		$result = $viHttp->CancelDeactivateSubscription($subscriptionId);
		if (!$result)
		{
			$this->errorCollection[] = new Error($viHttp->GetError()->msg);
			return null;
		}

		$cursor = NumberTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=SUBSCRIPTION_ID' => $subscriptionId
			]
		]);

		while ($row = $cursor->fetch())
		{
			NumberTable::update($row['ID'], [
				'TO_DELETE' => 'N',
				'DATE_DELETE' => null
			]);
		}
	}
}