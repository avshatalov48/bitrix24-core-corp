import {Event, Tag, Text, Dom, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {StoryPointsStorage} from '../../utility/story.points.storage';

export class StoryPoints  extends EventEmitter
{
	constructor(storyPoints: string)
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.Item.StoryPoints');

		this.storyPointsStorage = new StoryPointsStorage();

		this.storyPointsStorage.setPoints(storyPoints);

		this.disableStatus = false;
	}

	render(): HTMLElement
	{
		const value = Text.encode(this.storyPointsStorage.getPoints());

		this.node = Tag.render`
			<div
				class="tasks-scrum__item--story-points ${this.storyPointsStorage.isEmpty() ? '--empty' : ''}"
				title="${Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS')}"
			>
				<div class="tasks-scrum__item--story-points-content">
					<div 
						class="tasks-scrum__item--story-points-element" 
						title="${this.storyPointsStorage.isEmpty() ? '' : value}"
					>
						<span class="tasks-scrum__item--story-points-element-text">
							${this.storyPointsStorage.isEmpty() ? '-' : value}
						</span>
					</div>
					<div class="tasks-scrum__item--story-points-input-container">
						<input
							type="text"
							class="tasks-scrum__item--story-points-input"
							value="${value}"
						>
					</div>
				</div>
			</div>
		`;

		Event.bind(
			this.node.querySelector('.tasks-scrum__item--story-points-element'),
			'click',
			this.onClick.bind(this)
		);

		const input = this.node.querySelector('.tasks-scrum__item--story-points-input');

		Event.bind(input, 'blur', this.onBlur.bind(this));
		Event.bind(input, 'keydown', this.onKeyDown.bind(input));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getValue(): StoryPointsStorage
	{
		return this.storyPointsStorage;
	}

	isDisable(): boolean
	{
		return this.disableStatus;
	}

	disable()
	{
		this.disableStatus = true;
	}

	unDisable()
	{
		this.disableStatus = false;
	}

	onClick()
	{
		if (this.isDisable())
		{
			return;
		}

		const inputContainer = this.node.querySelector('.tasks-scrum__item--story-points-input-container');
		const input = inputContainer.firstElementChild;
		const value = this.storyPointsStorage.getPoints();

		Dom.addClass(inputContainer, '--active');

		input.focus();
		input.setSelectionRange(value.length, value.length);
	}

	onBlur()
	{
		const inputContainer = this.node.querySelector('.tasks-scrum__item--story-points-input-container');
		const input = inputContainer.firstElementChild;

		const value = input.value.trim();

		const currentValue = this.storyPointsStorage.getPoints();

		if (currentValue !== value)
		{
			this.emit('setStoryPoints', value);
		}

		Dom.removeClass(inputContainer, '--active');
	}

	onKeyDown(event)
	{
		if (event.isComposing || event.key === 'Escape' || event.key === 'Enter')
		{
			this.blur();
		}
	}
}
