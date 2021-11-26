import {Dom, Event, Loc, Tag, Type, Cache, Runtime, Text} from 'main.core';
import {PopupMenuWindow} from 'main.popup';
import * as d3 from 'main.d3js';
import isOverlap from './internal/is-overlap';
import makeRelativeRect from './internal/make-relative-rect';
import getMiddlePoint from './internal/get-middle-point';
import type Link from '../type/link';

/**
 * Implements interface for works with marker
 */
export default class Marker extends Event.EventEmitter
{
	static instances: Array<Marker> = [];
	static paths: Array<Array<{x: number, y: number}>> = [];

	static getMarkerFromPoint(point)
	{
		return Marker.instances.find(marker => (
			marker.isReceiverIntersecting(point)
		));
	}

	static emitReceiverDragOutForAll(exclude: ?Marker = null)
	{
		Marker.instances.forEach((marker) => {
			if (marker !== exclude)
			{
				marker.onReceiverDragOut();
			}
		});
	}

	static getAllLinks(): Set<Link>
	{
		return Marker.instances.reduce((/* Set */acc, marker) => (
			[...marker.links].reduce((subAcc, link) => subAcc.add(link), acc)
		), new Set());
	}

	static getAllStubLinks(): Set<Link>
	{
		return Marker.instances.reduce((/* Set */acc, marker) => (
			[...marker.stubLinks].reduce((subAcc, link) => subAcc.add(link), acc)
		), new Set());
	}

	static highlightLink(...targets: Link)
	{
		Marker.getAllLinks().forEach((link) => {
			if (!targets.includes(link))
			{
				if ([...targets].every(item => item.from !== link.from))
				{
					Dom.addClass(link.from.dispatcher, 'crm-st-fade');
					Dom.addClass(link.from.receiver, 'crm-st-fade');
					Dom.addClass(link.from.getTunnelButton(), 'crm-st-fade');
				}

				Dom.addClass(link.to.dispatcher, 'crm-st-fade');
				Dom.addClass(link.to.receiver, 'crm-st-fade');
				Dom.addClass(link.node.node(), 'crm-st-fade');
				Dom.addClass(link.arrow.select('path').node(), 'crm-st-fade');
			}
			else
			{
				const node = link.node.node();
				node.parentNode.appendChild(node);

				const arrowMarker = link.arrow.node();
				const defs = arrowMarker.closest('defs');

				Dom.insertAfter(arrowMarker, defs.firstChild);
			}
		});
	}

	static unhighlightLinks()
	{
		Marker.getAllLinks().forEach((link) => {
			Dom.removeClass(link.from.dispatcher, 'crm-st-fade');
			Dom.removeClass(link.from.receiver, 'crm-st-fade');
			Dom.removeClass(link.from.getTunnelButton(), 'crm-st-fade');
			Dom.removeClass(link.to.dispatcher, 'crm-st-fade');
			Dom.removeClass(link.to.receiver, 'crm-st-fade');
			Dom.removeClass(link.node.node(), 'crm-st-fade');
			Dom.removeClass(link.arrow.select('path').node(), 'crm-st-fade');
		});
	}

	static blurLinks(marker: Marker)
	{
		Marker.getAllLinks().forEach((link) => {
			if (link.from === marker || link.to === marker)
			{
				Dom.addClass(link.node.node(), 'crm-st-blur-link');
				Dom.addClass(link.from.getTunnelButton(), 'crm-st-blur-link');
			}
		});
	}

	static unblurLinks()
	{
		Marker.getAllLinks().forEach((link) => {
			Dom.removeClass(link.node.node(), 'crm-st-blur-link');
			Dom.removeClass(link.from.getTunnelButton(), 'crm-st-blur-link');
			Dom.removeClass(link.to.getTunnelButton(), 'crm-st-blur-link');
		});
	}

	static removeAllLinks()
	{
		Marker.instances.forEach((marker) => {
			const preventSave = true;
			marker.removeAllLinks(preventSave);
		});

		Marker.instances.forEach((marker) => {
			marker.removeAllStubLinks();
		});
	}

	static restoreAllLinks()
	{
		const preventSave = true;

		Marker.getAllLinks().forEach((link) => {
			link.from.links.delete(link);
			link.from.addLinkTo(link.to, link.robotAction, preventSave);
		});
	}

