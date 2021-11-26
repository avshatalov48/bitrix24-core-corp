<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Crm;

class Manager
{
	/**
	 * Is crm-forms in use.
	 *
	 * @param string $isCallback Is callback.
	 * @return bool
	 */
	public static function isInUse($isCallback = 'N')
	{
		$filter = array();
		if (in_array($isCallback, array('N', 'Y')))
		{
			$filter['=FORM.IS_CALLBACK_FORM'] = $isCallback;
		}
		$resultDb = Internals\ResultTable::getList(array('select' => array('ID'), 'filter' => $filter, 'limit' => 1));
		if ($resultDb->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check read permissions.
	 *
	 * @param null|\CCrmAuthorizationHelper $userPermissions User permissions.
	 * @return bool
	 */
	public static function checkReadPermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckReadPermission('WEBFORM', 0, $userPermissions);
	}

	/**
	 * Get path to crm-form list page.
	 *
	 * @return string
	 */
	public static function getUrl()
	{
		return Option::get('crm', 'path_to_webform_list', '/crm/webform/');
	}

	/**
	 * Get path to crm-form edit page.
	 *
	 * @param integer $formId Form Id.
	 * @param bool $landingOnly Get link in landing editor.
	 * @return string
	 */
	public static function getEditUrl($formId = 0, $landingOnly = false)
	{
		return ($formId && ($landingOnly || Crm\Settings\WebFormSettings::getCurrent()->isNewEditorEnabled()))
			? Internals\LandingTable::getLandingEditUrl($formId)
			: str_replace('#form_id#', $formId, Option::get('crm', 'path_to_webform_edit', '/crm/webform/edit/#form_id#/'))
		;
	}

	/**
	 * Get active non-callback form list.
	 *
	 * @param array $params Query parameters.
	 * @return array
	 */
	public static function getActiveForms($params = array('order' => array('ID' => 'DESC'), 'cache' => array('ttl' => 36000)))
	{
		if (!isset($params['filter']))
		{
			$params['filter'] = array();
		}
		$params['filter']['ACTIVE'] = 'Y';
		$params['filter']['IS_CALLBACK_FORM'] = 'N';

		$list = array();
		$listDb = Internals\FormTable::getList($params);
		while($item = $listDb->fetch())
		{
			$list[] = $item;
		}

		return $list;
	}

	/**
	 * Get plain form list.
	 *
	 * @return array
	 */
	public static function getListPlain()
	{
		$parameters = array();
		$parameters["cache"] = array("ttl" => 3600);
		return Internals\FormTable::getList($parameters)->fetchAll();
	}

	/**
	 * Get list form names list.
	 *
	 * @return array
	 */
	public static function getListNames()
	{
		static $result = null;
		if (!is_array($result))
		{
			$result = array();
			$formList = self::getListPlain();
			foreach ($formList as $form)
			{
				$result[$form['ID']] = $form['NAME'];
			}
		}

		return $result;
	}

	/**
	 * Get encoded form names list.
	 *
	 * @return array
	 */
	public static function getListNamesEncoded(): array
	{
		static $result = null;

		if (!is_array($result))
		{
			$result = self::getListNames();
			foreach ($result as $id => $name)
			{
				$result[$id] = htmlspecialcharsbx($name);
			}
		}

		return $result;
	}

	public static function isEmbeddingEnabled($formId)
	{
		return !!$formId;
	}

	public static function isEmbeddingAvailable()
	{
		return true;
	}

	public static function isOrdersAvailable()
	{
		return Loader::includeModule('salescenter');
	}

	public static function updateScriptCache($fromFormId = null, $limit = 50)
	{
		$filter = [];
		if ($fromFormId)
		{
			$filter['>=ID'] = $fromFormId;
		}

		$parameters = [
			'select' => ['ID'],
			'filter' => $filter,
			'order' => ['ID' => 'ASC'],
		];
		if ($limit)
		{
			$parameters['limit'] = $limit + 1;
		}
		$forms = Internals\FormTable::getList($parameters);
		foreach ($forms as $index => $item)
		{
			if ($limit && $index >= $limit)
			{
				return $item['ID'];
			}

			$form = new Form();
			$form->loadOnlyForm($item['ID']);
			if (!$form->buildScript())
			{
				return $form->getId();
			}
		}

		return null;
	}

	public static function updateScriptCacheAgent($fromFormId = null)
	{
		/*@var $USER CUser*/
		global $USER;
		if (!is_object($USER))
		{
			$USER = new \CUser();
		}

		$resultId = self::updateScriptCache($fromFormId);
		if ($resultId)
		{
			return '\\Bitrix\\Crm\\WebForm\\Manager::updateScriptCacheAgent(' . $resultId . ');';
		}
		else
		{
			return '';
		}
	}
}