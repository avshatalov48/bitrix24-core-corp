<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content;

use Bitrix\Intranet;
use Bitrix\Intranet\User\Widget\ContentCollection;
use Bitrix\Main\ArgumentException;

class ToolsContentAssembler
{
	public const GROUP_MOBILE_AUTH = 'mobileAuth';
	public const GROUP_APPLICATION = 'application';
	public const GROUP_SECONDARY = 'secondary';
	public const GROUP_FOOTER = 'footer';
	public const GROUP_EXTRANET_SECONDARY = 'extranetSecondary';

	/**
	 * @var array<string, list<class-string<Tool\BaseTool>>>
	 */
	protected const GROUP_TOOL_CLASSES = [
		self::GROUP_MOBILE_AUTH => [
			Tool\FastMobileAuthFull::class
		],
		self::GROUP_APPLICATION => [
			Tool\ApplicationsInstaller::class,
			Tool\InstallMobile::class,
			Tool\FastMobileAuth::class,
		],
		self::GROUP_SECONDARY => [
			Tool\AccountChanger::class,
			Tool\PerformanceReview::class,
			Tool\Administration::class,
		],
		self::GROUP_EXTRANET_SECONDARY => [
			Tool\Security::class,
		],
		self::GROUP_FOOTER => [
			Tool\Theme::class,
			Tool\Pulse::class,
			Tool\Logout::class,
		],
	];
	protected const ONLY_EXTRANET_GROUP_TOOL_CLASSES = [
		self::GROUP_EXTRANET_SECONDARY,
	];

	public function __construct(private readonly Intranet\User $user)
	{
	}

	/**
	 * @return list<ToolsWrapper>
	 * @throws ArgumentException
	 */
	public function buildAll(): array
	{
		$result = [];

		foreach (static::GROUP_TOOL_CLASSES as $groupName => $toolClasses)
		{
			$wrapper = $this->build($groupName, $toolClasses);

			if (
				$this->user->isIntranet()
				&& in_array($groupName, static::ONLY_EXTRANET_GROUP_TOOL_CLASSES, true)
			) {
				continue;
			}

			if ($wrapper !== null)
			{
				$result[] = $wrapper;
			}
		}

		return $result;
	}

	/**
	 * @throws ArgumentException
	 */
	public function addToContentCollection(ContentCollection $collection): void
	{
		foreach ($this->buildAll() as $content)
		{
			$collection->add($content);
		}
	}

	/**
	 * @param string $name
	 * @param list<class-string<Tool\BaseTool>> $toolClasses
	 * @return ToolsWrapper|null
	 * @throws ArgumentException
	 */
	private function build(string $name, array $toolClasses): ?ToolsWrapper
	{
		$toolCollection = new ToolCollection();

		foreach ($toolClasses as $toolClass)
		{
			if ($toolClass::isAvailable($this->user))
			{
				$toolCollection->add(new $toolClass($this->user));
			}
		}

		if ($toolCollection->empty())
		{
			return null;
		}

		return new ToolsWrapper(
			$this->user,
			$name,
			$toolCollection,
		);
	}
}
