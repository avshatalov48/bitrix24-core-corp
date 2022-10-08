// @flow

import { Cache, Loc, Tag, ajax} from 'main.core';
import { PopupManager } from 'main.popup';

type CreateLineOptions = {
	path: string;
	sliderWidth: number;
};

export class CreateLine
{
	path: string;
	isLocked: boolean = false;
	cache = new Cache.MemoryCache();

	constructor(options: CreateLineOptions)
	{
		this.path = options.path;
		this.sliderWidth = options.sliderWidth ;

		if (this.path)
		{
			this.init();
		}
	}

	init()
	{
		if (this.isLocked)
		{
			return;
		}

		this.isLocked = true;

		ajax({
			url: '/bitrix/components/bitrix/imopenlines.lines/ajax.php',
			method: 'POST',
			data: {
				'action': 'create',
				'sessid': BX.bitrix_sessid()
			},
			timeout: 30,
			dataType: 'json',
			processData: true,
			onsuccess: data => {
				data = data || {};
				if(data.error)
				{
					this.onFail(data);
				}
				else
				{
					this.onSuccess(data);
				}
			},
			onfailure: data => this.onFail(data),
		});
	}

	onSuccess(data: {})
	{
		BX.SidePanel.Instance.open(
			this.path.replace('#LINE#', data.config_id),
			{ width: this.sliderWidth, cacheable: false }
		);
	}

	onFail(responseData: {})
	{
		responseData = responseData || {'error': true, 'text': ''};
		this.isLocked = false;

		if (responseData.limited) //see \Bitrix\ImOpenLines\Config::canActivateLine()
		{
			if (!B24 || !B24['licenseInfoPopup'])
			{
				return;
			}

			BX.UI.InfoHelper.show('limit_contact_center_ol_number');
		}
		else
		{
			responseData = responseData || {};
			const errorMessage = responseData.text || Loc.getMessage('IMOPENLINES_CREATE_LINE_ERROR_ACTION')

			this.showErrorPopup(errorMessage);
		}
	}

	showErrorPopup(errorMessage: string)
	{
		const popup = PopupManager.create({
			id: 'crm_webform_list_error',
			content: this.getPopupContent(errorMessage),
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('IMOPENLINES_CREATE_LINE_CLOSE_BUTTON'),
					onclick: () => popup.close()
				})
			],
			autoHide: true,
			lightShadow: true,
			closeByEsc: true,
			overlay: { backgroundColor: 'black', opacity: 500 }
		});

		popup.show();
	}

	getPopupContent(message: string)
	{
		return this.cache.remember('popupContent', () => {
			return Tag.render`
				<span class="crm-webform-edit-warning-popup-alert">${message}</span>
			`;
		});
	}
}