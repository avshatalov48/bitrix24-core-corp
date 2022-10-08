import './report.css';

import 'sidepanel';
import 'ui.progressbar';
import 'ui.fonts.opensans';
import { Tag, Runtime, Event, Loc, Text } from 'main.core';
import { EventEmitter } from 'main.core.events'
import { Menu, Popup, PopupOptions } from 'main.popup';

type Options = {
	sourceId: string;
	from: string;
	to: string;
	parentId: number;
	level: number;
	gridId: string;
};

class Report
{
	ui: Object = {
		container: null,
		loader: null,
		loaderText: null,
		loaderProgressBar: null,
		loaderActive: false,
		error: null,
		errorText: null,
		errorClose: null,
		grid: null,
	};

	loaded: boolean = false;
	statusButtonClassName: string = 'crm-tracking-report-source-status-disabled';

	constructor(options)
	{
		this.load(options);
	}

	static open(options: Options): Promise
	{
		if (window === top)
		{
			return Promise.resolve(new Report(options));
		}

		return top.BX.Runtime.loadExtension('crm.report.tracking.ad.report').then(() => {
			return new top.BX.Crm.Report.Tracking.Ad.Report(options);
		});
	}

	load({sourceId, from, to, parentId = 0, level = 0, gridId}: Options)
	{
		this.gridId = gridId = gridId || `crm-report-tracking-ad-l${level}`;
		this.filter = {sourceId, from, to, parentId, level, gridId};

		BX.SidePanel.Instance.open(
			'crm:api.tracking.ad.report' + `-${sourceId}-${level}`,
			{
				cacheable: false,
				contentCallback: () => {
					const container = this.createUiContainer(level);
					this.build();
					return container;
				}
			}
		);
	}

	build ()
	{
		this.showLoader();
		if (this.filter.level)
		{
			this.loadGrid();
			return;
		}

		BX.ajax.runAction(
			'crm.api.tracking.ad.report.build',
			{
				json: {
					...this.filter,
				}
			}
		)
			.then(({data}) => {
				if (data.label)
				{
					this.setLoaderText(data.label);
				}

				if (data.complete)
				{
					this.loadGrid();
				}
				else
				{
					this.build();
				}
			})
			.catch(({errors}) => {
				this.showError(errors[0]);
			})
		;
	}

	changeStatus (id, status)
	{
		this.showLoader();
		BX.ajax.runAction(
			'crm.api.tracking.ad.report.changeStatus',
			{
				json: { id, status }
			}
		)
			.then(() => {
				this.loadGrid();
			})
			.catch(({errors}) => {
				this.showError(errors[0]);
			})
		;
	}

	loadGrid ()
	{
		BX.ajax.runAction(
			'crm.api.tracking.ad.report.getGrid',
			{
				data: {
					...this.filter,
				}
			}
		)
			.then(({data}) => {
				//container.innerHTML = data.html;
				EventEmitter.subscribe(
					window,
					'Grid::beforeRequest',
					this.onBeforeGridRequest.bind(this)
				);

				Runtime.html(this.getNode('grid'), data.html)
				this.initActivators();
				this.hideLoader();
				this.loaded = true;

				if (!this.filter.level)
				{
					const popupOptions: PopupOptions = {
						content: Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_SETTINGS_HINT'),
						zIndex: 5000,
						maxWidth: 300,
						offsetLeft: -315,
						offsetTop: -30,
						animation: 'fading',
						darkMode: true,
						bindElement: this.ui.hint,
					};
					const popup = new Popup(popupOptions);
					popup.show();
					setTimeout(() => popup.destroy(), 10000);
				}
			});
	}

