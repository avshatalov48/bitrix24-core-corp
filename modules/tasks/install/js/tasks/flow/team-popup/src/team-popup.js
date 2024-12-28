import { Dom, Event, Loc, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Popup, PopupManager } from 'main.popup';
import { SidePanelIntegration } from 'tasks.side-panel-integration';

import './style.css';
import { TeamAjax } from './team-ajax';
import type { MemberData } from './team-member';
import { TeamMember } from './team-member';

type Params = {
	flowId: number,
	bindElement: HTMLElement,
	excludeMembers: ?number[],
};

export class TeamPopup
{
	static instances = {};

	#params: Params;
	#layout: {
		wrap: HTMLElement,
		members: HTMLElement,
		loader: Loader,
	};

	#teamAjax: TeamAjax;
	#members: MemberData[] = [];

	constructor(params: Params)
	{
		this.#params = params;
		this.#layout = {};

		this.#teamAjax = new TeamAjax(this.#params.flowId);

		void this.#load();
		this.#subscribeEvents();
	}

	static showInstance(params: Params): void
	{
		this.getInstance(params).show(params.bindElement);
	}

	static getInstance(params: Params): this
	{
		this.instances[params.flowId] ??= new this(params);

		return this.instances[params.flowId];
	}

	static removeInstance(flowId: number): void
	{
		if (Object.hasOwn(this.instances, flowId))
		{
			delete this.instances[flowId];
		}
	}

	#subscribeEvents(): void
	{
		EventEmitter.subscribe('BX.Tasks.Flow.EditForm:afterSave', (event) => {
			const flowId = event.data?.id ?? 0;

			TeamPopup.removeInstance(flowId);
		});
	}

	async #load(): Promise
	{
		if (this.#layout.loader)
		{
			return;
		}

		this.#showLoader();

		let { members, page } = await this.#teamAjax.get();

		if (!Type.isNil(this.#params.excludeMembers))
		{
			const isNeedToExclude = (member: MemberData) => this.#params.excludeMembers.includes(Number(member.id));

			page = page.filter((member: MemberData) => !isNeedToExclude(member));
			members = members.filter((member: MemberData) => !isNeedToExclude(member));
		}

		this.#members = members;
		page.forEach((data: MemberData) => Dom.append(new TeamMember(data).render(), this.#layout.members));

		this.#destroyLoader();
	}

	show(bindElement: HTMLElement): void
	{
		const popup = this.getPopup();

		popup.setContent(this.#render());
		popup.setBindElement(bindElement);

		popup.show();
	}

	getPopup(): Popup
	{
		const id = `tasks-flow-team-popup-${this.#params.flowId}`;

		if (PopupManager.getPopupById(id))
		{
			return PopupManager.getPopupById(id);
		}

		const popup = new Popup({
			id,
			className: 'tasks-flow__team-popup',
			width: 200,
			padding: 2,
			autoHide: true,
			closeByEsc: true,
			events: {
				onShow: () => {
					if (this.#layout.loader)
					{
						this.#showLoader();
					}
				},
			},
		});

		new SidePanelIntegration(popup);

		return popup;
	}

	#render(): HTMLElement
	{
		this.#layout.wrap = Tag.render`
			<div class="tasks-flow__team-popup_container">
				<div class="tasks-flow__team-popup_content">
					<div class="tasks-flow__team-popup_content-box">
						<span class="tasks-flow__team-popup_label">
							<span class="tasks-flow__team-popup_label-text">
								${Loc.getMessage('TASKS_FLOW_TEAM_POPUP_LABEL')}
							</span>
						</span>
						${this.#renderMembers()}
					</div>
				</div>
			</div>
		`;

		return this.#layout.wrap;
	}

	#renderMembers(): HTMLElement
	{
		this.#layout.members = Tag.render`
			<div class="tasks-flow__team-popup_members">
				${this.#members.map((data: MemberData) => new TeamMember(data).render())}
			</div>
		`;

		Event.bind(this.#layout.members, 'scroll', () => {
			const scrollTop = this.#layout.members.scrollTop;
			const maxScroll = this.#layout.members.scrollHeight - this.#layout.members.offsetHeight;

			if (Math.abs(scrollTop - maxScroll) < 1)
			{
				void this.#load();
			}
		});

		return this.#layout.members;
	}

	#showLoader(): void
	{
		this.#destroyLoader();

		const targetPosition = Dom.getPosition(this.#layout.members);
		const size = 40;

		this.#layout.loader = new Loader({
			target: this.#layout.members,
			size,
			mode: 'inline',
			offset: {
				left: `${(targetPosition.width / 2) - (size / 2)}px`,
			},
		});

		void this.#layout.loader.show();
	}

	#destroyLoader(): void
	{
		this.#layout.loader?.destroy();
		this.#layout.loader = null;
	}
}
