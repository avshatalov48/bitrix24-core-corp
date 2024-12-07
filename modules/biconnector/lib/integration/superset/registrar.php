<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Superset\Config\ConfigContainer;
use Bitrix\BIConnector\Superset\Logger\SupersetInitializerLogger;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Registrar
{
	private const REGISTER_STAGE_OPTION = '~superset_register_stage';

	private static self $instance;

	private function __construct(private readonly ConfigContainer $config)
	{}

	public static function getRegistrar(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self(ConfigContainer::getConfigContainer());
		}

		return self::$instance;
	}

	public function clear(): void
	{
		$this->setRegisterStage(0);
		$this->config->clearConfig();
	}

	public function isComplete(): bool
	{
		return $this->getRegisterStage() >= 2;
	}

	public function register(): Result
	{
		while ($this->getRegisterStage() < 2)
		{
			$nextResult = $this->next();
			if (!$nextResult->isSuccess())
			{
				return $nextResult;
			}
		}

		return (new Result());
	}

	private function next(): Result
	{
		$stage = $this->getRegisterStage();

		return match ($stage)
		{
			0 => $this->registerPortalId(),
			1 => $this->verifyPortalId(),
			default => (function() {
				$this->setRegisterStage(0);
				new Result();
			})(),
		};
	}

	private function registerPortalId(): Result
	{
		if ($this->config->getPortalId())
		{
			$this->setRegisterStage($this->getRegisterStage() + 1);

			return new Result();
		}

		$result = self::registerPortal();
		if ($result->isSuccess())
		{
			$this->setRegisterStage($this->getRegisterStage() + 1);
		}

		return $result;
	}

	private function verifyPortalId(): Result
	{
		if ($this->config->isPortalIdVerified())
		{
			$this->setRegisterStage($this->getRegisterStage() + 1);

			return new Result();
		}

		$result = self::verifyPortal();
		if ($result->isSuccess())
		{
			$this->config->setPortalIdVerified(true);
			$this->setRegisterStage($this->getRegisterStage() + 1);
		}
		else
		{
			$this->setRegisterStage(0);
			$this->config->clearConfig();
		}

		return $result;
	}

	private function getRegisterStage(): int
	{
		return (int)Option::get('biconnector', self::REGISTER_STAGE_OPTION, 0);
	}

	private function setRegisterStage(int $stage): void
	{
		Option::set('biconnector', self::REGISTER_STAGE_OPTION, $stage);
	}

	private static function registerPortal(): Result
	{
		$result = new Result();
		if (!empty(ConfigContainer::getConfigContainer()->getPortalId()))
		{
			return $result;
		}

		$response = Integrator::getInstance()->registerPortal();

		if ($response->getStatus() === IntegratorResponse::STATUS_CREATED)
		{
			ConfigContainer::getConfigContainer()->setPortalIdVerified(true);

			return $result;
		}

		if ($result->isSuccess() && $response->getStatus() === IntegratorResponse::STATUS_IN_PROGRESS)
		{
			ConfigContainer::getConfigContainer()->setPortalId($response->getData());
		}
		else
		{
			SupersetInitializerLogger::logErrors([new Error('Register request end with errors'), ...$result->getErrors()], [
				'STATUS_CODE' => $response->getStatus(),
			]);

			$result->addErrors($response->getErrors());
		}

		return $result;
	}

	private static function verifyPortal(): Result
	{
		$result = new Result();
		$response = Integrator::getInstance()->verifyPortal();
		if ($response->getStatus() !== IntegratorResponse::STATUS_OK)
		{
			SupersetInitializerLogger::logErrors([new Error('Verify register request end with errors'), ...$response->getErrors()], [
				'STATUS_CODE' => $response->getStatus(),
			]);

			$result->addErrors([new Error("Invalid response status code from verify: {$response->getStatus()}"), ...$response->getErrors()]);
		}

		return $result;
	}
}