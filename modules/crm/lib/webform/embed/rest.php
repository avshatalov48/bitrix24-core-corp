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
		$trace = empty($query['trace']) ? null : $query['trace'];
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
		if (!$form->isActive())
		{
			self::printErrors(["Form with id=`$formId` is disabled."]);
		}

		if (!$form->checkSecurityCode($securityCode))
		{
			self::printErrors(["Parameter `security_sign` is invalid."]);
		}

		///////////////////
		$values = $values ? Main\Web\Json::decode(
			Main\Text\Encoding::convertEncoding(
				$values,
				SITE_CHARSET,
				'UTF-8'
			)
		) : [];

		$signString = $signString ? Main\Text\Encoding::convertEncoding(
			$signString,
			SITE_CHARSET,
			'UTF-8'
		): null;

		$entities = [];
		$sign = new Sign();
		if ($sign->unpack($signString))
		{
			$entities = $sign->getEntities()->toSimpleArray(['typeId', 'id']);
		}


		$fields = $form->getFieldsMap();
		foreach($fields as $fieldKey => $field)
		{
			$fieldName = $field['name'];
			$fieldValues = isset($values[$fieldName]) ? $values[$fieldName] : [];
			if(!is_array($fieldValues))
			{
				$fieldValues = [$fieldValues];
			}

			if($field['type'] == 'file')
			{
				$files = [];
				foreach ($fieldValues as $fileData)
				{
					if (empty($fileData))
					{
						continue;
					}

					$filePos = strpos($fileData['content'], 'base64');
					$fileData['content'] = substr($fileData['content'], $filePos + 6);
					$files[] = \CRestUtil::saveFile($fileData['content'], $fileData['name']);
				}
				$fieldValues = $files;
			}
			elseif($field['type'] == 'phone')
			{
				$fieldValues = array_map(
					function ($value)
					{
						return preg_replace("/[^0-9+]/", '', $value);
					},
					$fieldValues
				);
			}
			else if ($field['entity_field_name'] == 'COMMENTS')
			{
				$fieldValues = array_map(
					function ($value)
					{
						return htmlspecialcharsbx($value);
					},
					$fieldValues
				);
			}

			$field['values'] = $fieldValues;
			$fields[$fieldKey] = $field;
		}

		$result = $form->addResult(
			$fields,
			[
				'ENTITIES' => $entities,
				'COMMON_FIELDS' => [],
				'PLACEHOLDERS' => [],
				'STOP_CALLBACK' => false,
				'COMMON_DATA' => [
					'VISITED_PAGES' => [],
					'TRACE' => $trace
				],
			]
		);

		if (!$result->getId())
		{
			self::printErrors($result->getErrors());
		}

		return [
			'resultId' => $result->getId(),
			'pay' => $form->isPayable(),
			'message' => $form->getSuccessText(),
			'redirect' => [
				'url' => $result->getUrl(),
				'delay' => $form->getRedirectDelay(),
			]
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
