import { DefaultFooter } from 'ui.entity-selector';
import { Loc, Tag, Event, Uri } from 'main.core';

export default class TagFooter extends DefaultFooter
{
	getContent(): HTMLElement | HTMLElement[] | string | null
	{
		return this.cache.remember('tag-footer-content', () => {
			const createButton = Tag.render`
				<a class="ui-selector-footer-link ui-selector-footer-link-add"  
					id="tags-widget-custom-footer-add-new" hidden>
						${Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_CREATE')}
				</a>
			`;

			Event.bind(createButton, 'click', () => this.createItem());

			const openTagListButton = Tag.render`
				<a class="ui-selector-footer-link">
					${Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_GET_TAG_SLIDER')}
				</a>
			`;

			Event.bind(
				openTagListButton,
				'click',
				() => {
					const sliderLink = new Uri(
						'/bitrix/components/bitrix/biconnector.apachesuperset.dashboard.tag.list/slider.php',
					);

					top.BX.SidePanel.Instance.open(
						sliderLink.toString(),
						{
							width: 970,
							allowChangeHistory: false,
							cacheable: false,
						},
					);
				},
			);

			return Tag.render`
				<div class="tags-widget-custom-footer">
					${createButton}
					<span class="ui-selector-footer-conjunction" 
						id="tags-widget-custom-footer-conjunction" hidden>
							${Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_OR')}
					</span>
					${openTagListButton}
				</div>
			`;
		});
	}

	createItem(): void
	{
		if (!this.canCreateTag())
		{
			return;
		}

		const tagSelector = this.getDialog().getTagSelector();
		if (tagSelector && tagSelector.isLocked())
		{
			return;
		}

		const finalize = () => {
			if (this.getDialog().getTagSelector())
			{
				this.getDialog().getTagSelector().unlock();
				this.getDialog().focusSearch();
			}
		};

		if (tagSelector)
		{
			tagSelector.lock();
		}

		this.getDialog()
			.emitAsync('Search:onItemCreateAsync', {
				searchQuery: this.getDialog().getSearchTab().getLastSearchQuery(),
			})
			.then(() => {
				this.getDialog().getSearchTab().clearResults();
				this.getDialog().clearSearch();
				if (this.getDialog().getActiveTab() === this.getTab())
				{
					this.getDialog().selectFirstTab();
				}

				finalize();
			})
			.catch(() => {
				finalize();
			})
		;
	}

	canCreateTag(): boolean
	{
		return this.options?.canCreateTag ?? false;
	}
}
