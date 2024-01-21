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
		this.#dashboardManager = new DashboardManager();

		this.subscribeOnEvents();

		if (Type.isDomNode(this.container))
		{
			Dom.append(this.getAnimationContainer(), this.container);
		}
	}

	subscribeOnEvents(): void
	{
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

		EventEmitter.subscribe('onPullEvent-biconnector', (event: BaseEvent) => {
			const [eventName] = event.getData();
			if (eventName !== 'onSupersetUnfreeze')
			{
				return;
			}

			window.location.reload();
		});
	}

	onDashboardStatusUpdated(status: string): void
	{
		switch (status) {
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
			<div class="biconnector-dashboard__error_logo">
				<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g filter="url(#filter0_d_2860_90331)">
					<circle cx="40" cy="39" r="38" fill="white"/>
					</g>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M62.4337 49.2382L43.2273 17.4144C41.7474 14.9712 38.221 14.9712 36.7726 17.4144L17.5662 49.2382C16.0548 51.744 17.881 54.9076 20.8092 54.9076H59.2222C62.1189 54.9076 63.9451 51.744 62.4337 49.2382ZM37.2449 28.5026C37.2449 27.0931 38.3784 25.9655 39.7953 25.9655H40.1416C41.5585 25.9655 42.692 27.0931 42.692 28.5026V37.9934C42.692 39.4029 41.5585 40.5305 40.1416 40.5305H39.7953C38.3784 40.5305 37.2449 39.4029 37.2449 37.9934V28.5026ZM43.1958 46.9203C43.1958 48.6744 41.7474 50.1152 39.9842 50.1152C38.221 50.1152 36.7726 48.6744 36.7726 46.9203C36.7726 45.1663 38.221 43.7254 39.9842 43.7254C41.7474 43.7254 43.1958 45.1663 43.1958 46.9203Z" fill="#FFC34D"/>
					<defs>
					<filter id="filter0_d_2860_90331" x="0" y="0" width="80" height="80" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
					<feFlood flood-opacity="0" result="BackgroundImageFix"/>
					<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
					<feOffset dy="1"/>
					<feGaussianBlur stdDeviation="1"/>
					<feComposite in2="hardAlpha" operator="out"/>
					<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/>
					<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2860_90331"/>
					<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2860_90331" result="shape"/>
					</filter>
					</defs>
				</svg>
			</div>
			<div class="biconnector-dashboard__hint_desc biconnector-dashboard__error_desc">
				${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_ERROR_DESC')}
			</div>
			${reloadBtn}
		`;
	}

	getLoadingHint(): HTMLElement
	{
		const content = this.getContentByStatus(this.status);

		return Tag.render`
			<div class="biconnector-dashboard__hint" id="biconnector-dashboard__hint">
				<div id="dashboard__hint__content">
					${content}
				</div>
			</div>
		`;
	}
}
