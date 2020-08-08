<?

namespace Bitrix\DocumentGenerator;

class CreationMethod
{
	public const CREATION_METHOD_PLACEHOLDER = '_creationMethod';

	public const METHOD_PUBLIC = 'public';
	public const METHOD_AUTOMATION = 'automation';
	public const METHOD_REST = 'rest';

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByPublic(Document $document): void
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_PUBLIC]);
	}

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByAutomation(Document $document): void
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_AUTOMATION]);
	}

	/**
	 * @param Document $document
	 */
	public static function markDocumentAsCreatedByRest(Document $document): void
	{
		$document->setValues([static::CREATION_METHOD_PLACEHOLDER => static::METHOD_REST]);
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByPublic(Document $document): bool
	{
		return $document->getCreationMethod() === static::METHOD_PUBLIC;
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByAutomation(Document $document): bool
	{
		return $document->getCreationMethod() === static::METHOD_REST;
	}

	/**
	 * @param Document $document
	 * @return bool
	 */
	public static function isDocumentCreatedByRest(Document $document): bool
	{
		return $document->getCreationMethod() === static::METHOD_AUTOMATION;
	}
}