<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

class Tooltip extends Controller
{
	private $entityTypeId;
	private	$itemId;
	private	$factory;
	private	$item;
	private	$detailUrl;

	public function cardAction(string $USER_ID): Json
	{
		Container::getInstance()->getLocalization()->loadMessages();
		$result = [
			'Toolbar' => '',
			'ToolbarItems' => '',
			'Toolbar2' => '',
			'Name' => Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
			'Card' => '',
			'Photo' => '',
			'Scripts' => ''
		];

		[$this->entityTypeId, $this->itemId] = explode('-', $USER_ID);
		$this->factory = Container::getInstance()->getFactory($this->entityTypeId);
		if (!$this->factory)
		{
			return new Json([
				'RESULT' => $result
			]);
		}

		$this->item = $this->factory->getItem($this->itemId);
		if (!$this->item)
		{
			return new Json([
				'RESULT' => $result
			]);
		}
		$this->detailUrl = Container::getInstance()->getRouter()->getItemDetailUrl(
			$this->entityTypeId,
			$this->itemId
		);

		$result = [
			'Toolbar' => '',
			'ToolbarItems' => '',
			'Toolbar2' => $this->getToolbar(),
			'Name' => $this->getName(),
			'Card' => $this->getCard(),
			'Photo' => $this->getPhoto(),
			'Scripts' => ''
		];

		return new Json([
			'RESULT' => $result
		]);
	}

	private function getToolbar(): string
	{
		return '
			<div class="bx-user-info-data-separator"></div>
			<ul>
				<li class="bx-icon bx-icon-show">
					<a href="' . $this->detailUrl . '" target="_blank">' . Loc::getMessage('CRM_COMMON_ACTION_SHOW') . '</a>
				</li>
				<li class="bx-icon bx-icon-message">
					<a href="' . $this->detailUrl . '?init_mode=edit" target="_blank">' . Loc::getMessage('CRM_COMMON_ACTION_EDIT') . '</a>
				</li>
			</ul>';
	}

	private function getName(): string
	{
		return '<a href="' . $this->detailUrl . '" target="_blank">'.HtmlFilter::encode($this->item->getHeading()).'</a>';
	}

	private function getCard(): string
	{
		return '
			<div class="bx-ui-tooltip-info-data-cont" id="bx_user_info_data_cont_' . $this->entityTypeId . '-' . $this->itemId . '">
				<div class="bx-ui-tooltip-info-data-info crm-tooltip-info">'
				. $this->getStage()
				. $this->getOpportunity()
				. $this->getUpdatedDate()
				.'</div>
			</div>
		';
	}

	private function getStage(): ?string
	{
		if($this->factory->isStagesEnabled())
		{
			$stage = $this->factory->getStage($this->item->getStageId());
			return '
				<span class="bx-ui-tooltip-field-row">
					<span class="bx-ui-tooltip-field-name">' . Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_ID') . '</span>: 
					<span class="bx-ui-tooltip-field-value">
						<span class="fields string">' . HtmlFilter::encode($stage->getName()) . '</span>
					</span>
				</span>
			';
		}

		return null;
	}

	private function getOpportunity(): ?string
	{
		if($this->item->getOpportunity())
		{
			return '
				<span class="bx-ui-tooltip-field-row">
					<span class="bx-ui-tooltip-field-name">' . Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY') . '</span>: 
					<span class="bx-ui-tooltip-field-value">
						<span class="fields money">' . $this->item->getOpportunity() . '</span>
					</span>
				</span>
			';
		}
		return null;
	}

	private function getUpdatedDate(): ?string
	{
		return '
			<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">' . Loc::getMessage('CRM_COMMON_MODIFY_DATE') . '</span>: 
				<span class="bx-ui-tooltip-field-value">
					<span class="fields datetime">' . $this->item->getUpdatedTime() . '</span>
				</span>
			</span>
		';
	}

	private function getPhoto(): string
	{
		return '<a href="' . $this->detailUrl . '" class="bx-ui-tooltip-info-data-photo no-photo" target="_blank"></a>';
	}
}
