<?php

namespace Bitrix\Crm\Service\Timeline\Item\AI\Call;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

abstract class Base extends Configurable
{
	private const HELPDESK_ABOUT_JOB_LANG = '20423978';

	abstract protected function getAICallTypeId(): string;
	abstract protected function getOpenAction(): ?Action;
	abstract protected function getAdditionalIconCode(): string;
	abstract protected function getOpenButtonTitle(): string;
	abstract protected function getJobResult(): ?Result;

	final public function getType(): string
	{
		return sprintf('AI:Call:%s', $this->getAICallTypeId());
	}

	public function getIconCode(): ?string
	{
		return Common\Icon::AI_PROCESS;
	}

	final public function getLogo(): ?Body\Logo
	{
		return Common\Logo::getInstance(Common\Logo::AI_COPILOT)
			->createLogo()
			?->setInCircle(false)
			?->setAdditionalIconType(Body\Logo::ICON_TYPE_PURPLE)
			?->setAdditionalIconCode($this->getAdditionalIconCode())
		;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->buildClientBlock(Client::BLOCK_WITH_FIXED_TITLE);
		if (isset($clientBlock))
		{
			$result['client'] = $clientBlock;
		}

		$responsibleUserBlock = $this->buildResponsibleUserBlock();
		if (isset($responsibleUserBlock))
		{
			$result['responsibleUser'] = $responsibleUserBlock;
		}

		$baseActivityBlock = $this->buildBaseActivityBlock();
		if (isset($baseActivityBlock))
		{
			$result['baseActivity'] = $baseActivityBlock;
		}

		$jobLanguageBlock = $this->buildJobLanguageBlock();
		if (isset($jobLanguageBlock))
		{
			$result['jobLanguage'] = $jobLanguageBlock;
		}

		return $result;
	}

	public function getButtons(): ?array
	{
		$openButton = (new Button($this->getOpenButtonTitle(), Button::TYPE_SECONDARY))
			->setScopeWeb()
			->setAction($this->getOpenAction())
		;

		return [
			'openButton' => $openButton
		];
	}

	final public function needShowNotes(): bool
	{
		return true;
	}

	final protected function getActivityId(): int
	{
		return $this->getModel()->getAssociatedEntityId();
	}

	private function buildResponsibleUserBlock(): ?ContentBlock
	{
		$data = $this->getUserData($this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID'));
		if (empty($data))
		{
			return null;
		}

		$url = isset($data['SHOW_URL']) ? new Uri($data['SHOW_URL']) : null;
		$textOrLink = ContentBlockFactory::createTextOrLink($data['FORMATTED_NAME'], $url ? new Redirect($url) : null);

		return (new ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID'))
			->setContentBlock($textOrLink->setIsBold(true))
			->setInline()
		;
	}

	private function buildBaseActivityBlock(): ?ContentBlock
	{
		$associatedActivityId = $this->getAssociatedEntityModel()?->get('ID');
		if (!isset($associatedActivityId))
		{
			return null;
		}

		$baseActivitySubject = Container::getInstance()
			->getActivityBroker()
			->getById($associatedActivityId)['SUBJECT'] ?? ''
		;

		if (empty($baseActivitySubject))
		{
			return null;
		}

		return (new ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_AI_CALL_BASE_TITLE'))
			->setContentBlock(
				(new Text())
					->setValue($baseActivitySubject)
					->setColor(Text::COLOR_BASE_70)
					->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
			)
			->setInline()
		;
	}

	protected function buildJobLanguageBlock(): ?ContentBlock
	{
		$languageTitle = $this->getJobResultLanguageTitle();
		if ($languageTitle === null)
		{
			return null;
		}

		$moreDetailLink = (new ContentBlock\Link())
			->setValue(Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_JOB_LANGUAGE_TEXT_DETAILS_LINK_TITLE'))
			->setAction((new JsEvent('Helpdesk:Open'))
				->addActionParamString('articleCode', self::HELPDESK_ABOUT_JOB_LANG)
			)
			->setDecoration(Text::DECORATION_UNDERLINE)
		;

		$replacement = [
			'#LANGUAGE_TITLE#' => (new Text())->setValue(mb_strtolower($languageTitle)),
			'#DETAILS_LINK#' => $moreDetailLink,
		];

		return ContentBlock\ContentBlockFactory::createLineOfTextFromTemplate(
			Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_FILLING_JOB_LANGUAGE_TEXT'),
			$replacement,
			'jobLanguage',
		)->setTextColor(Text::COLOR_BASE_60);
	}

	protected function getJobResultLanguageTitle(): ?string
	{
		$jobResult = $this->getJobResult();
		if ($jobResult === null)
		{
			return null;
		}

		$languageId = $jobResult->getLanguageId() ?? Application::getInstance()->getContext()->getLanguage();

		return AIManager::getAvailableLanguageList()[$languageId] ?? null;
	}
}
