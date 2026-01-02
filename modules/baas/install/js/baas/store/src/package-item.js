import { Cache, Dom, Tag, Text, Type, Loc, Extension, Event } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Button } from 'ui.buttons';
import { PurchasesFactory } from './purchase/purchases-factory';
import { ResponseDataType } from './types/response-data-type';
import type { ResponsePackageDataType } from './types/response-package-data-type';
import { Icon, Set } from 'ui.icon-set.api.core';
import { Label, LabelSize } from 'ui.label';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { PopupManager } from 'main.popup';
import { License } from 'bitrix24.license';
import 'ui.notification';

export class PackageItem extends EventEmitter
{
	#cache: Cache.MemoryCache = new Cache.MemoryCache();
	#data: ?ResponsePackageDataType = null;

	constructor(data: ResponsePackageDataType)
	{
		super();
		this.#data = data;
		this.setEventNamespace('BX.Baas');

		if (BX.PULL && Extension.getSettings('baas.store').pull)
		{
			BX.PULL.extendWatch(Extension.getSettings('baas.store').pull.channelName);

			EventEmitter.subscribe('onPullEvent-baas', (event: BaseEvent) => {
				const [command: string, params: ResponseDataType] = event.getData();
				if (command === 'updateService' && params.packages)
				{
					[...params.packages]
						.forEach((packagePullData: ResponsePackageDataType) => {
							if (packagePullData.code === this.#data.code)
							{
								this.#adjustPackage(packagePullData);
							}
						})
					;
				}
			});
		}
	}

	getContainer(): HTMLElement
	{
		return this.#cache.remember('container', () => {
			const data = this.#data;

			return Tag.render`
				<div class="ui-popupconstructor-content-item-wrapper" data-bx-role="package-tile" data-bx-package="${Text.encode(data.code)}">
					<div class="ui-popupconstructor-content-item-wrapper_information">
						<div class="ui-popupconstructor-content-item-wrapper-title">
							${this.getIcon()}
							<div class="ui-popupconstructor-content-item__title">${Text.encode(data.title)}</div>
						</div>
						<div>
							<div class="ui-popupconstructor-content-item__description">
								${Text.encode(data.description)}
								${this.#renderMoreLink(this.#data.featurePromotionCode)}
							</div>
						</div>
						${this.#getPurchaseBlock()}
					</div>
					<div class="ui-popupconstructor-content-item-wrapper_button">
						${this.#renderButton(this.#data)}
						${this.#renderButtonDescription(this.#data)}
					</div>
				</div>
			`;
		});
	}

	getIcon(): HTMLElement
	{
		return this.#renderIcon(this.#data.icon);
	}

	#renderIcon(iconParams: { className: ?string, color: ?string, style: ?Object }): ?HTMLElement
	{
		const params = {};
		const style = iconParams.style ?? {};

		let iconNode = Tag.render`
			<div class="ui-popupconstructor-content-item__icon ui-icon-set"></div>
		`;

		if (Type.isStringFilled(iconParams.className))
		{
			if (Set[iconParams.className])
			{
				params.icon = Set[iconParams.className];

				if (iconParams.color)
				{
					params.color = iconParams.color;
				}
				iconNode = (new Icon(params)).render();
			}
			else
			{
				Dom.addClass(iconNode, iconParams.className);
			}
		}

		Dom.style(iconNode, style);

		return iconNode;
	}

	#renderMoreLink(featurePromotionCode: ?string): ?HTMLElement
	{
		if (!Type.isStringFilled(featurePromotionCode))
		{
			return null;
		}

		const node = Tag.render`
			<div class="ui-popupconstructor-content-item__more-link">${Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE')}</div>
		`;
		const onclick = (e) => {
			e.stopPropagation();
			const popup = FeaturePromotersRegistry.getPromoter({ code: featurePromotionCode });
			popup.show();
		};

		Event.bind(node, 'click', onclick);

		return node;
	}

	#renderButton(data: ResponsePackageDataType): HTMLElement
	{
		if (!Type.isPlainObject(data.price))
		{
			return null;
		}

		const button = data.isActive ? new Button({
			round: true,
			text: Loc.getMessage('BAAS_WIDGET_BUY_BUTTON_TITLE'),
			size: Button.Size.EXTRA_SMALL,
			color: Button.Color.SUCCESS,
			noCaps: true,
			onclick: this.openNewPurchase.bind(this),
		}) : new Button({
			round: true,
			text: Loc.getMessage('BAAS_WIDGET_BUY_BUTTON_TITLE'),
			size: Button.Size.EXTRA_SMALL,
			color: Button.Color.SUCCESS,
			state: Button.State.DISABLED,
			noCaps: true,
			onclick: () => {
				return false;
			},
		});

		return button.render();
	}

	#isPaidLicense(): boolean
	{
		return Extension.getSettings('baas.store').isBaasActive;
	}

	openNewPurchase()
	{
		const purchaseUrl = this.#data.purchaseUrl;
		const isBitrix24 = Extension.getSettings('baas.store').get('isBitrix24License');

		if (isBitrix24 && !this.#isPaidLicense())
		{
			const findPackageCode = /product=(\w+)/gi;
			const params = { package: this.#data.code };

			if (findPackageCode.test(this.#data.purchaseUrl))
			{
				params.package = this.#data.purchaseUrl.match(findPackageCode)[0].replace('product=', '');
			}

			License.openPurchasePage(params);
		}
		else if (
			isBitrix24
			&& Extension.getSettings('baas.store').get('canBaasOnlyBePurchasedByAdmin')
			&& !Extension.getSettings('baas.store').get('isCurrentUserAdmin')
		)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('BAAS_WIDGET_ONLY_ADMIN_CAN_PURCHASE_BAAS'),
				category: 'baas',
				autoHideDelay: 5000,
			});
		}
		else if (purchaseUrl.indexOf('http') === 0)
		{
			window.open(purchaseUrl);
		}
		else
		{
			BX.SidePanel.Instance.open(
				purchaseUrl,
				{
					width: 1250,
					cacheable: false,
				},
			);
		}

		this.emit('onClickToBuyPackage', { packageCode: this.#data.code });
	}

	#renderButtonDescription(data: ResponsePackageDataType): ?HTMLElement
	{
		return data.isActive ? Tag.render`
			<div class="ui-popupconstructor-content-item__button-description">
				<div>${data.price.value}</div>
				${data.price.description}
			</div>
		` : null;
	}

	#getPurchaseBlock(): HTMLElement
	{
		function getStatusLabels(response: ResponsePackageDataType): Array<Label>
		{
			const labels: Array<Label> = [];
			const purchaseInfo = response.purchaseInfo;

			if (!purchaseInfo || purchaseInfo.purchaseCount <= 0)
			{
				return labels;
			}

			const isAnnual = response.code === 'COPILOT_Q1000_P12';
			const hasZeroBalance = purchaseInfo.purchaseBalance === 0;
			const isExceedAnnual = isAnnual
				&& hasZeroBalance
				&& purchaseInfo.purchases.length === 1
				&& purchaseInfo.purchases[0].length === 1;

			if (purchaseInfo.purchaseBalance <= 20 && !hasZeroBalance)
			{
				labels.push(
					new Label({
						text: Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED'),
						size: LabelSize.SM,
						fill: true,
						customClass: '--almost',
					}),
				);
			}

			if (isExceedAnnual || (hasZeroBalance && !isAnnual))
			{
				labels.push(
					new Label({
						text: Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED'),
						size: LabelSize.SM,
						fill: true,
						customClass: '--exceeded',
					}),
				);
			}

			if (hasZeroBalance && isAnnual)
			{
				labels.push(
					new Label({
						text: Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED_THIS_MONTH'),
						size: LabelSize.SM,
						fill: true,
						customClass: '--exceeded',
					}),
				);
			}

			const isActiveAnnual = isAnnual && (labels.length === 0 || !isExceedAnnual);
			const isActiveMonthly = !isAnnual && labels.length === 0;

			if (isActiveAnnual || isActiveMonthly)
			{
				labels.unshift(new Label({
					text: Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE'),
					size: LabelSize.SM,
					fill: true,
					customClass: '--active',
				}));
			}

			return labels;
		}

		return this.#cache.remember('purchase-block', (): HTMLElement => {
			const data: ResponsePackageDataType = this.#data;
			let htmlToRender = Tag.render`<div></div>`;
			const purchaseInfo = data.purchaseInfo;

			if (Type.isPlainObject(purchaseInfo) && purchaseInfo.purchaseCount > 0)
			{
				const labels = getStatusLabels(data);

				htmlToRender = Tag.render`
					<div class="ui-popupconstructor-content-item__purchase-description" onclick="${this.showPurchases.bind(this)}">
						<span class="ui-link ui-link-dashed">${Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE')}: ${purchaseInfo.purchaseCount}</span>
						${labels.map((label: Label) => label.render())}
					</div>
				`;
			}

			return htmlToRender;
		});
	}

	showPurchases()
	{
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			this.getFullEventName('onPurchaseShown'),
			new BaseEvent({ data: { package: this, packageCode: this.#data.code } }),
		);
		const bindElement = this.getContainer().closest('div.popup-window');

		const padding = 10;
		const popupWidth = bindElement.offsetWidth - padding * 2;
		const popupHeight = bindElement.offsetHeight - padding * 2;
		const fabric = new PurchasesFactory();

		const popup = PopupManager.create({
			id: 'purchase',
			autoHide: true,
			cacheable: false,
			closeIcon: true,
			lightShadow: true,
			draggable: false,
			closeByEsc: true,
			padding: 0,
			className: '--baas-widget --purchase',
			content: fabric.create(this).getContainer(),

			bindElement,
			offsetTop: 0 - popupHeight - padding,
			offsetLeft: padding,
			bindOptions: {
				position: 'bottom',
			},
			width: popupWidth,
			height: popupHeight,

			events: {
				onClose: () => {
					EventEmitter.emit(
						EventEmitter.GLOBAL_TARGET,
						this.getFullEventName('onPurchaseHidden'),
						new BaseEvent({ data: { package: this } }),
					);
				},
			},
		});

		popup.show();
	}

	#adjustPackage(newPackageData: ResponsePackageDataType)
	{
		this.#data.purchaseInfo = newPackageData.purchaseInfo;
		if (this.#cache.has('purchase-block'))
		{
			const oldNode = this.#cache.get('purchase-block');
			this.#cache.delete('purchase-block');
			const newNode = this.#getPurchaseBlock();
			Dom.replace(oldNode, newNode);
		}
	}

	getData(): ResponsePackageDataType
	{
		return this.#data;
	}
}
