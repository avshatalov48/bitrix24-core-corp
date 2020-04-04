<?php
namespace Bitrix\Crm\Controller\Response\Entity;

class EmptyContent implements ContentInterface
{
	public function getHtml()
	{
		return '';
	}
}