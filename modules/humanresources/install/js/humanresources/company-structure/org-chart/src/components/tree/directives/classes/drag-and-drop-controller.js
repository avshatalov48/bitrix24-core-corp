import { Event, Dom, Tag, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { events } from '../../../../consts';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes } from 'humanresources.company-structure.utils';

const FULL_WIDTH = 302;
const CANVAS_MOVE_SPEED = 15;

export class TreeNodeDragController
{
	#prevAffectedItems: HTMLElement[];
	#targetData: { targetItem: HTMLElement; insertion: string; };
	#transform: { x: number; y: number; prevX: number; prevY: number; };
	#tree: HTMLElement;
	#draggedItem: HTMLElement;
	#ghost: HTMLElement;
	#positionPointer: HTMLElement;
	#zoom: number;
	#canvas: { x: number; y: number; };
	#prevPageX: number = 0;
	#prevPageY: number = 0;
	#targetTeams: HTMLElement[];
	#permissionChecker: PermissionChecker;
	#draggedEntityType: string;
	#mouseMoveHandler: (event: MouseEvent) => void;
	#mouseUpHandler: (event: MouseEvent) => void;
	#mouseWheelHandler: (event: MouseEvent) => void;

	constructor(el: HTMLElement)
	{
		Event.bind(el, 'mousedown', (event: MouseEvent) => {
			this.#onMouseDown(event);
		});
		this.#mouseMoveHandler = (event: MouseEvent) => {
			this.#onMouseMove(event);
		};

