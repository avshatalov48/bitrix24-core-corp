import {Vue} from "ui.vue";
import {LoaderComponent} from "./loader";

export const UserOnlineComponent = {
	components: {LoaderComponent},
	props: [
		"isCrurrentUserAdmin",
	],
	computed: {
		localize(state)
		{
			return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
		}
	},
	methods: {
		getComponentContent()
		{
			BX.ajax.runAction("intranet.invitationwidget.getUserOnlineComponent", {
				data: {},
			}).then((response) => {
				this.showComponentData(response);
			}, (response) => {});
		},
		showComponentData(result)
		{
			new Promise((resolve, reject) => {
				if (result.data.hasOwnProperty("assets") && result.data.assets['css'].length)
				{
					BX.load(result.data.assets['css'], function () {
						if (result.data.assets['js'].length)
						{
							BX.load(result.data.assets['js'], function () {
								if (result.data.assets['string'].length)
								{
									for (var i = 0; i < result.data.assets['string'].length; i++)
									{
										BX.html(null, result.data.assets['string'][i]);
									}
								}

								resolve();
							});
						}
					});
				}
			}).then(() => {
				const container = document.querySelector("[data-role='invitation-widget-ustat-online']");
				const html = BX.prop.getString(result.data, "html", '');
				BX.html(container, html);
			});
		},
	},
	template: `
		<div data-role="invitation-widget-ustat-online" class="invitation-widget-ustat-online">
			<LoaderComponent
				:size="40"
			></LoaderComponent>
			{{getComponentContent()}}
		</div>
	`,
};