<?php
namespace Bitrix\Crm\Settings;

class EntityEditSettings
{
	/** @var string */
	protected $configID = "";
	/** @var array|null */
	protected $config = null;

	/**
	 * EntityEditSettings constructor.
	 * @param string $configID Configuration ID.
	 */
	public function __construct($configID)
	{
		$this->configID = $configID;
	}
	/**
	 * Get raw configuration for current user.
	 * @return array
	 */
	protected function getConfig()
	{
		if(!$this->config)
		{
			$result = \CUserOptions::GetOption('crm.entity.editor', mb_strtolower($this->configID).'_opts', null);
			$this->config = is_array($result) ? $result : array();
		}
		return $this->config;
	}

	public function isClientCompanyEnabled()
	{
		$config = $this->getConfig();
		return !isset($config['enable_client_company']) || $config['enable_client_company'] === 'Y';
	}

	public function isClientContactEnabled()
	{
		$config = $this->getConfig();
		return !isset($config['enable_client_contact']) || $config['enable_client_contact'] === 'Y';
	}
}