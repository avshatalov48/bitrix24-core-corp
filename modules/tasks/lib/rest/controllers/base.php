<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Item\Task\Template;

class Base extends Controller
{
	public function getManifestAction()
	{
		$list = [];

		$reflection = new \ReflectionClass(get_called_class());

		foreach ($reflection->getMethods() as $method)
		{
			if (get_called_class() != $method->class || !$method->isPublic() || $method->getName() == 'configureActions')
			{
				continue;
			}

			$pattern = "#\@([a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
			preg_match_all($pattern, $method->getDocComment(), $matches, PREG_PATTERN_ORDER);
			$docParams = [];
			foreach ($matches[1] as $row)
			{
				list($paramName, $paramText) = explode(' ', $row, 2);

				switch ($paramName)
				{
					case 'param':
						list($fieldType, $fieldName, $fieldDesc) = explode(' ', $paramText, 3);
						$fieldName = substr($fieldName, 1);

						$docParams[$paramName][$fieldName] = [
							'type'        => $fieldType,
							'description' => $fieldDesc
						];
						break;
					case 'return':
						list($fieldType, $fieldDesc) = explode(' ', $paramText, 2);

						$docParams[$paramName] = [
							'type'        => $fieldType,
							'description' => $fieldDesc
						];
						break;
				}
			}

			$params = [];
			foreach ($method->getParameters() as $param)
			{
				$params[] = [
					'name'        => $param->getName(),
					'description' => $docParams['param'][$param->getName()]['description'],
					'optional'    => $param->isOptional(),
					'default'     => $param->isOptional() ? strtolower((string)$param->getDefaultValue()) : 'null',
					'type'        => $docParams['param'][$param->getName()]['type'] //TODO php 7+
				];
			}

			preg_match('#\/\*\*\n.*?\* (.*?)$#im', $method->getDocComment(), $match);
			$title = trim($match[1]);

			$methodName = substr($method->getName(), 0, -6);
			$list[$methodName] = [
				'comment'   => $title,
				//				'docComment'=>trim($method->getDocComment()),
				'arguments' => $params ? $params : null,
				'return'    => $method->getReturnType() ? $method->getReturnType() : $docParams['return']['type']
			];
		}

		return $list;
	}

	/**
	 * @return array;
	 */
    public function getAutoWiredParameters()
    {
        return [
            new Parameter(
                \CTaskItem::class,
                function ($className, $id)  {
                    $userId = CurrentUser::get()->getId();
                    /** @var Task $className */
                    return new $className($id, $userId);
                }
            ),
            new Parameter(
                Template::class,
                function ($className, $id) {
                    $userId = CurrentUser::get()->getId();
                    /** @var Template $className */
                    return new $className($id, $userId);
                }
            ),
        ];
    }
}