	static adjustLinks()
	{
		Marker.getAllLinks()
			.forEach((link, index) => {
				const path = link.from.getLinkPath(link.to);

				link.node
					.style('transition', 'none');
				d3.select(link.from.getTunnelButton())
					.style('transition', 'none');

				link.from.showTunnelButton(path);
				link.node.attr('d', d3.line()(path));
				link.path = path;

				clearTimeout(Marker.adjustLinksTimeoutIds[index]);
				Marker.adjustLinksTimeoutIds[index] = setTimeout(() => {
					link.node
						.style('transition', null);
					d3.select(link.from.getTunnelButton())
						.style('transition', null);
				}, 1000);
			});
	}

	static adjustLinksTimeoutIds = {};

	// Links from this marker
	links: Set<Link> = new Set();
	stubLinks: Set<Link> = new Set();
	cache = new Cache.MemoryCache();

	constructor(options: {
		dispatcher: HTMLElement,
		receiver: HTMLElement,
		point: HTMLElement,
		container: HTMLElement,
		intermediateXPoints: Array<number>,
		name: string,
		data?: {[key: string]: any},
	})
	{
		super();

		this.dispatcher = options.dispatcher;
		this.receiver = options.receiver;
		this.point = options.point;
		this.container = options.container;
		this.intermediateXPoints = options.intermediateXPoints;
		this.name = options.name;
		this.data = options.data;

		const linksRoot = this.getLinksRoot();

		// Add arrow marker
		if (!linksRoot.select('defs').node())
		{
			linksRoot
				.append('svg:defs')
				.append('filter')
				.attr('id', 'crm-st-blur')
				.append('feGaussianBlur')
				.attr('stdDeviation', '2');

			linksRoot
				.select('#crm-st-blur')
				.append('feColorMatrix')
				.attr('type', 'saturate')
				.attr('values', '0');
		}

		this.onDispatcherMouseDown = this.onDispatcherMouseDown.bind(this);
		this.onMarkerRootMouseUp = this.onMarkerRootMouseUp.bind(this);
		this.onMarkerRootMouseMove = this.onMarkerRootMouseMove.bind(this);

		d3.select(this.dispatcher).on('mousedown', this.onDispatcherMouseDown);

		Marker.instances.push(this);
	}

	disable()
	{
		this.disabled = true;
	}

	enable()
	{
		this.disabled = false;
	}

	isEnabled(): boolean
	{
		return !this.disabled;
	}

	getMarkerRoot(): d3.Selection
	{
		return this.cache.remember('markerRoot', () => {
			const markerRoot = d3
				.select(this.container)
				.select('.crm-st-svg-root');

			if (markerRoot.node())
			{
				return markerRoot;
			}

			return d3
				.select(this.container)
				.append('svg')
				.attr('class', 'crm-st-svg-root');
		});
	}

	getMarkerRootRect(): ClientRect | DOMRect
	{
		return this.getMarkerRoot().node().getBoundingClientRect();
	}

	getLinksRoot(): d3.Selection
	{
		return this.cache.remember('linksRoot', () => {
			const linksRoot = d3
				.select(this.container)
				.select('.crm-st-svg-links-root');

			if (linksRoot.node())
			{
				return linksRoot;
			}

			return d3
				.select(this.container)
				.append('svg')
				.attr('class', 'crm-st-svg-links-root');
		});
	}

	getMarkerLine(): d3.Selection
	{
		return this.cache.remember('markerLine', (
			this.getMarkerRoot()
				.append('line')
				.attr('class', 'crm-st-svg-marker')
		));
	}

	removeMarkerLine()
	{
		this.getMarkerLine().remove();
		this.cache.delete('markerLine');
	}

	getDispatcherRect(): ClientRect | DOMRect | {middleX: number, middleY: number}
	{
		const relativeRect = makeRelativeRect(
			this.getMarkerRootRect(),
			this.dispatcher.getBoundingClientRect(),
		);

		return {
			...relativeRect,
			...getMiddlePoint(relativeRect),
		};
	}

