<?
namespace Bitrix\Lists\Controller;

use Bitrix\Lists\Copy\Container;
use Bitrix\Lists\Copy\Field as FieldCopier;
use Bitrix\Lists\Copy\Iblock as IblockCopier;
use Bitrix\Lists\Copy\Section as SectionCopier;
use Bitrix\Lists\Security\IblockRight;
use Bitrix\Lists\Security\Right;
use Bitrix\Lists\Security\RightParam;
use Bitrix\Lists\Service\Param;
use Bitrix\Main\Copy\ContainerManager;

class Iblock extends Entity
{
	public function copyAction()
	{
		$param = $this->getParamFromRequest();
		$params = $param->getParams();

		$this->checkPermission($param, IblockRight::EDIT);
		if ($this->getErrors())
		{
			return null;
		}

		$iblockCopier = $this->getCopier();
		$iblockIdToCopy = $params["IBLOCK_ID"];

		$containerManager = $this->getContainerManager($iblockIdToCopy);

		$result = $iblockCopier->copy($containerManager);
		if ($result->getErrors())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		$listCopiedIds = $result->getData();
		return $listCopiedIds[$iblockIdToCopy];
	}

	private function checkPermission(Param $param, $permission)
	{
		global $USER;
		$rightParam = new RightParam($param);
		$rightParam->setUser($USER);

		$right = new Right($rightParam, new IblockRight($rightParam));
		$right->checkPermission($permission);
		if ($right->hasErrors())
		{
			$this->addErrors($right->getErrors());
		}
	}

	private function getCopier()
	{
		$iblock = new IblockCopier();
		$iblock->addEntityToCopy(new FieldCopier());
		$iblock->addEntityToCopy(new SectionCopier());

		return $iblock;
	}

	private function getContainerManager($entityId)
	{
		$containerManager = new ContainerManager();
		$container = new Container($entityId);
		$containerManager->addContainer($container);

		return $containerManager;
	}
}