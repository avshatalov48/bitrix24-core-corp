import {Dom, Event, Loc, Tag, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Entity} from './entity';
import {Item} from '../item/item';

import 'ui.hint';
import 'main.polyfill.intersectionobserver';

import '../css/action-panel.css';

type Params = {
	entity: Entity,
	item: Item,
	bindElement: HTMLElement,
	itemList: Object
}

export class ActionPanel extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.ActionPanel');

		this.entity = params.entity;
		this.item = params.item;

		this.bindElement = this.item.getNode();

		this.itemList = {...{
			task: {activity: false},
			attachment: {activity: false},
			dod: {activity: false},
			move: {activity: false},
			sprint: {activity: false},
			backlog: {activity: false},
			tags: {activity: false},
			epic: {activity: false},
			decomposition: {activity: false},
			remove: {activity: false},
		}, ...params.itemList};

		this.node = null;

		this.isBlockBlur = false;

		this.hintManager = null;

		this.observeBindElement();
	}

	show()
	{
		this.node = this.calculatePanelPosition(this.createActionPanel());
		this.bindItems();

		this.hintManager = BX.UI.Hint.createInstance({
			popupParameters: {
				closeByEsc: true,
				autoHide: true,
				animation: null
			}
		});

		this.hintManager.init(this.node);

		Dom.append(this.node, document.body);
	}

	destroy()
	{
		Dom.remove(this.node);

		this.node = null;

		if (this.observer)
		{
			this.observer.disconnect();
		}

		this.hideHint();

		this.emit('onDestroy');
	}

	hideHint()
	{
		if (this.hintManager)
		{
			this.hintManager.hide();
		}
	}

	getNode(): HTMLElement
	{
		return this.node;
	}

	getItem(): Item
	{
		return this.item;
	}

	createActionPanel(): HTMLElement
	{
		let task = '';
		let attachment = '';
		let dod = '';
		let move = '';
		let sprint = '';
		let backlog = '';
		let tags = '';
		let epic = '';
		let decomposition = '';
		let remove = '';

		const baseBtnClass = 'tasks-scrum__action-panel--btn';
		const arrowClass = 'tasks-scrum__action-panel--btn-with-arrow';

		const selected = Tag.render`
			<div class="tasks-scrum__action-panel--selected-btn tasks-scrum__action-panel--btn-selected">
				<span class="tasks-scrum__action-panel--text">
					${this.getSelectedText(this.entity.getGroupModeItems().size)}
				</span>
				<span class="tasks-scrum__action-panel--icon"></span>
			</div>
			<div class="tasks-scrum__action-panel--separator"></div>
		`;

		if (this.itemList.task.activity)
		{
			const disableClass = this.itemList.task.disable === true ? '--disabled' : '';

			task = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-task ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--icon"></span>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.attachment.activity)
		{
			const disableClass = this.itemList.attachment.disable === true ? '--disabled' : '';

			attachment = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-attachment ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_FILE_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--icon"></span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.dod.activity)
		{
			const disableClass = this.itemList.dod.disable === true ? '--disabled' : '';

			dod = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-dod ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_DOD_HINT_NEW')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_DOD')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.move.activity)
		{
			const disableClass = this.itemList.move.disable === true ? '--disabled' : '';

			move = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-move ${arrowClass} ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.sprint.activity)
		{
			const disableClass = this.itemList.sprint.disable === true ? '--disabled' : '';
			const sprintArrowClass = this.itemList.sprint.multiple === true ? arrowClass : '';

			sprint = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-sprint ${sprintArrowClass} ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SPRINT_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SPRINT')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.backlog.activity)
		{
			const disableClass = this.itemList.backlog.disable === true ? '--disabled' : '';

			backlog = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-backlog ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_BACKLOG_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_BACKLOG')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.tags.activity)
		{
			const disableClass = this.itemList.tags.disable === true ? '--disabled' : '';

			tags = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-tags ${arrowClass} ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TAG_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TAGS')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.epic.activity)
		{
			const disableClass = this.itemList.epic.disable === true ? '--disabled' : '';

			epic = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-epics ${arrowClass} ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_EPIC_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_EPIC')}
					</span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.decomposition.activity)
		{
			const disableClass = this.itemList.decomposition.disable === true ? '--disabled' : '';
			const decClass = this.entity.isBacklog()
				? 'tasks-scrum__action-panel--btn-decomposition-backlog'
				: 'tasks-scrum__action-panel--btn-decomposition-sprint'
			;
			const hintText = this.entity.isBacklog()
				? Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_DEC_BACKLOG_HINT')
				: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_DEC_SPRINT_HINT')
			;

			decomposition = Tag.render`
				<div
					class="${baseBtnClass} ${decClass} ${disableClass}"
					data-hint="${hintText}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--icon"></span>
				</div>
				<div class="tasks-scrum__action-panel--separator"></div>
			`;
		}

		if (this.itemList.remove.activity)
		{
			const disableClass = this.itemList.remove.disable === true ? '--disabled' : '';

			remove = Tag.render`
				<div
					class="${baseBtnClass} tasks-scrum__action-panel--btn-remove ${disableClass}"
					data-hint="${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_REMOVE_HINT')}" data-hint-no-icon
				>
					<span class="tasks-scrum__action-panel--icon"></span>
				</div>
			`;
		}

		return Tag.render`
			<div class="tasks-scrum__action-panel--container tasks-scrum__action-panel--scope" tabindex="1">
				<div class="tasks-scrum__action-panel">
					${selected}
					${task}
					${attachment}
					${dod}
					${move}
					${sprint}
					${backlog}
					${tags}
					${epic}
					${decomposition}
					${remove}
				</div>
			</div>
		`;
	}

	bindItems()
	{
		const selectedBtn = this.node.querySelector('.tasks-scrum__action-panel--btn-selected')
		Event.bind(
			selectedBtn.querySelector('.tasks-scrum__action-panel--icon'),
			'click',
			() => this.emit('unSelect')
		);

		if (this.itemList.task.activity && this.itemList.task.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-task'),
				'click',
				this.itemList.task.callback
			);
		}

		if (this.itemList.attachment.activity && this.itemList.attachment.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-attachment'),
				'click',
				this.itemList.attachment.callback
			);
		}

		if (this.itemList.dod.activity && this.itemList.dod.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-dod'),
				'click',
				this.itemList.dod.callback
			);
		}

		if (this.itemList.move.activity && this.itemList.move.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-move'),
				'click',
				this.itemList.move.callback
			);
		}

		if (this.itemList.sprint.activity && this.itemList.sprint.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-sprint'),
				'click',
				this.itemList.sprint.callback
			);
		}

		if (this.itemList.backlog.activity && this.itemList.backlog.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-backlog'),
				'click',
				this.itemList.backlog.callback
			);
		}

		if (this.itemList.tags.activity && this.itemList.tags.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-tags'),
				'click',
				this.itemList.tags.callback
			);
		}

		if (this.itemList.epic.activity && this.itemList.epic.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-epics'),
				'click',
				this.itemList.epic.callback
			);
		}

		if (this.itemList.decomposition.activity && this.itemList.decomposition.disable !== true)
		{
			const decClass = this.entity.isBacklog()
				? 'tasks-scrum__action-panel--btn-decomposition-backlog'
				: 'tasks-scrum__action-panel--btn-decomposition-sprint'
			;

			Event.bind(
				this.node.querySelector('.' + decClass),
				'click',
				this.itemList.decomposition.callback
			);
		}

		if (this.itemList.remove.activity && this.itemList.remove.disable !== true)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__action-panel--btn-remove'),
				'click',
				this.itemList.remove.callback
			);
		}
	}

	getSelectedText(number: number): string
	{
		return Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SELECTED') + parseInt(number, 10);
	}

	observeBindElement()
	{
		if (Type.isUndefined(IntersectionObserver))
		{
			return;
		}

		this.observer = new IntersectionObserver((entries) =>
			{
				if (entries[0].isIntersecting === true)
				{
					this.displayPanel();
				}
				else
				{
					this.hidePanel();
				}
			},
			{
				threshold: [0]
			}
		);

		this.observer.observe(this.bindElement);
	}

	displayPanel()
	{
		if (!Dom.isShown(this.getNode()))
		{
			Dom.style(this.getNode(), 'display', 'block');
		}
	}

	hidePanel()
	{
		if (Dom.isShown(this.getNode()))
		{
			Dom.style(this.getNode(), 'display', 'none');
		}
	}

	calculatePanelPosition(panel: HTMLElement)
	{
		const position = Dom.getPosition(this.bindElement);

		const top = `${position.top}px`;
		let left = `${position.left}px`;

		const fakePanel = panel.cloneNode(true);
		Dom.style(fakePanel, 'visibility', 'hidden');
		Dom.style(fakePanel, 'top', `${position.top}px`);
		Dom.style(fakePanel, 'left', `${position.left}px`);

		Dom.append(fakePanel, document.body);
		if (this.isPanelWiderThanViewport(fakePanel))
		{
			const fakePanelRect = fakePanel.getBoundingClientRect();
			const windowWidth = (window.innerWidth || document.documentElement.clientWidth);
			left = `${fakePanelRect.left - (fakePanelRect.right - windowWidth + 40)}px`;
		}
		Dom.remove(fakePanel);

		Dom.style(panel, 'top', top);
		Dom.style(panel, 'left', left);
		Dom.style(panel, 'zIndex', 1100);

		this.removeLastSeparator(panel);

		return panel;
	}

	isPanelWiderThanViewport(element: HTMLElement): boolean
	{
		const rect = element.getBoundingClientRect();
		const windowWidth = (window.innerWidth || document.documentElement.clientWidth);

		return (rect.right > windowWidth);
	}

	calculatePanelTopPosition()
	{
		if (!this.getNode())
		{
			return;
		}

		const position = Dom.getPosition(this.bindElement);

		Dom.style(this.getNode(), 'top', `${position.top}px`);
	}

	removeLastSeparator(panel: HTMLElement)
	{
		const actionPanel =  panel.querySelector('.tasks-scrum__action-panel');
		if (Dom.hasClass(actionPanel.lastElementChild, 'tasks-scrum__action-panel--separator'))
		{
			Dom.remove(actionPanel.lastElementChild);
		}
	}
}