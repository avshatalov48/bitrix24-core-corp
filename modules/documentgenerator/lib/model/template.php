<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\DocumentGenerator\Body;
use Bitrix\DocumentGenerator\DataProvider\Filterable;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\DocumentGenerator\Dictionary;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

Loc::loadMessages(__FILE__);

/**
 * Class TemplateTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Template_Query query()
 * @method static EO_Template_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Template_Result getById($id)
 * @method static EO_Template_Result getList(array $parameters = array())
 * @method static EO_Template_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Template createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Template_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Template wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Template_Collection wakeUpCollection($rows)
 */
class TemplateTable extends FileModel
{
	public const PRODUCTS_TABLE_VARIANT_ALL = '';
	public const PRODUCTS_TABLE_VARIANT_SERVICE = Dictionary\ProductVariant::SERVICE;
	public const PRODUCTS_TABLE_VARIANT_GOODS = Dictionary\ProductVariant::GOODS;

	protected static $fileFieldNames = [
		'FILE_ID',
	];

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_template';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\BooleanField('ACTIVE', [
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\ORM\Fields\Validators\LengthValidator(1, 100),
					];
				}
			]),
			new Main\Entity\StringField('CODE'),
			new Main\Entity\StringField('REGION'),
			new Main\Entity\IntegerField('SORT', [
				'default_value' => 500,
			]),
			new Main\Entity\DatetimeField('CREATE_TIME', [
				'required' => true,
				'default_value' => function(){return new Main\Type\DateTime();},
			]),
			new Main\Entity\DatetimeField('UPDATE_TIME', [
				'default_value' => function(){return new Main\Type\DateTime();},
			]),
			new Main\Entity\IntegerField('CREATED_BY', [
				'default_value' => function()
				{
					return Driver::getInstance()->getUserId();
				}
			]),
			new Main\Entity\IntegerField('UPDATED_BY'),
			new Main\Entity\StringField('MODULE_ID', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('FILE_ID', [
				'required' => true,
			]),
			new Main\Entity\StringField('BODY_TYPE', [
				'required' => true,
				'validation' => function()
				{
					return [
						function($value)
						{
							if(is_a($value, Body::class, true))
							{
								return true;
							}
							else
							{
								return Loc::getMessage('DOCUMENTGENERATOR_MODEL_TEMPLATE_CLASS_VALIDATION', ['#CLASSNAME#' => $value, '#PARENT#' => Body::class]);
							}
						},
					];
				},
			]),
			new Main\Entity\ReferenceField(
				'PROVIDER',
				'\Bitrix\DocumentGenerator\Model\TemplateProvider',
				['=this.ID' => 'ref.TEMPLATE_ID']
			),
			new Main\Entity\ReferenceField(
				'USER',
				'\Bitrix\DocumentGenerator\Model\TemplateUser',
				['=this.ID' => 'ref.TEMPLATE_ID']
			),
			new Main\Entity\IntegerField('NUMERATOR_ID'),
			new Main\Entity\BooleanField('WITH_STAMPS', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new Main\Entity\EnumField('PRODUCTS_TABLE_VARIANT', [
				'values' => self::getProductsTableVariantList(),
				'default_value' => self::PRODUCTS_TABLE_VARIANT_ALL,
			]),
			new Main\Entity\BooleanField('IS_DELETED', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
		];
	}

	public static function getProductsTableVariantList() : array
	{
		return [
			self::PRODUCTS_TABLE_VARIANT_ALL,
			self::PRODUCTS_TABLE_VARIANT_GOODS,
			self::PRODUCTS_TABLE_VARIANT_SERVICE
		];
	}

	/**
	 * @param string $className
	 * @param null $userId
	 * @param mixed $value
	 * @param bool $activeOnly
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getListByClassName($className, $userId = null, $value = ' ', $activeOnly = true)
	{
		$userId = $userId === null ? $userId : (int)$userId;

		return static::getList([
			'order' => ['SORT' => 'asc', 'ID' => 'asc'],
			'filter' => self::prepareClassNameFilter((string)$className, $userId, $value, (bool)$activeOnly),
			'cache' => ['ttl' => 1800],
			'group' => ['ID'],
		])->fetchAll();
	}

	/**
	 * @param string $className
	 * @param int|null $userId
	 * @param mixed $value
	 * @param bool $activeOnly
	 * @return ConditionTree
	 */
	public static function prepareClassNameFilter(
		string $className,
		?int $userId = null,
		$value = ' ',
		bool $activeOnly = true
	): ConditionTree
	{
		$filterProvider = $className;
		if (is_a($className, Filterable::class, true))
		{
			/** @var Filterable $provider */
			$provider = DataProviderManager::getInstance()->getDataProvider($className, $value, [
				'isLightMode' => true,
				'noSubstitution' => true,
			]);
			if ($provider)
			{
				$filterProvider = $provider->getFilterString();
			}
		}
		$filter = Main\Entity\Query::filter()
			->where('IS_DELETED', 'N')
		;
		$filterProvider = mb_strtolower($filterProvider);
		if (mb_strpos($filterProvider, '%'))
		{
			$filterProvider = str_replace('\\', '\\\\', $filterProvider);
			$filter->whereLike('PROVIDER.PROVIDER', $filterProvider);
		}
		else
		{
			$filter->where('PROVIDER.PROVIDER', $filterProvider);
		}
		if ($activeOnly)
		{
			$filter->where('ACTIVE', 'Y');
		}
		if ($userId > 0)
		{
			$filter->where(Driver::getInstance()->getUserPermissions($userId)->getFilterForRelatedTemplateList());
		}

		return $filter;
	}

	/**
	 * @param Event $event
	 * @return Main\EventResult
	 */
	public static function onBeforeDelete(Event $event)
	{
		$id = $event->getParameter('primary')['ID'];
		$data = static::getById($id)->fetch();

		foreach(static::$fileFieldNames as $name)
		{
			if($data[$name])
			{
				static::$filesToDelete[] = $data[$name];
			}
		}

		TemplateProviderTable::deleteByTemplateId($id);
		TemplateUserTable::delete($id);
		static::addToStack();

		return parent::onBeforeDelete($event);
	}

	public static function onBeforeAdd(Event $event)
	{
		$result = parent::onBeforeAdd($event);
		if(!$result)
		{
			$result = new Main\ORM\EventResult();
		}
		$fileId = $event->getParameter('fields')['FILE_ID'];
		$size = FileTable::getSize($fileId);
		$maxSize = Bitrix24Manager::getMaximumTemplateFileSize();
		if($size > $maxSize)
		{
			$result->addError(new Main\ORM\EntityError(Loc::getMessage('DOCUMENTGENERATOR_MODEL_TEMPLATE_SIZE_IS_EXCEEDED', ['#SIZE#' => $maxSize])));
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return Main\Entity\EventResult
	 */
	public static function onAfterAdd(Event $event)
	{
		parent::onAfterAdd($event);
		static::normalizeBody($event->getParameter('primary')['ID']);
		static::addToStack();

		return new Main\Entity\EventResult();
	}

	/**
	 * @param Event $event
	 * @return Main\Entity\EventResult
	 * @throws \Exception
	 */
	public static function onAfterUpdate(Event $event)
	{
		if(!empty(static::$filesToDelete))
		{
			static::normalizeBody($event->getParameter('primary')['ID']);
		}
		static::addToStack();
		return parent::onAfterUpdate($event);
	}

	/**
	 * Normalizes Body of the template for correct work.
	 *
	 * @param int $templateId
	 */
	public static function normalizeBody($templateId)
	{
		if(!$templateId)
		{
			return;
		}
		$template = Template::loadById($templateId);
		if(!$template)
		{
			return;
		}
		$body = $template->getBody();
		if(!$body)
		{
			return;
		}

		$body->normalizeContent();
		FileTable::updateContent($template->FILE_ID, $body->getContent(), [
			'fileName' => $template->getFileName(),
			'contentType' => $body->getFileMimeType(),
			'isTemplate' => true,
		]);
	}

	/**
	 * @param mixed $primary
	 * @param bool $isForever
	 * @return Main\ORM\Data\DeleteResult
	 * @throws \Exception
	 */
	public static function delete($primary, $isForever = false)
	{
		if(!$isForever)
		{
			$documents = DocumentTable::getCount(['TEMPLATE_ID' => $primary]);
			if($documents == 0)
			{
				$isForever = true;
			}
		}
		if($isForever)
		{
			return parent::delete($primary);
		}

		$deleteResult = new Main\ORM\Data\DeleteResult();
		$result = static::update($primary, ['IS_DELETED' => 'Y', 'FILE_ID' => 0]);
		if(!$result->isSuccess())
		{
			$deleteResult->addErrors($result->getErrors());
		}

		return $deleteResult;
	}

	protected static function addToStack()
	{
		if(Loader::includeModule("pull"))
		{
			\CPullWatch::AddToStack(static::getPullTagName(), [
				'module_id' => Driver::MODULE_ID,
				'command' => static::getPullTagCommand(),
			]);
		}
	}

	/**
	 * @return string
	 */
	protected static function getPullTagName()
	{
		return 'DOCGENUPDATETEMPLATES';
	}

	/**
	 * @return string
	 */
	protected static function getPullTagCommand()
	{
		return 'updateTemplate';
	}

	/**
	 * @return bool|string
	 * @throws Main\LoaderException
	 */
	public static function getPullTag()
	{
		if(Loader::includeModule("pull"))
		{
			$pullTag = static::getPullTagName();
			\CPullWatch::Add(Driver::getInstance()->getUserId(), $pullTag, true);
			return $pullTag;
		}

		return false;
	}
}
