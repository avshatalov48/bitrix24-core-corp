<?php

namespace Bitrix\Location\Entity\Address;

use Bitrix\Location\Entity\Address;

/**
 * Class FieldCollection
 * @package Bitrix\Location\Entity\Address
 */
final class FieldCollection extends \Bitrix\Location\Entity\Generic\FieldCollection
{
	/** @var Field[] */
	protected $items = [];
}