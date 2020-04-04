<?php
namespace Bitrix\Crm\UserField;
use Bitrix\Main;
class FileViewer
{
	protected static $urlTemplates = array(
		\CCrmOwnerType::LeadName => "/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		\CCrmOwnerType::ContactName => "/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		\CCrmOwnerType::CompanyName => "/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		\CCrmOwnerType::DealName => "/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		\CCrmOwnerType::Invoice => "/bitrix/components/bitrix/crm.invoice.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		\CCrmOwnerType::Quote => "/bitrix/components/bitrix/crm.quote.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	);

	/** @var int */
	protected $entityTypeID = 0;
	/** @var string */
	protected $entityTypeName = '';

	public function __construct($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
	}

	public function getUrl($entityID, $fieldName, $fileID = 0)
	{
		$params = array('owner_id' => $entityID, 'field_name' => $fieldName);
		if($fileID > 0)
		{
			$params['file_id'] = $fileID;
		}
		return \CComponentEngine::MakePathFromTemplate(self::$urlTemplates[$this->entityTypeName], $params);
	}
}