	initActivators ()
	{
		this.getNodes('grid/activator').forEach((node) => {
			const options = JSON.parse(node.dataset.options);
			const statusBtn = node.previousElementSibling;
			if (statusBtn)
			{
				options.enabled
					? statusBtn.classList.remove(this.statusButtonClassName)
					: statusBtn.classList.add(this.statusButtonClassName)
				;
				Event.bind(statusBtn, 'click', () => {
					const popup = new Menu({
						bindElement: statusBtn,
						zIndex: 3010,
						items: [
							{
								text: Text.encode(Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_STATUS_ENABLED')),
								onclick: () => {
									this.changeStatus(options.parentId, true);
									popup.close();
								},
							},
							{
								text: Text.encode(Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_STATUS_PAUSE')),
								onclick: () => {
									this.changeStatus(options.parentId, false);
									popup.close();
								},
							},
						],
					});
					popup.show();
				});
			}

			if (options.level === null || options.level === undefined)
			{
				return;
			}

			Event.bind(node, 'click', () => {
				new Report({
					...this.filter,
					level: options.level,
					parentId: options.parentId,
					gridId: this.filter.gridId + '-lvl' + options.level,
				});
			});
		});

		const selectorTitle = this.getNode('grid/selector/title');
		const selector = this.getNode('grid/selector');
		if (selector)
		{
			const container = this.getNode('selector');
			if (container.children.length > 0)
			{
				selector.parentElement.removeChild(selector);
				selectorTitle.parentElement.removeChild(selectorTitle);
				return;
			}

			selector.dataset.role = '';
			selectorTitle.dataset.role = '';
			container.appendChild(selectorTitle);
			container.appendChild(selector);

			Event.bind(selector, 'click', () => {
				const options = JSON.parse(selector.dataset.options);
				const popup = new Menu({
					bindElement: selector,
					zIndex: 3010,
					items: options.items.map(item => {
						return {
							text: Text.encode(item.title),
							onclick: () => {
								popup.close();
								selector.textContent = item.title;
								this.filter.parentId = item.parentId;
								this.filter.level = item.level;
								this.filter.sourceId = item.sourceId;
								this.build();
							},
						};
					})
				});
				popup.show();
			});
		}
	}

	createUiContainer (level)
	{
		const container = Tag.render`
			<div class="crm-report-tracking-panel">
				<div class="crm-report-tracking-panel-title">
					<div class="crm-report-tracking-panel-title-name">
						<div class="crm-report-tracking-panel-title-line">
							<div data-role="title"></div>							
							${level ? '' : '<div data-role="hint" class="ui-hint-icon crm-report-tracking-panel-hint"></div>'}
						</div>
						<div data-role="selector"></div>
					</div>
				</div>
				<div class="crm-report-tracking-panel-body">
					<div data-role="loader" class="crm-report-tracking-panel-loader">
						<div data-role="loader/text" class="crm-report-tracking-panel-loader-text"></div>
						<div data-role="loader/bar" class="crm-report-tracking-panel-loader-bar">
							<div data-role="error" class="ui-alert ui-alert-danger" style="display: none;">
								<span class="ui-alert-message">
									<strong>${Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_ERROR_TITLE')}:</strong>
									<span data-role="error/text"></span>
								</span>
							</div>
							<div style="text-align: center;">
								<button 
									data-role="error/close" 
									class="ui-btn ui-btn-light-border"
									style="display: none;"
								>${Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_CLOSE')}</button>
							</div>
						</div>
					</div>
					<div data-role="grid"></div>
				</div>
			</div>
		`;

		this.ui.container = container;
		this.ui.title = this.getNode('title');
		this.ui.hint = this.getNode('hint');
		this.ui.loader = this.getNode('loader');
		this.ui.loaderText = this.getNode('loader/text');
		this.ui.error = this.getNode('error');
		this.ui.errorText = this.getNode('error/text');
		this.ui.errorClose = this.getNode('error/close');
		this.ui.grid = this.getNode('grid');

		const progressBar = new BX.UI.ProgressBar({
			value: 0,
			maxValue: 100,
			statusType: 'none',
			column: true,
		});

		if (this.ui.hint)
		{
			this.ui.hint.addEventListener('click', () => BX.Helper.show("redirect=detail&code=12526974"));
		}
		if (this.ui.errorClose)
		{
			this.ui.errorClose.addEventListener('click', () => {
				if (this.loaded)
				{
					this.hideError();
				}
				else
				{
					BX.SidePanel.Instance.close();
				}
			});
		}

		this.getNode('loader/bar').insertBefore(progressBar.getContainer(), this.getNode('loader/bar').children[0]);
		this.ui.loaderProgressBar = progressBar;
		this.setLoaderText(Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_BUILD'));

		this.setTitle(Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_TITLE_' + this.filter.level));

		return container;
	}

	setLoaderText (text)
	{
		this.ui.loaderProgressBar.setValue(0);
		this.ui.loaderProgressBar.setTextBefore(text);
	}

	animateLoader ()
	{
		if (!this.ui.loaderActive)
		{
			return;
		}

		const progressBar = this.ui.loaderProgressBar;
		const val = parseInt(progressBar.getValue()) + 1;
		if (val <= 95)
		{
			progressBar.update(val);
		}

		setTimeout(() => this.animateLoader(), 100);
	}

	showError (error: Error)
	{
		this.ui.errorClose.style.display = '';
		this.ui.error.style.display = '';
		this.ui.errorText.textContent = error.message;
		this.ui.loaderProgressBar.getContainer().style.display = 'none';
	}

	hideError ()
	{
		this.ui.errorClose.style.display = 'none';
		this.ui.error.style.display = 'none';
		this.ui.errorText.textContent = '';
		this.ui.loaderProgressBar.getContainer().style.display = '';

		this.hideLoader();
	}

	showLoader ()
	{
		if (!this.ui.loaderActive)
		{
			setTimeout(() => this.animateLoader(), 100);
		}

		this.ui.loaderActive = true;
		this.ui.loader.style.display = '';
	}

	hideLoader ()
	{
		const progressBar = this.ui.loaderProgressBar;
		if (progressBar)
		{
			progressBar.update(0);
		}
		this.ui.loaderActive = false;
		this.ui.loader.style.display = 'none';
	}

	setTitle (title)
	{
		return this.ui.title.textContent = title;
	}

	getNode (role)
	{
		return this.ui.container.querySelector(`[data-role="${role}"]`);
	}

	getNodes (role)
	{
		return Array.from(this.ui.container.querySelectorAll(`[data-role="${role}"]`));
	}

	onBeforeGridRequest (grid, eventArgs)
	{
		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.method = 'POST';

		if (!eventArgs.url)
		{
			const parameters = Object.keys(this.filter).forEach(key => key + '=' + this.filter[key]);
			eventArgs.url = '/bitrix/services/main/ajax.php?action=crm.api.tracking.ad.grid.report.get&' + parameters;
		}

		eventArgs.data = {
			...eventArgs.data,
		};
	}
}


export {Report};