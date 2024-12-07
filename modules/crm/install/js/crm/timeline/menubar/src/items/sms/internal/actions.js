import { ajax as Ajax, Loc, Reflection, Type, Uri } from 'main.core';
import { SidePanel } from 'ui.sidepanel';

const CHANNEL_MANAGER_SLIDER_WIDTH = 700;

export function saveSmsMessage(
	serviceUrl: string,
	senderId: string,
	params: Object,
	onSuccessHandler: Function,
	onFailureHandler: Function,
): Promise
{
	const baseParams = {
		site: Loc.getMessage('SITE_ID'),
		sessid: Loc.getMessage('bitrix_sessid'),
		ACTION: 'SAVE_SMS_MESSAGE',
		SENDER_ID: senderId,
	};

	return new Promise((resolve, reject): void => {
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		BX.ajax({
			url: getSendUrl(serviceUrl, senderId),
			method: 'POST',
			dataType: 'json',
			data: { ...params, ...baseParams },
			onsuccess: () => {
				onSuccessHandler();
				resolve();
			},
			onfailure: () => {
				onFailureHandler();
				reject();
			},
		});
	});
}

export function createOrUpdatePlaceholder(
	templateId: number,
	entityTypeId: number,
	entityCategoryId: ?number,
	params: Object,
): Promise
{
	const { id, value, entityType, text } = params;

	return Ajax.runAction(
		'crm.activity.smsplaceholder.createOrUpdatePlaceholder',
		{
			data: {
				placeholderId: id,
				fieldName: Type.isStringFilled(value) ? value : null,
				entityType: Type.isStringFilled(entityType) ? entityType : null,
				fieldValue: Type.isStringFilled(text) ? text : null,
				templateId,
				entityTypeId,
				entityCategoryId,
			},
		},
	);
}

export function showChannelManagerSlider(manageUrl: string): void
{
	if (!Type.isStringFilled(manageUrl))
	{
		throw new Error('"manageUrl" parameter must be specified');
	}

	if (!Reflection.getClass('BX.SidePanel.Instance.getTopSlider'))
	{
		throw new Error('Class "SidePanel.Instance.getTopSlider" not found');
	}

	const url = Uri.addParam(manageUrl, { IFRAME: 'Y' });
	const slider = SidePanel.Instance.getTopSlider();
	const options = {
		width: CHANNEL_MANAGER_SLIDER_WIDTH,
		events: {
			onClose: () => {
				if (slider)
				{
					slider.reload();
				}
			},
			onCloseComplete: () => {
				if (!slider)
				{
					document.location.reload();
				}
			},
		},
	};
	SidePanel.Instance.open(url, options);
}

function getSendUrl(serviceUrl: string, senderId: string): string
{
	if (!Type.isStringFilled(serviceUrl))
	{
		throw new Error('"serviceUrl" parameter must be specified');
	}

	if (!Type.isStringFilled(senderId))
	{
		throw new Error('"senderId" parameter must be specified');
	}

	return BX.util.add_url_param(
		serviceUrl,
		{
			action: 'save_sms_message',
			sender: senderId,
		},
	);
}
