<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
use Bitrix\Crm\UserField;
class EntityConversionFileViewer extends UserField\FileViewer
{
	/** @var int */
	protected $srcEntityTypeID = 0;
	/** @var string */
	protected $srcEntityTypeName = '';
	/** @var int */
	protected $srcEntityID = 0;
	/** @var EntityConversionMap|null  */
	private $map = null;

	public function __construct($dstEntityTypeID, $srcEntityTypeID, $srcEntityID)
	{
		parent::__construct($dstEntityTypeID);
		$this->srcEntityTypeID = $srcEntityTypeID;
		$this->srcEntityTypeName = \CCrmOwnerType::ResolveName($srcEntityTypeID);
		$this->srcEntityID = $srcEntityID;
	}
	public function getUrl($entityID, $fieldName, $fileID = 0)
	{
		$srcFieldName = '';

		/** @var EntityConversionMap|null $map */
		$map = $this->getMap();
		if($map !== null)
		{
			$srcFieldName = $map->resolveSourceID($fieldName);
		}

		if($srcFieldName !== '')
		{
			$params = array('owner_id' => $this->srcEntityID, 'field_name' => $srcFieldName);
			if($fileID > 0)
			{
				$params['file_id'] = $fileID;
			}
			return \CComponentEngine::MakePathFromTemplate(self::$urlTemplates[$this->srcEntityTypeName], $params);
		}
		return parent::getUrl($entityID, $fieldName, $fileID);
	}
	protected function getMap()
	{
		if($this->map == null)
		{
			$this->map = EntityConversionMap::load($this->srcEntityTypeID, $this->entityTypeID);
		}
		return $this->map;
	}
}