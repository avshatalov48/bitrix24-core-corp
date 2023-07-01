<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\DeleteResult;

/**
 * Class ExternalLinkTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalLink_Query query()
 * @method static EO_ExternalLink_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ExternalLink_Result getById($id)
 * @method static EO_ExternalLink_Result getList(array $parameters = array())
 * @method static EO_ExternalLink_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_ExternalLink_Collection wakeUpCollection($rows)
 */
class ExternalLinkTable extends ORM\Data\DataManager
{
	protected static $cache = [];

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_documentgenerator_external_link';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new ORM\Fields\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new ORM\Fields\IntegerField('DOCUMENT_ID', [
				'required' => true,
			]),
			new ORM\Fields\StringField('HASH', [
				'required' => true,
			]),
			new ORM\Fields\Relations\Reference('DOCUMENT', '\Bitrix\DocumentGenerator\Model\Document',
				['=this.DOCUMENT_ID' => 'ref.ID']
			),
			new ORM\Fields\DatetimeField('VIEWED_TIME'),
		];
	}

	public static function loadByDocumentId(int $documentId): ?array
	{
		if($documentId <= 0)
		{
			return null;
		}
		if(!isset(static::$cache[$documentId]))
		{
			$item = static::getList(['filter' => ['=DOCUMENT_ID' => $documentId]])->fetch();
			if($item)
			{
				static::$cache[$documentId] = $item;
			}
		}

		return static::$cache[$documentId] ?? null;
	}

	/**
	 * @param int $documentId
	 * @return string|false
	 */
	public static function getByDocumentId(int $documentId)
	{
		$item = static::loadByDocumentId($documentId);
		if($item)
		{
			$url = static::formUrl($documentId, $item['HASH']);
			if($url)
			{
				return \CBXShortUri::GetShortUri($url);
			}
		}

		return false;
	}

	/**
	 * @param int $documentId
	 * @param string $hash
	 * @return string|false
	 */
	protected static function formUrl(int $documentId, string $hash)
	{
		if($documentId > 0 && !empty($hash))
		{
			return '/pub/document/'.$documentId.'/'.$hash.'/';
		}

		return false;
	}

	/**
	 * @param int $documentId
	 */
	public static function deleteByDocumentId(int $documentId): DeleteResult
	{
		$link = static::getList(['select' => ['ID'], 'filter' => ['=DOCUMENT_ID' => $documentId]])->fetch();
		if($link)
		{
			return static::delete($link['ID']);
		}

		return new DeleteResult();
	}

	/**
	 * @param string $hash
	 * @return array|false
	 */
	public static function getByHash(string $hash)
	{
		if($hash)
		{
			return static::getList(['filter' => ['=HASH' => $hash]])->fetch();
		}

		return false;
	}

	/**
	 * @param $documentId
	 * @param bool $isAbsolute
	 * @return array
	 */
	public static function getPublicUrlsByDocumentId(int $documentId, bool $isAbsolute = false): array
	{
		$result = [];
		if($documentId > 0)
		{
			$item = static::getList([
				'select' => ['HASH', 'DOCUMENT_PDF_ID' => 'DOCUMENT.PDF_ID', 'DOCUMENT_IMAGE_ID' => 'DOCUMENT.IMAGE_ID'],
				'filter' => ['=DOCUMENT_ID' => $documentId],
			])->fetch();
			if($item)
			{
				$hash = $item['HASH'];
				$urlManager = UrlManager::getInstance();
				$result['hash'] = $hash;
				$result['publicDownloadUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getFile', [
					'id' => $documentId,
					'hash' => $hash,
				], $isAbsolute);
				if($item['DOCUMENT_IMAGE_ID'] > 0)
				{
					$result['imageUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getImage', [
						'id' => $documentId,
						'hash' => $hash,
					], $isAbsolute);
				}
				if($item['DOCUMENT_PDF_ID'] > 0)
				{
					$result['pdfUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getPdf', [
						'id' => $documentId,
						'hash' => $hash,
					], $isAbsolute);
					$result['printUrl'] = $urlManager->create('documentgenerator.api.publicdocument.showPdf', [
						'print' => 'y',
						'id' => $documentId,
						'hash' => $hash,
					], $isAbsolute);
				}
			}
		}

		return $result;
	}

	public static function onBeforeDelete(Event $event): ORM\EventResult
	{
		$id = $event->getParameter('primary')['ID'];
		$link = static::getById($id)->fetch();
		if($link)
		{
			$uri = \CBXShortUri::GetList([], ['URI' => static::formUrl($link['DOCUMENT_ID'], $link['HASH'])])->Fetch();
			if($uri)
			{
				\CBXShortUri::Delete($uri['ID']);
			}
		}

		return new ORM\EventResult();
	}

	public static function isUserEmployee(int $userId = null): bool
	{
		if($userId === null)
		{
			$userId = Driver::getInstance()->getUserId();
		}
		if($userId <= 0)
		{
			return false;
		}

		$user = \Bitrix\Intranet\UserTable::getList([
			'filter' => [
				'=ID' => $userId
			],
			'select' => ['ID', 'USER_TYPE']

		])->fetch();

		return ($user && $user['USER_TYPE'] === 'employee');
	}
}
