import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Popup, PopupManager } from 'main.popup';
import { TeamPopup } from 'tasks.flow.team-popup';
import { SidePanelIntegration } from 'tasks.side-panel-integration';
import { Label, LabelColor, LabelSize } from 'ui.label';
import { SegmentButton } from './layout/segment-button';
import { ViewAjax } from './view-ajax';
import { SimilarFlows } from './similar-flows';
import type { Entity, FlowFormData } from './view-ajax';
import 'ui.notification';

import './style.css';

type Params = {
	flowId: number,
	bindElement: HTMLElement,
	isFeatureEnabled: 'Y' | 'N',
	flowUrl: string,
};

export class ViewForm
{
	static instances = {};

	#params: Params;
	#layout: {
		popup: Popup,
		teamPopup: Popup,
		details: HTMLElement,
		teamNode: HTMLElement,
		showTeamButton: HTMLElement,
		similarFlows: SimilarFlows,
	};

	#notificationList: Set = new Set();

	#viewAjax: ViewAjax;

	#selectedSegment: string;

	#viewFormData: FlowFormData;

	constructor(params: Params)
	{
		this.#params = params;
		this.#layout = {};

		this.#viewAjax = new ViewAjax(this.#params.flowId);

		this.isFeatureEnabled = params.isFeatureEnabled === 'Y';
		this.flowUrl = params.flowUrl;

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

			ViewForm.removeInstance(flowId);
		});
	}

	async #load(): Promise
	{
		this.#viewFormData = await this.#viewAjax.getViewFormData();

		this.#layout.popup.setContent(this.#render());
	}

	show(bindElement: HTMLElement): void
	{
		this.#layout.popup = this.getPopup();

		this.#layout.popup.setContent(this.#render());
		this.#layout.popup.setBindElement(bindElement);

		this.#layout.popup.show();
	}

	getPopup(): Popup
	{
		const id = `tasks-flow-view-popup-${this.#params.flowId}`;

		if (PopupManager.getPopupById(id))
		{
			return PopupManager.getPopupById(id);
		}

		const popup = new Popup({
			id,
			className: 'tasks-flow__view-popup',
			animation: 'fading-slide',
			minWidth: 347,
			maxWidth: 347,
			padding: 0,
			borderRadius: 12,
			autoHide: true,
			overlay: true,
			closeByEsc: true,
			autoHideHandler: ({ target }) => {
				const isSelf = popup.getPopupContainer().contains(target);
				const isTeam = this.#layout.teamPopup?.getPopup().getPopupContainer().contains(target);

				return !isSelf && !isTeam;
			},
		});

		new SidePanelIntegration(popup);

		return popup;
	}

	#render(): HTMLElement
	{
		if (!this.#viewFormData)
		{
			return this.#renderLoader();
		}

		return Tag.render`
			<div class="tasks-flow__view-form">
				${this.#renderHeader()}
				${this.#renderContent()}
			</div>
		`;
	}

	#renderLoader(): HTMLElement
	{
		const loaderContainer = Tag.render`
			<div class="tasks-flow__view-form-loader" style="width: 347px; height: 300px;">
			</div>
		`;

		void new Loader({ target: loaderContainer }).show();

		return loaderContainer;
	}

	#renderHeader(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__view-form_header">
				<div class="tasks-flow__view-form_header-title">
					${this.#renderTitle()}
					<div class="tasks-flow__view-form-header_title-efficiency">
						${this.#renderEfficiencyLabel(this.#viewFormData.flow.efficiency)}
					</div>
				</div>
				<div class="tasks-flow__view-form-header_description">
					${this.#renderDescription(this.#viewFormData.flow.description)}
				</div>
			</div>
		`;
	}

	#renderDescription(description: string): HTMLElement
	{
		const descriptionNode = Tag.render`
			<div 
				class="tasks-flow__view-form_header-description" 
				title="${Text.encode(description)}"
			></div>
		`;

		descriptionNode.innerText = description;

		return descriptionNode;
	}

	#renderEfficiencyLabel(efficiency: number): HTMLElement
	{
		return new Label({
			text: `${efficiency}%`,
			color: efficiency < 60 ? LabelColor.DANGER : LabelColor.SUCCESS,
			size: LabelSize.SM,
			fill: true,
		}).render();
	}

	#renderTitle(): HTMLElement
	{
		const title = Tag.render`
			<div class="tasks-flow__view-form-header_title-link">
				<div
					class="tasks-flow__view-form-header_title-text"
					title="${Text.encode(this.#viewFormData.flow.name)}"
				>
					${Text.encode(this.#viewFormData.flow.name)}
				</div>
				<div 
					class="tasks-flow__view-form-header_title-link-icon ui-icon-set --link-3"
					style="--ui-icon-set__icon-size: 16px;"
					title="${Loc.getMessage('TASKS_FLOW_VIEW_FORM_LINK_TITLE')}"
				></div>
			</div>
		`;

		Event.bind(title, 'click', () => {
			const notificationId = 'copy-link';

			if (!this.#notificationList.has(notificationId))
			{
				const flowURL = window.location.protocol + this.flowUrl;
				BX.clipboard.copy(flowURL);

				BX.UI.Notification.Center.notify({
					id: notificationId,
					content: Loc.getMessage('TASKS_FLOW_VIEW_FORM_TITLE_COPY_LINK'),
				});

				this.#notificationList.add(notificationId);

				EventEmitter.subscribeOnce(
					'UI.Notification.Balloon:onClose',
					(baseEvent: BaseEvent) => {
						const closingBalloon = baseEvent.getTarget();
						if (closingBalloon.getId() === notificationId)
						{
							this.#notificationList.delete(notificationId);
						}
					},
				);
			}
		});

		return title;
	}

	#renderContent(): HTMLElement
	{
		const content = Tag.render`
			<div class="tasks-flow__view-form-content">
				${this.#renderSegmentButton()}
				${this.#renderDetails()}
				${this.#renderSimilarFlows()}
			</div>
		`;

		this.#updateSegmentsVisibility();

		return content;
	}

	#renderSegmentButton(): HTMLElement
	{
		this.#selectedSegment = 'details';

		return new SegmentButton({
			segments: [
				{
					id: 'details',
					title: Loc.getMessage('TASKS_FLOW_VIEW_FORM_DETAILS'),
					isActive: true,
				},
				{
					id: 'similarFlows',
					title: Loc.getMessage('TASKS_FLOW_VIEW_FORM_SIMILAR_FLOWS'),
				},
			],
			onSegmentSelected: (segment) => {
				if (this.#selectedSegment !== segment.id)
				{
					this.#selectedSegment = segment.id;
					this.#updateSegmentsVisibility();
				}
			},
		}).render();
	}

	#updateSegmentsVisibility(): void
	{
		Dom.style(this.#layout.details, 'display', 'none');
		this.#layout.similarFlows.hide();

		if (this.#selectedSegment === 'details')
		{
			Dom.style(this.#layout.details, 'display', '');
		}

		if (this.#selectedSegment === 'similarFlows')
		{
			this.#layout.similarFlows.show();
		}
	}

	#renderDetails(): HTMLElement
	{
		this.#layout.details = Tag.render`
			<div class="tasks-flow__view-form-details">
				${this.#renderField(Loc.getMessage('TASKS_FLOW_VIEW_FORM_CREATOR'), this.#renderEntity(this.#viewFormData.creator))}
				${this.#renderField(Loc.getMessage('TASKS_FLOW_VIEW_FORM_ADMINISTRATOR'), this.#renderEntity(this.#viewFormData.owner))}
				${this.#renderField(Loc.getMessage('TASKS_FLOW_VIEW_FORM_TEAM'), this.#renderTeam())}
				${this.#renderProjectField()}
			</div>
		`;

		return this.#layout.details;
	}

	#renderProjectField(): HTMLElement | string
	{
		if (!this.#viewFormData.project)
		{
			const content = (
				this.#viewFormData.flow.demo === true
					? Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_DEMO')
					: Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_HIDDEN')
			);

			return this.#renderField(
				Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT'),
				content,
			);
		}

		return this.#renderField(
			Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT'),
			this.#renderEntity(this.#viewFormData.project),
		);
	}

	#renderField(title, content): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__view-form_field-name">
				${title}
			</div>
			<div class="tasks-flow__view-form_field-value">
				${content}
			</div>
		`;
	}

	#renderEntity(entity: Entity): HTMLElement
	{
		return Tag.render`
			<a class="tasks-flow__view-form_entity" href="${encodeURI(entity.url)}">
				${this.#renderAvatar(entity)}
				<div class="tasks-flow__view-form_entity-name" title="${Text.encode(entity.name)}">
					${Text.encode(entity.name)}
				</div>
			</a>
		`;
	}

	#renderTeam(): ?HTMLElement
	{
		if (this.#viewFormData.flow.demo === true)
		{
			return Loc.getMessage('TASKS_FLOW_VIEW_FORM_PROJECT_DEMO');
		}

		if (this.#viewFormData.team.length === 1)
		{
			return this.#renderEntity(this.#viewFormData.team[0]);
		}

		this.#layout.teamNode = Tag.render`
			<div class="tasks-flow__view-form_line-avatars">
				${this.#viewFormData.team.map((entity) => this.#renderAvatar(entity))}
				${this.#renderShowTeamButton()}
			</div>
		`;

		if (this.#viewFormData.teamCount === this.#viewFormData.team.length)
		{
			Event.bind(this.#layout.teamNode, 'click', this.#onShowTeamButtonClickHandler.bind(this));
		}

		return this.#layout.teamNode;
	}

	#renderAvatar(entity: Entity): HTMLElement
	{
		const style = this.#isAvatar(entity.avatar) ? `background-image: url('${encodeURI(entity.avatar)}');` : '';

		return Tag.render`
			<span class="ui-icon ui-icon-common-user tasks-flow__view-form_avatar" title="${Text.encode(entity.name)}">
				<i style="${style}"></i>
			</span>
		`;
	}

	#isAvatar(avatar: string): boolean
	{
		return Type.isStringFilled(avatar);
	}

	#renderShowTeamButton(): HTMLElement | string
	{
		if (this.#viewFormData.teamCount === this.#viewFormData.team.length)
		{
			this.#layout.showTeamButton?.remove();
			this.#layout.showTeamButton = null;

			return '';
		}

		this.#layout.showTeamButton = Tag.render`
			<div class="tasks-flow__view-form_show-team-button">
				${this.#getShowTeamButtonText()}
			</div>
		`;

		Event.bind(this.#layout.showTeamButton, 'click', this.#onShowTeamButtonClickHandler.bind(this));

		return this.#layout.showTeamButton;
	}

	#getShowTeamButtonText(): string
	{
		return Loc.getMessage('TASKS_FLOW_VIEW_FORM_ALL_N', {
			'#NUM#': this.#viewFormData.teamCount,
		});
	}

	#onShowTeamButtonClickHandler(): void
	{
		const flowId = this.#params.flowId;
		const bindElement = this.#layout.showTeamButton ?? this.#layout.teamNode;
		this.#layout.teamPopup ??= TeamPopup.getInstance({ flowId });
		this.#layout.teamPopup.show(bindElement);
	}

	#renderSimilarFlows(): HTMLElement
	{
		this.#layout.similarFlows ??= new SimilarFlows({
			flowId: this.#params.flowId,
			isFeatureEnabled: this.isFeatureEnabled,
			createTaskButtonClickHandler: () => this.#layout.popup?.destroy(),
		});

		return this.#layout.similarFlows.render();
	}
}
