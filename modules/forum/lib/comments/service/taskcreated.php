<?php
namespace Bitrix\Forum\Comments\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Socialnetwork\CommentAux;

final class TaskCreated extends Base
{
	const TYPE = 'CREATETASK';

	public function getType()
	{
		return static::TYPE;
	}

	public function getText(string $text = '', array $params = [])
	{
		$result = '';

		try
		{
			$data = Json::decode($text);
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			$data = [];
		}

		if (
			!is_array($data)
			|| empty($data)
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$options = [];
		if (isset($params['suffix']))
		{
			$options['suffix'] = $params['suffix'];
		}

		$socNetProvider = CommentAux\Base::init(CommentAux\CreateTask::TYPE, $data, $options);
		$result = $socNetProvider->getText();

		return $result;
	}

	public function canDelete()
	{
		return false;
	}
}