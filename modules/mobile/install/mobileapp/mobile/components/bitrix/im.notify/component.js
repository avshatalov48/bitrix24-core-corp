"use strict";

(()=>{

const NotificationsComponent = {
	newNotificationsComponent: null,
};

NotificationsComponent.init = () => {

	BX.onViewLoaded(() => {

		this.newNotificationsComponent = new NewNotificationsComponent();
		layoutWidget.showComponent(newNotificationsComponent);

		const topMenuInstance = dialogs.createPopupMenu();
		topMenuInstance.setData(
			[{ id: "readAll", title: BX.message('IM_NOTIFY_READ_ALL'), sectionCode: "general", iconName: "read"}],
			[{ id: "general" }],
			(event, item) => {
				if (event === 'onItemSelected' && item.id === 'readAll')
				{
					this.newNotificationsComponent.readAll();
				}
			}
		);
		layoutWidget.setRightButtons([{
			type: "more", callback: () => {
				topMenuInstance.show();
			}
		}]);
	})
};

NotificationsComponent.init();
})();