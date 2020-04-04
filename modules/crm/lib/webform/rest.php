<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\WebForm;

use Bitrix\Rest\RestException;

/**
 * Class Rest
 * @package Bitrix\Crm\WebForm
 */
class Rest
{
	/**
	 * Handler of `rest/onRestServiceBuildDescription` event.
	 *
	 * @return array
	 */
	public static function onRestServiceBuildDescription()
	{
		return array(
			'crm' => array(
				'crm.webform.list' => array(__CLASS__, 'getFormList'),
				'crm.webform.result.add' => array(__CLASS__, 'addFormResult'),
			)
		);
	}

	/**
	 * Get form list.
	 *
	 * @return array
	 */
	public static function getFormList()
	{
		$result = array();
		$res = Internals\FormTable::getList(array(
			'select' => array(
				'ID', 'NAME', 'SECURITY_CODE', 'IS_CALLBACK_FORM'
			),
			'filter' => array(
				'ACTIVE' => 'Y'
			),
			'order' => array(
				'ID' => 'DESC'
			)
		));
		while ($row = $res->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	/**
	 * Add form result.
	 *
	 * @param array $query Query parameters.
	 * @param int $nav Navigation.
	 * @param \CRestServer $server Rest server.
	 * @return int
	 * @throws RestException
	 */
	public static function addFormResult($query, $nav = 0, \CRestServer $server)
	{
		$formId = empty($query['FORM_ID']) ? null : $query['FORM_ID'];
		if (!$formId)
		{
			self::printErrors(["Parameter `FORM_ID` required."]);
		}
		if (!isset($query['FIELDS']) || !is_array($query['FIELDS']))
		{
			self::printErrors(["Wrong parameter `FIELDS`."]);
		}

		$form = new Form();
		if (!$form->load($formId))
		{
			self::printErrors(["Form not found."]);
		}
		if (!$form->isActive())
		{
			self::printErrors(["Form is not active."]);
		}

		$resultParameters = new ResultParameters($form);
		$resultParameters->addCallback(
			$resultParameters::EVENT_FIELDS_FILE,
			function ()
			{
				self::printErrors(['Field with type `file` in parameter `FIELDS` not implemented.'], RestException::ERROR_ARGUMENT);
			}
		);

		$resultParameters->setFields($query['FIELDS'])
			->setPresets((empty($query['PRESETS']) || !is_array($query['PRESETS'])) ? [] : $query['PRESETS'])
			->setFromUrl(empty($query['FROM_URL']) ? null : $query['FROM_URL']);

		if($form->hasErrors())
		{
			self::printErrors($form->getErrors());
		}

		$result = $form->addResult($resultParameters->getFieldsMap(), $resultParameters->toArray());
		if($result->hasErrors())
		{
			self::printErrors($result->getErrors());
		}

		return $result->getId();
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
