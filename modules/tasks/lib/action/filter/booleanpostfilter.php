<?php

namespace Bitrix\Tasks\Action\Filter;

class BooleanPostFilter implements \Bitrix\Main\Type\IRequestFilter
{
	public function filter(array $values)
	{
		if(empty($values['post']) || !is_array($values['post']))
		{
			return null;
		}

		return array(
			'post' => $this->prepareBooleanValues($values['post']),
		);
	}

	private function prepareBooleanValues($data)
	{
		if ($data === 'true')
		{
			return true;
		}

		if ($data === 'false')
		{
			return false;
		}

		if ($data === 'null')
		{
			return null;
		}

		if (is_array($data))
		{
			foreach ($data as $k => $v)
			{
				$data[$k] = $this->prepareBooleanValues($v);
			}
		}

		return $data;
	}
}