		this.#mouseUpHandler = (event: MouseEvent) => {
			this.#onMouseUp(event);
		};

		this.#mouseWheelHandler = (event: MouseEvent) => {
			this.#onMouseWheel(event);
		};

		this.#permissionChecker = PermissionChecker.getInstance();
	}

	#onMouseDown({ target, currentTarget: tree, pageX, pageY }: MouseEvent): void
	{
		if (!Dom.hasClass(target, 'humanresources-tree__node_dnd-icon'))
		{
			return;
		}

		event.stopPropagation();
		this.#tree = tree;
		this.#zoom = Number(tree.dataset.zoom);
		this.#canvas = { x: Number(tree.dataset.x), y: Number(tree.dataset.y) };
		this.#draggedItem = target.closest('.humanresources-tree__node');
		this.#ghost = this.#createGhost();
		this.#prevPageX = pageX;
		this.#prevPageY = pageY;
		this.#positionPointer = this.#createPositionPointer();
		this.#targetTeams = [];
		this.#prevAffectedItems = [];
		this.#transform = { x: 0, y: 0, prevX: 0, prevY: 0 };
		this.#targetData = {};
		this.#draggedEntityType = Dom.hasClass(this.#draggedItem, '--team') ? EntityTypes.team : EntityTypes.department;
		Dom.addClass(tree, '--drag-progress');
		Dom.addClass(this.#draggedItem.parentElement.parentElement, '--blur');
		this.#setPositionPointerTransform(this.#draggedItem);
		this.#setStyles();
		Event.bind(document, 'mousemove', this.#mouseMoveHandler);
		Event.bind(document, 'mouseup', this.#mouseUpHandler);
		Event.bind(document, 'wheel', this.#mouseWheelHandler);
		EventEmitter.emit(events.HR_ENTITY_TOGGLE_ELEMENTS, { shouldShowElements: false });
		const draggedId = Number(this.#draggedItem.dataset.id);
		EventEmitter.emit(events.HR_DRAG_ENTITY, { draggedId });
	}

	#onMouseMove({ pageX, pageY }: MouseEvent): void
	{
		Dom.addClass(this.#draggedItem, '--hidden');
		Dom.addClass(this.#tree.parentElement, '--disabled-transition');
		Dom.removeClass(this.#positionPointer, ['--inside', '--no-permission']);
		Dom.style(this.#positionPointer, 'height', `${this.#draggedItem.offsetHeight}px`);
		this.#resetTeamsBlur();
		this.#transform.prevX = this.#transform.x;
		this.#transform.prevY = this.#transform.y;
		this.#transform.x += (pageX - this.#prevPageX) / this.#zoom;
		this.#transform.y += (pageY - this.#prevPageY) / this.#zoom;
		this.#prevPageX = pageX;
		this.#prevPageY = pageY;
		this.#prevAffectedItems.forEach((affectedItem) => this.#setTransform(affectedItem, 0, 0));
		this.#prevAffectedItems = [];
		const { directionX, directionY } = this.#checkForDraggedOverflow();
		this.#transform.x = directionX === 0
			? this.#transform.x
			: this.#transform.x + directionX * CANVAS_MOVE_SPEED / this.#zoom;
		this.#transform.y = directionY === 0
			? this.#transform.y
			: this.#transform.y + directionY * CANVAS_MOVE_SPEED / this.#zoom;
		this.#setTransform(this.#ghost, this.#transform.x, this.#transform.y, 4);
		this.#setPositionPointerTransform(this.#draggedItem);
		this.#targetData = this.#getTargetData();
		if (!this.#targetData.insertion)
		{
			return;
		}

		const { insertion, targetItem, hasPermission } = this.#targetData;
		if (!hasPermission)
		{
			Dom.addClass(this.#positionPointer, '--no-permission');
		}

		this.#setPositionPointerTransform(targetItem);
		switch (insertion)
		{
			case 'reorder':
			{
				const affectedItems = this.#reorderEntity(targetItem);
				this.#prevAffectedItems = affectedItems;
				break;
			}
			case 'sibling-left':
			case 'sibling-right':
			{
				const affectedItems = this.#changeParentWithReorder(targetItem, insertion);
				this.#prevAffectedItems = affectedItems;
				break;
			}
			default:
				Dom.addClass(this.#positionPointer, '--inside');
				Dom.style(this.#positionPointer, 'height', `${targetItem.offsetHeight}px`);
		}
	}

	#onMouseUp(): void
	{
		this.#setStyles(true);
		Event.unbind(document, 'mousemove', this.#mouseMoveHandler);
		Event.unbind(document, 'mouseup', this.#mouseUpHandler);
		Event.unbind(document, 'mouseup', this.#mouseWheelHandler);
		this.#prevAffectedItems.forEach((item) => this.#setTransform(item, 0, 0));
		Dom.removeClass(this.#tree, '--drag-progress');
		Dom.removeClass(this.#draggedItem.parentElement.parentElement, '--blur');
		Dom.removeClass(this.#draggedItem, '--hidden');
		Dom.removeClass(this.#tree.parentElement, '--disabled-transition');
		Dom.remove(this.#ghost);
		Dom.remove(this.#positionPointer);
		this.#resetTeamsBlur();
		EventEmitter.emit(events.HR_ENTITY_TOGGLE_ELEMENTS, { shouldShowElements: true });
		const { insertion, targetItem, hasPermission } = this.#targetData;
		if (!insertion || !hasPermission)
		{
			return;
		}

		const draggedIndex = [...this.#draggedItem.parentElement.children].indexOf(this.#draggedItem);
		const targetIndex = [...targetItem.parentElement.children].indexOf(targetItem);
		const draggedId = Number(this.#draggedItem.dataset.id);
		EventEmitter.emit(events.HR_DROP_ENTITY, {
			draggedId,
			targetId: Number(targetItem.dataset.id),
			targetIndex,
			affectedItems: this.#prevAffectedItems.map((item) => Number(item.dataset.id)),
			direction: draggedIndex < targetIndex ? 1 : -1,
			insertion,
		});
	}

	#onMouseWheel({ shiftKey }: WheelEvent): void
	{
		if (shiftKey)
		{
			const currentCanvasX = Number(this.#tree.dataset.x);
			const movementX = currentCanvasX - this.#canvas.x;
			this.#transform.x -= movementX / this.#zoom;
			this.#canvas.x = currentCanvasX;
		}
		else
		{
			const currentCanvasY = Number(this.#tree.dataset.y);
			const movementY = currentCanvasY - this.#canvas.y;
			this.#transform.y -= movementY / this.#zoom;
			this.#canvas.y = currentCanvasY;
		}

		this.#setTransform(this.#ghost, this.#transform.x, this.#transform.y, 4);
	}

	#createGhost(): HTMLElement
	{
		const ghost = this.#draggedItem.cloneNode(true);
		Dom.addClass(ghost, 'humanresources-tree__dnd-ghost');
		Dom.removeClass(ghost, ['--expanded', '--focused']);
		const { x: treeX, y: treeY } = this.#tree.getBoundingClientRect();
		const { x: draggedX, y: draggedY } = this.#draggedItem.getBoundingClientRect();
		const left = draggedX - treeX;
		const top = draggedY - treeY;
		Dom.style(ghost, 'left', `${left / this.#zoom}px`);
		Dom.style(ghost, 'top', `${top / this.#zoom}px`);
		Dom.append(ghost, this.#tree);

		return ghost;
	}

	#createPositionPointer(): HTMLElement
	{
		const { offsetWidth, offsetHeight } = this.#draggedItem;
		const text = Dom.hasClass(this.#draggedItem, '--team')
			? Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DRAG_TEAM_LABEL')
			: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DRAG_DEPARTMENT_LABEL');
		const positionPointer = Tag.render`
			<div
				class="humanresources-tree__position-pointer"
				style="width: ${offsetWidth}px; height: ${offsetHeight}px;);"
			>
				<div>
					<div class="ui-icon-set --circle-plus"></div>
					<span>${text}</span>
				</div>
				<div>
					<div class="ui-icon-set --cross-circle-50"></div>
					<span>
						${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NO_DRAG_PERMISSION')}
					</span>
				</div>
			</div>
		`;
		Dom.append(positionPointer, this.#tree);
		this.#setPositionPointerTransform(this.#draggedItem);

		return positionPointer;
	}

	#setTransform(element: HTMLElement, x: number, y: number, rotation: number = 0): void
	{
		if (x === 0 && y === 0)
		{
			Dom.style(element, 'transform', '');

			return;
		}

		Dom.style(element, 'transform', `translate3d(${x}px, ${y}px, 0) rotate(${rotation}deg)`);
	}

	#setPositionPointerTransform(node: HTMLElement): void
	{
		const { x: treeX, y: treeY } = this.#tree.getBoundingClientRect();
		const { x: nodeX, y: nodeY } = node.getBoundingClientRect();
		const x = (nodeX - treeX) / this.#zoom;
		const y = (nodeY - treeY) / this.#zoom;
		this.#setTransform(this.#positionPointer, x, y);
	}

	#getTargetData(): { insertion?: string; targetItem?: HTMLElement; }
	{
		Dom.addClass(this.#ghost, '--disabled-events');
		this.#setTransform(this.#ghost, this.#transform.x, this.#transform.y);
		const reorderThreshold = 0.7;
		const changeParentWithReorderThreshold = 0.6;
		const {
			x,
			y,
			width: draggedWidth,
			height: draggedHeight,
		} = this.#ghost.getBoundingClientRect();
		this.#setTransform(this.#ghost, this.#transform.x, this.#transform.y, 4);
		const points = [
			{ x, y },
			{ x: x + draggedWidth, y },
			{ x, y: y + draggedHeight },
			{ x: x + draggedWidth, y: y + draggedHeight },
			{ x, y: y + draggedHeight / 2 },
			{ x: x + draggedWidth, y: y + draggedHeight / 2 },
		];
		const targetData = points.reduce((acc, point, i) => {
			if (acc.insertion)
			{
				return acc;
			}

			const belowItem = document.elementFromPoint(point.x, point.y);
			const targetItemSummary = belowItem?.closest('.humanresources-tree__node_summary');
			const targetItem = targetItemSummary?.parentElement;
			if (!targetItem || targetItem === this.#draggedItem)
			{
				return acc;
			}

			const {
				x: targetX,
				y: targetY,
				width: targetWidth,
				height: targetHeight,
			} = targetItem.getBoundingClientRect();
			const sameParent = this.#draggedItem.parentElement === targetItem.parentElement;
			const hasSortPermission = this.#permissionChecker.canSortEntitiesByParentId(
				Number(targetItem.parentElement.parentElement.dataset.id),
			);
			const allowSort = i === 0 && !Dom.hasClass(targetItem, '--root') && hasSortPermission;
			acc.targetItem = targetItem;
			if (sameParent && x + reorderThreshold * draggedWidth < targetX + targetWidth && allowSort)
			{
				return { ...acc, insertion: 'reorder' };
			}

			if (!sameParent && y + changeParentWithReorderThreshold * draggedHeight < targetY + targetHeight && allowSort)
			{
				return {
					...acc,
					insertion: x < targetX + targetWidth / 2 ? 'sibling-left' : 'sibling-right',
				};
			}

			const isDepartmentInsertToTeam = Dom.hasClass(targetItem, '--team') && !Dom.hasClass(this.#draggedItem, '--team');
			const isInsertToParent = this.#draggedItem.parentElement.parentElement === targetItem;
			if (isDepartmentInsertToTeam || isInsertToParent)
			{
				if (isDepartmentInsertToTeam)
				{
					Dom.addClass(targetItem, '--blur');
					this.#targetTeams.push(targetItem);
				}

				return acc;
			}

			return {
				...acc,
				insertion: 'inside',
				hasPermission: this.#permissionChecker.canBeParentForEntity(
					Number(targetItem.dataset.id),
					this.#draggedEntityType,
				),
			};
		}, { hasPermission: true });

		Dom.removeClass(this.#ghost, '--disabled-events');

		return targetData;
	}

	#reorderEntity(targetItem: HTMLElement): HTMLElement[]
	{
		const children = [...this.#draggedItem.parentElement.children];
		const draggedIndex = children.indexOf(this.#draggedItem);
		const targetIndex = [...targetItem.parentElement.children].indexOf(targetItem);
		const direction = draggedIndex < targetIndex ? 1 : -1;
		const affectedItems = direction > 0
			? children.slice(draggedIndex + 1, targetIndex + 1)
			: children.slice(targetIndex, draggedIndex);
		affectedItems.forEach((affectedItem) => this.#setTransform(affectedItem, -direction * FULL_WIDTH, 0));

		return affectedItems;
	}

	#changeParentWithReorder(targetItem: HTMLElement, insertion: string): HTMLElement[]
	{
		const children = [...targetItem.parentElement.children];
		const targetIndex = children.indexOf(targetItem);
		let affectedItems = [];
		if (insertion === 'sibling-right')
		{
			affectedItems = children.slice(0, targetIndex + 1);
			affectedItems.forEach((affectedItem) => this.#setTransform(affectedItem, -FULL_WIDTH, 0));
		}
		else
		{
			affectedItems = children.slice(targetIndex);
			affectedItems.forEach((affectedItem) => this.#setTransform(affectedItem, FULL_WIDTH, 0));
		}

		return affectedItems;
	}

	#checkForDraggedOverflow(): { directionX: number; directionY: number; }
	{
		let directionX = 0;
		let directionY = 0;
		const { left, top } = this.#draggedItem.getBoundingClientRect();
		const zoomedLeft = left / this.#zoom;
		const zoomedTop = top / this.#zoom;
		if (zoomedLeft + this.#transform.x < 0)
		{
			directionX = this.#transform.x < this.#transform.prevX ? -1 : 0;
		}

		if (zoomedLeft + this.#transform.x + this.#draggedItem.offsetWidth > document.body.offsetWidth / this.#zoom)
		{
			directionX = this.#transform.x > this.#transform.prevX ? 1 : 0;
		}

		if (zoomedTop + this.#transform.y < 0)
		{
			directionY = this.#transform.y < this.#transform.prevY ? -1 : 0;
		}

		if (zoomedTop + this.#transform.y + this.#draggedItem.offsetHeight > document.body.offsetHeight / this.#zoom)
		{
			directionY = this.#transform.y > this.#transform.prevY ? 1 : 0;
		}

		if (directionX !== 0 || directionY !== 0)
		{
			EventEmitter.emit(events.HR_ORG_CHART_TRANSFORM_CANVAS, {
				directionX,
				directionY,
				speed: CANVAS_MOVE_SPEED,
			});
		}

		return {
			directionX,
			directionY,
		};
	}

	#setStyles(shouldReset: ?boolean): void
	{
		Dom.style(document.body, 'userSelect', 'none');
		Dom.style(document.body, '-webkit-user-select', 'none');
		Dom.style(document.body, 'cursor', 'grabbing');
		if (shouldReset)
		{
			Dom.style(document.body, 'userSelect', '');
			Dom.style(document.body, '-webkit-user-select', '');
			Dom.style(document.body, 'cursor', '');
		}
	}

	#resetTeamsBlur(): void
	{
		this.#targetTeams.forEach((team) => Dom.removeClass(team, '--blur'));
	}
}
