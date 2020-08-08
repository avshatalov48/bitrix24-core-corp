<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\Event;

class ExternalLinkTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_external_link';
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
			new Main\Entity\IntegerField('DOCUMENT_ID', [
				'required' => true,
			]),
			new Main\Entity\StringField('HASH', [
				'required' => true,
			]),
			new Main\Entity\ReferenceField('DOCUMENT', '\Bitrix\DocumentGenerator\Model\Document',
				['=this.DOCUMENT_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * @param int $documentId
	 * @return string|false
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getByDocumentId($documentId)
	{
		if($documentId > 0)
		{
			$item = static::getList(['filter' => ['DOCUMENT_ID' => $documentId]])->fetch();
			if($item)
			{
				$url = static::formUrl($documentId, $item['HASH']);
				if($url)
				{
					return \CBXShortUri::GetShortUri($url);
				}
			}
		}

		return false;
	}

	/**
	 * @param int $documentId
	 * @param string $hash
	 * @return string|false
	 */
	protected static function formUrl($documentId, $hash)
	{
		if($documentId > 0 && !empty($hash))
		{
			return '/pub/document/'.$documentId.'/'.$hash.'/';
		}

		return false;
	}

	/**
	 * @param int $documentId
	 * @return Main\Entity\DeleteResult
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteByDocumentId($documentId)
	{
		$link = static::getList(['select' => ['ID'], 'filter' => ['DOCUMENT_ID' => $documentId]])->fetch();
		if($link)
		{
			return static::delete($link['ID']);
		}

		return new Main\Entity\DeleteResult();
	}

	/**
	 * @param string $hash
	 * @return array|false
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getByHash($hash)
	{
		if($hash)
		{
			return static::getList(['filter' => ['HASH' => $hash]])->fetch();
		}

		return false;
	}

	/**
	 * @param $documentId
	 * @param bool $absolute
	 * @return array
	 */
	public static function getPublicUrlsByDocumentId($documentId, $absolute = false)
	{
		$result = [];
		if($documentId > 0)
		{
			$item = static::getList([
				'select' => ['HASH', 'DOCUMENT_PDF_ID' => 'DOCUMENT.PDF_ID', 'DOCUMENT_IMAGE_ID' => 'DOCUMENT.IMAGE_ID'],
				'filter' => ['DOCUMENT_ID' => $documentId],
			])->fetch();
			if($item)
			{
				$hash = $item['HASH'];
				$urlManager = UrlManager::getInstance();
				$result['hash'] = $hash;
				$result['publicDownloadUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getFile', [
					'id' => $documentId,
					'hash' => $hash,
				], $absolute);
				if($item['DOCUMENT_IMAGE_ID'] > 0)
				{
					$result['imageUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getImage', [
						'id' => $documentId,
						'hash' => $hash,
					], $absolute);
				}
				if($item['DOCUMENT_PDF_ID'] > 0)
				{
					$result['pdfUrl'] = $urlManager->create('documentgenerator.api.publicdocument.getPdf', [
						'id' => $documentId,
						'hash' => $hash,
					], $absolute);
					$result['printUrl'] = $urlManager->create('documentgenerator.api.publicdocument.showPdf', [
						'print' => 'y',
						'id' => $documentId,
						'hash' => $hash,
					], $absolute);
				}
			}
		}

		return $result;
	}

	public static function onBeforeDelete(Event $event)
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

		return new Main\Entity\EventResult();
	}
}