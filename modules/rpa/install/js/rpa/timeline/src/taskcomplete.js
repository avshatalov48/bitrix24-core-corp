import {Tag, Text, Type} from 'main.core';
import {Timeline} from 'ui.timeline';

export class TaskComplete extends Timeline.History
{
	renderContainer(): Element
	{
		const container = super.renderContainer();
		container.classList.add('ui-item-detail-stream-section-history');

		return container;
	}

	renderTaskInfo(): Element
	{
		let taskName = this.renderTaskName();
		if(!taskName)
		{
			taskName = '';
		}

		let taskResponsible = this.renderTaskResponsible();
		if(!taskResponsible)
		{
			taskResponsible = '';
		}

		return Tag.render`<div class="ui-item-detail-stream-content-detail-subject">
			${this.renderHeaderUser(this.getUserId(), 30)}
			<div class="ui-item-detail-stream-content-detail-subject-inner">
				${taskName}
				${taskResponsible}
			</div>
		</div>`;
	}

	renderTaskName(): ?Element
	{
		const task = this.getTask();
		if(task)
		{
			return Tag.render`<a class="ui-item-detail-stream-content-detail-subject-text">${Text.encode(task.NAME)}</a>`;
		}

		return null;
	}

	renderTaskResponsible(): ?Element
	{
		let user = this.users.get(Text.toInteger(this.getUserId()));
		if(user)
		{
			return Tag.render`<span class="ui-item-detail-stream-content-detail-subject-resp">${Text.encode(user.fullName)}</span>`;
		}

		return null;
	}

	renderMain(): Element
	{
		let taskInfo = this.renderTaskInfo();
		let detailMain = this.renderDetailMain();
		if(!detailMain)
		{
			taskInfo.classList.add('rpa-item-detail-stream-content-detail-no-main');
		}

		return Tag.render`<div class="ui-item-detail-stream-content-detail">
			${taskInfo}
			${detailMain || ''}
		</div>`;
	}

	getTask(): ?{NAME: ?string, DESCRIPTION: ?string, USERS: ?Array}
	{
		if(Type.isPlainObject(this.data.task))
		{
			return this.data.task;
		}

		return null;
	}

	renderDetailMain(): Element
	{
		const task = this.getTask();
		let taskDescription = '';
		if(task && task.DESCRIPTION)
		{
			taskDescription = Tag.render`<span class="ui-item-detail-stream-content-detail-main-text">${Text.encode(task.DESCRIPTION)}</span>`;
		}

		let stageChange = this.renderStageChange();
		let fieldsChange = this.renderFieldsChange();
		if(taskDescription || stageChange || fieldsChange)
		{
			return Tag.render`<div class="ui-item-detail-stream-content-detail-main">
				${taskDescription}
				${(fieldsChange ? [this.renderFieldsChangeTitle(), fieldsChange] : '')}
				${(stageChange ? [this.renderStageChangeTitle(), stageChange] : '')}
			</div>`;
		}

		return null;
	}
}