"use strict";

(()=>{

const NotificationsComponent = {
	newNotificationsComponent: null,
};

NotificationsComponent.init = () => {

	BX.onViewLoaded(() => {
		layoutWidget.showComponent(new NewNotificationsComponent())
	})
};

NotificationsComponent.init();
})();