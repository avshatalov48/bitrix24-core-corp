import {Type, Text} from 'main.core';
import {PopupMenu} from 'main.popup';
import {Vue} from 'ui.vue';
import {Manager} from 'salescenter.manager';
import './url_popup.css';
import 'ui.forms';

Vue.component('bx-salescenter-url-popup',
	{
		data()
		{
			return {
				urlCheckStatus: 0,
				errorMessage: null,
				previousUrl: null,
				isEmptyNameOnSave: false,
				isDefaultName: false,
				previousName: null,
				fieldsPopupMenuId: 'salescenter-url-fields-popup',
				params: [],
				fieldsMap: null,
			};
		},
		created()
		{
			this.$root.$on('onAddUrlPopupCreate', this.setPageData);
			this.previousUrl = new Set();

			let checkUrlDebounce = BX.debounce(this.checkUrl, 1500, this);
			this.debounceCheckUrl = () =>
			{
				let url = this.$refs['urlInput'].value;
				if(this.previousUrl.has(url))
				{
					return;
				}
				this.errorMessage = null;
				this.urlCheckStatus = 0;
				checkUrlDebounce();
			};
		},
		mounted()
		{
			setTimeout(() =>
			{
				this.$refs['urlInput'].focus();
			}, 100);
		},
		beforeDestroy()
		{
			this.$root.$off('onAddUrlPopupCreate', this.setPageData);
		},
		methods:
		{
			onTitleKeyUp()
			{
				if(this.isDefaultName && this.$refs['nameInput'].value !== this.previousName)
				{
					this.isDefaultName = false;
					this.previousName = this.$refs['nameInput'].value;
				}
			},
			checkUrl()
			{
				let url = this.$refs['urlInput'].value;
				this.$refs['isFrameDeniedInput'].value = '';
				this.errorMessage = null;
				this.urlCheckStatus = 0;
				this.previousUrl = new Set();
				this.previousUrl.add(url);
				if(url.length < 5)
				{
					return;
				}
				this.urlCheckStatus = 1;
				this.$root.$app.checkUrl(url).then((result) =>
				{
					this.urlCheckStatus = 11;
					if(!result.answer.result)
					{
						//this.urlCheckStatus = 23;
						if(url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0)
						{
							this.$refs['urlInput'].value = 'http://' + url;
						}
						this.errorMessage = this.localize.SALESCENTER_ACTION_ADD_CUSTOM_NO_META;
					}
					else
					{
						if(this.$refs['nameInput'].value.length <= 0 || this.isDefaultName)
						{
							this.$refs['nameInput'].value = result.answer.result.title;
							this.isDefaultName = true;
							this.previousName = this.$refs['nameInput'].value
						}
						if(result.answer.result.extra && result.answer.result.extra.effectiveUrl)
						{
							this.$refs['urlInput'].value = result.answer.result.extra.effectiveUrl;
							this.previousUrl.add(result.answer.result.extra.effectiveUrl);
						}
						else
						{
							this.$refs['urlInput'].value = result.answer.result.url;
						}
						if(result.answer.result.isFrameDenied === true)
						{
							this.$refs['isFrameDeniedInput'].value = 'Y';
						}
						this.$refs['nameInput'].focus();
					}
				}).catch((result) =>
				{
					this.urlCheckStatus = 22;
					this.errorMessage = result.answer.error_description;
				});
			},
			save()
			{
				this.isEmptyNameOnSave = false;
				if(this.$refs['nameInput'].value.length <= 0)
				{
					this.isEmptyNameOnSave = true;
					return;
				}
				if(this.urlCheckStatus > 10 && this.urlCheckStatus < 20)
				{
					let params = [];
					this.params.forEach((param) =>
					{
						params.push(param.chain);
					});
					if(params.length <= 0)
					{
						params = 'false';
					}
					this.$root.$app.addPage({
						url: this.$refs['urlInput'].value,
						name: this.$refs['nameInput'].value,
						id: this.$refs['idInput'].value,
						isFrameDenied: this.$refs['isFrameDeniedInput'].value,
						isWebform: this.$refs['isWebformInput'].value,
						params,
					}).then((result) =>
					{
						this.$refs['idInput'].value = result.answer.result.page.id;
						this.$refs['isSaved'].value = 'y';
						if(this.$root.$app.addUrlPopup)
						{
							this.$root.$app.addUrlPopup.close();
						}
					}).catch((result) =>
					{
						this.errorMessage = result.answer.error_description;
					});
				}
			},
			cancel()
			{
				if(this.$root.$app.addUrlPopup)
				{
					this.$root.$app.addUrlPopup.close();
				}
			},
			setPageData(page = {
				url: '',
				name: '',
				id: 0,
				isWebform: '',
				params: [],
			})
			{
				const fields = ['url', 'name', 'id', 'isWebform'];
				for(let i in fields)
				{
					let name = fields[i];
					let value = '';
					if(page.hasOwnProperty(name))
					{
						value = page[name];
					}
					let input = this.$refs[name + 'Input'];
					if(input)
					{
						input.value = value;
					}
				}
				this.checkUrl();
				this.$refs['isSaved'].value = 'n';
				this.params = [];
				if(Type.isArray(page.params) && page.params.length > 0)
				{
					Manager.getFieldsMap().then((fields) =>
					{
						page.params.forEach((param) =>
						{
							let field = this.findFieldByParam(fields, param.field);
							if(field)
							{
								this.params.push(field);
							}
						});
					});
				}
			},
			findFieldByParam(fields, param)
			{
				if(!Type.isArray(fields) || !Type.isString(param) || param.length <= 0)
				{
					return null;
				}
				return this.getEntityField({items: fields}, param);
			},
			getEntityField(entity, chain)
			{
				let result = null;
				if(!Type.isPlainObject(entity) || !Type.isString(chain) || chain.length <= 0 || !entity.items || !Type.isArray(entity.items))
				{
					return result;
				}
				let parts = chain.split('.');
				entity.items.forEach((field) =>
				{
					if(!result)
					{
						if(field.name === parts[0])
						{
							if(parts.length === 1)
							{
								result = field;
							}
							else
							{
								parts.shift();
								result = this.getEntityField(field, parts.join('.'));
							}
						}
					}
				});

				return result;
			},
			openFieldsMenu()
			{
				Manager.getFieldsMap().then((fields) =>
				{
					fields = this.prepareFieldsMenu(fields);
					PopupMenu.show({
						id: this.fieldsPopupMenuId,
						bindElement: this.$refs['fieldsSelector'],
						items: fields,
						offsetLeft: 0,
						offsetTop: 0,
						closeByEsc: true,
						zIndex: 2000,
						zIndexAbsolute: 2000,
						maxHeight: 500,
					});
				}).catch((errors) =>
				{
					if(Type.isArray(errors))
					{
						this.errorMessage = errors.pop().message;
					}
					else
					{
						this.errorMessage = errors;
					}
				});
			},
			prepareFieldsMenu(fields)
			{
				const result = [];
				fields.forEach((item) =>
				{
					const menu = {
						text: Text.encode(item.title),
						dataset: {
							rootMenu: this.fieldsPopupMenuId,
						},
					};
					if(Type.isArray(item.items))
					{
						menu.items = this.prepareFieldsMenu(item.items);
					}
					else
					{
						menu.onclick = () =>
						{
							this.selectField(item);
						}
					}
					result.push(menu);
				});

				return result;
			},
			selectField(field)
			{
				this.removeParam(field);
				this.params.push(field);
			},
			removeParam(param)
			{
				this.params = this.params.filter((item) =>
				{
					return (item.chain !== param.chain);
				});
			}
		},
		computed:
		{
			localize()
			{
				return Vue.getFilteredPhrases('SALESCENTER_');
			},
			hasParams()
			{
				return (this.params && this.params.length > 0);
			},
		},
		template: `
		<div class="salescenter-add-custom-url-popup">
			<div class="salescenter-add-custom-url-popup-form">
				<div class="ui-ctl-label-text">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_INPUT}}</div>
				<div class="ui-ctl ui-ctl-ext-after-icon ui-ctl-textbox" :class="{
					'ui-ctl-danger': this.urlCheckStatus > 20 || errorMessage, 
					'ui-ctl-success': this.urlCheckStatus > 10 && this.urlCheckStatus < 20
				}">
					<div class="ui-ctl-ext-after ui-ctl-icon-dots salescenter-url-params-selector" @click="openFieldsMenu" ref="fieldsSelector"></div>
					<input name="url" class="ui-ctl-element" @keyup="debounceCheckUrl" ref="urlInput" :placeholder="localize.SALESCENTER_ACTION_ADD_CUSTOM_INPUT_PLACEHOLDER" autocomplete="off"/>
				</div>
				<div style="min-height: 20px;">
					<template v-if="urlCheckStatus > 20 || errorMessage" class="salescenter-url-message">{{errorMessage}}</template>
					<template v-else-if="urlCheckStatus > 10"></template>
					<template v-else-if="urlCheckStatus > 0">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_PENDING}}</template>
				</div>
				<label class="ui-ctl" :class="{
					'hidden': !this.hasParams
				}">
					<div class="ui-ctl-label-text">{{localize.SALESCENTER_ACTION_URL_PARAMS}}</div>
					<div class="salescenter-url-params-field">
						<div v-for="param in params" class="salescenter-url-param">
							<span class="salescenter-url-param-name">{{param.fullName}}</span>
							<span class="salescenter-url-param-delete" @click="removeParam(param)"></span>
					    </div>
				    </div>
				</label>
				<div class="ui-ctl-label-text">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_NAME}}</div>
				<label class="ui-ctl ui-ctl-textbox" :class="{
					'ui-ctl-danger': this.isEmptyNameOnSave
				}">
					<input class="ui-ctl-element" name="name" @keyup="onTitleKeyUp" ref="nameInput" :placeholder="localize.SALESCENTER_ACTION_ADD_CUSTOM_NAME_PLACEHOLDER" autocomplete="off"/>
				</label>
				<input type="hidden" name="id" value="" ref="idInput" id="salescenter-app-add-custom-url-id" />
				<input type="hidden" name="isFrameDenied" value="" ref="isFrameDeniedInput"/>
				<input type="hidden" name="isWebform" value="" ref="isWebformInput"/>
				<input type="hidden" name="isSaved" value="n" id="salescenter-app-add-custom-url-is-saved" ref="isSaved" />
			</div>
			<div class="popup-window-buttons salescenter-add-custom-url-popup-buttons">
				<button :class="{
					'ui-btn-disabled': this.urlCheckStatus < 10 || this.urlCheckStatus > 20
				}" class="ui-btn ui-btn-md ui-btn-success" @click="save">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_SAVE}}</button>
				<button class="ui-btn ui-btn-md ui-btn-link" @click="cancel">{{localize.SALESCENTER_CANCEL}}</button>
			</div>
		</div>
	`,
	});