import { Dom, Tag, Type, Loc } from 'main.core';
import { Lottie } from 'ui.lottie';
import SkeletonAnimation from './skeleton/biconnector-dashboard-skeleton.json';
import DashboardLoadingAnimation from './skeleton/biconnector-dashboard-loading.json';
import DotsAnimation from './skeleton/biconnector-dots-animation.json';
import type { SkeletonConfig } from './type/skeleton-config';
import { DashboardManager } from 'biconnector.apache-superset-dashboard-manager';
import { BaseEvent, EventEmitter } from 'main.core.events';

export class Skeleton
{
	#dashboardManager: DashboardManager;
	#periodicReload: boolean;
	#reloadInterval: number;

	constructor(options: SkeletonConfig)
	{
		this.container = options.container ?? null;
		this.dashboardId = options.dashboardId;
		this.status = options.status;
		this.dashboardType = options.dashboardType;
		this.supersetStatus = options.supersetStatus;
		this.isFirstStartup = options.isFirstStartup;
		this.#dashboardManager = new DashboardManager();
		this.#periodicReload = options.periodicReload ?? false;
		this.#reloadInterval = null;

		this.subscribeOnEvents();

		if (Type.isDomNode(this.container))
		{
			Dom.append(this.#initAnimationContainer(), this.container);
			this.#changeContent(this.#getContent(this.status));
		}

		if (this.supersetStatus === 'READY' && this.status === 'N')
		{
			DashboardManager.installDashboard(this.dashboardId)
				.then(() => {})
				.catch(() => {
					this.#changeContent(this.#getFailedContent());
					this.#clearReloadInterval();
				});
		}

		if (this.#periodicReload)
		{
			this.#reloadInterval = setInterval(() => {
				window.location.reload();
			}, 10000);
		}
	}

