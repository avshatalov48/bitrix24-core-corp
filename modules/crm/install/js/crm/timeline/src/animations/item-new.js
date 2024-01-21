import Shift from './shift';
import { Dom, Tag, Type, bindOnce } from 'main.core';
import CompatibleItem from '../items/compatible-item';
import ConfigurableItem from '../../item/src/configurable-item';

/** @memberof BX.Crm.Timeline.Animation */
export default class ItemNew
{
	#startPosition: DOMRect;
	#node: HTMLElement;
	#anchor: HTMLElement;
	#areAnimatedItemsVisible: boolean;
	#id: string;
	#initialItem: CompatibleItem | ConfigurableItem | null;
	#finalItem: any;
	#events: any;
	#settings: any;
	#stub: HTMLElement;

	constructor()
	{
		this.#id = '';
		this.#settings = {};
		this.#initialItem = null;
		this.#finalItem = null;
		this.#events = null;
		this.#areAnimatedItemsVisible = false;
	}

	initialize(id, settings)
	{
		this.#id = Type.isStringFilled(id) ? id : BX.util.getRandomString(4);
		this.#settings = settings || {};

		this.#initialItem = this.getSetting('initialItem');
		this.#finalItem = this.getSetting('finalItem');

		this.#anchor = this.getSetting('anchor');
		this.#events = this.getSetting('events', {});

		this.#node = this.#initialItem.getWrapper();
	}

	getId(): string
	{
		return this.#id;
	}

	getSetting(name: string, defaultval): any
	{
		return Object.hasOwn(this.#settings, name) ? this.#settings[name] : defaultval;
	}

	addHistoryItem()
	{
		if (this.#finalItem.getWrapper() === null && !(this.#finalItem instanceof CompatibleItem))
		{
			this.#finalItem.initWrapper();
			this.#finalItem.initLayoutApp({ add: false });
		}

		Dom.style(this.#anchor, {
			height: 0,
		});
		this.#makeNodeStatic(this.#finalItem.getWrapper());
		Dom.insertBefore(this.#finalItem.getWrapper(), this.#anchor.nextSibling);
		requestAnimationFrame(() => {
			Dom.style(this.#finalItem.getWrapper(), {
				opacity: 1,
			});
		});
	}

	run()
	{
		this.#areAnimatedItemsVisible = this.#isNodeVisible(this.#node);
		if (this.#areAnimatedItemsVisible === false)
		{
			this.finish();

			return;
		}

		this.#prepareInitialItemBeforeShift();
		this.#prepareFinalItemBeforeShift();

		setTimeout(() => {
			this.shiftAndReplaceInitialWithFinal();
			this.collapseStub();
		}, 300);
	}

	#prepareInitialItemBeforeShift(): void
	{
		Dom.addClass(this.#node, 'crm-entity-stream-section-animate-start');

		this.#startPosition = Dom.getPosition(this.#node);
		this.#makeNodeAbsoluteWithSavePosition(this.#node);

		this.addStubForInitialItem(this.#node);
		Dom.append(this.#node, document.body);
	}

	#prepareFinalItemBeforeShift(): void
	{
		if (!(this.#finalItem instanceof CompatibleItem))
		{
			this.#finalItem.initWrapper();
			this.#finalItem.initLayoutApp({ add: false });
		}

		Dom.style(this.#finalItem.getWrapper(), 'opacity', 0);
		Dom.append(this.#finalItem.getWrapper(), document.body);
		requestAnimationFrame(() => {
			this.#makeNodeAbsoluteWithSavePosition(this.#finalItem.getWrapper(), this.#startPosition);
		});
	}

	collapseStub(): void
	{
		bindOnce(this.#stub, 'transitionend', () => {
			Dom.remove(this.#stub);
		});

		Dom.style(this.#stub, {
			height: 0,
			margin: 0,
		});
	}

	shift(): void
	{
		const shift = Shift.create(
			this.#node,
			this.#anchor,
			this.#startPosition,
			this.#stub,
			{ complete: this.finish.bind(this) },
		);

		shift.run();

		const heightDiff = Dom.getPosition(this.#finalItem.getWrapper()).height - this.#startPosition.height;

		const newNodeShift = Shift.create(
			this.#finalItem.getWrapper(),
			this.#anchor,
			Dom.getPosition(this.#finalItem.getWrapper()),
			undefined,
			undefined,
			heightDiff + 1,
		);

		newNodeShift.run();
	}

	shiftAndReplaceInitialWithFinal(): void
	{
		this.shift();

		setTimeout(() => {
			this.#replaceInitialWithFinal();
		}, 100);
	}

	#replaceInitialWithFinal(): void
	{
		Dom.style(this.#node, 'opacity', 0);
		Dom.style(this.#finalItem.getWrapper(), 'opacity', 0.5);
	}

	addStubForInitialItem(node: HTMLElement): void
	{
		const wrapper = this.#initialItem.getWrapper();

		this.#stub = Tag.render`
			<div class="crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow">
				<div
					class="crm-entity-stream-section-content"
					style="height: ${wrapper.clientHeight}px;"
				></div>
			</div>
		`;

		const height = Dom.getPosition(wrapper).height;

		Dom.style(this.#stub, {
			height: `${height}px`,
			margin: getComputedStyle(wrapper).margin,
			animation: 'none',
			transition: 'height 0.2s ease-in-out',
		});

		Dom.insertBefore(this.#stub, node);
	}

	finish()
	{
		if (this.#areAnimatedItemsVisible)
		{
			Dom.removeClass(this.#node, 'crm-entity-stream-section-animate-start');
		}
		setTimeout(() => {
			this.#initialItem.clearLayout();
			Dom.remove(this.#node);

			this.addHistoryItem();

			if (Type.isFunction(this.#events.complete))
			{
				this.#events.complete();
			}
		}, 500);
	}

	#makeNodeAbsoluteWithSavePosition(node: HTMLElement, pos?: DOMRect): void
	{
		const nodePosition = Dom.getPosition(node);
		const position = pos || nodePosition;

		Dom.style(node, {
			position: 'absolute',
			width: `${position.width}px`,
			height: `${nodePosition.height}px`,
			top: `${position.top}px`,
			left: `${position.left}px`,
			zIndex: 960,
		});
	}

	#makeNodeStatic(node: HTMLElement): void
	{
		Dom.style(node, {
			position: null,
			width: null,
			height: null,
			top: null,
			left: null,
		});
	}

	#isNodeVisible(node: HTMLElement): boolean
	{
		const nodePosition = Dom.getPosition(node);

		return node.offsetParent !== null && nodePosition.height > 0 && nodePosition.width > 0;
	}

	static create(id: string, settings): this
	{
		const self = new ItemNew();
		self.initialize(id, settings);

		return self;
	}
}
