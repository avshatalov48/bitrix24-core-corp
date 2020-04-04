<?php

namespace Bitrix\Crm\Controller\DocumentGenerator;

use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class Numerator extends Base
{
	/**
	 * @return \Bitrix\DocumentGenerator\Controller\Base
	 */
	protected function getDocumentGeneratorController()
	{
		return new \Bitrix\DocumentGenerator\Controller\Numerator();
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Numerator::getAction()
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @return array
	 */
	public function getAction(\Bitrix\Main\Numerator\Numerator $numerator)
	{
		return $this->proxyAction('getAction', [$numerator]);
	}

	/**
	 * @param PageNavigation|null $pageNavigation
	 * @return Page
	 */
	public function listAction(PageNavigation $pageNavigation = null)
	{
		return $this->proxyAction('listAction', [$pageNavigation]);
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		return $this->proxyAction('addAction', [$fields]);
	}

	/**
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @param array $fields
	 * @return array|null
	 */
	public function updateAction(\Bitrix\Main\Numerator\Numerator $numerator, array $fields)
	{
		return $this->proxyAction('updateAction', [$numerator, $fields]);
	}

	/**
	 * @param \Bitrix\Main\Numerator\Numerator $numerator
	 * @return null
	 */
	public function deleteAction(\Bitrix\Main\Numerator\Numerator $numerator)
	{
		return $this->proxyAction('deleteAction', [$numerator]);
	}
}