import { Config } from "./config";
import type { ConfigItemData } from "./config-item";
import { ConfigItem } from "./config-item";
import { SchemeItem } from "./scheme-item";
import { CategoryList } from "crm.category-list";
import { CategoryModel } from "crm.category-model";
import { Button, ButtonColor } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';
import { Popup } from "main.popup";
import { ajax as Ajax, Loc, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'ui.forms';

declare type CategorySelectResult = {
	isCanceled?: boolean,
	category?: CategoryModel|null,
};

declare type CollectAdditionalDataResult = {
	isCanceled: boolean,
};

export type ConverterParams = {
	serviceUrl: string,
	originUrl: string,
	isRedirectToDetailPageEnabled?: boolean,
	messages: Object<string, string>
};

/**
 * @memberOf BX.Crm.Conversion
 */
export class Converter
{
	#entityTypeId: number;
	#entityId: number;
	#config: Config;
	#params: ConverterParams;
	#isProgress: boolean;
	#isSynchronisationAllowed: boolean;
	#fieldsSynchronizer: BX.CrmEntityFieldSynchronizationEditor|null;

	constructor(
		entityTypeId: number,
		config: Config,
		params?: ConverterParams,
	)
	{
		this.#entityTypeId = Number(entityTypeId);
		if (config instanceof Config)
		{
			this.#config = config;
		}
		else
		{
			console.error('Config is invalid in Converter constructor. Expected instance of Config, got ' + (typeof config));
		}
		this.#params = params ?? {};

		this.#isProgress = false;
		this.#isSynchronisationAllowed = false;
		this.#entityId = 0;
	}

	getEntityTypeId(): number
	{
		return this.#entityTypeId;
	}

	getConfig(): Config
	{
		return this.#config;
	}

	getServiceUrl()
	{
		let serviceUrl = this.#params.serviceUrl;
		if (!serviceUrl)
		{
			return null;
		}

		const additionalParams = {
			action: "convert",
		};

		this.getConfig().getItems().forEach((item: ConfigItem) => {
			additionalParams[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.isActive() ? "Y" : "N";
		});

		return BX.util.add_url_param(serviceUrl, additionalParams);
	}

	getOriginUrl(): ?string
	{
		if (this.#params && this.#params.hasOwnProperty("originUrl"))
		{
			return String(this.#params.originUrl);
		}

		return null;
	}

	isRedirectToDetailPageEnabled(): boolean
	{
		if (this.#params && this.#params.hasOwnProperty("isRedirectToDetailPageEnabled"))
		{
			return this.#params.isRedirectToDetailPageEnabled;
		}

		return true;
	}

	convert(entityId: number, data?: Object): void
	{
		this.#entityId = entityId;
		this.data = data;
		const schemeItem = this.#config.getScheme().getCurrentItem();
		if (!schemeItem)
		{
			console.error('Scheme is not found');
			return;
		}

		this.#collectAdditionalData(schemeItem).then((result: CollectAdditionalDataResult) => {
			if (result.isCanceled)
			{
				return;
			}

			this.#request();

		}).catch((error) => {
			if (error)
			{
				console.error(error);
			}
		});
	}

	#request(): void
	{
		const serviceUrl = this.getServiceUrl();
		if (!serviceUrl)
		{
			console.error('Convert endpoint is not specifier');
			return;
		}
		if (this.#isProgress)
		{
			console.error('Another request is in progress');
			return;
		}
		this.#isProgress = true;
		Ajax({
			url: serviceUrl,
			method: "POST",
			dataType: "json",
			data:
				{
					"MODE": "CONVERT",
					"ENTITY_ID": this.#entityId,
					"ENABLE_SYNCHRONIZATION": this.#isSynchronisationAllowed ? "Y" : "N",
					"ENABLE_REDIRECT_TO_SHOW": this.isRedirectToDetailPageEnabled ? "Y" : "N",
					"CONFIG": this.getConfig().externalize(),
					"CONTEXT": this.data,
					"ORIGIN_URL": this.getOriginUrl()
				},
			onsuccess: this.#onRequestSuccess.bind(this),
			onfailure: this.#onRequestError.bind(this),
		});
	}

	#onRequestSuccess(response)
	{
		// todo return promise
		this.#isProgress = false;
		if (response.ERROR)
		{
			MessageBox.alert(response.ERROR.MESSAGE || "Error during conversion");
			return;
		}
		if (Type.isPlainObject(response.REQUIRED_ACTION))
		{
			return this.#processRequiredAction(response.REQUIRED_ACTION);
		}

		const data = Type.isPlainObject(response.DATA) ? response.DATA : {};
		if (!data)
		{
			return;
		}

		const redirectUrl = Type.isString(data.URL) ? data.URL : "";
		let isRedirected = false;
		if (data.IS_FINISHED && data.IS_FINISHED === "Y")
		{
			this.data = {};

			isRedirected = this.#emitConvertedEvent(redirectUrl);
		}

		if(redirectUrl !== "" && !isRedirected)
		{
			BX.Crm.Page.open(redirectUrl);
		}
		else if(!(isRedirected && window.top === window))
		{
			// window.location.reload();
		}
	}

	#onRequestError(error: string)
	{
		this.#isProgress = false;
		MessageBox.alert(error);
	}

	#collectAdditionalData(schemeItem: SchemeItem): Promise<CollectAdditionalDataResult,string>
	{
		const config = this.getConfig();

		const promises = [];

		schemeItem.getEntityTypeIds().forEach((entityTypeId) => {
			promises.push(() => {
				return this.#getCategoryForEntityTypeId(entityTypeId);
			});
		});

		let result: CollectAdditionalDataResult = {
			isCanceled: false,
		};
		const promiseIterator = ((promises: Array, index: number = 0) => {
			return new Promise((resolve, reject) => {
				if (result.isCanceled || !promises[index])
				{
					resolve(result);
					return;
				}
				promises[index]().then((categoryResult: CategorySelectResult) => {
					if (categoryResult.isCanceled)
					{
						result.isCanceled = true;
					}
					else if (categoryResult.category)
					{
						const entityTypeId = categoryResult.category.getEntityTypeId();
						const configItem = config.getItemByEntityTypeId(entityTypeId);
						if (!configItem)
						{
							reject('Scheme is not correct: configItem is not found for ' + entityTypeId);
							return;
						}
						const initData = configItem.getInitData();
						initData.categoryId = categoryResult.category.getId();
						configItem.setInitData(initData);
					}

					resolve(promiseIterator(promises, ++index));
				});
			})
		});

		return promiseIterator(promises);
	}

	#getCategoryForEntityTypeId(entityTypeId): Promise<CategorySelectResult, string>
	{
		return new Promise((resolve, reject) => {
			const configItem = this.getConfig().getItemByEntityTypeId(entityTypeId);
			if (!configItem)
			{
				reject('Scheme is not correct: configItem is not found for ' + entityTypeId);
				return;
			}
			if (this.#isNeedToLoadCategories(entityTypeId))
			{
				CategoryList.Instance.getItems(entityTypeId).then((categories: CategoryModel[]) => {
					if (categories.length > 1)
					{
						this.#showCategorySelector(categories, configItem.getTitle()).then(resolve).catch(reject);
					}
					else
					{
						resolve({
							isCanceled: false,
							category: categories[0],
						});
					}
				}).catch(reject);
			}
			else
			{
				resolve({isCanceled:false, category:null});
			}
		});
	}

	#isNeedToLoadCategories(entityTypeId: number): boolean
	{
		// todo pass isCategoriesEnabled from backend
		return (
			entityTypeId === BX.CrmEntityType.enumeration.deal
			|| BX.CrmEntityType.isDynamicTypeByTypeId(entityTypeId)
		);
	}

	#showCategorySelector(categories: CategoryModel[], title: string): Promise<CategorySelectResult,string>
	{
		return new Promise((resolve) => {
			const categorySelectorContent: HTMLElement = Tag.render`
				<div class="crm-converter-category-selector ui-form ui-form-line">
					<div class="ui-form-row">
						<div class="crm-converter-category-selector-label ui-form-label">
							<div class="ui-ctl-label-text">${Loc.getMessage("CRM_COMMON_CATEGORY")}</div>
						</div>
						<div class="ui-form-content">
							<div class="crm-converter-category-selector-select ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element"></select>
							</div>
						</div>
					</div>
				</div>
			`;

			const select = categorySelectorContent.querySelector('select');
			categories.forEach((category) => {
				select.appendChild(Tag.render`<option value="${category.getId()}">${Text.encode(category.getName())}</option>`);
			});

			const popup = new Popup({
				titleBar: Loc.getMessage("CRM_CONVERSION_CATEGORY_SELECTOR_TITLE", {
					'#ENTITY#': Text.encode(title),
				}),
				content: categorySelectorContent,
				closeByEsc: true,
				closeIcon: true,
				buttons: [
					new Button({
						text: Loc.getMessage("CRM_COMMON_ACTION_SAVE"),
						color: ButtonColor.SUCCESS,
						onclick: () => {
							const value = Array.from(select.selectedOptions)[0].value;

							popup.destroy();

							for (const category of categories)
							{
								if (category.getId() === Number(value))
								{
									resolve({category});
									return true;
								}
							}
							console.error('Selected category not found');
							resolve({isCanceled: true});

							return true;
						},
					}),
					new Button({
						text: Loc.getMessage("CRM_COMMON_ACTION_CANCEL"),
						color: ButtonColor.LIGHT,
						onclick: () => {
							popup.destroy();

							resolve({isCanceled: true});
							return true;
						},
					}),
				],
				events: {
					onClose: () =>
					{
						resolve({isCanceled: true});
					}
				}
			});

			popup.show();
		});
	}

	#processRequiredAction(action: Object): void
	{
		const name = String(action.NAME);
		const data = Type.isPlainObject(action.DATA) ? action.DATA : {};

		if(name === "SYNCHRONIZE")
		{
			let newConfig: ConfigItemData[]|null = null;
			if (Type.isArray(data.CONFIG))
			{
				newConfig = data.CONFIG;
			}
			else if (Type.isPlainObject(data.CONFIG))
			{
				newConfig = Object.values(data.CONFIG);
			}

			if (newConfig)
			{
				this.#config.updateItems(newConfig);
			}

			this.#getFieldsSynchronizer(Type.isArray(data.FIELD_NAMES) ? data.FIELD_NAMES : []).show();

			return;
		}
		if(name === "CORRECT")
		{
			if(Type.isPlainObject(data.CHECK_ERRORS))
			{
				// todo this is actual for leads only.
				// this.openEntityEditorDialog(
				// 	{
				// 		title: manager ? manager.getMessage("checkErrorTitle") : null,
				// 		helpData: { text: manager.getMessage("checkErrorHelp"), code: manager.getMessage("checkErrorHelpArticleCode") },
				// 		fieldNames: Object.keys(checkErrors),
				// 		initData: BX.prop.getObject(data, "EDITOR_INIT_DATA", null),
				// 		context: BX.prop.getObject(data, "CONTEXT", null)
				// 	}
				// );
				return;
			}
		}
	}

	#getFieldsSynchronizer(fieldNames: string[])
	{
		if (this.#fieldsSynchronizer)
		{
			this.#fieldsSynchronizer.setConfig(this.#config.externalize());
			this.#fieldsSynchronizer.setFieldNames(fieldNames);

			return this.#fieldsSynchronizer;
		}

		this.#fieldsSynchronizer = BX.CrmEntityFieldSynchronizationEditor.create(
			"crm_converter_fields_synchronizer_" + this.getEntityTypeId(),
			{
				config: this.#config.externalize(),
				title: this.#getMessage("dialogTitle"),
				fieldNames: fieldNames,
				legend: this.#getMessage("syncEditorLegend"),
				fieldListTitle: this.#getMessage("syncEditorFieldListTitle"),
				entityListTitle: this.#getMessage("syncEditorEntityListTitle"),
				continueButton: this.#getMessage("continueButton"),
				cancelButton: this.#getMessage("cancelButton"),
			}
		);
		this.#fieldsSynchronizer.addClosingListener((sender, args) => {
			if(!(Type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			this.#isSynchronisationAllowed = true;
			this.#config.updateItems(Object.values(this.#fieldsSynchronizer.getConfig()));
			this.#request();
		});

		return this.#fieldsSynchronizer;
	}

	#getMessage(phraseId): string
	{
		if (!this.#params.messages)
		{
			this.#params.messages = {};
		}
		return this.#params.messages[phraseId] || phraseId;
	}

	#emitConvertedEvent(redirectUrl): boolean
	{
		const entityTypeId = this.getEntityTypeId();

		const eventArgs = {
			entityTypeId,
			entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
			entityId: this.#entityId,
			redirectUrl,
			isRedirected: false,
		};

		var current = BX.Crm.Page.getTopSlider();
		if(current)
		{
			eventArgs["sliderUrl"] = current.getUrl();
		}

		BX.onCustomEvent(window, "Crm.EntityConverter.Converted", [ this, eventArgs ]);
		BX.localStorage.set("onCrmEntityConvert", eventArgs, 10);

		this.getConfig().getItems().forEach((item) => {
			if (item.isActive())
			{
				EventEmitter.emit('Crm.EntityConverter.SingleConverted', {
					entityTypeName: BX.CrmEntityType.resolveName(item.getEntityTypeId()),
				});
			}
		});

		return eventArgs["isRedirected"];
	}
}
