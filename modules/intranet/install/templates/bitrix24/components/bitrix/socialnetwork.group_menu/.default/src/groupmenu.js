import { Type } from 'main.core';
import { GroupMenu as UIGroupMenu, Common as UICommon } from 'socialnetwork.common';

import Scrum from './scrum';
import Widget from './widget';
import ControlButton from './controlbutton';
import SonetGroupEvent from './sonetgroupevent';
import JoinButton from './joinbutton';
import TaskEvent from './taskevent';
import MoreButton from './morebutton';

export default class GroupMenu
{
	constructor(params)
	{
		this.initialized = false;
		this.moreButtonInstance = null;

		this.init(params);
	}

	init(params: { [key: string]: any }): void
	{
		if (this.initialized === true)
		{
			return;
		}

		this.initialized = true;

		this.pageId = Type.isStringFilled(params.pageId) ? params.pageId : '';
		this.currentUserId = !Type.isUndefined(params.currentUserId) ? Number(params.currentUserId) : 0;

		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.groupType = Type.isStringFilled(params.groupType) ? params.groupType : '';
		this.projectTypeCode = Type.isStringFilled(params.projectTypeCode) ? params.projectTypeCode : '';

		this.userRole = Type.isStringFilled(params.userRole) ? params.userRole : '';
		this.userIsMember = Type.isBoolean(params.userIsMember) ? params.userIsMember : false;
		this.userIsAutoMember = Type.isBoolean(params.userIsAutoMember) ? params.userIsAutoMember : false;
		this.userIsScrumMaster = Type.isBoolean(params.userIsScrumMaster) ? params.userIsScrumMaster : false;

		this.isProject = Type.isBoolean(params.isProject) ? params.isProject : false;
		this.isScrumProject = Type.isBoolean(params.isScrumProject) ? params.isScrumProject : false;
		this.isOpened = Type.isBoolean(params.isOpened) ? params.isOpened : false;
		this.favoritesValue = Type.isBoolean(params.favoritesValue) ? params.favoritesValue : false;

		this.canInitiate = Type.isBoolean(params.canInitiate) ? params.canInitiate : false;
		this.canModify = Type.isBoolean(params.canModify) ? params.canModify : false;
		this.canProcessRequestsIn = Type.isBoolean(params.canProcessRequestsIn) ? params.canProcessRequestsIn : false;
		this.canPickTheme = Type.isBoolean(params.canPickTheme) ? params.canPickTheme : false;

		this.avatarPath = Type.isStringFilled(params.avatarPath) ? params.avatarPath : '';
		this.avatarType = Type.isStringFilled(params.avatarType) ? params.avatarType : '';

		this.urls = Type.isPlainObject(params.urls) ? params.urls : {};

		this.editFeaturesAllowed = Type.isBoolean(params.editFeaturesAllowed) ? params.editFeaturesAllowed : true;
		this.copyFeatureAllowed = Type.isBoolean(params.copyFeatureAllowed) ? params.copyFeatureAllowed : true;

		new JoinButton(params);
		new ControlButton(params);
		new Scrum(params);
		new Widget(params);
		new TaskEvent(params);
		this.moreButtonInstance = new MoreButton(params);

		new SonetGroupEvent(params, {
			moreButtonInstance: this.moreButtonInstance,
		});

		const settingsButtonNode = document.getElementById('bx-group-menu-settings');
		if (settingsButtonNode)
		{
			const sonetGroupMenu = UIGroupMenu.getInstance();
			sonetGroupMenu.favoritesValue = this.favoritesValue;

			settingsButtonNode.addEventListener('click', this.showMenu.bind(this))
		}
	}

	showMenu(event)
	{
		UICommon.showGroupMenuPopup({
			bindElement: event.currentTarget,
			groupId: this.groupId,
			groupType: this.groupType,

			userRole: this.userRole,
			userIsMember: this.userIsMember,
			userIsAutoMember: this.userIsAutoMember,
			userIsScrumMaster: this.userIsScrumMaster,

			isProject: this.isProject,
			isScrumProject: this.isScrumProject,

			isOpened: this.isOpened,
			editFeaturesAllowed: this.editFeaturesAllowed,
			copyFeatureAllowed: this.copyFeatureAllowed,
			canPickTheme: this.canPickTheme,
			perms: {
				canInitiate: this.canInitiate,
				canProcessRequestsIn: this.canProcessRequestsIn,
				canModify: this.canModify
			},
			urls: {
				requestUser: (
					Type.isStringFilled(this.urls.Invite)
						? this.urls.Invite
						: `${this.urls.Edit}${(this.urls.Edit.indexOf('?') >= 0 ? '&' : '?')}tab=invite`
				),
				edit: `${this.urls.Edit}${(this.urls.Edit.indexOf('?') >= 0 ? '&' : '?')}tab=edit`,
				delete: this.urls.Delete,
				features: this.urls.Features,
				members: this.urls.GroupUsers,
				requests: this.urls.GroupRequests,
				requestsOut: this.urls.GroupRequestsOut,
				userRequestGroup: this.urls.UserRequestGroup,
				userLeaveGroup: this.urls.UserLeaveGroup,
				copy: this.urls.Copy,
			}
		});

		event.preventDefault();
	}
}
