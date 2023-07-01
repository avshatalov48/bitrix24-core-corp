<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

interface SupportsEditorProvider
{
	public function initializeParams(array $params);

	public function setEntityId($entityId);

	public function initializeEditorData();

	public function getEditorConfig();
}