	getReceiverRect(): ClientRect | DOMRect | {middleX: number, middleY: number}
	{
		const relativeRect = makeRelativeRect(
			this.getMarkerRootRect(),
			this.receiver.getBoundingClientRect(),
		);

		return {
			...relativeRect,
			...getMiddlePoint(relativeRect),
		};
	}

	getPointRect(): ClientRect | DOMRect | {middleX: number, middleY: number}
	{
		const relativeRect = makeRelativeRect(
			this.getMarkerRootRect(),
			this.point.getBoundingClientRect(),
		);

		return {
			...relativeRect,
			...getMiddlePoint(relativeRect),
		};
	}

	getMarkerRootMousePosition(): {x: number, y: number}
	{
		const [x, y] = d3.mouse(this.getMarkerRoot().node());
		return {x, y};
	}

	/** @private */
	onReceiverDragOver(from, to)
	{
		if (!this.hovered)
		{
			this.hovered = true;
			this.emit('Marker:receiver:dragOver', {from, to});
		}
	}

	/** @private */
	onReceiverDragOut()
	{
		if (this.hovered)
		{
			this.hovered = false;
			this.emit('Marker:receiver:dragOut');
		}
	}

	/** @private */
	onDispatcherMouseDown()
	{
		const {middleX, middleY} = this.getDispatcherRect();

		this.getMarkerLine()
			.attr('x1', middleX)
			.attr('y1', middleY)
			.attr('x2', middleX)
			.attr('y2', middleY);

		this.getMarkerRoot()
			.style('z-index', '222')
			.on('mousemove', this.onMarkerRootMouseMove)
			.on('mouseup', this.onMarkerRootMouseUp);

		this.emit('Marker:dragStart');
	}

	/** @private */
	onMarkerRootMouseMove()
	{
		const {x, y} = this.getMarkerRootMousePosition();

		this.getMarkerLine()
			.attr('x2', x)
			.attr('y2', y);

		this.emit('Marker:drag');

		const destinationMarker = this.getDestinationMarker();

		if (destinationMarker && destinationMarker.isEnabled())
		{
			if (destinationMarker !== this)
			{
				destinationMarker.onReceiverDragOver(this, destinationMarker);
			}
		}

		Marker.emitReceiverDragOutForAll(destinationMarker);
	}

	/** @private */
	onMarkerRootMouseUp()
	{
		this.getMarkerRoot()
			.on('mousemove', null)
			.on('mouseup', null)
			.style('z-index', null);

		this.removeMarkerLine();

		// @todo refactoring
		Marker.instances.forEach(marker => marker.onReceiverDragOut());

		const destinationMarker = this.getDestinationMarker();
		const event = new Event.BaseEvent({
			data: {from: this, to: destinationMarker},
		});
		this.emit('Marker:dragEnd', event);

		if (destinationMarker && destinationMarker.isEnabled())
		{
			if (destinationMarker && !event.isDefaultPrevented())
			{
				if (!this.data.column.data.isCategoryEditable)
				{
					this.emit('Marker:error', {
						message: Loc.getMessage('CRM_ST_TUNNEL_EDIT_ACCESS_DENIED'),
					});

					return;
				}

				this.addLinkTo(destinationMarker, 'copy');
			}
		}
	}

	getTunnelMenu(): PopupMenuWindow
	{
		return this.cache.remember('tunnelMenu', () => (
			new PopupMenuWindow({
				bindElement: this.getTunnelButton(),
				items: this.getTunnelMenuItems([...this.links][0]),
				events: {
					onPopupClose: () => this.deactivateTunnelButton(),
					onPopupShow: () => this.activateTunnelButton(),
				},
			})
		));
	}

