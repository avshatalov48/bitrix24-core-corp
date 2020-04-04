<?php

namespace Bitrix\Crm\UI\Webpack;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\WebPacker;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Crm\WebForm;

/**
 * Class Form
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class Form extends Webpack\Base
{
	protected static $type = 'form';

	/** @var WebForm\Form $form */
	protected $form;

	/** @var array $additionalOptions */
	protected $additionalOptions = [];

	/** @var array $additionalFormOptions */
	protected $additionalFormOptions = [];

	/** @var int $cacheTtl Cache ttl. */
	protected $cacheTtl = 180;

	/**
	 * Get instance.
	 *
	 * @param int $formId Form ID.
	 * @return static
	 */
	public static function instance($formId)
	{
		return new static($formId);
	}

	/**
	 * Rebuild resources.
	 *
	 * @return string
	 */
	public static function rebuildResources()
	{
		return Form\Polyfill::instance()->build() && Form\App::instance()->build();
	}

	/**
	 * Rebuild resources agent.
	 *
	 * @return string
	 */
	public static function rebuildResourcesAgent()
	{
		if (static::rebuildResources())
		{
			return '';
		}
		else
		{
			return '\\Bitrix\\Crm\\UI\\Webpack\\Form::rebuildResources();';
		}
	}

	/**
	 * Rebuild agent.
	 *
	 * @param int $formId Form ID.
	 * @return string
	 */
	public static function rebuildAgent($formId)
	{
		if ((new static($formId))->build())
		{
			return '';
		}
		else
		{
			return '\\Bitrix\\Crm\\UI\\Webpack\\Form::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		if (!$this->form)
		{
			$this->form = new WebForm\Form($this->getId());
		}

		$data = $this->form->get();
		$isCloud = Loader::includeModule('bitrix24');

		$this->fileDir = 'form';
		$this->fileName = str_replace(
			['#id#', '#sec#'],
			[$this->getId(), $data['SECURITY_CODE']],
			$isCloud ? 'loader_#id#.js' : 'loader_#id#_#sec#.js'
		);

		$name = 'crm.site.form.embed.unit';
		$this->addExtension($name);
		$module = $this->getModule($name);
		$module->getProfile()->setCallParameter($this->getCallParameter());

		$this->embeddedModuleName = 'crm.site.form.unit.loader';
	}

	protected function configureFile()
	{
		if (!$this->form)
		{
			$this->form = new WebForm\Form($this->getId());
		}

		$data = $this->form->get();
		$this->configureFormEmbeddedScript([
			'action' => 'inline',
			'sec' => $data['SECURITY_CODE'],
		]);
	}

	protected function getCallParameter()
	{
		if (!$this->form)
		{
			$this->form = new WebForm\Form($this->getId());
		}

		$formOptions = (new WebForm\Embed\Config($this->form))->toArray();
		$formOptions = $this->additionalOptions + $formOptions;
		$formOptions['data'] = $this->additionalFormOptions + $formOptions['data'];

		$parameter = [
			'form' => $formOptions,
			'resources' => [
				'app' => Form\App::instance()->getEmbeddedFileUrl(),
				'polyfill' => Form\Polyfill::instance()->getEmbeddedFileUrl(),
			]
		];

		return $parameter;
	}

	public function setAdditionalOptions(array $options = [])
	{
		$this->additionalOptions = $options;
		return $this;
	}

	public function setAdditionalFormOptions(array $options = [])
	{
		$this->additionalFormOptions = $options;
		return $this;
	}

	public function configureFormEmbeddedScript(array $options = [])
	{
		// script parameters
		$this->skipMoving = true;
		$this->tagAttributes['data-b24-form'] = join('/', [
			$options['action'] ?: 'inline',
			$this->getId(),
			$options['sec']
		]);

		return $this;
	}
}