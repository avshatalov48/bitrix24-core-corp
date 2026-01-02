import { Router } from 'crm.router';
import { ajax as Ajax, Loc, Runtime, Text, Type } from 'main.core';
import { Button as ButtonUI, ButtonState } from 'ui.buttons';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { UI } from 'ui.notification';
import { EditableDescriptionAiStatus } from '../components/enums/editable-description-ai-status';
import { Button } from '../components/layout/button';
import ConfigurableItem from '../configurable-item';

import { ActionParams, Base } from './base';

const COPILOT_BUTTON_DISABLE_DELAY = 5000;
const COPILOT_HELPDESK_CODE = 18_799_442;

declare type CoPilotAdditionalInfoData = {
	sliderCode: ?string,
	isAiMarketplaceAppsExist: ?boolean,
}

export class RepeatSale extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:RepeatSale:ShowRestrictionSlider')
		{
			this.#showRestrictionSlider();
		}

		if (!Type.isObject(actionData))
		{
			return;
		}

		if (action === 'Activity:RepeatSale:Schedule')
		{
			this.runScheduleAction(actionData.activityId, actionData.scheduleDate, actionData.description);
		}

		if (action === 'Activity:RepeatSale:OpenSegment')
		{
			this.#openSegment(actionData.activityId, actionData.segmentId);
		}

		if (action === 'Activity:RepeatSale:LaunchCopilot')
		{
			// @todo
			const isCopilotAgreementNeedShow = actionData.isCopilotAgreementNeedShow || false;
			if (isCopilotAgreementNeedShow)
			{
				Runtime.loadExtension('ai.copilot-agreement')
					.then(({ CopilotAgreement }) => {
						const copilotAgreementPopup = new CopilotAgreement({
							moduleId: 'crm',
							contextId: 'audio',
							events: {
								onAccept: () => this.#launchCopilot(item, actionData),
							},
						});

						void copilotAgreementPopup.checkAgreement()
							// eslint-disable-next-line promise/no-nesting
							.then((isAgreementAccepted) => {
								if (isAgreementAccepted)
								{
									this.#launchCopilot(item, actionData);
								}
							});
					})
					.catch(() => console.error('Cant load "ai.copilot-agreement" extension'))
				;
			}
			else
			{
				this.#launchCopilot(item, actionData);
			}
		}
	}

	#openSegment(item: ConfigurableItem, segmentId: number): void
	{
		if (!Type.isInteger(segmentId))
		{
			return;
		}

		void Router.Instance.openRepeatSaleSegmentSlider(segmentId, true, {
			section: 'deal_section',
		});
	}

	#showRestrictionSlider(): void
	{
		FeaturePromotersRegistry.getPromoter({ featureId: 'limit_v2_crm_repeat_sale' }).show();
	}

	#launchCopilot(item: ConfigurableItem, actionData: Object): void
	{
		const isValidParams: boolean = Type.isNumber(actionData.activityId)
			&& Type.isNumber(actionData.ownerId)
			&& Type.isNumber(actionData.ownerTypeId)
			&& BX.CrmEntityType.enumeration.deal === parseInt(actionData.ownerTypeId, 10)
		;

		if (!isValidParams)
		{
			throw new Error('Invalid "actionData" parameters');
		}

		const aiCopilotBtn: Button = item.getLayoutFooterButtonById('aiButton');
		if (!aiCopilotBtn)
		{
			throw new Error('"CoPilot" button is not found in layout');
		}
		const aiCopilotBtnUI: ButtonUI = aiCopilotBtn.getUiButton();
		const aiCopilotBtnUIPrevState = aiCopilotBtnUI.getState();

		if (aiCopilotBtnUI.getState() === ButtonState.AI_WAITING)
		{
			return;
		}

		aiCopilotBtnUI.setState(ButtonState.AI_WAITING);

		const descriptionBlock = item.getLayoutContentBlockById('description');
		const prevHeaderText = descriptionBlock?.getHeaderText();
		descriptionBlock?.setHeaderText('');
		descriptionBlock?.setCopilotStatus(EditableDescriptionAiStatus.IN_PROGRESS);

		const errorBlock = item.getLayoutContentBlockById('error');
		errorBlock?.closeBlock();

		Ajax
			.runAction('crm.timeline.repeatsale.launchCopilot', {
				data: {
					activityId: actionData.activityId,
					ownerTypeId: actionData.ownerTypeId,
					ownerId: actionData.ownerId,
				},
			}).then((response) => {}).catch((response) => {
				const customData: ?CoPilotAdditionalInfoData = response.errors[0].customData;
				if (customData)
				{
					this.#showAdditionalInfo(customData, item, actionData);

					aiCopilotBtnUI.setState(aiCopilotBtnUIPrevState || ButtonState.ACTIVE);
					descriptionBlock?.setHeaderText(prevHeaderText);
					descriptionBlock?.setCopilotStatus(EditableDescriptionAiStatus.NONE);
				}
				else
				{
					aiCopilotBtnUI.setState(ButtonState.DISABLED);
					descriptionBlock?.setHeaderText(prevHeaderText);
					descriptionBlock?.setCopilotStatus(EditableDescriptionAiStatus.NONE)

					UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
						autoHideDelay: COPILOT_BUTTON_DISABLE_DELAY,
					});

					setTimeout(() => {
						aiCopilotBtnUI.setState(ButtonState.ACTIVE);
					}, COPILOT_BUTTON_DISABLE_DELAY);
				}

				throw response;
			});
	}

	#showAdditionalInfo(data: CoPilotAdditionalInfoData, item: ConfigurableItem, actionData: Object): void
	{
		if (this.#isSliderCodeExist(data))
		{
			if (data.sliderCode === 'limit_boost_copilot')
			{
				Runtime.loadExtension('baas.store')
					.then(({ ServiceWidget, Analytics }) => {
						if (!ServiceWidget)
						{
							void FeaturePromotersRegistry.getPromoter({ code: 'limit_boost_copilot' }).show();

							console.error('Cant load "baas.store" extension');
						}

						const serviceWidget = ServiceWidget?.getInstanceByCode('ai_copilot_token');
						const bindElement = item.getLayoutFooterButtonById('aiButton')?.getUiButton()?.getContainer();

						serviceWidget.bind(bindElement, Analytics.CONTEXT_CRM);
						serviceWidget.show(bindElement);
						serviceWidget.getPopup().adjustPosition({
							forceTop: true,
						});
					})
					.catch(() => {
						void FeaturePromotersRegistry.getPromoter({ code: 'limit_boost_copilot' }).show();

						console.error('Cant load "baas.store" extension');
					})
				;
			}
			else
			{
				data.sliderCode?.includes('redirect=detail&code')
					? top.BX.Helper.show(data.sliderCode)
					: FeaturePromotersRegistry.getPromoter({ code: data.sliderCode }).show()
				;
			}
		}
		else if (this.#isAiMarketplaceAppsExist(data))
		{
			this.#showMarketMessageBox();
		}
		else
		{
			this.#showFeedbackMessageBox();
		}
	}

	#showMarketMessageBox(): void
	{
		MessageBox.show({
			title: Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_TITLE'),
			message: Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_TEXT', {
				'[helpdesklink]': `<br><br><a href="##" onclick="top.BX.Helper.show('redirect=detail&code=${COPILOT_HELPDESK_CODE}');">`,
				'[/helpdesklink]': '</a>',
			}),
			modal: true,
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('CRM_TIMELINE_ITEM_AI_PROVIDER_POPUP_OK_TEXT'),
			onOk: () => {
				return Router.openSlider(Loc.getMessage('AI_APP_COLLECTION_MARKET_LINK'));
			},
			onCancel: (messageBox) => {
				messageBox.close();
			},
		});
	}

	#showFeedbackMessageBox(): void
	{
		MessageBox.show({
			title: Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_TITLE'),
			message: Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_TEXT'),
			modal: true,
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('CRM_TIMELINE_ITEM_NO_AI_PROVIDER_POPUP_OK_TEXT'),
			onOk: (messageBox) => {
				messageBox.close();

				BX.UI.Feedback.Form.open({
					id: 'b24_ai_provider_partner_crm_feedback',
					forms: [
						{
							zones: ['en'],
							id: 682,
							lang: 'en',
							sec: '3sd3le',
						},
						{
							zones: ['cn'],
							id: 678,
							lang: 'cn',
							sec: 'wyufoe',
						},
						{
							zones: ['vn'],
							id: 680,
							lang: 'vn',
							sec: '2v97xr',
						},
					],
				});
			},
			onCancel: (messageBox) => {
				messageBox.close();
			},
		});
	}

	#isSliderCodeExist(data: CoPilotAdditionalInfoData): boolean
	{
		return Object.hasOwn(data, 'sliderCode')
			&& Type.isStringFilled(data.sliderCode)
		;
	}

	#isAiMarketplaceAppsExist(data: CoPilotAdditionalInfoData): boolean
	{
		return Object.hasOwn(data, 'isAiMarketplaceAppsExist')
			&& Type.isBoolean(data.isAiMarketplaceAppsExist)
			&& data.isAiMarketplaceAppsExist
		;
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'Activity:RepeatSale'
			|| item.getType() === 'RepeatSaleCreated'
			|| item.getType() === 'LaunchError'
		;
	}
}
