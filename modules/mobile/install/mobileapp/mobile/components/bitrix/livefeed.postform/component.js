"use strict";
(()=>{

const LivefeedPostForm = {

	newPostComponent: null,
};

LivefeedPostForm.clean = () => {
	if (this.newPostComponent)
	{
		if (this.newPostComponent.actionSheetWidget)
		{
			this.newPostComponent.actionSheetWidget.close();
		}
		if (this.newPostComponent.backgroundWidget)
		{
			this.newPostComponent.backgroundWidget.close();
		}
		if (this.newPostComponent.attachmentWidget)
		{
			this.newPostComponent.attachmentWidget.close();
		}
		if (this.newPostComponent.medalWidget)
		{
			this.newPostComponent.medalWidget.close();
		}
	}

};

LivefeedPostForm.init = () => {
	LivefeedPostForm.clean();

	this.newPostComponent = new NewPostComponent();

	BX.onViewLoaded(() => {
		postFormLayoutWidget.showComponent(newPostComponent);
		postFormLayoutWidget.setRightButtons([
			{
				name: BX.message('MOBILEAPP_LIVEFEED_POSTFORM_BUTTON_SUBMIT_TITLE'),
				callback: this.newPostComponent.onPublish.bind(this.newPostComponent),
				color: '#0B66C3'
			}
		]);
		postFormLayoutWidget.setLeftButtons([
			{
				name: BX.message('MOBILEAPP_LIVEFEED_POSTFORM_BUTTON_CLOSE_TITLE'),
				callback: () => {
					this.newPostComponent.onClose();
				},
			}
		]);
		postFormLayoutWidget.setBackButtonHandler(() => {
			this.newPostComponent.onClose();
			return true;
		});

	})
};

LivefeedPostForm.init();

})();
