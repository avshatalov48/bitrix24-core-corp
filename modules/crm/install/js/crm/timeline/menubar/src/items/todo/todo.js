import type { ColorSettings } from 'crm.activity.todo-editor-v2';
import { TodoEditorV2 } from 'crm.activity.todo-editor-v2';
import { TourManager } from 'crm.tour-manager';
import { Dom, Loc, Tag, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import Context from '../../context';
import Item from '../../item';
import Tour from './tour';

type Settings = {
	calendarSettings: CalendarSettings,
	copilotSettings: Object,
	colorSettings: ColorSettings,
	currentUser: Object,
	pingSettings: Object,
};

type CalendarSettings = {
	ownerId: string;
	timezoneName: string;
};

const ARTICLE_CODE = '21064046';

/** @memberof BX.Crm.Timeline.MenuBar */
export default class ToDo extends Item
{
	#toDoEditor: TodoEditorV2 = null;
	#todoEditorContainer: HTMLElement = null;
	#saveButton: HTMLElement = null;
	#isTourViewed: boolean = false;

	initialize(context: Context, settings: Settings): void
	{
		super.initialize(context, settings);
	}

	createLayout(): HTMLElement
	{
		this.#todoEditorContainer = Tag.render`<div></div>`;

		this.#saveButton = Tag.render`<button onclick="${this.onSaveButtonClick.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-disabled" >${Loc.getMessage('CRM_TIMELINE_SAVE_BUTTON')}</button>`;

		return Tag.render`
			<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-todo --hidden">
				${this.#todoEditorContainer}
				<div class="crm-entity-stream-content-new-comment-btn-container">
					${this.#saveButton}
					<span onclick="${this.onCancelButtonClick.bind(this)}"  class="ui-btn ui-btn-xs ui-btn-link">${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}</span>
				</div>
			</div>
		`;
	}

	initializeLayout(): void
	{
		Dom.removeClass(this.#saveButton, 'ui-btn-disabled');

		this.#createEditor();
	}

	initializeSettings(): void
	{
		this.#isTourViewed = this.getSetting('isTourViewed');
	}

	onSaveButtonClick()
	{
		if (
			this.isLocked()
			|| Dom.hasClass(this.#saveButton, 'ui-btn-disabled')
		)
		{
			return;
		}
		this.setLocked(true);

		this.save().then(
			() => this.setLocked(false),
			() => this.setLocked(false),
		).catch(() => this.setLocked(false));
	}

	onCancelButtonClick()
	{
		this.cancel();
		this.emitFinishEditEvent();
	}

	#createEditor(): void
	{
		const params = {
			container: this.#todoEditorContainer,
			defaultDescription: '',
			ownerTypeId: this.getEntityTypeId(),
			ownerId: this.getEntityId(),
			currentUser: this.getSetting('currentUser'),
			pingSettings: this.getSetting('pingSettings'),
			copilotSettings: this.getSetting('copilotSettings'),
			colorSettings: this.getSetting('colorSettings'),
			actionMenuSettings: this.getSetting('actionMenuSettings'),
			events: {
				onCollapsingToggle: (event: BaseEvent) => {
					const { isOpen } = event.getData();
					this.setFocused(isOpen);
				},
			},
		};

		params.calendarSettings = this.getSetting('calendarSettings');

		const extras = this.getExtras();
		if (Type.isPlainObject(extras.analytics))
		{
			params.analytics = {
				section: extras.analytics.c_section,
				subSection: extras.analytics.c_sub_section,
			};
		}

		this.#toDoEditor = new TodoEditorV2(params);

		this.#toDoEditor.show();
	}

	save(): Promise
	{
		if (Dom.hasClass(this.#saveButton, 'ui-btn-disabled'))
		{
			return false;
		}

		return this.#toDoEditor.save().then((response) => {
			if (Type.isArray(response.errors) && response.errors.length > 0)
			{
				return false;
			}
			this.cancel(false);
			this.emitFinishEditEvent();

			return true;
		});
	}

	cancel(sendAnalytics: boolean = true): void
	{
		this.#toDoEditor.cancel({ sendAnalytics });

		this.setFocused(false);
	}

	bindInputHandlers(): void
	{
		// do nothing
	}

	setParentActivityId(activityId: Number): void
	{
		this.#toDoEditor.setParentActivityId(activityId);
	}

	setDeadLine(deadLine: String): void
	{
		this.#toDoEditor.setDeadline(deadLine);
	}

	focus(): void
	{
		this.#toDoEditor.setFocused();
	}

	setVisible(visible: Boolean): void
	{
		super.setVisible(visible);

		if (visible)
		{
			this.showTour();
		}
	}

	showTour(): void
	{
		if (!this.isVisible())
		{
			return;
		}

		const guideBindElementClass = '.crm-activity__todo-show-actions-popup-button';
		const guideBindElement = document.querySelector(guideBindElementClass);

		if (guideBindElement && !this.#isTourViewed && !BX.Crm.EntityEditor.getDefault().isNew())
		{
			const tour = new Tour({
				itemCode: 'todo',
				title: Loc.getMessage('CRM_TIMELINE_TODO_GUIDE_TITLE'),
				text: Loc.getMessage('CRM_TIMELINE_TODO_GUIDE_TEXT'),
				articleCode: ARTICLE_CODE,
				userOptionName: 'isTimelineTourViewedInWeb',
				guideBindElement,
			});

			setTimeout(() => {
				TourManager.getInstance().registerWithLaunch(tour);
			});
		}
	}
}
