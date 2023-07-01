<?php

namespace Bitrix\Crm\Component\EntityList\NearestActivity;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use CCrmViewHelper;
use CUtil;

class Block
{
	private ItemIdentifier $itemIdentifier;
	private ?array $activity;
	private bool $allowEdit;
	private string $emptyStatePlaceholder = '';
	private int $userId;

	public function __construct(
		ItemIdentifier $itemIdentifier,
		?array $activity,
		bool $allowEdit
	)
	{
		$this->itemIdentifier = $itemIdentifier;
		$this->activity = $activity;
		$this->allowEdit = $allowEdit;
		$this->userId = Container::getInstance()->getContext()->getUserId();
		$this->emptyStatePlaceholder = Loc::getMessage('CRM_ENTITY_ADD_ACTIVITY_HINT');
	}

	public function render(string $gridManagerId): string
	{
		$preparedGridId = htmlspecialcharsbx(CUtil::JSescape($gridManagerId));
		$entityTypeId = $this->itemIdentifier->getEntityTypeId();
		$entityID = $this->itemIdentifier->getEntityId();

		$allowEdit = $this->allowEdit;

		if ($this->activity)
		{
			$ID = $this->activity['ID'] ?? 0;
			$subject = $this->activity['SUBJECT'] ?? '';

			$isExpired = $this->isExpired();

			$deadline = isset($this->activity['DEADLINE']) && !\CCrmDateTimeHelper::IsMaxDatabaseDate($this->activity['DEADLINE'])
				? DateTime::createFromUserTime($this->activity['DEADLINE'])->toUserTime()
				: null
			;

			$timeFormatted = $deadline
				? \CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $deadline))
				: Loc::getMessage('CRM_ACTIVITY_TIME_NOT_SPECIFIED')
			;

			$isDetailExist = true;
			if (isset($this->activity['PROVIDER_ID']))
			{
				$provider = \CCrmActivity::GetProviderById($this->activity['PROVIDER_ID']);
				if ($provider)
				{
					$isDetailExist = $provider::hasPlanner($this->activity);
				}
			}

			$activityEl = '<span class="crm-link">' . htmlspecialcharsbx($timeFormatted) . '</span>';
			if ($isDetailExist)
			{
				$activityEl =
					'<a class="crm-link" target = "_self"href = "#" onclick="BX.CrmUIGridExtension.viewActivity(\'' . $preparedGridId . '\', ' . $ID . ', { enableEditButton:'
					. ($allowEdit ? 'true' : 'false') . ' }); return false;">' . htmlspecialcharsbx($timeFormatted) . '</a>';
			}

			$result = '
				<div class="crm-nearest-activity-wrapper">
					<div class="crm-list-deal-date crm-nearest-activity-time' . ($isExpired ? '-expiried' : '') . '">' . $activityEl . '</div>
					<div class="crm-nearest-activity-subject">'
				. htmlspecialcharsbx($subject)
				. '</div>
			';

			if ($allowEdit)
			{
				$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));
				$jsOnClick = "BX.CrmUIGridExtension.showActivityAddingPopup(this, '" . $preparedGridId . "', " . (int)$entityTypeId . ", " . (int)$entityID . ", " . $currentUser . ");";
				$result .= '<div class="crm-nearest-activity-plus" onclick="' . $jsOnClick . ' return false;"></div>';
			}

			$result .= '</div>';

			$responsibleId = (int)($this->activity['RESPONSIBLE_ID'] ?? 0);
			if ($responsibleId > 0 && $responsibleId !== $this->userId)
			{
				$responsibleData = Container::getInstance()->getUserBroker()->getById($responsibleId);

				$responsibleFullName = $responsibleData['FORMATTED_NAME'] ?? '';
				$responsibleShowUrl = $responsibleData['SHOW_URL'] ?? '';

				$result .= '<div class="crm-list-deal-responsible"><span class="crm-list-deal-responsible-grey">'
					. htmlspecialcharsbx(Loc::getMessage('CRM_ENTITY_ACTIVITY_FOR_RESPONSIBLE')). '</span><a class="crm-list-deal-responsible-name" target="_blank" href="'
					. htmlspecialcharsbx($responsibleShowUrl) . '">' . htmlspecialcharsbx($responsibleFullName) . '</a></div>';
			}
			return $result;
		}
		elseif ($allowEdit)
		{
			$hintText = $this->emptyStatePlaceholder;
			$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));
			$jsOnClick = "BX.CrmUIGridExtension.showActivityAddingPopup(this, '" . $preparedGridId . "', " . (int)$entityTypeId . ", " . (int)$entityID . ", " . $currentUser. ");";

			return '<span class="crm-activity-add-hint">' . htmlspecialcharsbx($hintText) . '</span>
				<a class="crm-activity-add" onclick="' . $jsOnClick . ' return false;">' . htmlspecialcharsbx(Loc::getMessage('CRM_ENTITY_ADD_ACTIVITY')) . '</a>';
		}

		return '';
	}

	public function setEmptyStatePlaceholder(string $emptyStatePlaceholder): self
	{
		$this->emptyStatePlaceholder = $emptyStatePlaceholder;

		return $this;
	}

	public function needHighlight(): bool
	{
		$responsibleId = (int)($this->activity['RESPONSIBLE_ID'] ?? 0);
		if ($responsibleId === $this->userId)
		{
			return $this->isExpired();
		}

		return false;
	}

	private function isExpired(): bool
	{
		if (!$this->activity)
		{
			return false;
		}

		$lightCounterAt = $this->activity['LIGHT_COUNTER_AT'] ?? null;
		$lightCounterTs = 0;
		if (
			$lightCounterAt && !\CCrmDateTimeHelper::IsMaxDatabaseDate($lightCounterAt)
			&& $lightCounterAt instanceof DateTime
		)
		{
			$lightCounterTs = $lightCounterAt->getTimestamp();
		}

		$nowTs = (new DateTime())->getTimestamp();

		return ($lightCounterTs > 0 && $lightCounterTs < $nowTs);
	}
}
