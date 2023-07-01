<?php
namespace Bitrix\BIConnector\DB;

class MysqliResult extends \Bitrix\Main\DB\MysqliResult
{
	protected $rowData = null;
	protected $rowDataReference = null;

	/**
	 * Returns null because there is no way to know the fields.
	 *
	 * @return null
	 */
	public function getFields()
	{
		return null;
	}

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	protected function fetchRowInternal()
	{
		/** @var \mysqli_stmt $stmt */
		$stmt = $this->resource;
		if (!$this->rowData)
		{
			$stmt->execute();

			$c = $stmt->field_count;
			$this->rowData = [];
			$this->rowDataReference = [];
			for ($i = 0; $i < $c; $i++)
			{
				$this->rowData[] = '';
				$this->rowDataReference[] = &$this->rowData[count($this->rowData) - 1];
			}

			call_user_func_array([ $stmt, 'bind_result'], $this->rowDataReference);
		}

		if ($stmt->fetch())
		{
			$row = [];
			foreach ($this->rowData as $v)
			{
				$row[] = $v; //dereference
			}

			return $row;
		}
		else
		{
			return false;
		}
	}
}
