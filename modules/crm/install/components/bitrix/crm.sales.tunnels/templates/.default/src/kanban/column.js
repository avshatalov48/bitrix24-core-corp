import {Dom, Type, Tag, Event, Loc} from 'main.core';
import {Kanban} from 'main.kanban';
import createStub from './internal/create-stub';
import Marker from '../marker/marker';
import isLinkInSameCategory from '../internal/is-link-in-same-category';
import isCycleLink from '../internal/is-cycle-link';

if (BX.Kanban.Pagination)
{
	BX.Kanban.Pagination.prototype.adjust = () => {};
}

export default class Column extends Kanban.Column
{
	constructor(options)
	{
		super(options);

		this.currentName = this.getName();

		this.marker = new Marker({
			dispatcher: this.getDot(),
			receiver: this.getHeader(),
			point: this.getDot(),
			container: this.getData().appContainer,
			intermediateXPoints: () => this.getIntermediateXPoints(),
			name: `${this.getData().categoryName} (${this.getName()})`,
			data: {
				column: this,
			},
		});

		this.marker
			.subscribe('Marker:dragStart', this.onMarkerDragStart.bind(this))
			.subscribe('Marker:receiver:dragOver', this.onMarkerDragOver.bind(this))
			.subscribe('Marker:receiver:dragOut', this.onMarkerDragOut.bind(this))
			.subscribe('Marker:dragEnd', this.onMarkerDragEnd.bind(this))
			.subscribe('Marker:linkFrom', this.onMarkerLinkFrom.bind(this))
			.subscribe('Marker:stubLinkFrom', this.onMarkerStubLinkFrom.bind(this))
			.subscribe('Marker:linkTo', this.onMarkerLinkTo.bind(this))
			.subscribe('Marker:stubLinkTo', this.onMarkerStubLinkTo.bind(this))
			.subscribe('Marker:removeLinkFrom', this.onRemoveLinkFrom.bind(this))
			.subscribe('Marker:changeRobotAction', this.onChangeRobotAction.bind(this))
			.subscribe('Marker:editLink', this.onEditLink.bind(this))
			.subscribe('Marker:unlink', this.onMarkerUnlink.bind(this))
			.subscribe('Marker:unlinkStub', this.onMarkerUnlinkStub.bind(this))
			.subscribe('Marker:error', this.onMarkerError.bind(this));


		this.onTransitionStart = this.onTransitionStart.bind(this);
		this.onTransitionEnd = this.onTransitionEnd.bind(this);
	}

	isAllowedTransitionProperty(propertyName)
	{
		return [
			'width',
			'min-width',
			'max-width',
			'transform',
		].includes(propertyName);
	}

	onTransitionStart(event)
	{
		if (
			event.srcElement === this.getContainer()
			&& this.isAllowedTransitionProperty(event.propertyName)
		)
		{
			clearInterval(this.intervalId);
			this.intervalId = setInterval(Marker.adjustLinks, 16);
		}
	}

	onTransitionEnd(event)
	{
		if (
			event.srcElement === this.getContainer()
			&& this.isAllowedTransitionProperty(event.propertyName)
		)
		{
			clearInterval(this.intervalId);
			this.intervalId = null;
		}
	}

	setOptions(options: {name?: string; color?: string; data?: {[p: string]: any}; total?: number})
	{
		super.setOptions(options);

		if (Type.isFunction(options.data.onLink))
		{
			this.onLinkHandler = options.data.onLink;
		}

		if (Type.isFunction(options.data.onRemoveLinkFrom))
		{
			this.onRemoveLinkFromHandler = options.data.onRemoveLinkFrom;
		}

		if (Type.isFunction(options.data.onChangeRobotAction))
		{
			this.onChangeRobotAction = options.data.onChangeRobotAction;
		}

		if (Type.isFunction(options.data.onEditLink))
		{
			this.onEditLinkhandler = options.data.onEditLink;
		}

		if (Type.isFunction(options.data.onNameChange))
		{
			this.onNameChangeHandler = options.data.onNameChange;
		}

		if (Type.isFunction(options.data.onColorChange))
		{
			this.onColorChangeHandler = options.data.onColorChange;
		}

		if (Type.isFunction(options.data.onAddColumn))
		{
			this.onAddColumnHandler = options.data.onAddColumn;
		}

		if (Type.isFunction(options.data.onRemove))
		{
			this.onRemoveHandler = options.data.onRemove;
		}

		if (Type.isFunction(options.data.onChange))
		{
			this.onChangeHandler = options.data.onChange;
		}

		if (Type.isFunction(options.data.onError))
		{
			this.onErrorHandler = options.data.onError;
		}

		if (this.marker)
		{
			this.marker.container = this.getData().appContainer;
			if (Type.isFunction(this.marker.cache.clear))
			{
				this.marker.cache.clear();
			}
		}
	}

	onMarkerError(event)
	{
		this.onErrorHandler(event.data);
	}

	onEditLink(event)
	{
		this.onEditLinkhandler(event.data);
	}

	onMarkerLinkFrom(event)
	{
		this.onLinkHandler(event.data);
		this.activateDot();
	}

	onMarkerStubLinkFrom()
	{
		this.activateStubDot();
	}

	onMarkerStubLinkTo()
	{
		this.activateStubDot();
	}

	onRemoveLinkFrom(event)
	{
		this.onRemoveLinkFromHandler(event.data);
	}

	onMarkerLinkTo()
	{
		this.activateDot();
	}

