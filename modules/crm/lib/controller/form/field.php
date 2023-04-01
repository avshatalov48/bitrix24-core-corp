<?php
namespace Bitrix\Crm\Controller\Form;

use Bitrix\Main;
use Bitrix\Crm\WebForm;

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
		if (!WebForm\Manager::checkReadPermission())
		{
			$this->addError(new Main\Error('Access denied.', 510));
			return [];
		}

		return [
			'tree' => WebForm\EntityFieldProvider::getFieldsTree([], $presetId),
		];
	}
}
