import {Event, Loc, Tag, Type} from 'main.core';
import {Loader} from 'main.loader';
import {PopupWindowManager} from 'main.popup';

export class MembersPopup
{
	static get titles()
	{
		return {
			heads: Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS'),
			members: Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS'),
		};
	}

	constructor(options)
	{
		this.signedParameters = options.signedParameters;
	}

	show(groupId, type, bindNode)
	{
		if (this.isPopupShown)
		{
			this.popup.destroy();
		}

		this.currentPage = 1;
		this.innerContainer = '';
		this.renderedUsers = [];

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
					const popupContainer = this.renderContainer(type);
					const loaderNode = popupContainer.querySelector('.tasks-projects-members-popup-content');

					this.innerContainer = popupContainer.querySelector('.tasks-projects-members-popup-inner');

					popup.contentContainer.appendChild(popupContainer);

					this.showLoader(loaderNode);
					this.showUsers(groupId, type);

					this.isPopupShown = true;
				},
			},
		});
		this.popupScroll(groupId, type);
		this.popup.show();
	}

	renderContainer(type)
	{
		return Tag.render`
			<div>
				<span class="tasks-projects-members-popup-name-title">
					${MembersPopup.titles[type]}
				</span>
				<div class="tasks-projects-members-popup-container">
					<div class="tasks-projects-members-popup-content">
						<div class="tasks-projects-members-popup-content-box">
							<div class="tasks-projects-members-popup-inner"></div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	popupScroll(groupId, type)
	{
		if (!BX.type.isDomNode(this.innerContainer))
		{
			return;
		}

		Event.bind(this.innerContainer, 'scroll', (event) => {
			const area = event.target;
			if (area.scrollTop > (area.scrollHeight - area.offsetHeight) / 1.5)
			{
				this.showUsers(groupId, type);
				Event.unbindAll(this.innerContainer);
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
				page: this.currentPage,
			},
			signedParameters: this.signedParameters,
		}).then(
			(response) => {
				if (response.data)
				{
					this.currentPage++;
					this.renderUsers(response.data);
					this.popupScroll(groupId, type);
				}
				else if (!this.innerContainer.hasChildNodes())
				{
					this.innerContainer.innerText = Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_EMPTY');
				}
				this.hideLoader();
			},
			() => this.hideLoader()
		);
	}

	renderUsers(users)
	{
		Object.values(users).forEach((user) => {
			if (this.renderedUsers.indexOf(user.ID) >= 0)
			{
				return;
			}
			this.renderedUsers.push(user.ID);

			this.innerContainer.appendChild(
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
					<i style="background-image: url('${user['PHOTO']}')"></i>
				</div>
			`;
		}

		return Tag.render`
			<div class="ui-icon ui-icon-common-user tasks-projects-members-popup-avatar-img"><i></i></div>
		`;
	}

	showLoader(target)
	{
		if (!this.loader)
		{
			this.loader = new Loader({
				target,
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
}