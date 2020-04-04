<?
namespace Bitrix\Lists\Controller;

use Bitrix\Lists\Entity\Element as ElementEntity;
use Bitrix\Lists\Entity\Utils;
use Bitrix\Lists\Security\ElementRight;
use Bitrix\Lists\Security\Right;
use Bitrix\Lists\Security\RightParam;
use Bitrix\Lists\Service\Param;

class Element extends Entity
{
	public function copyAction()
	{
		$param = $this->getParamFromRequest();
		$params = $param->getParams();

		$this->checkPermission($param, ElementRight::EDIT);
		if ($this->getErrors())
		{
			return null;
		}

		$element = new ElementEntity($param);
		if ($id = $element->copyById($params["IBLOCK_ID"], $params["ELEMENT_ID"]))
		{
			return $id;
		}
		else
		{
			if ($element->getErrors())
			{
				$this->addErrors($element->getErrors());
			}
			return null;
		}
	}

	protected function checkPermission(Param $param, $permission)
	{
		global $USER;
		$rightParam = new RightParam($param);
		$rightParam->setUser($USER);
		$rightParam->setEntityId(Utils::getElementId($param->getParams()));

		$right = new Right($rightParam, new ElementRight($rightParam));
		$right->checkPermission($permission);
		if ($right->hasErrors())
		{
			$this->addErrors($right->getErrors());
		}
	}
}