"use strict";
(()=>{


	class NotifyLegacy
	{
		constructor()
		{
			console.log('Notify legacy is loaded.');
			this.initTopMenu();
		}

		initTopMenu()
		{
			let topMenuInstance = dialogs.createPopupMenu();
			topMenuInstance.setData(
				[{ id: "readAll", title: BX.message('IM_READ_ALL'), sectionCode: "general", iconName: "read"}],
				[{ id: "general" }],
				(event, item) => {
					if (event === 'onItemSelected' && item.id === 'readAll')
					{
						this.readAll();
					}
				}
			);

			widget.setRightButtons([
				{type: "more", callback: () => {topMenuInstance.show();}}
			]);
		}

		readAll()
		{
			BX.rest.callMethod('im.notify.read.all')
				.then(result => {
					console.log('im.notify.read.all result:', result);
				})
				.catch(error => {
					console.log('im.notify.read.all error:', error);
				})
			;

			BX.postComponentEvent('chatdialog::notification::readAll', [], 'im.recent');
		};
	}

	window.Notify = new NotifyLegacy();

})();