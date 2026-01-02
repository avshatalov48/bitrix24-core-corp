<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User;

use Bitrix\Intranet;
use Bitrix\Intranet\User\Widget\BaseContent;
use Bitrix\Intranet\User\Widget\Content\Main;
use Bitrix\Intranet\User\Widget\Content\ToolsContentAssembler;
use Bitrix\Intranet\User\Widget\Content\Tool\AccountChanger;
use Bitrix\Intranet\User\Widget\Content\Tool\Administration;
use Bitrix\Intranet\User\Widget\Content\Tool\PerformanceReview;
use Bitrix\Intranet\User\Widget\ContentCollection;
use Bitrix\Main\ArgumentException;

class Widget
{
	private Intranet\User $user;
	private const SKELETON_ITEM_HEIGHT = 36;

	public function __construct()
	{
		$this->user = new Intranet\User();
	}

	/**
	 * @throws ArgumentException
	 */
	public function getContentCollection(): ContentCollection
	{
		$contentCollection = new ContentCollection();
		$contentClasses = [
			Main::class,
		];

		/**
		 * @var class-string<BaseContent> $contentClass
		 */
		foreach ($contentClasses as $contentClass)
		{
			if ($contentClass::isAvailable())
			{
				$contentCollection->add(new $contentClass($this->user));
			}
		}

		$toolsSectionBuilder = new ToolsContentAssembler($this->user);
		$toolsSectionBuilder->addToContentCollection($contentCollection);

		return $contentCollection;
	}

	public function getSkeletonConfiguration(): array
	{
		$items = [];

		$items[] = ['type' => 'splitColumn', 'height' => 72, 'count' => 2];

		$countSecondaryTools = 0;

		if (AccountChanger::isAvailable($this->user))
		{
			$countSecondaryTools++;
		}

		if (PerformanceReview::isAvailable($this->user))
		{
			$countSecondaryTools++;
		}

		if (Administration::isAvailable($this->user))
		{
			$countSecondaryTools++;
		}

		if ($countSecondaryTools === 1)
		{
			$items[] = ['type' => 'item', 'height' => self::SKELETON_ITEM_HEIGHT];
		}
		else
		{
			$items[] = ['type' => 'splitColumn', 'height' => self::SKELETON_ITEM_HEIGHT * $countSecondaryTools, 'count' => $countSecondaryTools];
		}

		if (!$this->user->isIntranet() && $this->user->isExtranet())
		{
			$items[] = ['type' => 'splitColumn', 'height' => self::SKELETON_ITEM_HEIGHT * 2, 'count' => 2];
		}

		$hasTimeman = Intranet\Internal\Integration\Timeman\WorkTime::canUse();

		return [
			'header' => false,
			'avatarWidgetHeader' => ['hasTimeman' => $hasTimeman, 'hasTools' => $this->user->isIntranet(), 'isAdmin' => $this->user->isAdmin()],
			'footer' => ['height' => 10],
			'items' => $items,
		];
	}
}