	getTunnelMenuItems(link): Array<{text: string, onclick: () => void}>
	{
		const self = this;
		const onRobotActionChange = function(robotAction: string) {
			if (!Type.isNil(link) && link.robotAction !== robotAction)
			{
				self.changeRobotAction(link, robotAction)
				link.robotAction = robotAction;
			}

			this.getParentMenuWindow().close();
			this.getParentMenuWindow().getMenuItems()[0].setText(
				Loc.getMessage(`CRM_ST_ROBOT_ACTION_${robotAction.toUpperCase()}`)
			);
		}
		const robotAction = Type.isNil(link) ? 'COPY' : link.robotAction.toUpperCase();

		return [
			{
				text: Loc.getMessage(`CRM_ST_ROBOT_ACTION_${robotAction}`),
				items: [
					{
						text: Loc.getMessage('CRM_ST_ACTION_COPY'),
						onclick() {
							onRobotActionChange.call(this, 'copy');
						},
					},
					{
						text: Loc.getMessage('CRM_ST_ACTION_MOVE'),
						onclick() {
							onRobotActionChange.call(this, 'move');
						}
					},
				],
			},
			{
				text: Loc.getMessage('CRM_ST_SETTINGS'),
				onclick() {
					self.editLink(link);
					this.close();
				},
			},
			{
				text: Loc.getMessage('CRM_ST_REMOVE'),
				onclick() {
					self.removeLink(link);
					const parentMenu = this.getParentMenuWindow();

					if (parentMenu)
					{
						parentMenu
							.removeMenuItem(this.getParentMenuItem().id);
					}
				},
			},
		];
	}

	changeRobotAction(link: Link, action: string)
	{
		this.emit('Marker:changeRobotAction', {
			link,
			action,
			onChangeRobotEnd: () => this.emit('Marker:editLink', { link }),
		});
	}

	editLink(link: Link)
	{
		this.emit('Marker:editLink', {link});
	}

	addLinkTo(destination: Marker, robotAction: string, preventSave = false)
	{
		setTimeout(() => {
			if (![...this.links].some(link => link.to === destination))
			{
				const linksRoot = this.getLinksRoot();
				const path = this.getLinkPath(destination);

				const line = d3.line();

				const fromId = this.data.column.getId().replace(':', '-');
				const toId = destination.data.column.getId().replace(':', '-');

				const arrowId = `${fromId}-${toId}`;

				const arrow = linksRoot
					.select('defs')
					.append('svg:marker')
					.attr('id', arrowId)
					.attr('refX', 8)
					.attr('refY', 6)
					.attr('markerWidth', 30)
					.attr('markerHeight', 30)
					.attr('markerUnits', 'userSpaceOnUse')
					.attr('orient', 'auto')
					.append('path')
					.attr('d', 'M 0 0 12 6 0 12 3 6')
					.attr('class', 'crm-st-svg-link-arrow')
					.select(function selectCallback() {
						return this.parentNode;
					});

				const linkNode = linksRoot
					.append('path')
					.attr('class', 'crm-st-svg-link')
					.attr('marker-end', `url(#${arrowId})`)
					.attr('d', line(path));

				this.showTunnelButton(path);

				const link = {
					from: this,
					to: destination,
					node: linkNode,
					robotAction,
					arrow,
					path,
				};

				this.emit('Marker:linkFrom', {link, preventSave});
				destination.emit('Marker:linkTo', {link, preventSave});

				this.links.add(link);

				const menu = this.getTunnelsListMenu();
				const id = menu.getMenuItems().length;

				menu.addMenuItem({
					id: `#${id}`,
					text: Text.encode(destination.name),
					events: {
						onMouseEnter() {
							Marker.highlightLink(link);
						},
						onMouseLeave() {
							Marker.unhighlightLinks();
						},
					},
					items: this.getTunnelMenuItems(link),
				});
			}

			if (this.links.size > 1)
			{
				this.setTunnelsCounterValue(this.links.size);
			}
		});
	}

	addStubLinkTo(destination: Marker)
	{
		setTimeout(() => {
			if (![...this.stubLinks].some(link => link.to === destination))
			{
				const linksRoot = this.getLinksRoot();
				const path = this.getLinkPath(destination);

				const line = d3.line();

				const fromId = this.data.column.getId().replace(':', '-');
				const toId = destination.data.column.getId().replace(':', '-');

				const arrowId = `${fromId}-${toId}`;

				const arrow = linksRoot
					.select('defs')
					.append('svg:marker')
					.attr('id', arrowId)
					.attr('refX', 8)
					.attr('refY', 6)
					.attr('markerWidth', 30)
					.attr('markerHeight', 30)
					.attr('markerUnits', 'userSpaceOnUse')
					.attr('orient', 'auto')
					.append('path')
					.attr('d', 'M 0 0 12 6 0 12 3 6')
					.attr('class', 'crm-st-svg-link-arrow crm-st-svg-link-arrow-stub')
					.select(function selectCallback() {
						return this.parentNode;
					});

				const linkNode = linksRoot
					.append('path')
					.attr('class', 'crm-st-svg-link crm-st-svg-link-stub')
					.attr('marker-end', `url(#${arrowId})`)
					.attr('d', line(path));

				this.showTunnelStubButton(path);

				const link = {
					from: this,
					to: destination,
					node: linkNode,
					arrow,
					path,
				};

				this.emit('Marker:stubLinkFrom', {link, preventSave: true});
				destination.emit('Marker:stubLinkTo', {link, preventSave: true});

				this.stubLinks.add(link);
			}
		});
	}

