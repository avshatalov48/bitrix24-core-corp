import { Dom, Tag, Event, Type, Loc } from 'main.core';
import { Lottie } from 'ui.lottie';
import SkeletonAnimation from './skeleton/biconnector-dashboard-skeleton.json';
import type { SkeletonConfig } from './type/skeleton-config';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { BaseEvent, EventEmitter } from 'main.core.events';

export class Skeleton
{
	#dashboardManager: DashboardManager;
	constructor(options: SkeletonConfig)
	{
		this.container = options.container ?? null;
		this.dashboardId = options.dashboardId;
		this.status = options.status;
		this.isSupersetAvailable = options.isSupersetAvailable;
		this.#dashboardManager = new DashboardManager();
		this.paramsCompatible = options.paramsCompatible ?? true;

		this.subscribeOnEvents();

		if (Type.isDomNode(this.container))
		{
			Dom.append(this.getAnimationContainer(), this.container);
		}

		if (this.paramsCompatible === false)
		{
			this.#changeContent(this.#getParamsCompatibilityErrorContent());
		}
	}

	subscribeOnEvents(): void
	{
		BX.PULL && BX.PULL.extendWatch('superset_dashboard', true);
		EventEmitter.subscribe('onPullEvent-biconnector', (event: BaseEvent) => {
			const [eventName, eventData] = event.data;
			if (eventName !== 'onSupersetStatusUpdated' || !eventData)
			{
				return;
			}

			const status = eventData?.status;
			if (!status)
			{
				return;
			}

			switch (status)
			{
				case 'READY':
					window.location.reload();
					break;
				case 'LOAD':
					this.showLoadingContent();
					break;
				case 'ERROR':
					this.showErrorContent();
					break;
			}
		});

		EventEmitter.subscribe('BIConnector.Superset.DashboardManager:onDashboardBatchStatusUpdate', (event) => {
			const data = event.getData();
			if (!data.dashboardList)
			{
				return;
			}

			const dashboardList = data.dashboardList;

			if (BX.SidePanel?.Instance)
			{
				BX.SidePanel.Instance.postMessage(window, 'BIConnector.Superset.DashboardDetail:onDashboardBatchStatusUpdate', { dashboardList });
			}

			for (const dashboard of dashboardList)
			{
				if (Number(dashboard.id) === this.dashboardId)
				{
					this.onDashboardStatusUpdated(dashboard.status);
				}
			}
		});
	}

	onDashboardStatusUpdated(status: string): void
	{
		switch (status) {
			case DashboardManager.DASHBOARD_STATUS_DRAFT:
			case DashboardManager.DASHBOARD_STATUS_READY: {
				window.location.reload();
				break;
			}

			case DashboardManager.DASHBOARD_STATUS_LOAD: {
				this.showLoadingContent();
				break;
			}

			case DashboardManager.DASHBOARD_STATUS_FAILED: {
				this.showFailedContent();
				break;
			}
		}
	}

	showLoadingContent(): void
	{
		this.#changeContent(this.getLoadingContent());
	}

	showFailedContent(): void
	{
		this.#changeContent(this.getFailedContent());
	}

	showErrorContent(): void
	{
		this.#changeContent(this.getUnavailableSupersetHint());
	}

	#changeContent(innerContent: HTMLElement): void
	{
		if (!this.container)
		{
			return;
		}

		const hint = this.container.querySelector('#biconnector-dashboard__hint');
		let content = hint.querySelector('#dashboard__hint__content');
		if (content)
		{
			content.remove();
		}

		content = Tag.render`
			<div id="dashboard__hint__content">
					${innerContent}
			</div>
		`;

		Dom.append(content, hint);
	}

	getAnimationContainer(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__animation">
				${this.getLoadingHint()}
				${this.getAnimationBox()}
			</div>
		`;
	}

	getAnimationBox(): HTMLElement
	{
		const animationBox = Tag.render`
			<div class="biconnector-dashboard__animation_box"></div>
		`;

		const animation = Lottie.loadAnimation({
			container: animationBox,
			renderer: 'svg',
			loop: true,
			autoplay: false,
			animationData: SkeletonAnimation,
		});

		animation.play();

		return animationBox;
	}

	getContentByStatus(status: string): HTMLElement
	{
		if (status === DashboardManager.DASHBOARD_STATUS_LOAD)
		{
			return this.getLoadingContent();
		}

		if (status === DashboardManager.DASHBOARD_STATUS_FAILED)
		{
			return this.getFailedContent();
		}

		return '';
	}

	getLoadingContent(): HTMLElement
	{
		const hintLink = Tag.render`
			<a href="#" class="biconnector-dashboard__hint_link">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_LINK')}
			</a>
		`;

		Event.bind(hintLink, 'click', () => {
			top.BX.Helper.show('redirect=detail&code=18897300');
		});

		return Tag.render`
			<div class="biconnector-dashboard__hint_title">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_MSGVER_1')}
			</div>
			<div class="biconnector-dashboard__hint_desc">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_MSGVER_1')}
			</div>
			${hintLink}
		`;
	}

	getFailedContent(): HTMLElement
	{
		const reloadBtn = Tag.render`
			<button class="ui-btn ui-btn-sm biconnector-dashboard__error_btn ui-btn-primary">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_ERROR_RELOAD_BTN')}
			</button>
		`;

		reloadBtn.onclick = () => {
			Dom.addClass(reloadBtn, 'ui-btn-wait');
			reloadBtn.setAttribute('disabled', 'true');
			this.#dashboardManager.restartDashboardImport(this.dashboardId).then(
				(response) => {
					const dashboardIds = response?.data?.restartedDashboardIds;
					if (!dashboardIds)
					{
						return;
					}

					for (const restartedDashboardId of dashboardIds)
					{
						if (Number(restartedDashboardId) === this.dashboardId)
						{
							this.showLoadingContent();
						}
					}
				},
			);
		};

		return Tag.render`
			<div class="biconnector-dashboard__error__logo-wrapper">
				${this.getErrorLogo()}
			</div>
			<div class="biconnector-dashboard__hint_desc biconnector-dashboard__error_desc">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_ERROR_DESC')}
			</div>
			${reloadBtn}
		`;
	}

	getLoadingHint(): HTMLElement
	{
		let content: HTMLElement;
		if (this.isSupersetAvailable)
		{
			content = this.getContentByStatus(this.status);
		}
		else
		{
			content = this.getUnavailableSupersetHint();
		}

		return Tag.render`
			<div class="biconnector-dashboard__hint" id="biconnector-dashboard__hint">
				<div id="dashboard__hint__content">
					${content}
				</div>
			</div>
		`;
	}

	getUnavailableSupersetHint(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__error__logo-wrapper">
				${this.getErrorLogo()}
			</div>
			<div class="biconnector-dashboard__hint_title">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_UNAVAILABLE')}
			</div>
			<div class="biconnector-dashboard__hint_desc">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_UNAVAILABLE')}
			</div>
		`;
	}

	getErrorLogo(): HTMLElement {
		return Tag.render`
			<div class="biconnector-dashboard__error__logo"></div>
		`;
	}

	#getParamsCompatibilityErrorContent(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__error__logo-wrapper">
				${this.getErrorLogo()}
			</div>
			<div class="biconnector-dashboard__hint_desc">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_PARAMS_INCOMPATIBLE')}
			</div>
		`;
	}
}
