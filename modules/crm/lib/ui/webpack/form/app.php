<?php

namespace Bitrix\Crm\UI\Webpack\Form;

use Bitrix\Crm\UI\Webpack;
use Bitrix\Main;
use Bitrix\Crm\WebForm;

/**
 * Class App
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class App extends Webpack\Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = 'form.app';

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!static::$instance)
		{
			static::$instance = new static(1);
		}

		return static::$instance;
	}

	/**
	 * Rebuild agent.
	 *
	 * @return string
	 */
	public static function rebuildAgent()
	{
		if ((new static(1))->build())
		{
			return '';
		}
		else
		{
			return static::class . '::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->fileDir = 'form';
		$this->fileName = 'app.js';
		$this->addExtension('crm.site.form.embed');
		$this->embeddedModuleName = 'crm.site.form.loader';
	}

	protected function configureFile()
	{
		$this->fileDir = 'form';
		$this->fileName = 'app.js';
	}

	protected function fixAppFileDuplicates()
	{
		$canUseWebPackFileTableForApp = Main\Config\Option::get('crm', 'can_use_webpack_table_for_app');

		if ($canUseWebPackFileTableForApp === 'Y')
		{
			$files = Webpack\Internals\WebPackFileLogTable::query()
				->setSelect(['ID' => 'FILE_ID'])
				->where('ENTITY_ID', $this->getId())
				->where('ENTITY_TYPE', static::$type)
				->fetchAll()
			;
		}
		else
		{
			$files = Main\FileTable::query()
				->setSelect(['ID'])
				->where('MODULE_ID', 'crm')
				->where('SUBDIR', 'crm/form')
				->where('FILE_NAME', 'app.js')
				->setOrder(['ID' => 'DESC'])
				->fetchAll()
			;
		}

		foreach ($files as $file)
		{
			if ($canUseWebPackFileTableForApp === 'Y')
			{
				Webpack\Internals\WebPackFileLogTable::delete($file['ID']);
			}
			\CFile::Delete($file['ID']);
		}

		if ($canUseWebPackFileTableForApp !== 'Y')
		{
			Main\Config\Option::set('crm', 'can_use_webpack_table_for_app', 'Y');
		}
	}

	public function build()
	{
		$this->fixAppFileDuplicates();
		return parent::build();
	}

	public function onAfterBuild($result): void
	{
		if (!$result->isSuccess())
		{
			return;
		}

		$data = $result->getData();
		if ($data['ENTITY_TYPE'] !== static::$type || $data['ENTITY_ID'] !== $this->getId())
		{
			return;
		}

		$webFormFile = Webpack\Internals\WebPackFileLogTable::query()
			->setSelect(['FILE_ID'])
			->where('FILE_ID', $result->getId())
			->fetch()
		;

		if (!$webFormFile)
		{
			Webpack\Internals\WebPackFileLogTable::add([
				'FILE_ID' => $result->getId(),
				'ENTITY_TYPE' => $data['ENTITY_TYPE'],
				'ENTITY_ID' => $data['ENTITY_ID'],
			]);
		}
	}
}