import {Dom, Event, Loc, Tag, Text} from 'main.core';
import {TagSearcher} from './tag.searcher';
import {EventEmitter} from 'main.core.events';

import './css/input.css';

export class Input extends EventEmitter
{
	constructor(options)
	{
		super(options);

		this.setEventNamespace('BX.Tasks.Scrum.Input');

		this.nodeId = Text.getRandom();
		this.placeholder = Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_PLACEHOLDER');

		this.epicId = 0;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div id="${Text.encode(this.nodeId)}" class="tasks-scrum-input">
				<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
					<input type="text" class="ui-ctl-element" placeholder=
						"${Text.encode(this.placeholder)}" autocomplete="off">
				</div>
			</div>
		`;
	}

	onAfterAppend()
	{
		this.setNode();

		Event.bind(this.getInputNode(), 'input', (event) => {
			this.onTagSearch(event);
			this.onEpicSearch(event);
		});
		Event.bind(this.getInputNode(), 'keydown', this.onKeydown.bind(this));
	}

	setNode()
	{
		this.node = document.getElementById(this.nodeId);
	}

	setPlaceholder(placeholder: String)
	{
		this.placeholder = placeholder;
	}

	setEpicId(parentId)
	{
		this.epicId = parseInt(parentId, 10);
	}

	getNode(): HTMLElement
	{
		return this.node;
	}

	getNodeId(): String
	{
		return this.nodeId;
	}

	getInputNode(): HTMLElement
	{
		return this.node.querySelector('input');
	}

	getEpicId()
	{
		return this.epicId;
	}

	removeYourself()
	{
		Dom.remove(this.node);
	}

	setTagsSearchMode(value: Boolean)
	{
		Dom.attr(this.getInputNode(), 'data-tag-disabled', value);
	}

	isTagsSearchMode(): Boolean
	{
		return Dom.attr(this.getInputNode(), 'data-tag-disabled');
	}

	setEpicSearchMode(value: Boolean)
	{
		Dom.attr(this.getInputNode(), 'data-epic-disabled', value);
	}

	isEpicSearchMode(): Boolean
	{
		return Dom.attr(this.getInputNode(), 'data-epic-disabled');
	}

	onTagSearch(event)
	{
		const inputNode = event.target;
		const enteredHashTags = TagSearcher.getHashTagNamesFromText(inputNode.value);
		if (event.data === '#')
		{
			this.setEpicSearchMode(false);
			this.setTagsSearchMode(true);
		}
		if (enteredHashTags.length > 0 && this.isTagsSearchMode())
		{
			const enteredHashTagName = enteredHashTags.pop().trim();
			this.emit('tagsSearchOpen', enteredHashTagName);
		}
		else
		{
			this.emit('tagsSearchClose');
		}
	}

	onEpicSearch(event)
	{
		const inputNode = event.target;
		const enteredHashEpics = TagSearcher.getHashEpicNamesFromText(inputNode.value);
		if (event.data === '@')
		{
			this.setTagsSearchMode(false);
			this.setEpicSearchMode(true);
		}
		if (enteredHashEpics.length > 0 && this.isEpicSearchMode())
		{
			const enteredHashTagName = enteredHashEpics.pop().trim();
			this.emit('epicSearchOpen', enteredHashTagName);
		}
		else
		{
			this.emit('epicSearchClose');
		}
	}

	onCreateTaskItem()
	{
		if (!this.isTagsSearchMode() && !this.isEpicSearchMode())
		{
			const input = this.getInputNode();
			if (input.value)
			{
				this.emit('createTaskItem', input.value);
				input.value = '';
				input.focus();
			}
		}
	}

	onKeydown(event)
	{
		if (event.isComposing || event.keyCode === 13)
		{
			this.onCreateTaskItem();
		}
	}
}