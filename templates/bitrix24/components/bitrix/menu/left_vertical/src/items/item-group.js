import Item from './item';
import Backend from '../backend';
import { Dom, Text } from 'main.core';

export default class ItemGroup extends Item
{
	static code = 'group';
	groupContainer: Element;

	constructor()
	{
		super(...arguments);
		this.container.addEventListener('click', this.toggleAndSave.bind(this), true);
		this.container.addEventListener('mouseleave', () => {
			Dom.removeClass(this.container, 'menu-item-group-actioned');
		});
		this.groupContainer = this.container.parentNode.querySelector(`[data-group-id="${this.getId()}"]`);
		if (this.container.getAttribute('data-collapse-mode') === 'collapsed')
		{
			this.groupContainer.style.display = 'none';
		}
		setTimeout(() => {
			this.updateCounter();
		}, 0);
	}

	toggleAndSave(event)
	{
		event.preventDefault();
		event.stopPropagation();
		if (this.container.getAttribute('data-collapse-mode') === 'collapsed')
		{
			Backend.expandGroup(this.getId());
			this
				.expand()
				.then(() => {
					this.container.setAttribute('data-collapse-mode', 'expanded');
				});
		}
		else
		{
			Backend.collapseGroup(this.getId());
			this
				.collapse()
				.then(() => {
					this.container.setAttribute('data-collapse-mode', 'collapsed');
				});
		}
		return false;
	}

	checkAndCorrect(): ItemGroup
	{
		const groupContainer = this.groupContainer;

		if (groupContainer.parentNode === this.container)
		{
			Dom.insertAfter(groupContainer, this.container);
		}
		[...groupContainer
			.querySelectorAll(`.menu-item-block`)
		].forEach((node) => {
			node.setAttribute('data-status', this.container.getAttribute("data-status"));
		});
		return this;
	}

	#collapsingAnimation;
	collapse(hideGroupContainer): Promise
	{
		return new Promise((resolve) => {
			const groupContainer = this.groupContainer;

			if (this.#collapsingAnimation)
			{
				this.#collapsingAnimation.stop();
			}

			groupContainer.style.overflow = 'hidden';
			Dom.addClass(this.container, 'menu-item-group-collapsing');
			Dom.addClass(this.container, 'menu-item-group-actioned');
			Dom.addClass(groupContainer, 'menu-item-group-collapsing');
			const slideParams = {
				height: groupContainer.offsetHeight,
				display: groupContainer.style.display
			};

			this.#collapsingAnimation = (new BX.easing({
				duration: 500,
				start: {height: slideParams.height, opacity: 100},
				finish: {height: 0, opacity: 0},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					groupContainer.style.height = state.height + 'px';
					groupContainer.style.opacity = state.opacity / 100;
				},
				complete: () => {
					groupContainer.style.display = 'none';
					groupContainer.style.opacity = 'auto';
					groupContainer.style.height = 'auto';

					if (this.container.getAttribute('data-contains-active-item') === 'Y')
					{
						Dom.addClass(this.container, 'menu-item-active');
					}
					Dom.removeClass(this.container, 'menu-item-group-collapsing');
					Dom.removeClass(groupContainer, 'menu-item-group-collapsing');
					this.#collapsingAnimation = null;
					if (hideGroupContainer === true)
					{
						this.container.appendChild(groupContainer);
					}
					resolve();
				}
			}));
			this.#collapsingAnimation.animate();
		});
	}

	expand(checkAttribute): Promise
	{
		return new Promise((resolve) => {
			const container = this.container;
			const groupContainer = this.groupContainer;

			if (checkAttribute === true
				&& container.getAttribute('data-collapse-mode') === 'collapsed')
			{
				return resolve();
			}
			const contentHeight = groupContainer.querySelectorAll('li').length * container.offsetHeight;
			Dom.addClass(container, 'menu-item-group-expanding');
			Dom.addClass(container, 'menu-item-group-actioned');
			Dom.addClass(groupContainer, 'menu-item-group-expanding');

			if (groupContainer.parentNode === this.container)
			{
				Dom.insertAfter(groupContainer, this.container);
			}

			groupContainer.style.display = 'block';
			this.#collapsingAnimation = (new BX.easing({
				duration: 500,
				start: {height: 0, opacity: 0},
				finish: {height: contentHeight, opacity: 100},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					groupContainer.style.height = state.height + 'px';
					groupContainer.style.opacity = state.opacity / 100;
				},
				complete: function () {
					Dom.removeClass(container, 'menu-item-group-expanding menu-item-active');
					Dom.removeClass(groupContainer, 'menu-item-group-expanding');
					groupContainer.style.height = 'auto';
					groupContainer.style.opacity = 'auto';
					resolve();
				}
			}));
			this.#collapsingAnimation.animate();
		});
	}

	canDelete(): boolean
	{
		return false;
	}

	updateCounter()
	{
		let counterValue = 0;
		[...this.container
			.parentNode
			.querySelector(`[data-group-id="${this.getId()}"]`)
			.querySelectorAll('[data-role="counter"]')]
			.forEach((node) => {
				counterValue += Text.toNumber(node.dataset.counterValue);
			});
		const node = this.container.querySelector('[data-role="counter"]');

		if (counterValue > 0)
		{
			node.innerHTML = (counterValue > 99 ? '99+' : counterValue);
			this.container.classList.add('menu-item-with-index');
		}
		else
		{
			node.innerHTML = '';
			this.container.classList.remove('menu-item-with-index');
		}
	}

	markAsActive()
	{
		this.container.setAttribute('data-contains-active-item', 'Y');
		if (this.container.getAttribute('data-collapse-mode') === 'collapsed')
			Dom.addClass(this.container, 'menu-item-active');
	}

	markAsInactive()
	{
		this.container.removeAttribute('data-contains-active-item');
		Dom.removeClass(this.container, 'menu-item-active');
	}

	isActive()
	{
		return this.container.getAttribute('data-contains-active-item') === 'Y';
	}

	static detect(node)
	{
		return node.getAttribute("data-role") === 'group' &&
			node.getAttribute("data-type") === this.code;
	}
}
