<?php

namespace Bitrix\Crm\Tour;

class TourRepository
{
	public function getAllByCategory(): array
	{
		$tours = $this->getAllTours();
		$result = [];
		foreach ($tours as $tour)
		{
			$categoryId = $tour['category'];
			if (!isset($result[$categoryId]))
			{
				$result[$categoryId] = [
					'name' => $categoryId,
					'items' => [],
				];
			}

			$host = \Bitrix\Main\Engine\UrlManager::getInstance()->getHostUrl();
			$secretLink = $host . '/crm/configs/?resetTour=' . urlencode($tour['id']);

			$result[$categoryId]['items'][] = [
				'name' => $tour['title'],
				'description' => $tour['text'],
				'id' => $tour['id'],
				'secretLink' => $secretLink,
			];
		}

		return $result;
	}

	public function getById(string $id): ?array
	{
		$tours = $this->getAllTours();
		foreach ($tours as $tour)
		{
			if ($tour['id'] === $id)
			{
				return $tour;
			}
		}

		return null;
	}

	private function getAllTours(): array
	{
		$result = [];

		$baseDir = __DIR__;
		$files = $this->findFiles($baseDir);
		foreach ($files as $file)
		{
			if (preg_match('/(.*)\.php/', $file, $matches))
			{
				$classParts = explode('/', $matches[1]);
				$className = '\\Bitrix\\Crm\\Tour\\' . implode('\\', $classParts);
				if (is_subclass_of($className, Base::class))
				{
					$reflectionTourClass = new \ReflectionClass($className);
					if ($reflectionTourClass->isAbstract())
					{
						continue;
					}

					$tour = $className::getInstance();
					$getSteps = new \ReflectionMethod($className, 'getSteps');
					$getSteps->setAccessible(true);
					$steps = $getSteps->invoke($tour);

					$getOptionCategory = new \ReflectionMethod($className, 'getOptionCategory');
					$getOptionCategory->setAccessible(true);
					$optionCategory = $getOptionCategory->invoke($tour);

					$getOptionName = new \ReflectionMethod($className, 'getOptionName');
					$getOptionName->setAccessible(true);
					$optionName = $getOptionName->invoke($tour);

					$getComponentTemplate = new \ReflectionMethod($className, 'getComponentTemplate');
					$getComponentTemplate->setAccessible(true);
					$componentTemplate = $getComponentTemplate->invoke($tour);
					if ($componentTemplate === 'popup')
					{
						continue; // skip \Bitrix\Crm\Tour\NumberOfClients
					}

					array_pop($classParts);
					$result[] = [
						'id' => $matches[1],
						'category' => implode('/', $classParts),
						'optionCategory' => $optionCategory,
						'optionName' => $optionName,
						'title' => str_replace(['<br>', '<br/>'], ' ', $steps[0]['title']) ?: $matches[1],
						'text' => $steps[0]['text'],
					];
				}
				if (is_subclass_of($className, BaseStubTour::class))
				{
					$reflectionTourClass = new \ReflectionClass($className);
					if ($reflectionTourClass->isAbstract())
					{
						continue;
					}
					$stubTour = new $className();
					array_pop($classParts);
					$result[] = [
						'id' => $matches[1],
						'category' => implode('/', $classParts),
						'optionCategory' => $stubTour->getOptionCategory(),
						'optionName' => $stubTour->getOptionName(),
						'title' => $stubTour->getTitle(),
						'text' => $stubTour->getText(),
					];
				}
			}
		}

		return $result;
	}

	private function findFiles(string $dir): array
	{
		$result = [];

		$files = scandir($dir);
		foreach ($files as $file)
		{
			if ($file === '.' || $file === '..')
			{
				continue;
			}

			if (is_dir($dir . DIRECTORY_SEPARATOR . $file))
			{
				$subdirFiles = $this->findFiles($dir . DIRECTORY_SEPARATOR . $file);
				foreach ($subdirFiles as $subdirFile)
				{
					$result[] = $file . '/' . $subdirFile;
				}
			}
			elseif (str_ends_with($file, '.php'))
			{
				$result[] = $file;
			}
		}

		return $result;
	}
}
