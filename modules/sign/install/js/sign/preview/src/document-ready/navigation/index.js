import { Tag, Type, Dom, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';

import './style.css';

export class Navigation
{
	constructor({ totalPages })
	{
		this.totalPages = totalPages ? totalPages : null;
		this.currentPage = 1;
		this.layout = {
			container: null,
			next: null,
			prev: null,
			currentPage: null,
			totalPages: null
		}
	}

	#getNodeNext()
	{
		if (!this.layout.next)
		{
			const icon = Tag.render`<i></i>`;
			this.layout.next = Tag.render`
				<div class="sign-preview__navigation-control --next">
					${icon}
				</div>
			`;

			icon.addEventListener('click', () => {
				if (this.currentPage < this.totalPages)
				{
					this.currentPage++;
					this.#getNodePrev().classList.remove('--lock');
				}

				if (this.currentPage === this.totalPages)
				{
					this.#getNodeNext().classList.add('--lock');
				}

				this.#setCurrentPage(this.currentPage);
				EventEmitter.emit(this, 'showNextPage', this.currentPage);
			});

		}

		return this.layout.next;
	}

	#getNodePrev()
	{
		if (!this.layout.prev)
		{
			const icon = Tag.render`<i></i>`;
			this.layout.prev = Tag.render`
				<div class="sign-preview__navigation-control --prev --lock">
					${icon}
				</div>
			`;

			icon.addEventListener('click', () => {
				if (this.currentPage > 1)
				{
					this.currentPage--;
					this.#getNodeNext().classList.remove('--lock');
				}

				if (this.currentPage === 1)
				{
					this.#getNodePrev().classList.add('--lock');
				}

				this.#setCurrentPage(this.currentPage);
				EventEmitter.emit(this, 'showPrevPage', this.currentPage);
			});
		}

		return this.layout.prev;
	}

	#getNodeTotalPages()
	{
		if (!this.layout.totalPages)
		{
			this.layout.totalPages = Tag.render`
				<span>/${this.totalPages}</span>
			`;
		}

		return this.layout.totalPages;
	}

	#getNodeCurrentPage()
	{
		if (!this.layout.currentPage)
		{
			this.layout.currentPage = Tag.render`
				<span>${this.currentPage}</span>
			`;
		}

		return this.layout.currentPage;
	}

	#getNodeContainer()
	{
		if (!this.layout.container)
		{
			this.layout.container = Tag.render`
				<div class="sign-preview__navigation">
					${this.#getNodePrev()}
					<div class="sign-preview__navigation-info">
						${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_PAGE')} ${this.#getNodeCurrentPage()}${this.#getNodeTotalPages()}
					</div>
					${this.#getNodeNext()}
				</div>
			`;

			if (this.totalPages === 1 || !this.totalPages)
			{
				this.lock();
			}
		}

		return this.layout.container;
	}

	#setCurrentPage(param: Number)
	{
		if (Type.isNumber(param))
		{
			this.#getNodeCurrentPage().innerText = param;
		}
	}

	lock()
	{
		Dom.addClass(this.#getNodeContainer(), '--lock');
	}

	unLock()
	{
		Dom.removeClass(this.#getNodeContainer(), '--lock');
	}

	render()
	{
		return this.#getNodeContainer();
	}
}