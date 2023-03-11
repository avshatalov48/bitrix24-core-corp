<?php
namespace Bitrix\ImConnector;

/**
 * Class for reception of messages from the server of connectors at the initiative of the server.
 * @package Bitrix\ImConnector
 */
class Input
{
	protected $params = [];

	/** @var Result */
	protected $result;

	/** @var Provider\Base\Input|Provider\ImConnectorServer\Input|Provider\LiveChat\Input|Provider\Network\Input|Provider\Custom\Input|Provider\Notifications\Input $provider */
	protected $provider;

	/**
	 * Input constructor.
	 * @param array $params
	 */
	function __construct(array $params)
	{
		$this->result = new Result();
		$this->params = $params;

		if (!empty($this->params['CONNECTOR']))
		{
			$provider = Provider::getProviderForConnectorInput($this->params['CONNECTOR'], $this->params);
			if ($provider->isSuccess())
			{
				/** @var Provider\Base\Input $this->provider */
				$this->provider = $provider->getResult();
			}
			else
			{
				$this->result->addErrors($provider->getErrors());
			}
		}
		else
		{
			$this->result->addError(new Error('Connector id not specified', 'CONNECTOR_ID_NOT_SPECIFIED', __METHOD__, $params));
		}
	}

	/**
	 * Processing of the connectors accepted data from the server.
	 *
	 * @return Result
	 */
	public function reception(): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			try
			{
				$resultReception = $this->provider->reception();

				if (!$resultReception->isSuccess())
				{
					$result->addErrors($resultReception->getErrors());
				}

				$result->setData($result->getData());
			}
			catch (\Bitrix\Main\SystemException $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
			}
		}

		return $result;
	}
}
