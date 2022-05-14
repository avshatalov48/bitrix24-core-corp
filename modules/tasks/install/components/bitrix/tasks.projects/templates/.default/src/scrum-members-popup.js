import {Loc, Tag, Type} from 'main.core';
import {MembersPopup} from './members-popup';

export class ScrumMembersPopup extends MembersPopup
{
	renderContainer()
	{
		return Tag.render`
			<span class="tasks-projects-members-popup-container">
				<span class="tasks-projects-members-popup-head">
					${this.popupData.all.tab}
					${this.popupData.scrumTeam.tab}
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

	resetPopupData()
	{
		this.popupData = {
			all: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span
						class="tasks-projects-members-popup-head-item"
						onclick="${this.changeType.bind(this, 'all')}"
					>
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_ALL')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			},
			scrumTeam: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span
						class="tasks-projects-members-popup-head-item"
						onclick="${this.changeType.bind(this, 'scrumTeam')}"
					>
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_TEAM')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			},
			members: {
				currentPage: 1,
				renderedUsers: [],
				tab: Tag.render`
					<span
						class="tasks-projects-members-popup-head-item"
						onclick="${this.changeType.bind(this, 'members')}"
					>
						<span class="tasks-projects-members-popup-head-text">
							${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_MEMBERS')}
						</span>
					</span>
				`,
				innerContainer: Tag.render`<div class="tasks-projects-members-popup-inner"></div>`,
			}
		};
	}

	renderUsers(users)
	{
		if (this.currentType === 'scrumTeam')
		{
			this.renderLabels(users);

			Object.values(users).forEach((user) => {
				if (
					this.getCurrentPopupData().renderedUsers.indexOf(user.ID) >= 0
					&& user.ROLE !== 'M'
				)
				{
					return;
				}
				this.getCurrentPopupData().renderedUsers.push(user.ID);

				const containersMap = new Map();
				containersMap.set('A', 'tasks-scrum-members-popup-owner-container');
				containersMap.set('M', 'tasks-scrum-members-popup-master-container');
				containersMap.set('E', 'tasks-scrum-members-popup-team-container');

				if (Type.isUndefined(containersMap.get(user.ROLE)))
				{
					return;
				}

				this.getCurrentPopupData()
					.innerContainer
					.querySelector('.' + containersMap.get(user.ROLE))
					.appendChild(
						Tag.render`
							<a class="tasks-projects-members-popup-item" href="${user['HREF']}" target="_blank">
								<span class="tasks-projects-members-popup-avatar-new">
									${this.getAvatar(user)}
									<span class="tasks-projects-members-popup-avatar-status-icon"></span>
								</span>
								<span class="tasks-scrum-members-popup-name">${user['FORMATTED_NAME']}</span>
							</a>
						`
					)
				;
			});
		}
		else
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
						<span class="tasks-scrum-members-popup-name">${user['FORMATTED_NAME']}</span>
					</a>
				`
				);
			})
		}
	}

	renderLabels(users)
	{
		const hasOwner = users.find((user) => user.ROLE === 'A');
		const hasMaster = users.find((user) => user.ROLE === 'M');
		const hasTeam = users.find((user) => user.ROLE === 'E');

		if (hasOwner)
		{
			this.getCurrentPopupData().innerContainer.appendChild(
				Tag.render`
					<div class="tasks-scrum-members-popup-owner-container">
						<span class="tasks-scrum-members-popup-label">
							<span class="tasks-scrum-members-popup-label-text">
								${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_OWNER')}
							</span>
						</span>
					</div>
				`
			);
		}

		if (hasMaster)
		{
			this.getCurrentPopupData().innerContainer.appendChild(
				Tag.render`
					<div class="tasks-scrum-members-popup-master-container">
						<span class="tasks-scrum-members-popup-label">
							<span class="tasks-scrum-members-popup-label-text">
								${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_MASTER')}
							</span>
						</span>
					</div>
				`
			);
		}

		if (hasTeam)
		{
			this.getCurrentPopupData().innerContainer.appendChild(
				Tag.render`
					<div class="tasks-scrum-members-popup-team-container">
						<span class="tasks-scrum-members-popup-label">
							<span class="tasks-scrum-members-popup-label-text">
								${Loc.getMessage('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_TEAM')}
							</span>
						</span>
					</div>
				`
			);
		}
	}
}