<?php

namespace Bitrix\AI;

use Bitrix\AI\Facade\Bitrix24;

class Rest
{
	private const REST_SCOPE_ADMIN = 'ai_admin';

	/**
	 * REST Endpoint for retrieve available methods.
	 *
	 * @return array
	 */
	public static function onRestServiceBuildDescription(): array
	{
		$actions = [
			'ai.engine.unRegister' => [ThirdParty\Manager::class, 'unRegister'],
			'ai.engine.list' => [ThirdParty\Manager::class, 'getList'],
			'ai.prompt.register' => [Prompt\Rest::class, 'register'],
			'ai.prompt.unRegister' => [Prompt\Rest::class, 'unRegister'],
			'ai.history.enable' => [History\Rest::class, 'enable'],
			'ai.history.disable' => [History\Rest::class, 'disable'],
			'ai.history.list' => [History\Rest::class, 'getList'],
		];

		if (self::isAllowedRegisterEngine())
		{
			$actions['ai.engine.register'] = [ThirdParty\Manager::class, 'register'];
		}

		return [
			self::REST_SCOPE_ADMIN => $actions,
		];
	}

	/**
	 * Checks if is allowed to register third-party engine.
	 * @return bool
	 */
	private static function isAllowedRegisterEngine(): bool
	{
		return Bitrix24::shouldUseB24();
	}

	/**
	 * Executes when REST Application was deleted.
	 *
	 * @param mixed $app Application data.
	 * @return void
	 */
	public static function onRestAppDelete(mixed $app): void
	{
		if ($app['APP_ID'] ?? null)
		{
			$app['CODE'] = Facade\Rest::getApplicationCode($app['APP_ID']);
		}

		if ($app['CODE'] ?? null)
		{
			Prompt\Manager::deleteByFilter(['=APP_CODE' => $app['CODE']]);
			ThirdParty\Manager::deleteByAppCode($app['CODE']);
		}
	}
}
