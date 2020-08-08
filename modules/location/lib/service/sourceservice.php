<?php

namespace Bitrix\Location\Service;

use Bitrix\Location\Common\BaseService;
use Bitrix\Location\Infrastructure\Service\Config\Container;
use Bitrix\Location\Source\BaseSource;

final class SourceService extends BaseService
{
	/** @var SourceService */
	protected static $instance;
	/** @var BaseSource|null */
	protected $source;

	public function getSourceCode(): string
	{
		return $this->source->getCode();
	}

	public function getSource(): ?BaseSource
	{
		return $this->source;
	}

	protected function __construct(Container $config)
	{
		parent::__construct($config);
		$this->source = $config->get('source');
	}
}