<?php

namespace Bitrix\Crm\Service\Router\Page\RepeatSale\Segment;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;

class ResultPage extends AbstractPage
{
	protected bool $isPlainView = true;
	private const COMPONENT_NAME = 'bitrix:crm.repeat_sale.segment_result.list.wrapper';

	public function __construct(
		private readonly int $segmentId,
		HttpRequest $request,
		?Scope $currentScope,
	)
	{
		parent::__construct($request, $currentScope);
	}

	public function component(): Contract\Component
	{
		return new Component(
			name: self::COMPONENT_NAME,
			parameters: [
				'segmentId' => $this->segmentId,
			],
		);
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->isUsePadding = false;
		$sidePanel->isUseBackgroundContent = false;
		$sidePanel->isPlainView = false;
		$sidePanel->isHideToolbar = false;
		$sidePanel->isUseBitrix24Theme = true;
	}

	public static function routes(): array
	{
		return [
			(new Route('repeat-sale-segment/result/{segmentId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
		];
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule('repeat-sale-segment/result/[0-9]+/?'))
				->scopes(self::scopes())
				->stopParameters(['page', 'IFRAME'])
				->configureOptions(function (SidePanelAnchorOptions $options){
					$options
						->setCacheable(false)
						->setAllowChangeHistory(false)
					;
				})
			,
		];
	}
}