import { PhoneManager } from 'im.v2.lib.phone';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { Loc, Type } from 'main.core';
import { Controller } from './controller';
import { CallCardReplacementApp } from '../app';

export class CallCardIntegrator
{
	static async integrate(): ?CallCardReplacementApp
	{
		const callView = DesktopApi.isDesktop() ? window?.PCW : PhoneManager.getInstance().getPhoneCallView();
		if (!callView)
		{
			return null;
		}

		const callId = callView.callId;
		if (!Type.isStringFilled(callId))
		{
			return null;
		}

		return (new Controller())
			.resolveCallAssessment(callId)
			.then((response): ?CallCardReplacementApp => {
				const { callAssessment, hasAvailableSelectorItems } = response?.data ?? {};
				if (!callAssessment)
				{
					return null;
				}

				const replacement = new CallCardReplacementApp({
					hasAvailableSelectorItems,
					callAssessment,
					callId,
				});

				const tabTitle = Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_TAB_TITLE');

				return callView
					.addTab(tabTitle)
					.then((tab: Object): CallCardReplacementApp => {
						replacement.mount(tab.getContentContainerId());

						return replacement;
					})
				;
			})
			.catch(() => null)
		;
	}
}
