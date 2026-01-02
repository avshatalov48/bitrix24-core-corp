<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;
use \Bitrix\Baas\UseCase\External\Exception\UseCaseException;

class Factory
{
	public static function createFromErrorCollection(Main\ErrorCollection $collection): UseCaseException
	{
		/** @var \Bitrix\Main\Error $error */
		foreach ($collection as $error)
		{
			if (is_string($error->getCode()) && str_starts_with($error->getCode(), 'baascontroller:'))
			{
				[, $category, $exceptionSymbolicCode] = explode(':', $error->getCode());

				if ($category === 'billing')
				{
					$class = BillingRespondsInWrongFormatException::class;
				}
				elseif ($category === 'client')
				{
					$class = match ($exceptionSymbolicCode)
					{
						//'is_not_recognized'
						ClientIsNotRecognizedException::SYMBOLIC_CODE => ClientIsNotRecognizedException::class,
						// 'host_key_is_not_recognized',
						ClientHostKeyIsNotRecognizedException::SYMBOLIC_CODE => ClientHostKeyIsNotRecognizedException::class,
						// 'host_name_is_not_recognized'
						ClientHostNameIsNotRecognizedException::SYMBOLIC_CODE => ClientHostNameIsNotRecognizedException::class,
						// 'is_not_responding'
						ClientIsNotRespondingException::SYMBOLIC_CODE => ClientIsNotRespondingException::class,
						// 'wrong_format'
						ClientRespondsInWrongFormatException::SYMBOLIC_CODE => ClientRespondsInWrongFormatException::class,
						// 'is_not_bitrix24'
						ClientIsNotBitrix24Exception::SYMBOLIC_CODE => ClientIsNotBitrix24Exception::class,
						default => ClientException::class,
					};
				}
				elseif ($category === 'service')
				{
					$class = match ($exceptionSymbolicCode)
					{
						// 'not_enough',
						ServiceIsNotEnoughException::SYMBOLIC_CODE => ServiceIsNotEnoughException::class,
						// 'not_supported',
						ServiceIsNotSupportedException::SYMBOLIC_CODE => ServiceIsNotSupportedException::class,
						default => ServiceException::class,
					};
				}
			}
			else
			{
				$class = match ($error->getCode())
				{
					// 'LICENSE_NOT_FOUND',
					ClientLicenseIsNotFoundException::SYMBOLIC_CODE => ClientLicenseIsNotFoundException::class,
					// 'NETWORK'
					ClientNetworkException::SYMBOLIC_CODE => ClientNetworkException::class,
					default => UnknownException::class,
				};
			}

			if (!isset($class))
			{
				$class = UnknownException::class;
			}

			return (new $class())->setError($error);
		}

		return new UnknownException();
	}
}