	updateLink(link: Link, newTo: Marker, preventSave = false)
	{
		const path = this.getLinkPath(newTo);
		const line = d3.line();
		const oldTo = link.to;

		link.node
			.attr('d', line(path));

		link.path = path;
		link.to = newTo;

		this.emit('Marker:linkFrom', {link, preventSave});
		newTo.emit('Marker:linkTo', {link, preventSave});

		if (!oldTo.isLinked())
		{
			oldTo.emit('Marker:unlink');
		}
	}

	removeLink(link: Link, preventSave = false)
	{
		// @todo refactoring

		link.hidden = true;

		if (!preventSave)
		{
			this.links.delete(link);
		}

		link.node.remove();
		link.arrow.remove();

		if (!this.isLinkedFrom())
		{
			Dom.remove(link.from.getTunnelButton());
			this.getTunnelMenu().destroy();
			this.deactivateTunnelButton();
			this.cache.delete('tunnelMenu');
		}

		this.setTunnelsCounterValue(this.links.size);

		const visibleLinks = [...this.links].filter(item => !item.hidden);

		if (visibleLinks.length <= 1)
		{
			if (this.getTunnelsListMenu().getPopupWindow().isShown())
			{
				this.getTunnelMenu().destroy();
				this.cache.delete('tunnelMenu');

				this.getTunnelMenu().show();
			}

			this.getTunnelsListMenu().destroy();
			this.deactivateTunnelButton();
			this.cache.delete('tunnelsListMenu');
		}

		link.from.emit('Marker:removeLinkFrom', {link, preventSave});

		if (!link.from.isLinked())
		{
			link.from.emit('Marker:unlink', {preventSave});
		}

		link.to.emit('Marker:removeTo', {link, preventSave});

		if (!link.to.isLinked())
		{
			link.to.emit('Marker:unlink', {preventSave});
		}
	}

	removeAllLinks(preventSave = false)
	{
		this.links.forEach(link => this.removeLink(link, preventSave));
	}

	removeStubLink(link: Link)
	{
		this.stubLinks.delete(link);

		link.node.remove();
		link.arrow.remove();

		if (!this.isLinkedStub())
		{
			Dom.remove(link.from.getStubTunnelButton());
		}

		link.from.emit('Marker:removeStubLink', {link});
		link.from.emit('Marker:removeStubLinkFrom', {link});

		if (!link.from.isLinkedStub())
		{
			link.from.emit('Marker:unlinkStub');
		}

		link.to.emit('Marker:removeStubTo', {link});

		if (!link.to.isLinkedStub())
		{
			link.to.emit('Marker:unlinkStub');
		}
	}

	removeAllStubLinks()
	{
		this.stubLinks.forEach(link => this.removeStubLink(link));
	}

	isLinked(): boolean
	{
		return [...Marker.getAllLinks()]
			.some(item => !item.hidden && (item.from === this || item.to === this));
	}

	isLinkedFrom(): boolean
	{
		return [...Marker.getAllLinks()]
			.some(item => !item.hidden && item.from === this);
	}

	isLinkedTo(): boolean
	{
		return [...Marker.getAllLinks()]
			.some(item => !item.hidden && item.to === this);
	}

	isLinkedStub(): boolean
	{
		return [...Marker.getAllLinks()]
			.some(item => !item.hidden && (item.from === this || item.to === this));
	}

