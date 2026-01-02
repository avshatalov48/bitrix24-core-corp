import { Cache, Type, Tag } from 'main.core';
import { Button } from 'ui.buttons';
import type { WidgetPackageDataType } from './types/widget-package-data-type';
import { BaseTemplate } from 'ui.popup-with-header';
import { PackageItemFactory } from './package-item-factory';

export type ResultContent = {
	html: HTMLElement,
	backgroundColor?: string,
	margin?: string,
};

export class PackageTemplateManager extends BaseTemplate
{
	#cache = new Cache.MemoryCache();
	#items: WidgetPackageDataType[] = [];

	constructor(options?: { items: WidgetPackageDataType[]})
	{
		super();
		this.#items = Type.isPlainObject(options) ? options.items : [];
	}

	setOptions(data: {[key: string]: any }): void
	{
		if (data.items)
		{
			this.#items = (Type.isPlainObject(data.items) ? Object.values(data.items)
				: (Type.isArray(data.items) ? data.items : [])
			);
		}

		super.setOptions(data);
	}

	getContent(): Array<ResultContent>
	{
		return this.#cache.remember('popup-content', () => {
			const factory = new PackageItemFactory();

			return this.#items.map((serviceData: WidgetPackageDataType, index) => {
				const item = factory.create(serviceData);

				return {
					html: item.getContainer(),
					background: serviceData.styles?.background,
					margin: index === 0 ? '12px 0 0 0' : null,
				};
			});
		});
	}
}
