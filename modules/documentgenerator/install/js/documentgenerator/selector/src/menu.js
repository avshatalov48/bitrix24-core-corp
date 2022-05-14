import {ajax as Ajax, Type, Loc, Runtime} from 'main.core';
import {Loader} from 'main.loader';
import {PopupMenu, PopupWindow} from 'main.popup';
import {Template} from "./template";
import {Document} from "./document";

import 'documentpreview';

import './menu.css';

export class Menu
{
	progress = false;
	templates = null;
	documents = null;
	analyticsLabelPrefix = 'documentgeneratorSelector';
	loader;
	node;
	moduleId;
	provider;
	value;
	isDocumentsLimitReached = false;

	constructor(params)
	{
		if(Type.isPlainObject(params))
		{
			if(Type.isDomNode(params.node))
			{
				this.node = params.node;
			}
			if(Type.isString(params.moduleId))
			{
				this.moduleId = params.moduleId;
			}
			if(Type.isString(params.provider))
			{
				this.provider = params.provider;
			}
			if(Type.isString(params.analyticsLabelPrefix))
			{
				this.analyticsLabelPrefix = params.analyticsLabelPrefix;
			}
			if(Type.isString(params.value) || Type.isNumber(params.value))
			{
				this.value = params.value;
			}
		}
	}

	isValid(): boolean
	{
		return (
			(Type.isString(this.moduleId) && this.moduleId.length > 0) &&
			(Type.isString(this.provider) && this.provider.length > 0) &&
			(!Type.isNil(this.value))
		);
	}

	createDocument(template)
	{
		return new Promise((resolve, reject) =>
		{
			if(this.progress)
			{
				reject('loading');
			}
			if(this.isValid() && template instanceof Template)
			{
				this.progress = true;
				this.showLoader();
				BX.DocumentGenerator.Document.askAboutUsingPreviousDocumentNumber(this.provider, template.getId(), this.value, (previousNumber) =>
				{
					const data = {
						templateId: template.getId(),
						providerClassName: this.provider,
						value: this.value,
						values: {}
					};
					if(previousNumber)
					{
						data.values.DocumentNumber = previousNumber;
					}
					Ajax.runAction('documentgenerator.document.add', {
						data: data,
						analyticsLabel: this.analyticsLabelPrefix + 'CreateDocument',
					}).then((response) =>
					{
						this.progress = false;
						this.hideLoader();
						const document = Document.create(response.data.document);
						if(document)
						{
							if(Type.isArray(this.documents))
							{
								this.documents.unshift(document);
							}
							resolve(document);
						}
						else
						{
							reject('error trying create document object');
						}
					}).catch((response) =>
					{
						this.progress = false;
						this.hideLoader();
						reject(this.getErrorMessageFromResponse(response));
					});
				}, () =>
				{
					this.progress = false;
					this.hideLoader();
				});
			}
			else
			{
				reject('error trying generate document');
			}
		});
	}

	getDocumentPublicUrl(document)
	{
		return new Promise((resolve, reject) =>
		{
			if(!(document instanceof Document))
			{
				reject('wrong document');
				return;
			}
			if(Type.isString(document.getPublicUrl()) && document.getPublicUrl().length > 0)
			{
				resolve(document.getPublicUrl());
			}
			else
			{
				if(this.progress)
				{
					reject('loading');
				}
				else
				{
					this.progress = true;
					this.showLoader();
					Ajax.runAction('documentgenerator.document.enablePublicUrl', {
						data: {
							id: document.getId(),
							status: 1,
						},
						analyticsLabel: this.analyticsLabelPrefix + 'GetPublicUrl',
					}).then((response) =>
					{
						this.progress = false;
						this.hideLoader();
						document.data.publicUrl = response.data.publicUrl;
						resolve(document.getPublicUrl());
					}).catch((response) =>
					{
						this.progress = false;
						this.hideLoader();
						reject(this.getErrorMessageFromResponse(response));
					});
				}
			}
		});
	}

	show(node = null): Promise
	{
		return new Promise((resolve, reject) =>
		{
			if(!node)
			{
				node = this.node;
			}
			this.getTemplates().then((templates) =>
			{
				PopupMenu.show(this.getPopupMenuId(), node, this.prepareTemplatesList(templates, (object) =>
				{
					const menu = PopupMenu.getMenuById(this.getPopupMenuId());
					if(menu)
					{
						menu.destroy();
					}
					resolve(object);
				}), {
					offsetLeft: 0,
					offsetTop: 0,
					closeByEsc: true,
				});
			}).catch((error) =>
			{
				if(error !== 'loading')
				{
					reject(error);
				}
			});
		});
	}

	getTemplates(): Promise
	{
		return new Promise((resolve, reject) =>
		{
			if(!this.isValid())
			{
				reject('wrong data');
				return;
			}
			if(this.templates === null)
			{
				if(this.progress)
				{
					reject('loading');
					return;
				}
				this.progress = true;
				this.showLoader();
				Ajax.runAction('documentgenerator.api.document.getButtonTemplates', {
					data: {
						moduleId: this.moduleId,
						provider: this.provider,
						value: this.value,
					},
					analyticsLabel: this.analyticsLabelPrefix + 'LoadTemplates',
				}).then((response) =>
				{
					this.progress = false;
					this.hideLoader();
					this.parseButtonResponse(response);
					resolve(this.templates);
				}).catch((response) =>
				{
					this.progress = false;
					this.hideLoader();
					reject(this.getErrorMessageFromResponse(response));
				});
			}
			else
			{
				resolve(this.templates);
			}
		});
	}

