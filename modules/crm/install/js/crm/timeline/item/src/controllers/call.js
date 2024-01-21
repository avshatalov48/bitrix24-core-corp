import { ajax as Ajax, Event, Loc, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { sendData } from 'ui.analytics';
import { Button as ButtonUI, ButtonState } from 'ui.buttons';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';
import 'ui.feedback.form';
import { Router } from 'crm.router';

import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import { Button } from '../components/layout/button';

const COPILOT_BUTTON_DISABLE_DELAY = 5000;
const COPILOT_HELPDESK_CODE = 18_799_442;

export class Call extends Base
{
	#isCopilotTourShown: boolean = false;

	onInitialize(item: ConfigurableItem): void
	{
		this.#showCopilotTourIfNeeded(item);
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Call:MakeCall' && actionData)
		{
			this.#makeCall(actionData);
		}

		if (action === 'Call:Schedule' && actionData)
		{
			this.#scheduleCall(actionData.activityId, actionData.scheduleDate);
		}

		if (action === 'Call:OpenTranscript' && actionData && actionData.callId)
		{
			this.#openTranscript(actionData.callId);
		}

		if (action === 'Call:ChangePlayerState' && actionData && actionData.recordId)
		{
			this.#changePlayerState(item, actionData.recordId);
		}

		if (action === 'Call:DownloadRecord' && actionData && actionData.url)
		{
			this.#downloadRecord(actionData.url);
		}

		if (action === 'Call:LaunchCallRecordingTranscription' && actionData)
		{
			this.#launchCallRecordingTranscription(item, actionData);
		}
	}

	#makeCall(actionData): void
	{
		if (!Type.isStringFilled(actionData.phone))
		{
			return;
		}

		const params = {
			ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.entityTypeId),
			ENTITY_ID: actionData.entityId,
			AUTO_FOLD: true,
		};

		if (actionData.ownerTypeId !== actionData.entityTypeId || actionData.ownerId !== actionData.entityId)
		{
			params.BINDINGS = {
				OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.ownerTypeId),
				OWNER_ID: actionData.ownerId,
			};
		}

		if (actionData.activityId > 0)
		{
			params.SRC_ACTIVITY_ID = actionData.activityId;
		}

		window.top['BXIM'].phoneTo(actionData.phone, params);
	}

	#scheduleCall(activityId: Number, scheduleDate: String): void
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (menuBar)
		{
			menuBar.setActiveItemById('todo');

			const todoEditor = menuBar.getItemById('todo');
			todoEditor.focus();
			todoEditor.setParentActivityId(activityId);
			todoEditor.setDeadLine(scheduleDate);
		}
	}

	#openTranscript(callId): void
	{
		if (BX.Voximplant && BX.Voximplant.Transcript)
		{
			BX.Voximplant.Transcript.create({ callId }).show();
		}
	}

	#changePlayerState(item: ConfigurableItem, recordId: Number): void
	{
		const player = item.getLayoutContentBlockById('audio');
		if (!player)
		{
			return;
		}

		if (recordId !== player.id)
		{
			return;
		}

		if (player.state === 'play')
		{
			player.pause();
		}
		else
		{
			player.play();
		}
	}

	#downloadRecord(url: String): void
	{
		location.href = url;
	}

	#launchCallRecordingTranscription(item: ConfigurableItem, actionData: Object): void
	{
		const isValidParams: boolean = Type.isNumber(actionData.activityId)
			&& Type.isNumber(actionData.ownerId)
			&& Type.isNumber(actionData.ownerTypeId)
			&& [BX.CrmEntityType.enumeration.lead, BX.CrmEntityType.enumeration.deal]
				.includes(parseInt(actionData.ownerTypeId, 10))
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

		const data: Object = {
			activityId: actionData.activityId,
			ownerTypeId: actionData.ownerTypeId,
			ownerId: actionData.ownerId,
		};
		const ownerType: string = BX.CrmEntityType.resolveName(data.ownerTypeId).toLowerCase();
		const crmMode: string = Type.isStringFilled(actionData.crmMode) ? actionData.crmMode : '';
		const callId: string = Type.isStringFilled(actionData.callId) ? actionData.callId : '';

		// start call record transcription
		aiCopilotBtnUI.setState(ButtonState.AI_WAITING);

		Ajax
			.runAction('crm.timeline.ai.launchRecordingTranscription', { data })
			.then(() => {
				this.#sendAiCallParsingData(ownerType, crmMode, callId, 'success');
			})
			.catch((response) => {
				let errorType = 'error';

				const customData = response.errors[0].customData;
				if (customData)
				{
					this.#showAdditionalInfo(customData);

					aiCopilotBtnUI.setState(ButtonState.ACTIVE);

					errorType = 'error_no_limits';
				}
				else
				{
					aiCopilotBtnUI.setState(ButtonState.DISABLED);

					UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
						autoHideDelay: COPILOT_BUTTON_DISABLE_DELAY,
					});

					setTimeout(() => {
						aiCopilotBtnUI.setState(ButtonState.ACTIVE);
					}, COPILOT_BUTTON_DISABLE_DELAY);

					errorType = 'error_b24';
				}

				this.#sendAiCallParsingData(ownerType, crmMode, callId, errorType);

				throw response;
			});
	}

	#showAdditionalInfo(data: Object): void
	{
		if (Object.hasOwn(data, 'sliderCode') && Type.isStringFilled(data.sliderCode))
		{
			BX.UI.InfoHelper.show(data.sliderCode);
		}
		else if (Object.hasOwn(data, 'isAiMarketplaceAppsExist') && Type.isBoolean(data.isAiMarketplaceAppsExist))
		{
			if (data.isAiMarketplaceAppsExist)
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
			else
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
							defaultForm: {
								id: 682,
								lang: 'en',
								sec: '3sd3le',
							},
							forms: [{
								zones: ['cn'],
								id: 678,
								lang: 'cn',
								sec: 'wyufoe',
							}, {
								zones: ['vn'],
								id: 680,
								lang: 'vn',
								sec: '2v97xr',
							}],
						});
					},
					onCancel: (messageBox) => {
						messageBox.close();
					},
				});
			}
		}
	}

	#showCopilotTourIfNeeded(item: ConfigurableItem): void
	{
		if (!item)
		{
			return;
		}

		if (this.#isCopilotTourShown)
		{
			return;
		}

		const payload: Object = Type.isPlainObject(item.getDataPayload())
			? item.getDataPayload()
			: {};
		if (!payload.isCopilotTourCanShow)
		{
			return;
		}

		setTimeout(() => {
			const aiCopilotBtn: Button = item.getLayoutFooterButtonById('aiButton');
			if (!aiCopilotBtn)
			{
				return;
			}

			const aiCopilotUIBtn: ButtonUI = aiCopilotBtn.getUiButton();
			if (!aiCopilotUIBtn || aiCopilotUIBtn.getState() === ButtonState.DISABLED)
			{
				return;
			}

			if (aiCopilotBtn.isInViewport())
			{
				this.#emitTimelineCopilotTourEvent(aiCopilotUIBtn.getContainer());
			}
			else
			{
				const showCopilotTourOnScroll = () => {
					if (aiCopilotBtn.isInViewport())
					{
						this.#emitTimelineCopilotTourEvent(aiCopilotUIBtn.getContainer());
						this.#isCopilotTourShown = true;

						Event.unbind(window, 'scroll', showCopilotTourOnScroll);
					}
				};

				Event.bind(window, 'scroll', showCopilotTourOnScroll);
			}
		}, 50);
	}

	#emitTimelineCopilotTourEvent(container: Element): void
	{
		EventEmitter.emit('BX.Crm.Timeline.Call:onShowCopilotTour', {
			target: container,
			stepId: 'copilot-button-in-call',
			delay: 1500,
		});
	}

	#sendAiCallParsingData(ownerType: string, crmMode: string, callId: string, result: string): void
	{
		sendData({
			event: 'call_parsing',
			tool: 'AI',
			category: 'crm_operations',
			type: 'manual',
			c_section: 'crm',
			c_element: 'copilot_button',
			c_sub_section: ownerType,
			p1: crmMode,
			p2: callId,
			status: result,
		});
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:Call');
	}
}
