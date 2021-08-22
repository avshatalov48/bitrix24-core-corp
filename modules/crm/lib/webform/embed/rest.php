<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Main;
use Bitrix\Rest\RestException;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Crm\SiteButton\Guest;

/**
 * Class Rest
 * @package Bitrix\Crm\WebForm\Embed
 */
class Rest
{
	/**
	 * Register bindings.
	 *
	 * @param array $bindings Rest bindings.
	 * @return void
	 */
	public static function register(array &$bindings)
	{
		$bindings['crm.site.form.fill'] = [__CLASS__, 'fillForm'];
		$bindings['crm.site.form.user.get'] = [__CLASS__, 'getUser'];
	}

	/**
	 * Fill form.
	 *
	 * @param array $query Query parameters.
	 * @return array
	 * @throws RestException
	 */
	public static function fillForm($query)
	{
		$formId = empty($query['id']) ? null : (int) $query['id'];
		$securityCode = empty($query['sec']) ? null : $query['sec'];
		$values = (isset($query['values']) && $query['values'])
			? $query['values']
			: null;
		$properties = (isset($query['properties']) && $query['properties'])
			? $query['properties']
			: null;
		$trace = empty($query['trace']) ? null : $query['trace'];
		$recaptchaResponse = empty($query['recaptcha']) ? null : $query['recaptcha'];
		$signString = empty($query['security_sign']) ? null : $query['security_sign'];
		//$entities = empty($query['entities']) ? null : $query['entities'];


		if (!WebForm\Manager::isEmbeddingAvailable())
		{
			self::printErrors(["Form embedding feature disabled."]);
		}

		if (!$formId)
		{
			self::printErrors(["Parameter `id` required."]);
		}
		if (!$securityCode)
		{
			self::printErrors(["Parameter `sec` required."]);
		}

		$form = new WebForm\Form($formId);
		if (!$form->checkSecurityCode($securityCode))
		{
			self::printErrors(["Parameter `security_sign` is invalid."]);
		}

		if (!$form->isActive())
		{
			self::printErrors(["Form with id=`$formId` is disabled."]);
		}

		if ($form->isUsedCaptcha())
		{
			$recaptchaSecret = WebForm\ReCaptcha::getSecret(2) ?: WebForm\ReCaptcha::getDefaultSecret(2);
			$recaptchaKey = WebForm\ReCaptcha::getKey(2) ?: WebForm\ReCaptcha::getDefaultKey(2);
			if ($recaptchaSecret && $recaptchaKey)
			{
				if (!$recaptchaResponse)
				{
					self::printErrors(["Parameter `recaptcha` is invalid."]);
				}

				$recaptcha = new WebForm\ReCaptcha($recaptchaSecret);
				if (!$recaptcha->verify($recaptchaResponse))
				{
					self::printErrors([$recaptcha->getError()]);
				}
			}
		}

		$fill = $form->fill();

		///////////////////
		$values = $values ? Main\Web\Json::decode(
			Main\Text\Encoding::convertEncoding(
				$values,
				SITE_CHARSET,
				'UTF-8'
			)
		) : [];

		$properties = $properties ? Main\Web\Json::decode(
			Main\Text\Encoding::convertEncoding(
				$properties,
				SITE_CHARSET,
				'UTF-8'
			)
		) : [];

		$fill
			->setTrace($trace)
			->setValues($values)
			->setProperties($properties);

		$signString = $signString ? Main\Text\Encoding::convertEncoding(
			$signString,
			SITE_CHARSET,
			'UTF-8'
		): null;

		if ($signString)
		{
			$sign = new Sign();
			if ($sign->unpack($signString))
			{
				$fill->setEntities($sign->getEntities());
			}
		}

		$result = $fill->save();
		if (!$result->getId())
		{
			self::printErrors($result->getErrors());
		}


		$gid = $result->getResultEntity()->getTrace()->getGid();
		if (!$gid)
		{
			$gid = Guest::register([
				'ENTITIES' => array_map(
					function (array $item)
					{
						return [
							'ENTITY_TYPE_ID' => \CCrmOwnerType::resolveID($item['ENTITY_TYPE']),
							'ENTITY_ID' => $item['ENTITY_ID'],
						];
					},
					$result->getResultEntity()->getResultEntities()
				)
			]);
		}

		return [
			'resultId' => $result->getId(),
			'pay' => $form->isPayable(),
			'message' => $form->getSuccessText(),
			'gid' => $gid,
			'redirect' => [
				'url' => $result->getUrl(),
				'delay' => $form->getRedirectDelay(),
			]
		];
	}

	/**
	 * Get form.
	 *
	 * @param array $query Query parameters.
	 * @return array
	 * @throws RestException
	 */
	public static function getForm($query)
	{
		$formId = empty($query['id']) ? null : (int) $query['id'];
		$securityCode = empty($query['sec']) ? null : $query['sec'];
		$loaderOnly = !empty($query['loaderOnly']);

		if (!WebForm\Manager::isEmbeddingAvailable())
		{
			self::printErrors(["Form embedding feature disabled."]);
		}

		if (!$formId)
		{
			self::printErrors(["Parameter `id` required."]);
		}
		if (!$securityCode)
		{
			self::printErrors(["Parameter `sec` required."]);
		}

		$form = new WebForm\Form($formId);
		if (!$form->checkSecurityCode($securityCode))
		{
			self::printErrors(["Parameter `security_sign` is invalid."]);
		}

		if (!$form->isActive())
		{
			self::printErrors(["Form with id=`$formId` is disabled."]);
		}

		$appPack = Webpack\Form\App::instance();
		$scripts = WebForm\Script::getListContext($form->get(), []);

		return [
			'config' => $loaderOnly ? null : (new Config($form))->toArray(),
			'loader' => [
				'form' => [
					'inline' => $scripts['INLINE']['text'],
					'click' => $scripts['CLICK']['text'],
					'auto' => $scripts['AUTO']['text'],
				],
				'app' => [
					'link' => $appPack->getEmbeddedFileUrl(),
					'script' => $appPack->getEmbeddedBody(),
				],
			],
		];
	}

	/**
	 * Get user.
	 *
	 * @param array $query Query parameters.
	 * @return array
	 * @throws RestException
	 */
	public static function getUser($query)
	{
		$sign = empty($query['security_sign']) ? null : $query['security_sign'];
		if (!$sign)
		{
			self::printErrors(["Parameter `security_sign` required."]);
		}

		if (!WebForm\Manager::isEmbeddingAvailable())
		{
			self::printErrors(["Form embedding feature disabled."]);
		}

		$hash = new Sign();

		if (!$hash->unpack($sign))
		{
			self::printErrors(["Parameter `security_sign` is not valid."]);
		}

		return User::getData($hash->getEntities());
	}

	/**
	 * Print rest errors.
	 *
	 * @param string[] $errors Errors.
	 * @param string $errorCode Error Code.
	 * @return void
	 * @throws RestException
	 */
	protected static function printErrors(array $errors,  $errorCode = RestException::ERROR_CORE)
	{
		foreach ($errors as $error)
		{
			throw new RestException(
				$error,
				$errorCode,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}
	}
}
