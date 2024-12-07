<?php
namespace Bitrix\Crm\Controller\Form;

use Bitrix\Main;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\AccessController;

/**
 * Class Field
 * @package Bitrix\Crm\Controller\Form
 */
class Field extends Main\Engine\JsonController
{
	/**
	 * List form fields action.
	 *
	 * @return array
	 */
	public function listAction(?int $presetId): array
	{
		if (!$this->checkPermissionForFieldList())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FORM_FIELD_ACCESS_DENIED'), 510));
			return [];
		}

		return [
			'tree' => WebForm\EntityFieldProvider::getFieldsTree([], $presetId),
		];
	}

	public function checkPermissionForFieldList(): bool
	{
		$result = WebForm\Manager::checkReadPermission();
		if (!$result && Main\Loader::includeModule('sign'))
		{
			$result = AccessController::can(
					Main\Engine\CurrentUser::get()->getId(),
					\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_ADD
				)
				|| AccessController::can(
					Main\Engine\CurrentUser::get()->getId(),
					\Bitrix\Sign\Access\ActionDictionary::ACTION_DOCUMENT_ADD
				)
			;
		}

		return $result;
	}
}
