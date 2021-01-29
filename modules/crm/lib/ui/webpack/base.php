<?php

namespace Bitrix\Crm\UI\Webpack;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Web\WebPacker;

/**
 * Class Base
 *
 * @package Bitrix\Crm\UI\Webpack
 */
abstract class Base
{
	const TYPE_GUEST = 'guest';
	const TYPE_SITE_BUTTON = 'button';
	const TYPE_FORM = 'form';
	const TYPE_CALL_TRACKER= 'call.tracker';
	const TYPE_CALL_TRACKER_EDITOR = 'call.tracker.ed';
	const TYPE_GLOBAL = 'global';

	/** @var string Type. */
	protected static $type;

	/** @var WebPacker\FileController $controller */
	private $controller;

	/** @var int $id ID. */
	private $id;

	/** @var int $fileId ID. */
	private $fileId;

	/** @var int $fileName Filename. */
	protected $fileName;

	/** @var int $file dir. */
	protected $fileDir = 'tag';

	/** @var int $cacheTtl Cache ttl. */
	protected $cacheTtl = 60;

	/** @var array $tagAttributes Tag attributes. */
	protected $tagAttributes = [];
	protected $skipMoving = false;

	/** @var string $embeddedModuleName Embedded module name. */
	protected $embeddedModuleName;

	private $configured = false;
	private $fileConfigured = false;

	/**
	 * Rebuild all packs.
	 *
	 * @return void
	 */
	final static function rebuildAll()
	{
		// fetch all by static::$type
		// init and call build for each
		// use agents and Runtime\Timer
	}

	/**
	 * Rebuild.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	final static function rebuild($id)
	{
		return (new static($id))->build();
	}

	/**
	 * Base constructor.
	 *
	 * @param int|null $id ID.
	 */
	final protected function __construct($id)
	{
		if (empty(static::$type))
		{
			throw new ArgumentNullException('$type');
		}
		if (empty($id) || !is_numeric($id))
		{
			throw new ArgumentNullException('$id');
		}

		$this->id = (int) $id;
		$this->fileName = static::$type . '.js';

		$row = Internals\WebpackTable::getByPrimary($this->getWebpackPrimary())->fetch();
		$this->fileId = $row ? (int) $row['FILE_ID'] : null;

		$this->controller = (new WebPacker\FileController());
	}

	private function getWebpackPrimary()
	{
		return ['ENTITY_TYPE' => static::$type, 'ENTITY_ID' => $this->id];
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	abstract protected function configure();

	protected function configureFile()
	{

	}

	private function configureFileOnce()
	{
		if ($this->fileConfigured)
		{
			return;
		}

		$this->configureFile();

		$this->controller->configureFile(
			$this->fileId,
			'crm',
			$this->fileDir,
			$this->fileName
		);

		$this->fileConfigured = true;
	}

	private function configureOnce()
	{
		if ($this->configured)
		{
			return;
		}
		$this->configured = true;

		$this->configure();
		$this->configureFileOnce();
	}

	/**
	 * Get controller.
	 *
	 * @return int|null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Add extension.
	 *
	 * @param string $name Extension name.
	 * @return $this
	 */
	protected function addExtension($name)
	{
		$this->controller->addExtension($name);
		return $this;
	}

	/**
	 * Add module.
	 *
	 * @param WebPacker\Module $module Module.
	 * @return $this
	 */
	protected function addModule(WebPacker\Module $module)
	{
		$this->controller->addModule($module);
		return $this;
	}

	/**
	 * Get content.
	 *
	 * @return string
	 */
	public function getContent()
	{
		$this->configureOnce();
		return $this->controller->stringify();
	}

	/**
	 * Get module.
	 *
	 * @param string $name Name.
	 * @return WebPacker\Module|null
	 */
	public function getModule($name)
	{
		$this->configureOnce();
		return $this->controller->getModule($name);
	}

	/**
	 * Get tag attributes.
	 *
	 * @return array
	 */
	public function getTagAttributes()
	{
		$this->getLoader();
		return $this->tagAttributes;
	}

	/**
	 * Get embedded script.
	 *
	 * @return string
	 */
	public function getEmbeddedScript()
	{
		return $this->getLoader()
			->setTagAttributes($this->tagAttributes)
			->setSkipMoving($this->skipMoving)
			->getString();
	}

	/**
	 * Get embedded body.
	 *
	 * @return string
	 */
	public function getEmbeddedBody()
	{
		return $this->getLoader()->getStringJs();
	}

	/**
	 * Get embedded body.
	 *
	 * @param int $cacheTtl Cache ttl.
	 * @return $this
	 */
	public function setCacheTtl($cacheTtl)
	{
		$this->cacheTtl = $cacheTtl;
		return $this;
	}

	/**
	 * Get embedded body.
	 *
	 * @return WebPacker\Loader
	 */
	public function getLoader()
	{
		$this->configureFileOnce();
		return $this->controller->getLoader()->setCacheTtl($this->cacheTtl);
	}

	/**
	 * Get embedded body.
	 *
	 * @return string
	 */
	public function getEmbeddedFileUrl()
	{
		return $this->getLoader()->getFileUrl();
	}

	/**
	 * Get embedded module.
	 *
	 * @return WebPacker\Module
	 */
	public function getEmbeddedModule()
	{
		$this->configureOnce();
		if (!$this->embeddedModuleName)
		{
			throw new InvalidOperationException('Embedded module does not have a name.');
		}

		$asset = (new WebPacker\Resource\JsAsset())->setContent($this->getEmbeddedBody());
		$package = (new WebPacker\Resource\Package())->addAsset($asset);
		$module = (new WebPacker\Module($this->embeddedModuleName))->setPackage($package);

		return $module;
	}

	/**
	 * Return true if it was built.
	 *
	 * @return bool
	 */
	public function isBuilt()
	{
		return !empty($this->fileId);
	}

	/**
	 * Build.
	 *
	 * @return bool
	 */
	public function build()
	{
		$this->configureOnce();
		$result = $this->controller->build();
		if ($result->isSuccess())
		{
			$this->fileId = $result->getId();

			Internals\WebpackTable::delete($this->getWebpackPrimary());
			$data = $this->getWebpackPrimary();
			$data['FILE_ID'] = $this->fileId;
			Internals\WebpackTable::add($data);
		}

		// add agent!

		return $result->isSuccess();
	}

	/**
	 * Delete.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->controller->delete();
	}
}