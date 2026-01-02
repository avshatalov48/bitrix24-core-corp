<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Cloud\Dto\RegistrationDto;
use Bitrix\AI\Cloud\Configuration;
use Bitrix\AI\Cloud\LimitInspector;

class LimitControlBoxService
{
	protected RegistrationDto $registrationDto;
	protected Configuration $configuration;

	/**
	 * @param int $count
	 * @return ReserveBoxRequest|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function isAllowedQuery(int $count): ?ReserveBoxRequest
	{
		$registrationDto = $this->getRegistrationDto();
		if ($registrationDto === null)
		{
			return null;
		}

		$limitInspector = $this->getLimitInspector($registrationDto->serverHost);
		$responseResult = $limitInspector->isAllowedQuery(
			[
				'count' => $count
			],
			$registrationDto
		);

		if (!$responseResult->isSuccess())
		{
			$this->log(var_export($responseResult->getErrorCollection(), true));

			return null;
		}

		$data = $responseResult->getData();
		if (!array_key_exists('baasAvailable', $data) || !array_key_exists('errorLimitType', $data))
		{
			return null;
		}

		return new ReserveBoxRequest(
			$count,
			(bool)$data['baasAvailable'], /** @see \Bitrix\AiProxy\Controller\Limit::FIELD_API_BAAS_AVAILABLE */
			(string)$data['errorLimitType'] /** @see \Bitrix\AiProxy\Controller\Limit::FIELD_API_ERROR_LIMIT_TYPE */
		);
	}

	/**
	 * @param string $serverHost
	 * @return LimitInspector
	 */
	protected function getLimitInspector(string $serverHost): LimitInspector
	{
		return new LimitInspector($serverHost);
	}

	/**
	 * @return RegistrationDto|null
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	protected function getRegistrationDto(): ?RegistrationDto
	{
		if (!empty($this->registrationDto))
		{
			return $this->registrationDto;
		}

		$registrationDto = $this->getConfiguration()->getCloudRegistrationData();

		if ($registrationDto === null)
		{
			$this->log('Empty registration data');

			return null;
		}

		$this->registrationDto = $registrationDto;

		return $this->registrationDto;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	protected function log(string $message): void
	{
		AddMessage2Log('LIMIT_CONTROL_BOX_SERVICE: ' , $message);
	}

	protected function getConfiguration(): Configuration
	{
		if (empty($this->configuration))
		{
			$this->configuration = new Configuration();
		}

		return $this->configuration;
	}
}
