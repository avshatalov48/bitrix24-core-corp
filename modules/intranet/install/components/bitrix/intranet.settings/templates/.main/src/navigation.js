import { EventEmitter } from 'main.core.events';
import {Dom, Type} from 'main.core';
import {
	AscendingOpeningVisitor,
	BaseSettingsElement,
	BaseSettingsPage,
	RecursiveFilteringVisitor,
	SettingsField,
	SettingsRow,
	SettingsSection,
	TabField,
	TabsField,
} from 'ui.form-elements.field';
import { SiteTitleField } from './fields/site-title-field';
import { Settings } from './settings';
import {SiteTitle24Field} from "./fields/site-title-24-field";

export class Navigation
{
	#settings: Settings;
	#currentPage: ?BaseSettingsPage;
	#prevPage: ?BaseSettingsPage;

	constructor(settings: Settings)
	{
		this.#settings = settings;
		EventEmitter.subscribe('BX.Intranet.SettingsNavigation:onMove', (event) => {
			const {page, fieldName} = event.data;
			if (this.getCurrentPage()?.getType() === page)
			{
				this.moveTo(this.getCurrentPage(), fieldName);

				return;
			}

			const pageObj = this.getPageByType(page);

			if (!pageObj?.hasData())
			{
				EventEmitter.subscribeOnce('BX.Intranet.Settings:onPageComplete',(event) => {
					if (event.data.page.hasContent())
					{
						this.moveTo(event.data.page, fieldName);
					}
				});
			}


			EventEmitter.subscribeOnce('BX.Intranet.Settings:onAfterShowPage',(event) => {
				if (event.data.page.hasContent())
				{
					this.moveTo(event.data.page, fieldName);
				}
			});

			this.#settings.show(page);
		});
	}

	getPageByType(type: string): ?BaseSettingsPage
	{
		return this.getPages().find((page: BaseSettingsPage) => {
			return page.getType() === type;
		});
	}

	getCurrentPage(): ?BaseSettingsPage
	{
		return this.#currentPage;
	}

	getPrevPage(): ?BaseSettingsPage
	{
		return this.#prevPage;
	}

	changePage(page: BaseSettingsPage)
	{
		if (!(page instanceof BaseSettingsPage))
		{
			console.log('Not found "' + type + '" page');
			return;
		}

		if (page === this.#currentPage)
		{
			return;
		}

		this.#prevPage = this.#currentPage;
		this.#currentPage = page;
	}

	getPages(): [BaseSettingsPage]
	{
		return this.#settings.getChildrenElements();
	}

	updateAddressBar()
	{
		let url = new URL(window.location.href);
		url.searchParams.set('page', this.getCurrentPage()?.getType());
		url.searchParams.delete('IFRAME');
		url.searchParams.delete('IFRAME_TYPE');
		top.window.history.replaceState(null, '', url.toString());
	}


	findByFieldName(rootNode: BaseSettingsElement, fieldName: string): ?SettingsField
	{
		const node = RecursiveFilteringVisitor.startFrom(rootNode, (node) => {
			if (
				node instanceof SettingsSection
				&& node.getSectionView().getId() === fieldName
			)
			{
				return true;
			}
			if (
				node instanceof TabField
				&& node.getFieldView().getId() === fieldName
			)
			{
				return true;
			}

			return ((node instanceof SettingsField
				|| node instanceof SiteTitleField
					|| node instanceof SiteTitle24Field)
			&& (node.getFieldView().getName() === fieldName || node.getFieldView().getId() === fieldName))
		});

		return node.shift() ?? null;
	}

	scrollToNode(node: SettingsField): void
	{
		const element = node.render();
		const headerOffset = 45;
		const elementPosition = element.getBoundingClientRect().top;
		const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

		scrollTo({
			top: offsetPosition,
			behavior: "smooth"
		});
	}

	moveTo(element: BaseSettingsPage, fieldName: string): void
	{
		const fieldNode = this.findByFieldName(element, fieldName);
		if (Type.isNil(fieldNode))
		{
			return;
		}

		let isColored = false;
		AscendingOpeningVisitor.startFrom(fieldNode, (element) => {
			if (element instanceof SettingsRow)
			{
				element.getRowView().show();
			}
			else if (element instanceof SettingsSection)
			{
				element.getSectionView().toggle(true, false);
			}
			else if (element instanceof TabField)
			{
				const tabs = element.getParentElement();

				if (tabs instanceof TabsField)
				{
					tabs.activateTab(element, false);
				}
			}

			if (!isColored)
			{
				isColored = element.highlight();
			}
		});
		this.scrollToNode(fieldNode);
	}
}