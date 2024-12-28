<?php

namespace Bitrix\ImOpenLines\V2\Controller;

use Bitrix\ImOpenLines\V2\Transfer\QueueTransfer;
use Bitrix\ImOpenLines\V2\Transfer\Transferable;
use Bitrix\ImOpenLines\V2\Transfer\UserTransfer;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

class Session extends BaseController
{
	public function getAutoWiredParameters()
	{
		return array_merge([
			new ExactParameter(
				Transferable::class,
				'transfer',
				function ($className, int $transferId) {
					return UserTransfer::getInstance($transferId);
				}
			),
			new ExactParameter(
				Transferable::class,
				'transfer',
				function ($className, string $transferId) {
					return QueueTransfer::getInstance($transferId);
				}
			),
		], parent::getAutoWiredParameters());
	}

	/**
	 * @restMethod imopenlines.v2.Session.answer
	 */
	public function answerAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->answer())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.markSpam
	 */
	public function markSpamAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->markSpam())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.transfer
	 */
	public function transferAction(\Bitrix\Im\V2\Chat $chat, Transferable $transfer): ?array
	{
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId());

		$result = $operator->transfer([
			'TRANSFER_ID' => $transfer->getTransferId(),
		]);

		if ($result)
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.skip
	 */
	public function skipAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->skip())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.finish
	 */
	public function finishAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		$result = $operator->closeDialog();

		if ($result->isSuccess())
		{
			return ['result' => true];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.finishAnother
	 */
	public function finishAnotherAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		$result = $operator->closeDialogOtherOperator();

		if ($result->isSuccess())
		{
			return ['result' => true];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.join
	 */
	public function joinAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->joinSession())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.start
	 */
	public function startAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->startSession())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.intercept
	 */
	public function interceptAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());

		if ($operator->interceptSession())
		{
			return ['result' => true];
		}

		$basicError = $operator->getError();

		if (!is_null($basicError))
		{
			$this->addError($basicError->getError());
		}

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.pin
	 */
	public function pinAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());
		$result = $operator->setPinMode();

		if ($result->isSuccess())
		{
			return ['result' => true];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	/**
	 * @restMethod imopenlines.v2.Session.unpin
	 */
	public function unpinAction(\Bitrix\Im\V2\Chat $chat): ?array
	{
		$currentUser = $this->getCurrentUser();
		$operator = new \Bitrix\ImOpenLines\Operator($chat->getChatId(), (int)$currentUser?->getId());
		$result = $operator->setPinMode(false);

		if ($result->isSuccess())
		{
			return ['result' => true];
		}

		$this->addErrors($result->getErrors());

		return null;
	}
}