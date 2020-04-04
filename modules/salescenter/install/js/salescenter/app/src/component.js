import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import {Loader} from 'main.loader';
import {Type, Uri} from 'main.core';
import 'popup';
import 'ui.buttons';
import 'ui.buttons.icons';
import 'ui.forms';
import 'ui.fonts.opensans';
import 'ui.pinner';

import {config} from './config';

Vue.component(config.templateName, {
	data()
	{
		return {
			isShowPreview: false,
			isShowPayment: false,
			pageTitle: '',
			currentPageId: null,
			actions: [],
			frameCheckShortTimeout: false,
			frameCheckLongTimeout: false,
			isPagesOpen: false,
			isFormsOpen: false,
			showedPageIds: [],
			loadedPageIds: [],
			errorPageIds: [],
			lastAddedPages: [],
			ordersCount: null,
			editedPageId: null,
			isOrderPublicUrlAvailable: null,
		};
	},

	created()
	{
	},

	updated()
	{
		this.renderErrors();
	},

	mounted()
	{
		this.createPinner();
		this.createLoader();
		this.$root.$app.fillPages().then(() =>
		{
			this.refreshOrdersCount();
			this.openFirstPage();
		});
		this.isOrderPublicUrlAvailable = this.$root.$app.isOrderPublicUrlAvailable;
		this.isOrderPublicUrlExists = this.$root.$app.isOrderPublicUrlExists;

		if(this.$root.$app.isPaymentsLimitReached)
		{
			let paymentsLimitStartNode = this.$root.$nodes.paymentsLimit;
			let paymentsLimitNode = this.$refs['paymentsLimit'];
			for (let node of paymentsLimitStartNode.children)
			{
				paymentsLimitNode.appendChild(node);
			}
		}

		this.movePanels();
	},

	methods:
	{
		movePanels()
		{
			let sidepanel = this.$refs['sidebar'];
			let leftPanel = this.$root.$nodes.leftPanel;
			if(!leftPanel)
			{
				leftPanel = this.$refs['leftSide'];
			}
			else
			{
				this.$refs['leftSide'].remove();
			}

			if(sidepanel && leftPanel)
			{
				leftPanel.appendChild(sidepanel);
				BX.show(sidepanel);

				let nav = this.$refs['sidepanelNav'];
				if(nav)
				{
					leftPanel.appendChild(nav);
					BX.show(nav);
				}
			}
		},
		createPinner()
		{
			let buttonsPanel = this.$refs['buttonsPanel'];
			if(buttonsPanel)
			{
				this.$root.$el.parentNode.appendChild(buttonsPanel);
				new BX.UI.Pinner(
					buttonsPanel,
					{
						fixBottom: this.$root.$app.isFrame,
						fullWidth: this.$root.$app.isFrame
					}
				);
			}
		},
		getActions()
		{
			let actions = [];
			if(this.currentPage)
			{
				actions = [
					{text: this.localize.SALESCENTER_RIGHT_ACTION_COPY_URL, onclick: this.copyUrl},
				];
				if(this.currentPage.landingId > 0)
				{
					actions = [
						...actions,
						{text: this.localize.SALESCENTER_RIGHT_ACTION_HIDE, onclick: this.hidePage},
					];
				}
				else
				{
					actions = [
						...actions,
						{text: this.localize.SALESCENTER_RIGHT_ACTION_DELETE, onclick: this.hidePage},
					];
				}
			}
			return [
				...actions,
				{text: this.localize.SALESCENTER_RIGHT_ACTION_ADD, items: this.getAddPageActions()},
			];
		},
		getAddPageActions(isWebform = false)
		{
			return [
				{text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_SITE_B24, onclick: () =>
					{
						this.addSite(isWebform);
					}},
				{text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_CUSTOM, onclick: () =>
					{
						this.showAddUrlPopup({
							isWebform: isWebform === true ? 'Y' : null
						});
					}},
			];
		},
		openFirstPage()
		{
			this.isShowPayment = false;
			this.isShowPreview = true;
			if(this.pages && this.pages.length > 0)
			{
				let firstWebformPage = false;
				let pageToOpen = false;
				this.pages.forEach((page) =>
				{
					if(!pageToOpen)
					{
						if(!page.isWebform)
						{
							pageToOpen = page;
						}
						else
						{
							firstWebformPage = page;
						}
					}
				});
				if(!pageToOpen && firstWebformPage)
				{
					pageToOpen = firstWebformPage;
				}
				if(this.currentPageId !== pageToOpen.id)
				{
					this.onPageClick(pageToOpen);
					if(pageToOpen.isWebform)
					{
						this.isFormsOpen = true;
					}
					else
					{
						this.isPagesOpen = true;
					}
				}
				else
				{
					this.currentPageId = this.pages[0].id;
				}
			}
			else
			{
				this.pageTitle = null;
				this.currentPageId = null;
				this.setPageTitle(this.pageTitle);
			}
		},
		onPageClick(page)
		{
			this.pageTitle = page.name;
			this.currentPageId = page.id;
			this.hideActionsPopup();
			this.isShowPayment = false;
			this.isShowPreview = true;
			this.setPageTitle(this.pageTitle);
			if(page.isFrameDenied !== true)
			{
				if(!this.showedPageIds.includes(page.id))
				{
					this.startFrameCheckTimeout();
					this.showedPageIds.push(page.id);
				}
			}
			else
			{
				this.onFrameError();
			}
		},
		showActionsPopup({target})
		{
			BX.PopupMenu.show('salescenter-app-actions', target, this.getActions(), {
				offsetLeft: 0,
				offsetTop: 0,
				closeByEsc: true,
			});
		},
		showAddPageActionPopup({target}, isWebform = false)
		{
			let menuId = 'salescenter-app-add-page-actions';
			if(isWebform)
			{
				menuId += '-forms';
			}
			BX.PopupMenu.show(menuId, target, this.getAddPageActions(isWebform), {
				offsetLeft: target.offsetWidth + 20,
				offsetTop: -target.offsetHeight - 15,
				closeByEsc: true,
				angle: {
					position: 'left',
				}
			});
		},
		hideActionsPopup()
		{
			BX.PopupMenu.destroy('salescenter-app-actions');
			BX.PopupMenu.destroy('salescenter-app-add-page-actions');
		},
		addSite(isWebform = false)
		{
			Manager.addSitePage(isWebform).then((result) =>
			{
				let newPage = result.answer.result.page || false;
				this.$root.$app.fillPages().then(() =>
				{
					if(newPage)
					{
						this.onPageClick(newPage);
						this.lastAddedPages.push(parseInt(newPage.id));
					}
					else
					{
						this.openFirstPage();
					}
				});
			});
			this.hideActionsPopup();
		},
		copyUrl(event)
		{
			if(this.currentPage && this.currentPage.url)
			{
				Manager.copyUrl(this.currentPage.url, event);
				this.hideActionsPopup();
			}
		},
		editPage()
		{
			if(this.currentPage)
			{
				if(this.currentPage.landingId && this.currentPage.landingId > 0)
				{
					Manager.editLandingPage(this.currentPage.landingId, this.currentPage.siteId);
					this.hideActionsPopup();
				}
				else
				{
					this.showAddUrlPopup(this.currentPage);
				}
			}
		},
		hidePage()
		{
			if(this.currentPage)
			{
				this.$root.$app.hidePage(this.currentPage).then(() => {
					this.openFirstPage();
				});
				this.hideActionsPopup();
			}
		},
		showAddUrlPopup(newPage)
		{
			if(!Type.isPlainObject(newPage))
			{
				newPage = {};
			}
			Manager.addCustomPage(newPage).then((pageId) =>
			{
				if(!this.isShowPreview)
				{
					this.isShowPreview = false;
				}
				this.$root.$app.fillPages().then(() => {
					if(pageId && (!Type.isPlainObject(newPage) || !newPage.id))
					{
						this.lastAddedPages.push(parseInt(pageId));
					}
					if(!pageId && newPage)
					{
						pageId = newPage.id;
					}
					if(pageId)
					{
						this.pages.forEach((page) =>
						{
							if(parseInt(page.id) === parseInt(pageId))
							{
								this.onPageClick(page);
							}
						});
					}
					else
					{
						if(!this.isShowPayment)
						{
							this.isShowPreview = true;
						}
					}
				});
			});
			this.hideActionsPopup();
		},
		showPaymentForm()
		{
			this.isShowPayment = true;
			this.isShowPreview = false;
			if(this.isOrderPublicUrlAvailable)
			{
				this.setPageTitle(this.localize.SALESCENTER_LEFT_PAYMENT_ADD);
			}
			else
			{
				this.setPageTitle(this.localize.SALESCENTER_DEFAULT_TITLE);
			}
		},
		showOrdersList()
		{
			this.hideActionsPopup();
			Manager.showOrdersList({
				ownerId: this.$root.$app.ownerId,
				ownerTypeId: this.$root.$app.ownerTypeId,
			}).then(() =>
			{
				this.refreshOrdersCount();
			});
		},
		showOrderAdd()
		{
			this.hideActionsPopup();
			Manager.showOrderAdd({
				ownerId: this.$root.$app.ownerId,
				ownerTypeId: this.$root.$app.ownerTypeId,
			}).then(() =>
			{
				this.refreshOrdersCount();
			});
		},
		showCatalog()
		{
			this.hideActionsPopup();
			Manager.openSlider(`/saleshub/catalog/?sessionId=${this.$root.$app.sessionId}`);
		},
		onFormsClick()
		{
			this.isFormsOpen = !this.isFormsOpen;
			this.hideActionsPopup();
		},
		openControlPanel()
		{
			Manager.openControlPanel();
			this.hideActionsPopup();
		},
		openHelpDesk()
		{
			this.hideActionsPopup();
			Manager.openHowItWorks();
		},
		isPageSelected(page)
		{
			return (this.currentPage && this.isShowPreview && this.currentPage.id === page.id);
		},
		send(event, skipPublicMessage = 'n')
		{
			if(!this.isAllowedSubmitButton)
			{
				return;
			}
			if(this.isShowPayment && !this.isShowStartInfo)
			{
				this.$root.$app.sendPayment(event.target, skipPublicMessage);
			}
			else if(this.currentPage && this.currentPage.isActive)
			{
				this.$root.$app.sendPage(this.currentPage.id);
			}
		},
		close()
		{
			this.$root.$app.closeApplication();
		},
		setPageTitle(title = null)
		{
			if(!title)
			{
				return;
			}
			if(this.$root.$nodes.title)
			{
				this.$root.$nodes.title.innerText = title;
			}
		},
		onFrameError()
		{
			clearTimeout(this.frameCheckLongTimeout);
			if(this.showedPageIds.includes(this.currentPage.id))
			{
				this.loadedPageIds.push(this.currentPage.id);
			}
			this.errorPageIds.push(this.currentPage.id);
		},
		onFrameLoad(pageId)
		{
			clearTimeout(this.frameCheckLongTimeout);
			if(this.showedPageIds.includes(pageId))
			{
				this.loadedPageIds.push(pageId);
				if(this.currentPage && this.currentPage.id === pageId)
				{
					if(this.frameCheckShortTimeout && !this.currentPage.landingId)
					{
						this.onFrameError();
					}
					else if(this.errorPageIds.includes(this.currentPage.id))
					{
						this.errorPageIds = this.errorPageIds.filter((pageId) =>
						{
							return pageId !== this.currentPage.id;
						});
					}
				}
			}
			if(this.frameCheckShortTimeout && this.currentPage && this.currentPage.id === pageId && !this.currentPage.landingId)
			{
				this.onFrameError();
			}
		},
		startFrameCheckTimeout()
		{
			// this is a workaround for denied through X-Frame-Options sources
			if(this.frameCheckShortTimeout)
			{
				clearTimeout(this.frameCheckShortTimeout);
				this.frameCheckShortTimeout = false;
			}
			this.frameCheckShortTimeout = setTimeout(() =>
			{
				this.frameCheckShortTimeout = false;
			}, 500);

			// to show error on long loading
			clearTimeout(this.frameCheckLongTimeout);
			this.frameCheckLongTimeout = setTimeout(() =>
			{
				if(this.currentPage && this.showedPageIds.includes(this.currentPage.id) && !this.loadedPageIds.includes(this.currentPage.id))
				{
					this.errorPageIds.push(this.currentPage.id);
				}
			}, 5000);
		},
		connect()
		{
			Manager.startConnection({
				context: this.$root.$app.context,
			}).then(() =>
			{
				Manager.loadConfig().then((result) =>
				{
					if(result.isSiteExists)
					{
						this.$root.$app.isSiteExists = result.isSiteExists;
						this.isSiteExists = result.isSiteExists;
						this.$root.$app.fillPages().then(() =>
						{
							this.isOrderPublicUrlExists = true;
							this.$root.$app.isOrderPublicUrlExists = true;
							this.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
							this.$root.$app.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
							if(!this.isShowPayment)
							{
								this.openFirstPage();
							}
							else
							{
								this.showPaymentForm();
							}
						});
					}
				});
			});
		},
		checkRecycle()
		{
			Manager.openConnectedSite(true);
		},
		openConnectedSite()
		{
			Manager.openConnectedSite();
		},
		getFrameSource(page)
		{
			if(this.showedPageIds.includes(page.id))
			{
				if(page.landingId > 0)
				{
					if(page.isActive)
					{
						return (new Uri(page.url)).setQueryParam('theme', '').toString();
					}
				}
				else
				{
					return page.url;
				}
			}

			return null;
		},
		refreshOrdersCount()
		{
			this.$root.$app.getOrdersCount().then((result) =>
			{
				this.ordersCount = result.answer.result || null;
			}).catch(() =>
			{
				this.ordersCount = null;
			});
		},
		renderErrors()
		{
			if (this.isShowPayment && this.order.errors.length > 0)
			{
				let errorMessages = this.order.errors.map((item) => item.message).join('<br>');
				let params = {
					color: BX.UI.Alert.Color.DANGER,
					textCenter: true,
					text: BX.util.htmlspecialchars(errorMessages)
				};
				if (this.$refs.errorBlock.innerHTML.length === 0)
				{
					params.animated = true;
				}
				let alert = new BX.UI.Alert(params);
				this.$refs.errorBlock.innerHTML = '';
				this.$refs.errorBlock.appendChild(alert.getContainer());
			}
			else if (this.$refs.errorBlock)
			{
				this.$refs.errorBlock.innerHTML = '';
			}
		},
		editMenuItem(event, page)
		{
			this.editedPageId = page.id;
			setTimeout(() =>
			{
				event.target.parentNode.parentNode.querySelector('input').focus();
			}, 50);
		},
		saveMenuItem(event)
		{
			const pageId = this.editedPageId;
			const name = event.target.value;
			let oldName;
			this.pages.forEach((page) =>
			{
				if(page.id === this.editedPageId)
				{
					oldName = page.name
				}
			});
			if(pageId > 0 && oldName && name !== oldName && name.length > 0)
			{
				Manager.addPage({
					id: pageId,
					name: name,
					analyticsLabel: 'salescenterUpdatePageTitle',
				}).then(() =>
				{
					this.$root.$app.fillPages().then(() => {
						if(this.editedPageId === this.currentPageId)
						{
							this.setPageTitle(name);
						}
						this.editedPageId = null;
					});
				});
			}
			else
			{
				this.editedPageId = null;
			}
		},
		createLoader()
		{
			const loader = new Loader({size: 200});
			loader.show(this.$refs['previewLoader']);
		}
	},

	computed:
	{
		config: () => config,
		currentPage()
		{
			if(this.currentPageId > 0)
			{
				let pages = this.application.pages.filter((page) =>
				{
					return page.id === this.currentPageId;
				});
				if(pages.length > 0)
				{
					return pages[0];
				}
			}

			return null;
		},

		pagesSubmenuHeight()
		{
			if(this.isPagesOpen)
			{
				return (this.application.pages.filter((page) =>
				{
					return !page.isWebform;
				}).length * 39 + 30) + 'px';
			}
			else
			{
				return '0px';
			}
		},

		formsSubmenuHeight()
		{
			if(this.isFormsOpen)
			{
				return (this.application.pages.filter((page) =>
				{
					return page.isWebform;
				}).length * 39 + 30) + 'px';
			}
			else
			{
				return '0px';
			}
		},

		isFrameError()
		{
			if(this.isShowPreview && this.currentPage)
			{
				if(!this.currentPage.isActive)
				{
					return true;
				}
				else if(!this.currentPage.landingId && this.errorPageIds.includes(this.currentPage.id))
				{
					return true;
				}
			}
			return false;
		},

		isShowLoader()
		{
			return (
				this.isShowPreview &&
				this.currentPageId > 0 &&
				this.showedPageIds.includes(this.currentPageId) &&
				!this.loadedPageIds.includes(this.currentPageId)
			);
		},

		isShowStartInfo()
		{
			let res = false;
			if(this.isShowPreview)
			{
				res = (!this.pages || this.pages.length <= 0);
			}
			else if(this.isShowPayment)
			{
				res = !this.isOrderPublicUrlAvailable;
			}

			return res;
		},

		wrapperHeight()
		{
			if(this.isShowPreview || this.isShowPayment)
			{
				const position = BX.pos(this.$root.$el);
				let offset = position.top + 20;
				if(this.$root.$nodes.footer)
				{
					offset += BX.pos(this.$root.$nodes.footer).height;
				}
				const buttonsPanel = this.$refs['buttonsPanel'];
				if(buttonsPanel)
				{
					offset += BX.pos(buttonsPanel).height;
				}
				return 'calc(100vh - ' + offset + 'px)';
			}
			else
			{
				return 'auto';
			}
		},

		lastModified()
		{
			if(this.currentPage && this.currentPage.modifiedAgo)
			{
				return this.localize.SALESCENTER_MODIFIED.replace('#AGO#', this.currentPage.modifiedAgo);
			}

			return false;
		},

		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_');
		},

		pages()
		{
			this.isOrderPublicUrlAvailable = this.$root.$app.isOrderPublicUrlAvailable;
			return [...this.application.pages];
		},

		isAllowedSubmitButton()
		{
			if(this.$root.$app.disableSendButton)
			{
				return false;
			}
			if (this.isShowPreview && this.currentPage && !this.currentPage.isActive)
			{
				return false
			}
			if (this.isShowPayment)
			{
				return this.$store.getters['orderCreation/isAllowedSubmit'];
			}

			return this.currentPage;
		},

		isOrderPageDeleted()
		{
			return (this.$root.$app.isSiteExists && !this.isOrderPublicUrlExists);
		},

		...Vuex.mapState({
			application: state => state.application,
			order: state => state.orderCreation,
		})
	},

	template: `
		<div class="salescenter-app-wrapper" :style="{height: wrapperHeight}">
			<div class="ui-sidepanel-sidebar salescenter-app-sidebar" ref="sidebar">
				<div class="ui-sidepanel-head">
					<div class="ui-sidepanel-title">{{localize.SALESCENTER_DEFAULT_TITLE}}</div>
				</div>
				<ul class="ui-sidepanel-menu" ref="sidepanelMenu">
					<li :class="{'salescenter-app-sidebar-menu-active': isPagesOpen}" class="ui-sidepanel-menu-item">
						<a class="ui-sidepanel-menu-link" @click.stop.prevent="isPagesOpen = !isPagesOpen;">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_PAGES}}</div>
							<div class="ui-sidepanel-toggle-btn">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>
						</a>
						<ul class="ui-sidepanel-submenu" :style="{height: pagesSubmenuHeight}">
							<li v-for="page in pages" v-if="!page.isWebform" :key="page.id"
							:class="{
								'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),
								'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)
							}" class="ui-sidepanel-submenu-item">
								<a :title="page.name" class="ui-sidepanel-submenu-link" @click.stop="onPageClick(page)">
									<input class="ui-sidepanel-input" :value="page.name" v-on:keyup.enter="saveMenuItem($event)" @blur="saveMenuItem($event)" />
									<div class="ui-sidepanel-menu-link-text">{{page.name}}</div>
									<div v-if="lastAddedPages.includes(page.id)" class="ui-sidepanel-badge-new"></div>
									<div class="ui-sidepanel-edit-btn"><span class="ui-sidepanel-edit-btn-icon" @click="editMenuItem($event, page);"></span></div>
								</a>
							</li>
							<li class="salescenter-app-helper-nav-item salescenter-app-menu-add-page" @click.stop="showAddPageActionPopup($event)">
								<span class="salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add">+</span><span class="salescenter-app-helper-nav-item-text">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>
							</li>
						</ul>
					</li>
					<li v-if="this.$root.$app.isPaymentCreationAvailable" :class="{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}" class="ui-sidepanel-menu-item" @click="showPaymentForm">
						<a class="ui-sidepanel-menu-link">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_PAYMENT_ADD}}</div>
						</a>
					</li>
					<li @click="showOrdersList">
						<a class="ui-sidepanel-menu-link">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_ORDERS}}</div>
							<span class="ui-sidepanel-counter" ref="ordersCounter" v-show="ordersCount > 0">{{ordersCount}}</span>
						</a>
					</li>
					<li @click="showOrderAdd">
						<a class="ui-sidepanel-menu-link">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_ORDER_ADD}}</div>
						</a>
					</li>
					<li v-if="this.$root.$app.isCatalogAvailable" @click="showCatalog">
						<a class="ui-sidepanel-menu-link">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_CATALOG}}</div>
						</a>
					</li>
					<li :class="{'salescenter-app-sidebar-menu-active': isFormsOpen}" class="ui-sidepanel-menu-item">
						<a class="ui-sidepanel-menu-link" @click.stop.prevent="onFormsClick();">
							<div class="ui-sidepanel-menu-link-text">{{localize.SALESCENTER_LEFT_FORMS_ALL}}</div>
							<div class="ui-sidepanel-toggle-btn">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>
						</a>
						<ul class="ui-sidepanel-submenu" :style="{height: formsSubmenuHeight}">
							<li v-for="page in pages" v-if="page.isWebform" :key="page.id"
							 :class="{
								'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),
								'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)
							}" class="ui-sidepanel-submenu-item">
								<a :title="page.name" class="ui-sidepanel-submenu-link" @click.stop="onPageClick(page)">
									<input class="ui-sidepanel-input" :value="page.name" v-on:keyup.enter="saveMenuItem($event)" @blur="saveMenuItem($event)" />
									<div v-if="lastAddedPages.includes(page.id)" class="ui-sidepanel-badge-new"></div>
									<div class="ui-sidepanel-menu-link-text">{{page.name}}</div>
									<div class="ui-sidepanel-edit-btn"><span class="ui-sidepanel-edit-btn-icon" @click="editMenuItem($event, page);"></span></div>
								</a>
							</li>
							<li class="salescenter-app-helper-nav-item salescenter-app-menu-add-page" @click.stop="showAddPageActionPopup($event, true)">
								<span class="salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add">+</span><span class="salescenter-app-helper-nav-item-text">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>
							</li>
						</ul>
					</li>
				</ul>
			</div>
			<div class="salescenter-app-helper-nav" ref="sidepanelNav">
				<a class="salescenter-app-helper-nav-item" @click="openControlPanel">
					<span class="salescenter-app-helper-nav-item-text">{{localize.SALESCENTER_PAYMENT_TYPE_ADD}}</span>
				</a>
				<a class="salescenter-app-helper-nav-item" @click="openHelpDesk">
					<span class="salescenter-app-helper-nav-item-text">{{localize.SALESCENTER_HOW}}</span>
				</a>
			</div> 
			<div class="salescenter-app-left-side" ref="leftSide"></div>
			<div class="salescenter-app-right-side">
				<div class="salescenter-app-page-header" v-show="isShowPreview && !isShowStartInfo">
					<div class="salescenter-btn-action ui-btn ui-btn-link ui-btn-dropdown ui-btn-xs" @click="showActionsPopup($event)">{{localize.SALESCENTER_RIGHT_ACTIONS_BUTTON}}</div>
					<div class="salescenter-btn-delimiter salescenter-btn-action"></div>
					<div class="salescenter-btn-action ui-btn ui-btn-link ui-btn-xs ui-btn-icon-edit" @click="editPage">{{localize.SALESCENTER_RIGHT_ACTION_EDIT}}</div>
				</div>
				<template v-if="isShowStartInfo">
					<div class="salescenter-app-page-content salescenter-app-start-wrapper">
						<div class="ui-title-1 ui-text-center ui-color-medium" style="margin-bottom: 20px;">{{localize.SALESCENTER_INFO_TEXT_TOP}}</div>
						<div class="ui-hr ui-mv-25"></div>
						<template v-if="this.isOrderPublicUrlExists">
							<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC}}</div>
							<div style="padding-top: 5px;" class="ui-text-center">
								<div class="ui-btn ui-btn-primary ui-btn-lg" @click="openConnectedSite">{{localize.SALESCENTER_INFO_PUBLIC}}</div>
							</div>
						</template>
						<template v-else-if="isOrderPageDeleted">
							<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">{{localize.SALESCENTER_INFO_ORDER_PAGE_DELETED}}</div>
							<div style="padding-top: 5px;" class="ui-text-center">
								<div class="ui-btn ui-btn-primary ui-btn-lg" @click="checkRecycle">{{localize.SALESCENTER_CHECK_RECYCLE}}</div>
							</div>
						</template>
						<template v-else>
							<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">{{localize.SALESCENTER_INFO_TEXT_BOTTOM}}</div>
							<div style="padding-top: 5px;" class="ui-text-center">
								<div class="ui-btn ui-btn-primary ui-btn-lg" @click="connect">{{localize.SALESCENTER_INFO_CREATE}}</div>
							</div>
						</template>
					</div>
				</template>
				<template v-else-if="isFrameError && isShowPreview">
					<div class="salescenter-app-page-content salescenter-app-lost">
						<div class="salescenter-app-lost-block ui-title-1 ui-text-center ui-color-medium">{{localize.SALESCENTER_ERROR_TITLE}}</div>
						<div v-if="currentPage.isFrameDenied === true" class="salescenter-app-lost-helper ui-color-medium">{{localize.SALESCENTER_RIGHT_FRAME_DENIED}}</div>
						<div v-else-if="currentPage.isActive !== true" class="salescenter-app-lost-helper salescenter-app-not-active ui-color-medium">{{localize.SALESCENTER_RIGHT_NOT_ACTIVE}}</div>
						<div v-else class="salescenter-app-lost-helper ui-color-medium">{{localize.SALESCENTER_ERROR_TEXT}}</div>
					</div>
				</template>
				<div v-show="isShowPreview && !isShowStartInfo && !isFrameError" class="salescenter-app-page-content">
					<template v-for="page in pages">
						<iframe class="salescenter-app-demo" v-show="currentPage && currentPage.id == page.id" :src="getFrameSource(page)" frameborder="0" @error="onFrameError(page.id)" @load="onFrameLoad(page.id)" :key="page.id"></iframe>
					</template>
					<div class="salescenter-app-demo-overlay" :class="{
						'salescenter-app-demo-overlay-loading': this.isShowLoader
					}">
						<div v-show="isShowLoader" ref="previewLoader"></div>
						<div v-if="lastModified" class="salescenter-app-demo-overlay-modification">{{lastModified}}</div>
					</div>
				</div>
			    <template v-if="this.$root.$app.isPaymentsLimitReached">
			        <div ref="paymentsLimit" v-show="isShowPayment && !isShowStartInfo"></div>
				</template>
				<template v-else>
			        <component v-show="isShowPayment && !isShowStartInfo" :is="config.templateAddPaymentName"></component>
		        </template>
			</div>
			<div class="ui-button-panel-wrapper salescenter-button-panel" ref="buttonsPanel">
				<div class="ui-button-panel">
					<button :class="{
						'ui-btn-disabled': !this.isAllowedSubmitButton
					}" class="ui-btn ui-btn-md ui-btn-success" @click="send($event)">{{localize.SALESCENTER_SEND}}</button>
					<button class="ui-btn ui-btn-md ui-btn-link" @click="close">{{localize.SALESCENTER_CANCEL}}</button>
					<button v-if="isShowPayment && !isShowStartInfo && !this.$root.$app.isPaymentsLimitReached" class="ui-btn ui-btn-md ui-btn-link btn-send-crm" @click="send($event, 'y')">{{localize.SALESCENTER_SAVE_ORDER}}</button>
				</div>
				<div v-if="this.order.errors.length > 0" ref="errorBlock"></div>
			</div>
		</div>
	`,

});