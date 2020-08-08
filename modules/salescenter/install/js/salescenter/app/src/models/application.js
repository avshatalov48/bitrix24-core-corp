import {VuexBuilderModel} from 'ui.vue.vuex';

export class ApplicationModel extends VuexBuilderModel
{
	/**
	 * @inheritDoc
	 */
	getName()
	{
		return 'application';
	}

	getState()
	{
		return {
			pages: [],
		}
	}

	getGetters()
	{
		return {
			getPages: (state) => () =>
			{
				return state.pages;
			},
		}
	}

	getMutations()
	{
		return {
			setPages: (state, payload) =>
			{
				if(typeof payload.pages === 'object')
				{
					state.pages = payload.pages        ;
					this.saveState(state);
				}
			},
			removePage: (state, payload) =>
			{
				if(typeof payload.page === 'object')
				{
					state.pages = state.pages.filter((page) => {
						return !(
							(payload.page.id && payload.page.id > 0 && page.id === payload.page.id) ||
							(payload.page.landingId && payload.page.landingId > 0 && page.landingId === payload.page.landingId)
						);
					});
					this.saveState(state);
				}
			},
			addPage: (state, payload) =>
			{
				if(typeof payload.page === 'object')
				{
					state.pages.push(payload.page);
					this.saveState(state);
				}
			},
		}
	}
}