	subscribeOnEvents(): void
	{
		// eslint-disable-next-line no-unused-expressions
		BX.PULL && BX.PULL.extendWatch('superset_dashboard', true);
		EventEmitter.subscribe('onPullEvent-biconnector', (event: BaseEvent) => {
			const [eventName, eventData] = event.data;
			if (eventName === 'onSupersetStatusUpdated')
			{
				const status = eventData?.status;
				if (!status)
				{
					return;
				}

				switch (status)
				{
					case 'READY':
						this.#clearReloadInterval();
						DashboardManager.installDashboard(this.dashboardId);
						break;
					case 'LOAD':
						this.#changeContent(this.#getLoadingContent());
						break;
					case 'ERROR':
						this.#clearReloadInterval();
						this.#changeContent(this.#getUnavailableSupersetHint());
						break;
					case 'LIMIT_EXCEEDED':
						this.#changeContent(this.#getLimitExceededHint());
						break;
					default:
						break;
				}
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
					this.#onDashboardStatusUpdated(dashboard.status);
				}
			}
		});
	}

	#onDashboardStatusUpdated(status: string): void
	{
		switch (status)
		{
			case DashboardManager.DASHBOARD_STATUS_DRAFT:
			case DashboardManager.DASHBOARD_STATUS_READY: {
				window.location.reload();
				break;
			}

			case DashboardManager.DASHBOARD_STATUS_LOAD: {
				this.#changeContent(this.#getLoadingContent());
				break;
			}

			case DashboardManager.DASHBOARD_STATUS_FAILED: {
				this.#clearReloadInterval();
				this.#changeContent(this.#getFailedContent());
				break;
			}

			default:
				break;
		}
	}

	#clearReloadInterval(): void
	{
		if (this.#reloadInterval)
		{
			clearInterval(this.#reloadInterval);
			this.#reloadInterval = null;
		}
	}

	#initAnimationContainer(): HTMLElement
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

		return Tag.render`
			<div class="biconnector-dashboard__animation">
				<div class="biconnector-dashboard__hint_container"></div>
				${animationBox}
			</div>
		`;
	}

	#changeContent(innerContent: HTMLElement): void
	{
		if (!this.container)
		{
			return;
		}

		const hint = this.container.querySelector('.biconnector-dashboard__hint_container');
		Dom.clean(hint);
		Dom.append(innerContent, hint);
	}

	#getContent(): HTMLElement
	{
		if (this.supersetStatus === 'LIMIT_EXCEEDED')
		{
			return this.#getLimitExceededHint();
		}

		if (this.supersetStatus === 'ERROR')
		{
			return this.#getUnavailableSupersetHint();
		}

		if (
			this.status === DashboardManager.DASHBOARD_STATUS_LOAD
			|| this.status === DashboardManager.DASHBOARD_STATUS_NOT_INSTALLED
		)
		{
			return this.#getLoadingContent();
		}

		if (this.status === DashboardManager.DASHBOARD_STATUS_FAILED)
		{
			return this.#getFailedContent();
		}

		return '';
	}

	#getLoadingContent(): HTMLElement
	{
		const loadingAnimationContainer = Tag.render`
			<div class="biconnector-dashboard__loading-animation"></div>
		`;

		Lottie.loadAnimation({
			container: loadingAnimationContainer,
			renderer: 'svg',
			loop: false,
			autoplay: true,
			animationData: DashboardLoadingAnimation,
		});

		let descriptionBlock = '';
		if (this.isFirstStartup)
		{
			const description: string = this.dashboardType === 'CUSTOM'
				? Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_CREATING')
				: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_MSGVER_2')
			;

			descriptionBlock = Tag.render`
				<div class="biconnector-dashboard__hint_desc">
					${description.replaceAll('[br]', '<br>')}
				</div>
			`;
		}

		const title: string = this.dashboardType === 'CUSTOM'
			? Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_CREATING')
			: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_MSGVER_2')
		;

		const container = Tag.render`
			<div class="biconnector-dashboard__hint biconnector-dashboard__hint__loading">
				${loadingAnimationContainer}
				<div class="biconnector-dashboard__hint_title">
					${title.replace('[dots]', '<span class="biconnector-dashboard__dots-animation"></span>')}
				</div>
				${descriptionBlock}
			</div>
		`;

		const dotsContainer = container.querySelector('.biconnector-dashboard__dots-animation');
		Lottie.loadAnimation({
			container: dotsContainer,
			renderer: 'svg',
			loop: true,
			autoplay: true,
			animationData: DotsAnimation,
		});

		return container;
	}

	#getFailedContent(): HTMLElement
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
							this.#changeContent(this.#getLoadingContent());
						}
					}
				},
			);
		};

		return Tag.render`
			<div class="biconnector-dashboard__hint biconnector-dashboard__hint__error">
				<div class="biconnector-dashboard__error__logo-wrapper">
					${this.#getErrorLogo()}
				</div>
				<div class="biconnector-dashboard__hint_desc biconnector-dashboard__error_desc">
					${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_ERROR_DESC')}
				</div>
				${reloadBtn}
			</div>
		`;
	}

	#getUnavailableSupersetHint(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__hint biconnector-dashboard__hint__unavailable">
				<div class="biconnector-dashboard__error__logo-wrapper">
					${this.#getErrorLogo()}
				</div>
				<div class="biconnector-dashboard__hint_title">
					${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_UNAVAILABLE')}
				</div>
				<div class="biconnector-dashboard__hint_desc">
					${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_UNAVAILABLE')}
				</div>
			</div>
		`;
	}

	#getErrorLogo(): HTMLElement {
		return Tag.render`
			<div class="biconnector-dashboard__error__logo"></div>
		`;
	}

	#getLimitExceededHint(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__hint biconnector-dashboard__limit__exceeded__warning">
				<div class="biconnector-dashboard__error__logo-wrapper">
					${this.#getWarningLogo()}
				</div>
				<div class="biconnector-dashboard__hint_title">
					${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_TITLE_LIMIT_EXCEEDED')}
				</div>
				<div class="biconnector-dashboard__hint_desc biconnector-dashboard__limit__exceeded__warning_desc">
					${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_HINT_DESC_LIMIT_EXCEEDED_MSGVER_1').replaceAll('[br]', '<br>')}
				</div>
			</div>
		`;
	}

	#getWarningLogo(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-dashboard__limit__exceeded__warning__logo"></div>
		`;
	}
}
