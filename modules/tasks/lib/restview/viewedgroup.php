<?php

namespace Bitrix\Tasks\RestView;

use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\Base;
use Bitrix\Rest\Integration\View\DataType;

class ViewedGroup extends Base
{
	public function getFields()
	{
		return [
			'GROUP_ID' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'USER_ID' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'MEMBER_TYPE' => [
				'TYPE' => DataType::TYPE_CHAR,
			],
			'TYPE_ID' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'VIEWED_DATE' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				]
			],
		];
	}

	public function internalizeArguments($name, $arguments): array
	{
		$name = mb_strtolower($name);

		if($name !== 'markasread')
		{
			parent::internalizeArguments($name, $arguments);
		}

		return $arguments;
	}

	public function convertKeysToSnakeCaseArguments($name, $arguments)
	{
		$name = mb_strtolower($name);
		
		if($name == 'markasread')
		{
			if(isset($arguments['fields']))
			{
				$fields = $arguments['fields'];
				if(!empty($fields))
					$arguments['fields'] = $this->convertKeysToSnakeCaseFilter($fields);
			}
		}
		else
		{
			parent::convertKeysToSnakeCaseArguments($name, $arguments);
		}

		return $arguments;
	}
}