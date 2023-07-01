import { Content } from "./content";
import { Tag, ajax, Runtime, Dom } from "main.core";
import { Loader } from "main.loader";

export class UserOnlineContent extends Content
{
	getLoader(): Loader
	{
		return this.cache.remember('loader', () => {
			return new Loader({
				size: 45,
			});
		})
	}

	getComponentContent(): HTMLElement {
		return this.cache.remember('component-content', () => {
			const contentContainer = Tag.render`
				<div data-role="invitation-widget-ustat-online" class="invitation-widget-ustat-online"/>
			`;
			Dom.style(contentContainer, 'min-height', '70px');

			this.getLoader().show(contentContainer);

			ajax.runAction("intranet.invitationwidget.getUserOnlineComponent").then((response) => {
				this.getLoader().hide();
				const assets = response.data.assets;
				BX.load([...assets['css'], ...assets['js']], () => {
					Runtime.html(null, [...assets['string']].join('\n'), {useAdjacentHTML: true})
						.then(() => {
							Runtime.html(contentContainer, response.data.html).then(() => {
								this.getLoader().destroy();
							});
						})
					;
				});
			});

			return contentContainer;
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div class="intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--no-padding">
					${this.getComponentContent()}
				</div>
			`;
		});
	}
}
