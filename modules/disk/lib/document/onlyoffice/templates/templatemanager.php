<?php

namespace Bitrix\Disk\Document\OnlyOffice\Templates;

use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

final class TemplateManager
{
	/** @var array */
	protected $templates;

	public function __construct()
	{
		$this->templates = $this->buildDefaultTemplates();
	}

	protected function buildDefaultTemplates(): array
	{
		return [
			[
				'id' => 0,
				'name' => Loc::getMessage("DISK_DOC_OO_TEMPLATES_1_NAME"),
				'path' => Path::normalize(__DIR__ . '/../../../../document-templates/resume_0.docx'),
			],
			[
				'id' => 1,
				'name' => Loc::getMessage("DISK_DOC_OO_TEMPLATES_2_NAME"),
				'path' => Path::normalize(__DIR__ . '/../../../../document-templates/resume_1.docx'),
			],
			[
				'id' => 2,
				'name' => Loc::getMessage("DISK_DOC_OO_TEMPLATES_3_NAME"),
				'path' => Path::normalize(__DIR__ . '/../../../../document-templates/resume_2.docx'),
			],
		];
	}

	public function list(): array
	{
		return $this->templates;
	}

	public function getById(int $id): ?array
	{
		return $this->templates[$id] ?? [];
	}
}