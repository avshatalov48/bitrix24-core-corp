import { Type } from 'main.core';
import { WorkgroupWidget as UIWorkgroupWidget } from 'socialnetwork.common';

export default class Widget
{
	constructor(params)
	{
		this.projectWidgetInstance = null;

		this.init(params);
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.avatarPath = Type.isStringFilled(params.avatarPath) ? params.avatarPath : '';
		this.avatarType = Type.isStringFilled(params.avatarType) ? params.avatarType : '';
		this.projectTypeCode = Type.isStringFilled(params.projectTypeCode) ? params.projectTypeCode : '';
		this.canModify = Type.isBoolean(params.canModify) ? params.canModify : false;
		this.editFeaturesAllowed = Type.isBoolean(params.editFeaturesAllowed) ? params.editFeaturesAllowed : true;

		this.urls = Type.isPlainObject(params.urls) ? params.urls : {};

		const projectWidgetButton = document.getElementById('project-widget-button');
		if (projectWidgetButton)
		{
			projectWidgetButton.addEventListener('click', this.showProjectWidget.bind(this));
		}
	}

	showProjectWidget(event)
	{
		if (this.projectWidgetInstance === null)
		{
			this.projectWidgetInstance = new UIWorkgroupWidget({
				groupId: this.groupId,
				avatarPath: this.avatarPath,
				avatarType: this.avatarType,
				projectTypeCode: this.projectTypeCode,
				perms: {
					canModify: this.canModify,
				},
				urls: {
					card: this.urls.Card,
					members: this.urls.GroupUsers,
					features: this.urls.Features,
				},
				editRolesAllowed: this.editFeaturesAllowed,
			});
		}

		this.projectWidgetInstance.show(event.target);
		if (
			this.projectWidgetInstance.widget
			&& this.projectWidgetInstance.widget.getPopup()
		)
		{
			BX.UI.Hint.init(this.projectWidgetInstance.widget.getPopup().getContentContainer());
		}

		event.preventDefault();
	}
}
