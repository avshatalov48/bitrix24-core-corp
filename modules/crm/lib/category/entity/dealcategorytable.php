<?php
namespace Bitrix\Crm\Category\Entity;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;

class DealCategoryTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_category';
	}
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			],
			'CREATED_DATE' => [
				'data_type' => 'date',
				'required' => true,
				'default_value' => static function() {
					return new Main\Type\DateTime();
				}
			],
			'NAME' => [
				'data_type' => 'string'
			],
			'IS_LOCKED' => [
				'data_type' => 'boolean',
				'values' => ['N', 'Y'],
				'default_value' => 'N'
			],
			'SORT' => [
				'data_type' => 'integer'
			],
			'ORIGIN_ID' => [
				'data_type' => 'string'
			],
			'ORIGINATOR_ID' => [
				'data_type' => 'string'
			],
		];
	}

	public static function onAfterAdd(Event $event)
	{
		try
		{
			if(DocumentGeneratorManager::getInstance()->isEnabled())
			{
				$categoryId = $event->getParameter('primary')['ID'];
				$category = DealCategory::get($categoryId);
				if(!is_array($category))
				{
					return;
				}
				$codes = [];
				$controller = new \Bitrix\DocumentGenerator\Controller\Template();
				$result = $controller::getDefaultTemplateList(['MODULE_ID' => 'crm']);
				if($result->isSuccess())
				{
					$codes = array_keys($result->getData());
				}
				if(empty($codes))
				{
					return new Main\ORM\EventResult();
				}
				$provider = Deal::getExtendedProviderByCategory($category);
				$templates = \Bitrix\DocumentGenerator\Model\TemplateTable::getList([
					'select' => ['ID'],
					'filter' => [
						'@CODE' => $codes,
					]
				]);
				while($template = $templates->fetch())
				{
					\Bitrix\DocumentGenerator\Model\TemplateProviderTable::add([
						'TEMPLATE_ID' => $template['ID'],
						'PROVIDER' => $provider['PROVIDER'],
					]);
				}
			}
		}
		finally
		{
			return new Main\ORM\EventResult();
		}
	}

	public static function onBeforeDelete(Event $event)
	{
		try
		{
			if(DocumentGeneratorManager::getInstance()->isEnabled())
			{
				$categoryId = $event->getParameter('primary')['ID'];
				$category = DealCategory::get($categoryId);
				if(!is_array($category))
				{
					return new Main\ORM\EventResult();
				}
				$provider = Deal::getExtendedProviderByCategory($category);
				$templates = \Bitrix\DocumentGenerator\Model\TemplateTable::getListByClassName($provider['PROVIDER'], null, ' ', false);
				foreach($templates as $template)
				{
					\Bitrix\DocumentGenerator\Model\TemplateProviderTable::delete([
						'TEMPLATE_ID' => $template['ID'],
						'PROVIDER' => $provider['PROVIDER'],
					]);
				}
			}
		}
		finally
		{
			return new Main\ORM\EventResult();
		}
	}
}
