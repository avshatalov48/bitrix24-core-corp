import {Loc} from 'main.core';
import * as Tile from 'salescenter.tile';

class AppInstalled{

	open(tile: Tile.Base)
	{
		BX.ajax.runComponentAction(
			"bitrix:salescenter.app",
			"getRestApp",
			{
				data: {
					code: tile.code
				}
			}
		).then(function(response)
		{
			let app = response.data;
			if(app.TYPE === "A")
			{
				// this.showRestApplication(code);  ???
			}
			else
			{
				BX.rest.AppLayout.openApplication(tile.id);
			}
		}.bind(this)).catch(function(response)
		{
			this.errorPopup(" ", response.errors.pop().message);
		}.bind(this));
	}

	errorPopup(title, text)
	{
		let popup = new PopupWindow('rest-app-error-alert', null, {
			closeIcon: true,
			closeByEsc: true,
			autoHide: false,
			titleBar: title,
			content: text,
			zIndex: 16000,
			overlay: {
				color: 'gray',
				opacity: 30
			},
			buttons: [
				new PopupWindowButton({
					'id': 'close',
					'text': Loc.getMessage('SALESCENTER_JS_POPUP_CLOSE'),
					'events': {
						'click': function(){
							popup.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: function() {
					popup = null;
				}
			}
		});
		popup.show();
	}
}

export
{
	AppInstalled
}