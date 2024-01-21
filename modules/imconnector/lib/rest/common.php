<?php
namespace Bitrix\ImConnector\Rest;

use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

use	\Bitrix\Main\ArgumentNullException,
	\Bitrix\Main\Loader;

use \Bitrix\Rest\OAuth\Auth,
	\Bitrix\Rest\AuthTypeException,
	\Bitrix\Rest\NonLoggedExceptionDecorator,
	\Bitrix\Rest\RestException;

if(Loader::includeModule('rest'))
{
	/**
	 * Class Common
	 * @package Bitrix\ImConnector\Rest
	 */
	class Common extends \IRestService
	{
		/**
		 * @return array
		 */
		public static function onRestServiceBuildDescription()
		{
			return array(
				Library::SCOPE_REST_IMCONNECTOR => array(
					'imconnector.list' => [
						'callback' => [__CLASS__, 'list'],
						'options' => []
					],
				),
			);
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws ArgumentNullException
		 * @throws AuthTypeException
		 */
		public static function list($params, $n, \CRestServer $server)
		{
			if (!Loader::includeModule('imopenlines'))
			{
				throw new RestException('The ImOpenLines module is not installed.', 'ACCESS_DENIED', \CRestServer::STATUS_WRONG_REQUEST);
			}

			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new NonLoggedExceptionDecorator(new AuthTypeException());
			}

			$permission = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();
			$hasAccess = $permission->canPerform(
				\Bitrix\ImOpenlines\Security\Permissions::ENTITY_CONNECTORS,
				\Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY
			);
			if (!$hasAccess)
			{
				throw new RestException('You dont have access to this action', 'ACCESS_DENIED', \CRestServer::STATUS_WRONG_REQUEST);
			}

			return Connector::getListActiveConnectorReal();
		}
	}
}
