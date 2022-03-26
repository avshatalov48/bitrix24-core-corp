<?php

namespace Bitrix\ImBot;

use Bitrix\Main\Loader;
use Bitrix\Rest;
use Bitrix\Rest\RestException;
use Bitrix\Rest\AccessException;

if(!Loader::includeModule('rest'))
{
	return;
}

class RestService extends \IRestService
{
	/**
	 * Builds list of REST module's methods.
	 * @return \array[][]
	 */
	public static function onRestServiceBuildDescription(): array
	{
		return [
			'imbot' => [
				'imbot.support24.question.config.get' => [__CLASS__, 'support24QuestionGetConfig'],
				'imbot.support24.question.add' => [__CLASS__, 'support24QuestionAdd'],
				'imbot.support24.question.list' => [__CLASS__, 'support24QuestionList'],
				'imbot.support24.question.search' => [__CLASS__, 'support24QuestionSearch'],
			]
		];
	}

	/**
	 * Returns
	 *
	 * @param array $params Unused.
	 * @param int $offset Unused.
	 * @param \CRestServer $server Rest server.
	 * @return array
	 * @throws RestException
	 */
	public static function support24QuestionGetConfig($params, $offset, \CRestServer $server): array
	{
		if (!self::validateRequest($params, $server))
		{
			return [];
		}

		$classSupport = self::detectSupportBot();

		$config = $classSupport::getSupportQuestionConfig();

		if ($classSupport::hasError())
		{
			self::throwException($classSupport::getError());
		}

		return $config;
	}

	/**
	 * Starts new question dialog.
	 *
	 * @param array $params Unused.
	 * @param int $offset Unused.
	 * @param \CRestServer $server Rest server.
	 * @return int
	 * @throws RestException
	 */
	public static function support24QuestionAdd($params, $offset, \CRestServer $server)
	{
		if (!self::validateRequest($params, $server))
		{
			return -1;
		}

		$classSupport = self::detectSupportBot();

		$chatId = $classSupport::addSupportQuestion();

		if ($classSupport::hasError())
		{
			self::throwException($classSupport::getError());
		}

		return $chatId;
	}

	/**
	 * Returns the question dialog list.
	 * @param array $params Query parameters.
	 * <pre>
	 * [
	 * 	(int) limit - Number rows to select.
	 * 	(int) offset - Set starting offset.
	 * ]
	 * </pre>
	 * @param int $offset Starting offset.
	 * @param \CRestServer $server Rest server.
	 * @return array{id: int, title: string}
	 * @throws RestException
	 */
	public static function support24QuestionList($params, $offset = 0, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		unset($params['SEARCHQUERY']);

		return self::support24QuestionSearch($params, $offset, $server);
	}

	/**
	 * Perfoms searching within question dialogs.
	 * @param array $params Query parameters.
	 * <pre>
	 * [
	 * 	(string) searchQuery - String to search by title.
	 * 	(int) limit - Number rows to select.
	 * 	(int) offset - Set starting offset.
	 * ]
	 * </pre>
	 * @param int $offset Starting offset.
	 * @param \CRestServer $server Rest server.
	 * @return array{id: int, title: string}
	 * @throws RestException
	 */
	public static function support24QuestionSearch($params, $offset = 0, \CRestServer $server)
	{
		if (!self::validateRequest($params, $server))
		{
			return [];
		}

		$params = array_change_key_case($params, CASE_UPPER);
		if ($offset > 0)
		{
			$params['OFFSET'] = $offset;
		}

		$classSupport = self::detectSupportBot();

		$questions = $classSupport::getSupportQuestionList($params);

		if ($classSupport::hasError())
		{
			self::throwException($classSupport::getError());
		}

		return $questions;
	}


	/**
	 * Detects installed support bot.
	 * @return \Bitrix\ImBot\Bot\SupportBot & \Bitrix\Imbot\Bot\SupportQuestion|string|null
	 */
	private static function detectSupportBot(): ?string
	{
		static $classSupport = null;

		if ($classSupport === null)
		{
			/** @var \Bitrix\Imbot\Bot\SupportBot $classSupport */
			if (
				Loader::includeModule('bitrix24')
				&& \Bitrix\ImBot\Bot\Support24::isEnabled()
			)
			{
				$classSupport = \Bitrix\ImBot\Bot\Support24::class;
			}
			elseif (\Bitrix\ImBot\Bot\SupportBox::isEnabled())
			{
				$classSupport = \Bitrix\ImBot\Bot\SupportBox::class;
			}
		}

		return $classSupport;
	}

	/**
	 * Preforms common request verifications.
	 * @param array $params Unused.
	 * @param \CRestServer $server Rest server.
	 * @return bool
	 * @throws RestException
	 */
	private static function validateRequest($params, \CRestServer $server): bool
	{
		if ($server->getAuthType() !== Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException(
				'Access for this method not allowed with non session authorization.',
				'WRONG_AUTH_TYPE',
				\CRestServer::STATUS_FORBIDDEN
			);
		}

		if(
			!Loader::includeModule('im')
			|| !Loader::includeModule('imbot')
		)
		{
			throw new RestException(
				'Necessary modules are missing',
				'WRONG_REQUEST',
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$classSupport = self::detectSupportBot();
		if (empty($classSupport) || $classSupport::getBotId() <= 0)
		{
			throw new RestException(
				'Support bot has not been installed',
				'WRONG_REQUEST',
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		return true;
	}

	/**
	 * @param Error $error
	 * @throws RestException
	 * @return void|never
	 */
	private static function throwException(Error $error): void
	{
		$status =
			$error->code === AccessException::CODE
			? \CRestServer::STATUS_FORBIDDEN
			: \CRestServer::STATUS_WRONG_REQUEST;

		throw new RestException($error->msg, $error->code, $status);
	}
}
