import {Vue} from "ui.vue";

Vue.component('bx-salescenter-add-url',
{
	data()
	{
		return {
			urlCheckStatus: 0,
			addUrlPopup: null,
			errorMessage: null,
		};
	},
	created: function()
	{
		let checkUrlDebounce = BX.debounce(this.checkUrl, 2000, this);
		this.debounceCheckUrl = () =>
		{
			this.errorMessage = null;
			this.urlCheckStatus = 0;
			checkUrlDebounce();
		};
	},
	methods: {
		isValidUrl(url)
		{
			const pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
				'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
				'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
				'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
				'(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
				'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
			return !!pattern.test(url);
		},
		checkUrl()
		{
			this.urlCheckStatus = 1;
			let url = this.$refs['urlInput'].value;
			if(!this.isValidUrl(url))
			{
				this.urlCheckStatus = 21;
				this.errorMessage = this.localize.SALESCENTER_ACTION_ADD_INVALID_URL_ERROR;
				return;
			}
			this.$root.$app.checkUrl(url).then((result) =>
			{
				this.urlCheckStatus = 11;
				if(!result.answer.result)
				{
					this.urlCheckStatus = 23;
					this.$refs['nameInput'].value = '';
				}
				else
				{
					this.$refs['nameInput'].value = result.answer.result.title;
					this.$refs['urlInput'].value = result.answer.result.url;
				}
			}).catch((result) =>
			{
				this.urlCheckStatus = 22;
				this.$refs['nameInput'].value = '';
				this.errorMessage = result.answer.error_description;
			});
		},
		save()
		{
			if(this.urlCheckStatus > 10)
			{
				this.$root.$app.addPage({
					url: this.$refs['urlInput'].value,
					name: this.$refs['nameInput'].value,
				}).then(() =>
				{
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
	},
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_');
		},
	},
	template: `
		<div>
			<div>{{errorMessage}}</div>
			<label class="ui-ctl ui-ctl-textbox">
				<div class="ui-ctl-label-text">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_INPUT}}</div>
				<input name="url" class="ui-ctl-element" @keyup="debounceCheckUrl" ref="urlInput"/>
			</label>
	
			<label class="ui-ctl ui-ctl-textbox" v-bind:class="{'salescenter-hidden': urlCheckStatus < 10}">
				<div class="ui-ctl-label-text">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_NAME}}</div>
				<input class="ui-ctl-element" name="name" ref="nameInput"/>
			</label>
			<div v-if="urlCheckStatus > 20">error</div>
			<div v-else-if="urlCheckStatus > 10">
				ok
			</div>
			<div v-else-if="urlCheckStatus > 0">pending</div>
			<div class="popup-window-buttons">
				<button class="ui-btn ui-btn-md ui-btn-primary" @click="save">{{localize.SALESCENTER_ACTION_ADD_CUSTOM_SAVE}}</button>
				<button class="ui-btn ui-btn-md ui-btn-link" @click="cancel">{{localize.SALESCENTER_CANCEL}}</button>
			</div>
		</div>
	`,
});