	showTunnelButton(path)
	{
		const button = this.getTunnelButton();
		const category = this.getCategory();
		const left = path[0][0];

		Tag.style(button)`
			bottom: 0px;
			left: ${left}px;
			transform: translate3d(-50%, 50%, 0);
		`;

		if (!category.contains(button))
		{
			Dom.append(button, category);
		}
	}

	getStubTunnelButton()
	{
		return this.cache.remember('tunnelStubButton', () => {
			const button = Runtime.clone(this.getTunnelButton());
			Dom.addClass(button, 'crm-st-tunnel-button-stub');
			return button;
		});
	}

	showTunnelStubButton(path)
	{
		const button = this.getStubTunnelButton();
		const category = this.getCategory();
		const left = path[0][0];

		Tag.style(button)`
			bottom: 0px;
			left: ${left}px;
			transform: translate3d(-50%, 50%, 0);
		`;

		if (!category.contains(button))
		{
			Dom.append(button, category);
		}
	}

	getTunnelButton()
	{
		const canEdit = this.data.column.data.isCategoryEditable;
		return this.cache.remember('tunnelButton', () => (
			Tag.render`
				<div class="crm-st-tunnel-button" 
					 onmouseenter="${this.onTunnelButtonMouseEnter.bind(this)}"
					 onmouseleave="${Marker.onTunnelButtonMouseLeave}"
					 onclick="${this.onTunnelButtonClick.bind(this)}"
					 title="${Loc.getMessage('CRM_ST_TUNNEL_BUTTON_TITLE')}"
					 style="${!canEdit ? 'pointer-events: none;' : ''}"
				>${Loc.getMessage('CRM_ST_TUNNEL_BUTTON_LABEL')}</div>
			`
		));
	}

	/** @private */
	onTunnelButtonMouseEnter()
	{
		Marker.highlightLink(...this.links);
	}

	/** @private */
	static onTunnelButtonMouseLeave()
	{
		Marker.unhighlightLinks();
	}

	/** @private */
	onTunnelButtonClick()
	{
		if (BX.Crm.Restriction.Bitrix24.isRestricted('automation'))
		{
			BX.Crm.Restriction.Bitrix24.getHandler('automation').call();
		}
		else if (this.links.size > 1)
		{
			if (this.isTunnelButtonActive())
			{
				this.getTunnelsListMenu().close();
				return;
			}

			this.getTunnelsListMenu().show();
		}
		else
		{
			this.getTunnelMenu().show();
		}
	}

	getTunnelsListMenu(): PopupMenuWindow
	{
		return this.cache.remember('tunnelsListMenu', (
			new PopupMenuWindow({
				bindElement: this.getTunnelButton(),
				items: [],
				closeByEsc: true,
				menuShowDelay: 0,
				events: {
					onPopupClose: () => this.deactivateTunnelButton(),
					onPopupShow: () => this.activateTunnelButton(),
				},
			})
		));
	}

	activateTunnelButton()
	{
		Dom.addClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	}

	deactivateTunnelButton()
	{
		Dom.removeClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	}

	isTunnelButtonActive(): boolean
	{
		return Dom.hasClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	}

	getTunnelsCounter(): HTMLSpanElement
	{
		return this.cache.remember('tunnelsCounter', (
			Tag.render`<span class="crm-st-tunnel-button-counter">0</span>`
		));
	}

	setTunnelsCounterValue(value: number)
	{
		const tunnelButton = this.getTunnelButton();
		const tunnelsCounter = this.getTunnelsCounter();

		if (value > 1)
		{
			if (!tunnelButton.contains(tunnelsCounter))
			{
				Dom.append(tunnelsCounter, tunnelButton);
			}

			tunnelsCounter.innerText = value;
		}
		else
		{
			tunnelsCounter.innerText = 0;
			Dom.remove(tunnelsCounter);
		}
	}

	getCategory(): HTMLDivElement
	{
		return this.receiver.closest('.crm-st-category');
	}

	getIntermediateXPoints(): Array<number>
	{
		if (Type.isArray(this.intermediateXPoints))
		{
			const markerRootRect = this.getMarkerRootRect();
			return this.intermediateXPoints
				.map(value => value - markerRootRect.left);
		}

		if (Type.isFunction(this.intermediateXPoints))
		{
			const markerRootRect = this.getMarkerRootRect();
			return this.intermediateXPoints()
				.map(value => value - markerRootRect.left);
		}

		return [];
	}

