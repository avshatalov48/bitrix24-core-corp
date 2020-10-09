import './report.css';

import 'sidepanel';
import 'ui.progressbar';
import { Tag, Runtime, Event, Loc, Text } from 'main.core';
import { EventEmitter } from 'main.core.events'
import { Menu } from 'main.popup'

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
		grid: null,
	};

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
					const container = this.createUiContainer();
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
			});
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
			});
	}

	initActivators ()
	{
		this.getNodes('grid/activator').forEach((node) => {
			const options = JSON.parse(node.dataset.options);
			Event.bind(node, 'click', () => {
				new Report({
					...this.filter,
					level: options.level,
					parentId: options.parentId,
					gridId: this.filter.gridId + '-lvl' + options.level,
				});
			});
		});
		const selector = this.getNode('grid/selector');
		if (selector)
		{
			const container = this.getNode('selector');
			if (container.children.length > 0)
			{
				selector.parentElement.removeChild(selector);
				return;
			}

			selector.dataset.role = '';
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

	createUiContainer ()
	{
		const container = Tag.render`
			<div class="crm-report-tracking-panel">
				<div class="crm-report-tracking-panel-title">
					<div class="crm-report-tracking-panel-title-name">
						<div data-role="title"></div>
						<div data-role="selector"></div>
					</div>
				</div>
				<div class="crm-report-tracking-panel-body">
					<div data-role="loader" class="crm-report-tracking-panel-loader">
						<div data-role="loader/text" class="crm-report-tracking-panel-loader-text"></div>
						<div data-role="loader/bar" class="crm-report-tracking-panel-loader-bar"></div>
					</div>
					<div data-role="grid"></div>
				</div>
			</div>
		`;

		this.ui.container = container;
		this.ui.title = this.getNode('title');
		this.ui.loader = this.getNode('loader');
		this.ui.loaderText = this.getNode('loader/text');
		this.ui.grid = this.getNode('grid');

		const progressBar = new BX.UI.ProgressBar({
			value: 0,
			maxValue: 100,
			statusType: 'none',
			column: true,
		});
		this.getNode('loader/bar').appendChild(progressBar.getContainer());
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