	getDocuments(node): Promise
	{
		return new Promise((resolve, reject) =>
		{
			if(this.progress)
			{
				reject('loading');
				return;
			}
			if(this.documents === null)
			{
				this.documents = [];
				this.progress = true;
				this.showLoader(node);
				Ajax.runAction('documentgenerator.document.list', {
					data: {
						select: ['id', 'number', 'title'],
						filter: {
							"=provider": this.provider.toLowerCase(),
							"=value": this.value
						},
						order: {id: 'desc'}
					},
					analyticsLabel: this.analyticsLabelPrefix + 'LoadDocuments',
				}).then((response) =>
				{
					this.progress = false;
					this.hideLoader();
					response.data.documents.forEach((data) =>
					{
						let document = Document.create(data);
						if(document)
						{
							this.documents.push(document);
						}
					});
					resolve(this.documents);
				}).catch((response) =>
				{
					this.progress = false;
					this.hideLoader();
					reject(this.getErrorMessageFromResponse(response));
				});
			}
			else
			{
				resolve(this.documents);
			}
		});
	}

	prepareTemplatesList(templates, onclick): Array
	{
		const result = [];
		if(this.isDocumentsLimitReached)
		{
			result.push({
				text: Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_LIMIT_REACHED_ADD'),
				className: 'documentgenerator-selector-menu-item-with-lock',
				onclick: () =>
				{
					this.showTariffPopup();
					onclick(null);
				},
			})
		}
		else if(Type.isArray(templates) && Type.isFunction(onclick))
		{
			templates.forEach((template) =>
			{
				result.push({
					text: template.getName(),
					onclick: () =>
					{
						onclick(template);
					}
				})
			});
		}
		if(result.length > 0)
		{
			result.push({delimiter: true});
		}
		const selector = this;
		result.push({
			text: Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS'),
			cacheable: true,
			events: {
				onSubMenuShow: function()
				{
					if(this.isSubmenuLoaded)
					{
						return;
					}
					this.isSubmenuLoaded = true;
					const submenu = this.getSubMenu();
					const loadingItem = submenu.getMenuItem('loading');
					selector.getDocuments(loadingItem.getLayout().text).then((documents) =>
					{
						if(documents.length <= 0)
						{
							if(loadingItem)
							{
								loadingItem.getLayout().text.innerText = Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_EMPTY');
							}
						}
						else
						{
							submenu.removeMenuItem('loading');
							const menuItems = [];
							documents.forEach((document) =>
							{
								menuItems.push({
									text: document.getTitle(),
									onclick: () =>
									{
										onclick(document);
									}
								});
							});
							this.addSubMenu(menuItems);
							this.showSubMenu();
						}
					}).catch((error) =>
					{
						if(loadingItem)
						{
							loadingItem.getLayout().text.innerText = error;
						}
					});
				}
			},
			items: [
				{
					id: 'loading',
					text: Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_LOADING')
				},
			]
		});

		return result;
	}

	parseButtonResponse(response): Array
	{
		this.templates = [];

		if(response.data && response.data.isDocumentsLimitReached)
		{
			this.isDocumentsLimitReached = response.data.isDocumentsLimitReached;
		}
		if(response.data && response.data.templates && Type.isArray(response.data.templates))
		{
			response.data.templates.forEach((data) =>
			{
				let template = Template.create(data);
				if(template)
				{
					this.templates.push(template);
				}
			});
		}

		return this.templates;
	}

	getErrorMessageFromResponse(response): string
	{
		let error = '';
		if(response.errors && Type.isArray(response.errors))
		{
			response.errors.forEach(({message}) =>
			{
				if(error.length > 0)
				{
					error += ', ';
				}
				error += message;
			});
		}

		return error;
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new Loader({size: 50});
		}

		return this.loader;
	}

	showLoader(node)
	{
		if(!Type.isDomNode(node))
		{
			node = this.node;
		}
		if(node && !this.getLoader().isShown())
		{
			this.getLoader().show(node);
		}
	}

	hideLoader()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	}

	getPopupMenuId(): string
	{
		return 'documentgenerator-selector-popup-menu';
	}

	showTariffPopup()
	{
		this.getFeatureContent().then((content) =>
		{
			this.getFeaturePopup(content).show();
		}).catch((error) =>
		{
			console.error(error);
		});
	}

	getFeaturePopup(content): PopupWindow
	{
		if(this.featurePopup != null)
		{
			return this.featurePopup;
		}
		this.featurePopup = new PopupWindow('bx-popup-documentgenerator-popup', null, {
			zIndex: 200,
			autoHide: true,
			closeByEsc: true,
			closeIcon: true,
			overlay : true,
			events : {
				onPopupDestroy : () =>
				{
					this.featurePopup = null;
				}
			},
			content : content,
			contentColor: 'white',
		});

		return this.featurePopup;
	}

	getFeatureContent()
	{
		return new Promise((resolve, reject) =>
		{
			if(this.featureContent)
			{
				resolve(this.featureContent);
				return;
			}
			Ajax.runAction('documentgenerator.document.getFeature').then((response) =>
			{
				this.featureContent = document.createElement('div');
				this.getFeaturePopup(this.featureContent);
				Runtime.html(this.featureContent, response.data.html, {
					htmlFirst: true,
				}).then(() =>
				{
					resolve(this.featureContent);
				});
			}).catch((response) =>
			{
				reject(this.getErrorMessageFromResponse(response));
			})
		});
	}
}