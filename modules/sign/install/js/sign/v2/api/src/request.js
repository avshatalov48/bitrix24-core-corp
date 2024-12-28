import { ajax, Loc, Text } from 'main.core';
import { UI } from 'ui.notification';
import { StubLinkType, StubNotAvailable, StubType } from 'ui.sidepanel-content';

export async function request(
	method: string,
	endpoint: string,
	data: ?Object,
	notifyError: ?boolean = true,
): Promise<Object>
{
	const config: Parameters<typeof BX.ajax.runAction>[2] = { method };
	if (method === 'POST')
	{
		Object.assign(config, { data }, {
			preparePost: false,
			headers: [
				{
					name: 'Content-Type',
					value: 'application/json',
				},
			],
		});
	}

	try
	{
		const response = await ajax.runAction(endpoint, config);
		if (response.errors?.length > 0)
		{
			throw new Error(response.errors[0].message);
		}

		return response.data;
	}
	catch (ex)
	{
		if (!notifyError)
		{
			return ex;
		}

		const { message = `Error in ${endpoint}`, errors = [] } = ex;
		const errorCode = errors[0]?.code ?? '';

		if (errorCode === 'SIGN_CLIENT_CONNECTION_ERROR')
		{
			const stub = new StubNotAvailable({
				title: Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_TITLE'),
				desc: Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_DESC'),
				type: StubType.noConnection,
				link: {
					text: Loc.getMessage('SIGN_JS_V2_API_ERROR_CLIENT_CONNECTION_LINK_TEXT'),
					value: '18740976',
					type: StubLinkType.helpdesk,
				},
			});
			stub.openSlider();

			throw ex;
		}

		if (errorCode === 'LICENSE_LIMITATIONS')
		{
			top.BX.UI.InfoHelper.show('limit_office_e_signature_box');

			throw ex;
		}

		if (errorCode === 'SIGN_DOCUMENT_INCORRECT_STATUS')
		{
			const stub = new StubNotAvailable({
				title: Loc.getMessage('SIGN_DOCUMENT_INCORRECT_STATUS_STUB_TITLE'),
				desc: Loc.getMessage('SIGN_DOCUMENT_INCORRECT_STATUS_STUB_DESC'),
				type: StubType.notAvailable,
			});
			stub.openSlider();

			//close previous slider (with editor)
			const slider = BX.SidePanel.Instance.getTopSlider();
			const onSliderCloseHandler = (e: BX.SidePanel.Event): void => {
				if (slider !== e.getSlider())
				{
					return;
				}

				window.top.BX.removeCustomEvent(
					slider.getWindow(),
					'SidePanel.Slider:onClose',
					onSliderCloseHandler,
				);

				const sliders = window.top.BX.SidePanel.Instance.getOpenSliders();
				for (let i = sliders.length - 2; i >= 0; i--)
				{
					if (sliders[i].getUrl().startsWith('/sign/doc/'))
					{
						sliders[i].close();

						return;
					}
				}
			};

			window.top.BX.addCustomEvent(slider.getWindow(), 'SidePanel.Slider:onClose', onSliderCloseHandler);

			throw ex;
		}

		if (errorCode === 'B2E_RESTRICTED_ON_TARIFF' || errorCode === 'B2E_SIGNERS_LIMIT_REACHED_ON_TARIFF')
		{
			top.BX.UI.InfoHelper.show('limit_office_e_signature');

			throw ex;
		}

		const content = errors[0]?.message ?? message;
		UI.Notification.Center.notify({
			content: Text.encode(content),
			autoHideDelay: 4000,
		});

		throw ex;
	}
}

export function post(endpoint: string, data: Object | null = null, notifyError: boolean = true): Promise<Object>
{
	return request('POST', endpoint, data, notifyError);
}
