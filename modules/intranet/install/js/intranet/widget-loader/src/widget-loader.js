import './style.css';
import { Tag, Cache, Dom, Event } from 'main.core';
import { Popup } from 'main.popup';

export class WidgetLoader
{
	#cache = new Cache.MemoryCache();

	constructor(options = {})
	{
		this.#setOptions(options);
		Event.bind(this.#getOptions().bindElement, 'click', () => {
			this.show();
		});
	}

	show(): void
	{
		this.getPopup().show();
	}

	clearBeforeInsertContent(): void
	{
		const popupContainer = this.getPopup().getPopupContainer();
		Dom.removeClass(popupContainer, 'intranet-widget-skeleton__wrap');

		const selectors = [
			'.intranet-widget-skeleton__row',
			'.intranet-widget-skeleton__header',
			'.intranet-widget-skeleton-avatar__header',
			'.intranet-widget-skeleton__footer',
		].join(', ');

		popupContainer.querySelectorAll(selectors).forEach((node) => Dom.remove(node));

		Dom.prepend(this.#cache.get('popup-content'), popupContainer);
	}

	getPopup(): Popup
	{
		return this.#cache.remember('popup', () => {
			const offsetLeft = (-(this.#getOptions().width / 2)
				+ (this.#getOptions().bindElement ? this.#getOptions().bindElement.offsetWidth / 2 : 0)
				+ 40);
			const popup = new Popup({
				autoHide: true,
				id: this.#getOptions().id ?? null,
				bindElement: this.#getOptions().bindElement,
				width: this.#getOptions().width,
				useAngle: this.#getOptions().useAngle ?? true,
				angle: this.#getOptions().useAngle ?? {
					offset: (this.#getOptions().width / 2) - 16,
				},
				className: this.#getOptions().className ?? null,
				animation: 'fading-slide',
				closeByEsc: true,
				offsetLeft: this.#getOptions().offsetLeft ?? offsetLeft,
				offsetTop: this.#getOptions().offsetTop ?? 3,
			});
			const container = popup.getPopupContainer();
			this.#cache.set('popup-content', container.querySelector('.popup-window-content'));
			Dom.remove(container.querySelector('.popup-window-content'));
			Dom.addClass(container, 'intranet-widget-skeleton__wrap');

			return popup;
		});
	}

	#setOptions(options): WidgetLoader
	{
		this.#cache.set('options', options);

		return this;
	}

	#getOptions(): Object
	{
		return this.#cache.get('options', {});
	}

	createSkeletonFromConfig(config = {}): WidgetLoader
	{
		if (config.header)
		{
			this.addHeaderSkeleton();
		}

		if (config.avatarWidgetHeader)
		{
			this.addAvatarWidgetHeaderSkeleton(config.avatarWidgetHeader);
		}

		if (Array.isArray(config.items))
		{
			config.items.forEach((item) => {
				switch (item.type)
				{
					case 'item':
						this.addItemSkeleton(item.height);
						break;
					case 'split':
						this.addSplitItemSkeleton(item.height);
						break;
					case 'splitColumn':
						this.addColumnSplitItemSkeleton(item.height, item.count);
						break;
					case 'applicationSection':
						this.addApplicationSectionSkeleton();
						break;
					default:
						break;
				}
			});
		}

		if (config.footer)
		{
			this.addFooterSkeleton(config.footer.height ?? 6);
		}

		return this;
	}

	addItemSkeleton(height: number): WidgetLoader
	{
		Dom.append(this.#createItemSkeleton(height), this.getPopup().getPopupContainer());

		return this;
	}

	addSplitItemSkeleton(height: number): WidgetLoader
	{
		Dom.append(this.#createSplitItemSkeleton(height), this.getPopup().getPopupContainer());

		return this;
	}

	addHeaderSkeleton(): WidgetLoader
	{
		Dom.prepend(this.#createHeaderSkeleton(), this.getPopup().getPopupContainer());

		return this;
	}

	addAvatarWidgetHeaderSkeleton(config: Object): WidgetLoader
	{
		Dom.prepend(this.#createAvatarWidgetHeaderSkeleton(config), this.getPopup().getPopupContainer());

		return this;
	}

	addColumnSplitItemSkeleton(height: number, count: number): WidgetLoader
	{
		Dom.append(this.#createColumnSplitItemSkeleton(height, count), this.getPopup().getPopupContainer());

		return this;
	}

	addApplicationSectionSkeleton(): WidgetLoader
	{
		Dom.append(this.#createApplicationSectionSkeleton(), this.getPopup().getPopupContainer());

		return this;
	}

	addFooterSkeleton(height: number): WidgetLoader
	{
		Dom.append(this.#createFooterSkeleton(height), this.getPopup().getPopupContainer());

		return this;
	}

	#createHeaderSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__header">
				<div class="intranet-widget-skeleton__line"></div>
			</div>
		`;
	}

	#createItemSkeleton(height: number): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div style="height: ${height}px" class="intranet-widget-skeleton__item">
					<div style="width: 24px;height: 24px;border-radius: 6px;margin-right: 12px;" class="intranet-widget-skeleton__cube"></div>
					<div style="max-width: 130px;height: 10px;" class="intranet-widget-skeleton__line"></div>
					<div style="width: 12px; height: 12px; margin-left: auto;" class="intranet-widget-skeleton__circle"></div>
				</div>
			</div>
		`;
	}

	#createColumnSplitItemSkeleton(height: number, count: number): HTMLElement
	{
		const wrapper = Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div style="height: ${height}px" class="intranet-widget-skeleton__item --column"></div>
			</div>
		`;

		for (let i = 0; i < count; i++)
		{
			const item = Tag.render`
				<div class="intranet-widget-skeleton__nested-item">
					<div class="intranet-widget-skeleton__cube intranet-widget-skeleton-column-split-item__cube"></div>
					<div class="intranet-widget-skeleton__line intranet-widget-skeleton-column-split-item__line"></div>
					<div class="intranet-widget-skeleton__circle intranet-widget-skeleton-column-split-item__circle"></div>
				</div>
			`;
			Dom.append(item, wrapper.querySelector('.intranet-widget-skeleton__item'));
		}

		return wrapper;
	}

	#createSplitItemSkeleton(height: number): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div style="height: ${height}px" class="intranet-widget-skeleton__item">
					<div class="intranet-widget-skeleton__nested-item">
						<div class="intranet-widget-skeleton__circle intranet-widget-skeleton-split-item__icon"></div>
						<div class="intranet-widget-skeleton__line intranet-widget-skeleton-split-item__line"></div>
					</div>
				</div>
			</div>
		`;
	}

	#createFooterSkeleton(height: number = 6): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__footer">
				<div style="height: ${height}px" class="intranet-widget-skeleton__line"></div>
				<div style="height: ${height}px" class="intranet-widget-skeleton__line"></div>
				<div style="height: ${height}px" class="intranet-widget-skeleton__line"></div>
			</div>
		`;
	}

	#createAvatarWidgetHeaderSkeleton(config: Object): HTMLElement
	{
		const wrapper = Tag.render`
			<div class="intranet-widget-skeleton-avatar__header">
				<div class="intranet-widget-skeleton-avatar__header-info">
					<div class="intranet-widget-skeleton__circle --avatar intranet-widget-skeleton-avatar__avatar"></div>
					<div class="intranet-widget-skeleton-avatar__user-info">
						<div class="intranet-widget-skeleton-avatar__name-wrapper">
							<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__name"></div>
							<div class="intranet-widget-skeleton__circle intranet-widget-skeleton-avatar__status-circle"></div>
						</div>
						<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__department"></div>
					</div>
				</div>
			</div>
		`;

		if (config.isAdmin)
		{
			Dom.append(Tag.render`<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__action-button"></div>`, wrapper);
		}

		if (config.hasTimeman)
		{
			Dom.append(this.#createAvatarWidgetTimemanSkeleton(), wrapper);
		}

		if (config.hasTools)
		{
			Dom.append(this.#createAvatarWidgetToolsSkeleton(), wrapper);
		}

		return wrapper;
	}

	#createAvatarWidgetTimemanSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__timeman">
				<div class="intranet-widget-skeleton-avatar__timeman-top">
					<div class="intranet-widget-skeleton-avatar__timeman-line1"></div>
					<div class="intranet-widget-skeleton-avatar__timeman-line2"></div>
					<div class="intranet-widget-skeleton__circle intranet-widget-skeleton-avatar__timeman-circle"></div>
					<div class="intranet-widget-skeleton-avatar__timeman-line3"></div>
				</div>
				<div class="intranet-widget-skeleton-avatar__timeman-bottom"></div>
			</div>
		`;
	}

	#createAvatarWidgetToolsSkeleton(): HTMLElement
	{
		const toolsWrapper = Tag.render`
			<div class="intranet-widget-skeleton-avatar__tools">
				<div class="intranet-widget-skeleton__cubes">
					<div class="intranet-widget-skeleton__cube"></div>
					<div class="intranet-widget-skeleton__cube"></div>
					<div class="intranet-widget-skeleton__cube"></div>
					<div class="intranet-widget-skeleton__cube"></div>
				</div>
			</div>
		`;

		const labelsWrapper = Tag.render`
			<div class="intranet-widget-skeleton-avatar__tools-labels">
				<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__tools-label"></div>
				<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__tools-label"></div>
				<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__tools-label"></div>
				<div class="intranet-widget-skeleton__line intranet-widget-skeleton-avatar__tools-label"></div>
			</div>
		`;

		Dom.append(labelsWrapper, toolsWrapper);

		return toolsWrapper;
	}

	#createApplicationSectionSkeleton(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-widget-skeleton__row">
				<div class="intranet-widget-skeleton__item --column">
					<div class="intranet-widget-skeleton__nested-item intranet-widget-skeleton-application-section__wrapper">
						<i class="intranet-widget-skeleton-application-section__qr ui-icon-set --o-qr-code"></i>
						<div class="intranet-widget-skeleton-application-section__text">
							<div class="intranet-widget-skeleton__line intranet-widget-skeleton-application-section__title"></div>
							<div class="intranet-widget-skeleton__line intranet-widget-skeleton-application-section__button"></div>
						</div>
					</div>
					<div class="intranet-widget-skeleton__nested-item">
						<div class="intranet-widget-skeleton__line intranet-widget-skeleton-application-section__description">
							<div class="intranet-widget-skeleton-application-section__desc-line"></div>
							<div class="intranet-widget-skeleton-application-section__desc-line"></div>
						</div>
					</div>
					<div class="intranet-widget-skeleton__nested-item">
						<div class="intranet-widget-skeleton__cube intranet-widget-skeleton-application-section__nested-cube"></div>
						<div class="intranet-widget-skeleton__line intranet-widget-skeleton-application-section__nested-line"></div>
						<div class="intranet-widget-skeleton__circle intranet-widget-skeleton-application-section__nested-circle"></div>
					</div>
				</div>
			</div>
		`;
	}
}
