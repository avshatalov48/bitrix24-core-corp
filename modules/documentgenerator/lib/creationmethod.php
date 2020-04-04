<?

namespace Bitrix\DocumentGenerator;

class CreationMethod
{
	const CREATION_METHOD_PLACEHOLDER = '_creationMethod';

	const METHOD_PUBLIC = 'public';
	const METHOD_AUTOMATION = 'automation';
	const METHOD_REST = 'rest';

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByPublic(Document $document)
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_PUBLIC]);
	}

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByAutomation(Document $document)
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_AUTOMATION]);
	}

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByRest(Document $document)
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_REST]);
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByPublic(Document $document)
	{
		return $document->getCreationMethod() === static::METHOD_PUBLIC;
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByAutomation(Document $document)
	{
		return $document->getCreationMethod() === static::METHOD_REST;
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByRest(Document $document)
	{
		return $document->getCreationMethod() === static::METHOD_AUTOMATION;
	}
}