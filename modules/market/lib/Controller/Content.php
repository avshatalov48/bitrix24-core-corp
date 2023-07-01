<?php

namespace Bitrix\Market\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Web\Uri;
use Bitrix\Market\PageRules;

class Content extends Controller
{
	public function loadAction(string $page): AjaxJson
	{
		$path = $page;

		$uri = new Uri($page);
		if (!empty($uri->getPath())) {
			$path = $uri->getPath();
		}
		if (mb_substr($path, -1, 1) == "/") {
			$path .= "index.php";
		}

		$pageRules = new PageRules($path, $this->getQueryParams($uri->getQuery()));
		$data = $pageRules->getComponentData();

		return AjaxJson::createSuccess([
			'params' => $data['params'],
			'result' => $data['result'],
		]);
	}

	private function getQueryParams($query): array
	{
		$params = [];

		if (!empty($query)) {
			foreach (explode('&', $query) as $param) {
				$queryParameter = explode('=', $param);
				if (empty($queryParameter) || !is_array($queryParameter) || count($queryParameter) != 2) {
					continue;
				}

				if (strpos($queryParameter[0], '[')) {
					$parameter = explode('[', $queryParameter[0]);
					$params[$parameter[0]][] = $queryParameter[1];
					continue;
				}

				$params[$queryParameter[0]] = $queryParameter[1];
			}
		}

		return $params;
	}
}