	getIntermediateXPoints()
	{
		const {
			progressStagesGroup,
			successStagesGroup,
			failStagesGroup,
		} = this.getData().stagesGroups;

		const progressRect = progressStagesGroup.getBoundingClientRect();
		const successRect = successStagesGroup.getBoundingClientRect();
		const failRect = failStagesGroup.getBoundingClientRect();

		const offset = 15;

		return [
			progressRect.left + offset,
			successRect.left - offset,
			successRect.left + offset,
			successRect.right - offset,
			successRect.right + offset,
			failRect.right - (offset / 2),
		];
	}

	onMarkerDragStart()
	{
		this.activateDot();
	}

	onMarkerDragOver(event: Event.BaseEvent)
	{
		if (isLinkInSameCategory(event) || isCycleLink(event))
		{
			event.preventDefault();
			this.disallowDot();
			return;
		}

		this.allowDot();
		this.highlightDot();
	}

	onMarkerDragOut()
	{
		this.allowDot();
		this.unhighlightDot();
	}

	onMarkerDragEnd(event: Event.BaseEvent)
	{
		if (!this.marker.isLinked())
		{
			this.deactivateDot();
		}

		if (event.data.from && event.data.to)
		{
			if (isLinkInSameCategory(event) || isCycleLink(event))
			{
				event.preventDefault();
			}
		}
	}

	onMarkerUnlink()
	{
		this.deactivateDot();
		this.deactivateStubDot();
	}

	onMarkerUnlinkStub()
	{
		this.deactivateStubDot();
	}

	activateDot()
	{
		Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-active');
	}

	deactivateDot()
	{
		Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-active');
	}

	activateStubDot()
	{
		Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-active-stub');
	}

	deactivateStubDot()
	{
		Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-active-stub');
	}

	highlightDot()
	{
		Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-highlight');
	}

	unhighlightDot()
	{
		Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-highlight');
	}

	allowDot()
	{
		Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-disallow');
	}

	disallowDot()
	{
		Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-disallow');
	}

	getBody()
	{
		return createStub();
	}

	getDot()
	{
		if (!Type.isDomNode(this.dot))
		{
			const title = Loc.getMessage('CRM_ST_DOT_TITLE');
			this.dot = Tag.render`<div class="crm-st-kanban-column-dot" title="${title}">
				<span class="crm-st-kanban-column-dot-disallow-icon"> </span>
				<span class="crm-st-kanban-column-dot-pulse"> </span>
			</div>`;
		}

		return this.dot;
	}

	getHeader()
	{
		const header = super.getHeader();

		if (!this.headerDotted)
		{
			this.headerDotted = true;

			const dot = this.getDot();

			Event.bind(dot, 'mousedown', (event) => event.stopPropagation());
			Event.bind(dot, 'mouseup', (event) => event.stopPropagation());
			Event.bind(dot, 'mousemove', (event) => event.stopPropagation());

			Dom.append(dot, header);
		}

		Tag.attrs(header)`
			title: ${this.getName()};
		`;

		return header;
	}

	getSubTitle()
	{
		return createStub();
	}

	getContainer()
	{
		const container = super.getContainer();
		Dom.addClass(container, 'crm-st-kanban-column');

		Event.bind(container, 'transitionstart', this.onTransitionStart);
		Event.bind(container, 'transitionend', this.onTransitionEnd);
		return container;
	}

	getTotalItem()
	{
		this.layout.total = createStub();
		return this.layout.total;
	}

	handleTextBoxBlur(event)
	{
		super.handleTextBoxBlur(event);

		setTimeout(() => {
			if (this.currentName !== this.getName())
			{
				this.onNameChangeHandler(this);
				this.currentName = this.getName();

				Tag.attrs(this.getHeader())`
					title: ${this.getName()};
				`;
			}
		}, 500);
	}

	onColorSelected(color)
	{
		super.onColorSelected(color);
		this.onColorChangeHandler(this);
	}

	handleAddColumnButtonClick(event)
	{
		this.onAddColumnHandler(this);
	}

	handleRemoveButtonClick(event: any)
	{
		this.getConfirmDialog().setContent(Loc.getMessage('CRM_ST_CONFIRM_STAGE_REMOVE_TEXT'));
		super.handleRemoveButtonClick(event);
	}

	handleConfirmButtonClick() {
		// @todo refactoring

		const event = new Event.BaseEvent({
			data: {
				column: this,
				onConfirm: () => {
					Marker.getAllLinks()
						.forEach((link) => {
							if (String(link.to.data.column.id) === String(this.id))
							{
								link.from.removeLink(link);
							}

							if (String(link.from.data.column.id) === String(this.id))
							{
								link.from.removeLink(link);
							}
						});

					super.handleConfirmButtonClick();
					setTimeout(() => {
						Marker.removeAllLinks();
						Marker.restoreAllLinks();
					});
				},
				onCancel: () => {
					this.getConfirmDialog().close();
				}
			},
		});

		this.onRemoveHandler(event);
	}

	switchToEditMode()
	{
		super.switchToEditMode();
	}

	applyEditMode()
	{
		const title = BX.util.trim(this.getTitleTextBox().value);
		const colorChanged = this.colorChanged;
		let titleChanged = false;
		if (title.length > 0 && this.getName() !== title)
		{
			titleChanged = true;
		}

		super.applyEditMode();

		if (titleChanged || colorChanged)
		{
			this.onChangeHandler(this);
		}

		Marker.adjustLinks();
	}

	onColumnDrag(x, y)
	{
		super.onColumnDrag(x, y);
		Marker.adjustLinks();
	}

	resetRectArea()
	{
		super.resetRectArea();

		clearTimeout(this.resetRectAreaTimeoutId);
		this.resetRectAreaTimeoutId = setTimeout(() => {
			Marker.adjustLinks();
		}, 200);
	}
}
