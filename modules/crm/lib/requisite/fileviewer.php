<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
class FileViewer
{
	public function getUrl($entityID, $fieldName, $fileID = 0)
	{
		$params = array('owner_id' => $entityID, 'field_name' => $fieldName);
		if($fileID > 0)
			$params['file_id'] = $fileID;
		return \CComponentEngine::MakePathFromTemplate(
			"/bitrix/components/bitrix/crm.requisite/show_file.php".
			"?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
			$params
		);
	}
}