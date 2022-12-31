<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

interface SupportsEditorProvider
{
	public function setEntityID($entityId);

	public function setCategoryID(int $categoryId);

	public function getCategoryID();

	public function getDefaultGuid();

	public function prepareConfigId();

	public function initializeParams(array $params);

	public function initializeData();

	public function prepareFieldInfos();

	public function prepareConfiguration();
}