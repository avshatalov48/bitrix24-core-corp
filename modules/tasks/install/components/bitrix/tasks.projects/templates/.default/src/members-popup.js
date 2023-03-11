import {Dom, Event, Loc, Tag, Type} from 'main.core';
import {Loader} from 'main.loader';
import {PopupWindowManager} from 'main.popup';

export class MembersPopup
{
	constructor(options)
	{
		this.signedParameters = options.signedParameters;
	}

	show(groupId, bindNode, type = 'all')
	{
		if (this.isPopupShown)
		{
			this.popup.destroy();
		}

		this.groupId = groupId;

		this.resetPopupData();
		this.changeType(type, false);

		this.popup = PopupWindowManager.create({
			id: 'projects-members-popup-menu',
			className: 'tasks-projects-members-popup',
			bindElement: bindNode,
			autoHide: true,
			closeByEsc: true,
			lightShadow: true,
			bindOptions: {
				position: 'bottom',
			},
			animationOptions: {
				show: {
					type: 'opacity-transform',
				},
				close: {
					type: 'opacity',
				},
			},
			events: {
				onPopupDestroy: () => {
					this.loader = null;
					this.isPopupShown = false;
				},
				onPopupClose: () => {
					this.popup.destroy();
				},
				onAfterPopupShow: (popup) => {
					popup.contentContainer.appendChild(this.renderContainer());

					this.showLoader();
					this.showUsers(groupId, type);

					this.isPopupShown = true;
				},
			},
		});
		this.popupScroll(groupId, type);
		this.popup.show();
	}

	renderContainer()
	{
		return Tag.render`
			<span class="tasks-projects-members-popup-container">
				<span class="tasks-projects-members-popup-head">
					${this.popupData.all.tab}
					${this.popupData.heads.tab}
					${this.popupData.members.tab}
				</span>
				<span class="tasks-projects-members-popup-body">
					<div class="tasks-projects-members-popup-content">
						<div class="tasks-projects-members-popup-content-box">
							${this.getCurrentPopupData().innerContainer}
						</div>
					</div>
				</span>
			</span>
		`;
	}

	popupScroll(groupId, type)
	{
		if (!BX.type.isDomNode(this.getCurrentPopupData().innerContainer))
		{
			return;
		}

		Event.bind(this.getCurrentPopupData().innerContainer, 'scroll', (event) => {
			const area = event.target;
			if (area.scrollTop > (area.scrollHeight - area.offsetHeight) / 1.5)
			{
				this.showUsers(groupId, type);
				Event.unbindAll(this.getCurrentPopupData().innerContainer);
			}
		});
	};

	showUsers(groupId, type)
	{
		BX.ajax.runComponentAction('bitrix:tasks.projects', 'getPopupMembers', {
			mode: 'class',
			data: {
				groupId,
				type,
				page: this.getCurrentPopupData().currentPage,
			},
			signedParameters: this.signedParameters,
		}).then(
			(response) => {
				if (this.groupId !== groupId || this.currentType !== type)
				{
					this.hideLoader();
					return;
				}
				if (response.data.length > 0)
				{
					this.renderUsers(response.data);
					this.popupScroll(groupId, this.currentType);
				}
				else if (!this.getCurrentPopupData().innerContainer.hasChildNodes())
				{
					this.getCurrentPopupData().innerContainer.innerText = Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_EMPTY');
				}
				this.getCurrentPopupData().currentPage++;
				this.hideLoader();
			},
			() => this.hideLoader()
		);
	}

	renderUsers(users)
	{
		Object.values(users).forEach((user) => {
			if (this.getCurrentPopupData().renderedUsers.indexOf(user.ID) >= 0)
			{
				return;
			}
			this.getCurrentPopupData().renderedUsers.push(user.ID);

			this.getCurrentPopupData().innerContainer.appendChild(
				Tag.render`
					<a class="tasks-projects-members-popup-item" href="${user['HREF']}" target="_blank">
						<span class="tasks-projects-members-popup-avatar-new">
							${this.getAvatar(user)}
							<span class="tasks-projects-members-popup-avatar-status-icon"></span>
						</span>
						<span class="tasks-projects-members-popup-name">${user['FORMATTED_NAME']}</span>
					</a>
				`
			);
		})
	}

	getAvatar(user)
	{
		if (Type.isStringFilled(user['PHOTO']))
		{
			return Tag.render`
				<div class="ui-icon ui-icon-common-user tasks-projects-members-popup-avatar-img">
					<i style="background-image: url('${encodeURI(user['PHOTO'])}')"></i>
				</div>
			`;
		}

		return Tag.render`
			<div class="ui-icon ui-icon-common-user tasks-projects-members-popup-avatar-img"><i></i></div>
		`;
	}

	showLoader()
	{
		if (!this.loader)
		{
			this.loader = new Loader({
				target: this.popup.getPopupContainer().querySelector('.tasks-projects-members-popup-content'),
				size: 40,
			});
		}
		void this.loader.show();
	}

	hideLoader()
	{
		if (this.loader)
		{
			void this.loader.hide();
			this.loader = null;
		}
	}

	changeType(newType, loadUsers = true)
	{
		const oldType = this.currentType;

		this.currentType = newType;

		Object.values(this.popupData).forEach((item) => {
			Dom.removeClass(item.tab, 'tasks-projects-members-popup-head-item-current');
		});
		Dom.addClass(this.getCurrentPopupData().tab, 'tasks-projects-members-popup-head-item-current');

		if (oldType)
		{
			Dom.replace(this.popupData[oldType].innerContainer, this.getCurrentPopupData().innerContainer);
		}

		if (loadUsers && this.getCurrentPopupData().currentPage === 1)
		{
			this.showLoader();
			this.showUsers(this.groupId, newType);
		}
	}

	resetPopupData()
	{
		this.popupData = {
			all: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span class="tasks-projects-members-popup-head-item" onclick="${this.changeType.bind(this, 'all')}">
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_ALL')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			},
			heads: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span class="tasks-projects-members-popup-head-item" onclick="${this.changeType.bind(this, 'heads')}">
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			},
			members: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span class="tasks-projects-members-popup-head-item" onclick="${this.changeType.bind(this, 'members')}">
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			},
		};
	}

	getCurrentPopupData()
	{
		return this.popupData[this.currentType];
	}
}