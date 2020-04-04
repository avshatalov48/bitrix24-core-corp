<?php

namespace Bitrix\ImOpenlines\QuickAnswers;

abstract class DataManager
{
	abstract public function add($data);

	abstract public function getList($filter = array(), $offset = 0, $limit = 0);

	abstract public function update($id, $data);

	abstract public function delete($id);

	abstract public function getById($id);

	abstract public function getUrlToList();

	abstract public function getSectionList();

	abstract public function getCount($filter = array());
}