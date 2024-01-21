import { BaseSettingsPage } from 'ui.form-elements.field';
import { Dom, Runtime, Type } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';

export class ExternalTemporaryPage extends BaseSettingsPage
{
	#type: ?string;
	#extensions: string[] = [];

	constructor(type: string, extensions: string[])
	{
		super();
		this.#type = type;
		this.#extensions = extensions;
	}

	getType(): string
	{
		return this.#type;
	}

	onSuccessDataFetched(response)
	{
		Runtime
			.loadExtension(this.#extensions)
			.then((exports) => {
				let externalPage;
				let externalPageHasBeenFound = Object.values(exports)
					.some((externalPageClassOrInstance) => {
						if (Type.isObjectLike(externalPageClassOrInstance))
						{
							let pageExemplar = null;
							if (externalPageClassOrInstance.prototype instanceof BaseSettingsPage)
							{
								pageExemplar = new externalPageClassOrInstance();
							}
							else if (externalPageClassOrInstance instanceof BaseSettingsPage)
							{
								pageExemplar = externalPageClassOrInstance;
							}

							if (pageExemplar instanceof BaseSettingsPage)
							{
								externalPage = pageExemplar;
								return true;
							}
						}

						return false;
					})
				;
				if (externalPageHasBeenFound === false)
				{
					const event = new BaseEvent();
					externalPageHasBeenFound = EventEmitter.emit(
							EventEmitter.GLOBAL_TARGET,
							'BX.Intranet.Settings:onExternalPageLoaded:' + this.getType(),
							event
						)
						.some((pageExemplar: BaseSettingsPage) =>
						{
							if (pageExemplar instanceof BaseSettingsPage)
							{
								externalPage = pageExemplar;
								return true;
							}

							return false;
						})
					;
				}

				if (externalPage instanceof BaseSettingsPage)
				{
					this.getParentElement().registerPage(externalPage);
					externalPage.setData(response.data);
					this.getParentElement().removeChild(this);

					if (Dom.isShown(this.getPage()))
					{
						externalPage.getParentElement().show(externalPage.getType());
					}
				}
				else
				{
					this.onFailDataFetched('The external page was not found.');
				}

			}, this.onFailDataFetched.bind(this))
		;
	}
}
