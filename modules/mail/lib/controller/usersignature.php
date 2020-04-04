<?php

namespace Bitrix\Mail\Controller;

use Bitrix\Mail\Internals\UserSignatureTable;
use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class UserSignature extends Base
{
	const USER_SIGNATURES_LIMIT = 100;

	/**
	 * @param array $fields
	 * @return array|false
	 */
	public function addAction(array $fields)
	{
		$unsafeFields = (array) $this->getRequest()->getPostList()->getRaw('fields');
		\CUtil::decodeUriComponent($unsafeFields);

		if (($limit = Main\Config\Option::get('mail', 'user_signatures_limit', static::USER_SIGNATURES_LIMIT)) > 0)
		{
			$count = UserSignatureTable::getCount(array(
				'USER_ID' => CurrentUser::get()->getId(),
			));
			if ($count >= $limit)
			{
				Loc::loadMessages(__FILE__);
				$this->errorCollection[] = new Error(Loc::getMessage('MAIL_USER_SIGNATURE_LIMIT'));
				return false;
			}
		}

		$userSignature = new \Bitrix\Mail\Internals\Entity\UserSignature;

		$userSignature->set('USER_ID', CurrentUser::get()->getId());
		$userSignature->set('SENDER', $fields['sender']);
		$userSignature->set('SIGNATURE', $this->sanitize($unsafeFields['signature']));

		$result = $userSignature->save();

		if($result->isSuccess())
		{
			$userSignature = UserSignatureTable::getById($result->getId())->fetchObject();
			return $this->getAction($userSignature);
		}
		else
		{
			$this->errorCollection = $result->getErrors();
			return false;
		}
	}

	/**
	 * @param \Bitrix\Mail\Internals\Entity\UserSignature $userSignature
	 */
	public function deleteAction(\Bitrix\Mail\Internals\Entity\UserSignature $userSignature)
	{
		$userSignature->delete();
	}

	public function getAction(\Bitrix\Mail\Internals\Entity\UserSignature $userSignature)
	{
		return [
			'userSignature' => $this->convertArrayKeysToCamel($userSignature->collectValues(), 1),
		];
	}

	/**
	 * @param \Bitrix\Mail\Internals\Entity\UserSignature $userSignature
	 * @param array $fields
	 * @return array|false
	 */
	public function updateAction(\Bitrix\Mail\Internals\Entity\UserSignature $userSignature, array $fields)
	{
		$unsafeFields = (array) $this->getRequest()->getPostList()->getRaw('fields');
		\CUtil::decodeUriComponent($unsafeFields);

		$userSignature->set('SENDER', $fields['sender']);
		$userSignature->set('SIGNATURE', $this->sanitize($unsafeFields['signature']));

		$result = $userSignature->save();
		if($result->isSuccess())
		{
			return $this->getAction($userSignature);
		}
		else
		{
			$this->errorCollection = $result->getErrors();
			return false;
		}
	}
}
