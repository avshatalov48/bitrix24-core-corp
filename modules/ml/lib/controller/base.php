<?php

namespace Bitrix\Ml\Controller;

use Bitrix\Main\Event;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Web\Json;
use Bitrix\Ml\Engine\Filter\Authorization;

class Base extends Controller
{
	protected function getDefaultPreFilters()
	{
		return [
			//new \Bitrix\Main\Engine\ActionFilter\HttpMethod(["POST"]),
			new Authorization(),
			function(Event $event)
			{
				$request = \Bitrix\Main\Context::getCurrent()->getRequest();
				$packedParameters = $request->get("serializedParameters");
				if(is_string($packedParameters))
				{
					$decodedParameters = gzdecode(base64_decode($packedParameters));

					if(is_string($decodedParameters))
					{
						$unpackedParameters = Json::decode($decodedParameters);
						if(is_array($unpackedParameters))
						{
							/** @var \Bitrix\Main\Engine\ActionFilter\Base $this */
							$this->getAction()->getController()->setSourceParametersList([
								$unpackedParameters
							]);
						}
					}
				}
			}
		];
	}
}