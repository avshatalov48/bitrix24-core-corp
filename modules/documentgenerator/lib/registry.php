<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Main\IO\Directory;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Path;

abstract class Registry
{
	/**
	 * Full name of the base class to look for its descendants.
	 *
	 * @return string
	 */
	abstract protected function getBaseClassName();

	/**
	 * Absolute path where to look for descendants.
	 *
	 * @return string
	 */
	abstract protected function getPath();

	/**
	 * Name of the event that will be send to collect more descendants.
	 *
	 * @return mixed
	 */
	abstract protected function getEventName();

	/**
	 * @param array $params
	 * @return array
	 */
	public static function getList(array $params = [])
	{
		$modules = [];
		if(isset($params['filter']['MODULE']) && is_array($params['filter']['MODULE']))
		{
			$modules = $params['filter']['MODULE'];
		}

		$self = new static();

		$result = $self->getFromPath($self->getPath());
		$result += $self->getFromEvent($modules);

		return $result;
	}

	/**
	 * @param string $path
	 * @param string $subPath
	 * @return array
	 */
	protected function getFromPath($path, $subPath = '\\')
	{
		$result = [];

		$fullBaseClassName = $this->getBaseClassName();

		if(Directory::isDirectoryExists($path))
		{
			$baseDirectory = scandir($path);
			foreach($baseDirectory as $fileName)
			{
				if($fileName == '.' || $fileName == '..')
				{
					continue;
				}
				$subdir = Path::combine($path, $fileName);
				if(Directory::isDirectoryExists($subdir))
				{
					$result = array_merge($result, $this->getFromPath($subdir, $subPath.$fileName.'\\'));
				}
				elseif(GetFileExtension($fileName) == 'php')
				{
					$fullClassName = mb_strtolower($fullBaseClassName.$subPath.GetFileNameWithoutExtension($fileName));
					if($this->checkClassName($fullClassName))
					{
						$result[$fullClassName] = [
							'NAME' => $fullClassName::getLangName(),
							'CLASS' => $fullClassName,
							'MODULE' => Driver::MODULE_ID,
						];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param array|null $modules
	 * @return array
	 */
	protected function getFromEvent(array $modules = null)
	{
		$result = [];

		foreach(EventManager::getInstance()->findEventHandlers('documentgenerator', $this->getEventName(), $modules) as $handler)
		{
			$eventResult = ExecuteModuleEventEx($handler);
			if(is_array($eventResult))
			{
				foreach ($eventResult as $fullClassName => $description)
				{
					if($fullClassName && $this->checkClassName($fullClassName) && is_array($description))
					{
						$result[mb_strtolower($fullClassName)] = $description;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $fullClassName
	 * @return bool
	 */
	protected function checkClassName($fullClassName)
	{
		return (
			class_exists($fullClassName) &&
			is_a($fullClassName, $this->getBaseClassName(), true) &&
			is_a($fullClassName, Nameable::class, true)
		);
	}
}