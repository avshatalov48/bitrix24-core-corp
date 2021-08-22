import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Menu} from 'main.popup';

export class ActiveSprintActionButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.ActiveSprintButton');
	}

	render(): HTMLElement
	{
		const node = Tag.render`
			<div class="ui-btn-split ui-btn-primary ui-btn-xs"> 
				<button class="ui-btn-main">
					${Loc.getMessage('TASKS_SCRUM_ACTIONS_COMPLETE_SPRINT')}
				</button> 
				<button class="ui-btn-menu"></button> 
			</div>
		`;

		const completeSprintButtonNode = node.querySelector('.ui-btn-main');
		const menuButtonNode = node.querySelector('.ui-btn-menu');

		Event.bind(completeSprintButtonNode, 'click', this.onCompleteSprintClick.bind(this));
		Event.bind(menuButtonNode, 'click', this.onMenuClick.bind(this, completeSprintButtonNode));

		return node;
	}

	onCompleteSprintClick()
	{
		this.emit('completeSprint');
	}

	onMenuClick(bindElement: HTMLElement)
	{
		const menu = new Menu({
			id: `active-sprint-actions-menu-${Text.getRandom()}`,
			bindElement: bindElement
		});

		menu.addMenuItem({
			text: Loc.getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON'),
			onclick: (event, menuItem) => {
				menuItem.getMenuWindow().close();
				this.emit('showBurnDownChart');
			}
		});

		menu.show();
	}
}