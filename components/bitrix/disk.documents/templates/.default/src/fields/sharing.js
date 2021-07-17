import {Loader} from 'main.loader';
import {Users} from 'disk.users';
import {observeIntersection} from "../utils";
import Backend from '../backend';

export class Sharing
{
	id: Number;
	loader: ?Loader;
	node: Element;

	constructor(id, node)
	{
		this.id = id;
		this.node = node;
		this.init();
		this.observe();
	}

	init()
	{
		this.actionName = 'getShared';
	}

	observe() {
		observeIntersection(this.node, () => {
			this.showLoading();
			Backend
				[this.actionName](this.id)
				.then(({data}) => {
					this.hideLoading();
					this.renderData(data);
				}, ({errors}) => {
					this.hideLoading();
					const errorMessages = [];
					errors.forEach((error) => {
						errorMessages.push(error.message);
					});
					this.node.innerHTML = 'Error! ' + errorMessages.join('<br>');
				});
		});
	}

	showLoading()
	{
		this.loader = (this.loader || new Loader({target: this.node, mode: 'inline', size: 20}));
		this.loader.show();
		this.node.dataset.bxLoading = 'Y';
	}

	hideLoading()
	{
		delete this.node.dataset.bxLoading;
		this.loader.hide();
	}

	renderData(data)
	{
		this.node.innerHTML = '';

		const res = new Users(data, null, {
			placeInGrid: true
		});
		this.node.appendChild(res.getContainer());
	}
}