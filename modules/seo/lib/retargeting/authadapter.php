<?

namespace Bitrix\Seo\Retargeting;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;
use Bitrix\Seo\BusinessSuite\Utils\QueueRemoveEventHandler;
use Bitrix\Seo\Service;
use Bitrix\Seo\Service as SeoService;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class AuthAdapter
{
	/** @var  IService $service */
	protected $service;
	protected $type;
	/* @var \CSocServOAuthTransport|\CFacebookInterface */
	protected $transport;
	protected $requestCodeParamName;
	protected $data;

	/** @var array $parameters Parameters. */
	protected $parameters = ['URL_PARAMETERS' => []];

	public function __construct($type)
	{
		$this->type = $type;
	}

	public static function create($type, IService $service = null)
	{
		if (!Loader::includeModule('socialservices'))
		{
			throw new SystemException('Module "socialservices" not installed.');
		}
		$instance = new static($type);
		if ($service)
		{
			$instance->setService($service);
		}

		return $instance;
	}

	public function setService(IService $service)
	{
		$this->service = $service;
		return $this;
	}

	public function setParameters(array $parameters = [])
	{
		$this->parameters = $parameters + $this->parameters;
		return $this;
	}

	public function getAuthUrl()
	{
		if (!SeoService::isRegistered())
		{
			SeoService::register();
		}

		$authorizeUrl = SeoService::getAuthorizeLink();
		$authorizeData = SeoService::getAuthorizeData($this->getEngineCode(),
			$this->canUseMultipleClients() ? Service::CLIENT_TYPE_MULTIPLE : Service::CLIENT_TYPE_SINGLE);
		$uri = new Uri($authorizeUrl);
		if (!empty($this->parameters['URL_PARAMETERS']))
		{
			$authorizeData['urlParameters'] = $this->parameters['URL_PARAMETERS'];
		}
		$uri->addParams($authorizeData);
		return $uri->getLocator();
	}

	protected function getAuthData($isUseCache = true)
	{
		return ($this->canUseMultipleClients() ?
			$this->getAuthDataMultiple() : $this->getAuthDataSingle($isUseCache));
	}

	protected function getAuthDataMultiple()
	{
		return $this->getClientById($this->getClientId());
	}

	protected function getAuthDataSingle($isUseCache = true)
	{
		if (!$isUseCache || !$this->data || count($this->data) == 0)
		{
			$this->data = SeoService::getAuth($this->getEngineCode());
		}

		return $this->data;
	}

	public function removeAuth()
	{
		$this->data = array();

		if ($existedAuthData = $this->getAuthData(false))
		{
			QueueRemoveEventHandler::handleEvent($existedAuthData['proxy_client_id'], $existedAuthData['engine_code']);
			if ($this->canUseMultipleClients())
			{
				SeoService::clearAuthForClient($existedAuthData);
			}
			else
			{
				SeoService::clearAuth($this->getEngineCode());
			}

		}
	}

	protected function getEngineCode()
	{
		if ($this->service)
		{
			return $this->service->getEngineCode($this->type);
		}
		else
		{
			return Service::getEngineCode($this->type);
		}
	}

	public function getType()
	{
		return $this->type;
	}

	public function getToken()
	{
		$data = $this->getAuthData();
		return $data ? $data['access_token'] : null;
	}

	public function hasAuth()
	{
		$multiple = $this->canUseMultipleClients();
		$authorized = count($this->getAuthorizedClientsList()) > 0;
		$token = $this->getToken() <> '';
		return  $multiple? $authorized : $token;
	}

	public function canUseMultipleClients()
	{
		return ($this->service && ($this->service instanceof IMultiClientService) && $this->service::canUseMultipleClients())
			|| (!$this->service && Service::canUseMultipleClients());
	}

	public function getClientList()
	{
		return $this->canUseMultipleClients() ? SeoService::getClientList($this->getEngineCode()) : [];
	}

	public function getClientById($clientId)
	{
		$clients = $this->getClientList();
		foreach ($clients as $client)
		{
			if ($client['proxy_client_id'] == $clientId)
			{
				return $client;
			}
		}
		return null;
	}

	public function getAuthorizedClientsList()
	{
		return array_filter($this->getClientList(), function ($item) {
			return $item['access_token'] <> '';
		});
	}

	public function getClientId()
	{
		if (!$this->canUseMultipleClients())
		{
			return null;
		}
		$clientId = $this->service->getClientId();
		if ($clientId)
		{
			$client = $this->getClientById($clientId);
			if ($client['engine_code'] == $this->getEngineCode())
			{
				return $clientId;
			}
			return null;
		}

		// try to guess account id from accounts list:
		$clients = $this->getClientList();
		foreach ($clients as $client)
		{
			if ($client['proxy_client_type'] == SeoService::CLIENT_TYPE_COMPATIBLE)
			{
				return $client['proxy_client_id'];
			}
		}
		return null;
	}
}