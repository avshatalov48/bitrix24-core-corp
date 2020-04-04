<?

namespace Bitrix\Seo\Retargeting\Services;

use Bitrix\Seo\Retargeting\ProxyRequest;
class RequestFacebook extends ProxyRequest
{
	const TYPE_CODE = 'facebook';
	const REST_METHOD_PREFIX = 'seo.client.ads.facebook';

	protected function directQuery(array $params = array())
	{
		$url = 'https://graph.facebook.com/v4.0/';
		$url .= $params['endpoint'];

		$clientParameters = is_array($params['fields']) ? $params['fields'] : array();
		$clientParameters = $clientParameters + array('access_token' => $this->adapter->getToken());

		if ($params['method'] == 'GET')
		{
			$url .= '?' . http_build_query($clientParameters, "", "&");
			return $this->client->get($url);
		}
		elseif ($params['method'] == 'DELETE')
		{
			return $this->client->delete($url, $clientParameters, true);
		}
		else
		{
			return $this->client->post($url, $clientParameters, true);
		}
	}
}