	getNearestIntermediateXPoint(x: number): number
	{
		return this.getIntermediateXPoints().reduce((prev, curr) => (
			Math.abs(curr - x) < Math.abs(prev - x) ? curr : prev
		));
	}

	getLinkPath(target: Marker): Array<[number, number]>
	{
		const targetPosition = target.getPointRect();
		const currentPosition = this.getDispatcherRect();

		const baseOffset = 80;
		const markerMargin = 10;
		const path = [];

		path.push([
			currentPosition.middleX,
			currentPosition.middleY,
		]);

		path.push([
			currentPosition.middleX,
			currentPosition.middleY + baseOffset,
		]);

		if (currentPosition.middleY !== targetPosition.middleY)
		{
			const intermediateX = this.getNearestIntermediateXPoint(targetPosition.middleX);

			path.push([
				intermediateX,
				currentPosition.middleY + baseOffset,
			]);

			path.push([
				intermediateX,
				targetPosition.middleY + (baseOffset / 3) - (markerMargin / 3),
			]);

			path.push([
				targetPosition.middleX,
				targetPosition.middleY + (baseOffset / 3) - (markerMargin / 3),
			]);
		}
		else
		{
			path.push([
				targetPosition.middleX,
				targetPosition.middleY + baseOffset,
			]);
		}

		path.push([
			targetPosition.middleX,
			targetPosition.middleY + markerMargin,
		]);

		const lineOffset = 4;

		return [...Marker.getAllLinks()].reduce((acc, link) => {
			const {from, path: currentPath} = link;

			if (from !== this)
			{
				/**
				 * Horizon lines
				 * 1x -> 2x
				 * 3x -> 4x
				 */

				if (acc[1][1] === currentPath[1][1])
				{
					if (isOverlap([acc[1][0], acc[2][0]], [currentPath[1][0], currentPath[2][0]]))
					{
						acc[1][1] += lineOffset;
						acc[2][1] += lineOffset;
					}
				}

				if (currentPath.length === 6)
				{
					if (acc[1][1] === currentPath[3][1])
					{
						if (isOverlap([acc[1][0], acc[2][0]], [currentPath[3][0], currentPath[4][0]]))
						{
							acc[1][1] += lineOffset;
							acc[2][1] += lineOffset;
						}
					}
				}

				if (acc.length === 6)
				{
					if (acc[3][1] === currentPath[1][1])
					{
						if (isOverlap([acc[3][0], acc[4][0]], [currentPath[1][0], currentPath[2][0]]))
						{
							acc[3][1] += lineOffset;
							acc[4][1] += lineOffset;
						}
					}

					if (currentPath.length === 6)
					{
						if (acc[3][1] === currentPath[3][1])
						{
							if (isOverlap([acc[3][0], acc[4][0]], [currentPath[3][0], currentPath[4][0]]))
							{
								acc[3][1] += lineOffset;
								acc[4][1] += lineOffset;
							}
						}
					}
				}

				/**
				 * Vertical line
				 * 2y -> 3y
				 */

				if (acc.length === 6)
				{
					if (acc[2][0] === currentPath[2][0])
					{
						if (isOverlap([acc[2][1], acc[3][1]], [currentPath[2][1], currentPath[3][1]]))
						{
							acc[2][0] += lineOffset;
							acc[3][0] += lineOffset;
						}
					}
				}
			}

			return acc;
		}, [...path]);
	}

	getDestinationMarker(): ?Marker
	{
		const mousePosition = this.getMarkerRootMousePosition();
		const destinationMarker = Marker.getMarkerFromPoint(mousePosition);

		if (destinationMarker && destinationMarker !== this)
		{
			return destinationMarker;
		}

		return null;
	}

	isReceiverIntersecting(point): boolean
	{
		const receiverRect = this.getReceiverRect();
		const heightOffset = 10;

		return (
			(point.x > receiverRect.left && point.x < receiverRect.right)
			&& (point.y > receiverRect.top && point.y < (receiverRect.bottom + heightOffset))
		);
	}

	blurLinks()
	{
		Marker.blurLinks(this);
	}

	getData(): {[key: string]: any}
	{
		return this.data;
	}
}