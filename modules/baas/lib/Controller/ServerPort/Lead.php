<?php

namespace Bitrix\Baas\Controller\ServerPort;

use Bitrix\Main;
use Bitrix\Baas;

class Lead extends Main\Engine\Controller
{
	public function configureActions(): array
	{
		return [
			'verificationAck' => [
				'prefilters' => [],
			],
		];
	}

	public function verificationAckAction(): Main\Engine\Response\Json
	{
		$ack = $this->getRequest()->getHeader('X-Domain-Ack');
		$syn = $this->getRequest()->getHeader('X-Domain-Syn');
		if (empty($ack) || empty($syn))
		{
			Baas\Internal\Diag\Logger::getInstance()->error(
				'Host has been asked about domain verification with errors',
				[
					'X-Domain-Ack' => $ack,
					'X-Domain-Syn' => $syn,
				],
			);
			$result = (new Main\Result())->addError(new Main\Error('Acknowledge tokens are empty'));
		}
		else
		{
			Baas\Internal\Diag\Logger::getInstance()->notice(
				'Host has been asked about domain verification',
				[
					'X-Domain-Ack' => $ack,
					'X-Domain-Syn' => $syn,
				],
			);
			$result = Baas\Service\BillingService::getInstance()->verifyAckDomain(
				$this->getRequest()->getHeader('X-Domain-Ack'),
				$this->getRequest()->getHeader('X-Domain-Syn'),
			);
		}

		if ($result->isSuccess())
		{
			return Main\Engine\Response\AjaxJson::createSuccess($result->getData());
		}

		return Main\Engine\Response\AjaxJson::createError($result->getErrorCollection());
	}
}
