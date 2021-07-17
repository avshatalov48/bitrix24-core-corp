import {Type} from 'main.core';
import {TagSearcher} from '../../src/utility/tag.searcher';

describe('Tasks.Scrum.TagSearcher', () => {

	describe('Initialization', () => {
		it('TagSearcher must be a function', () => {
			assert(typeof TagSearcher === 'function');
		});
		it('TagSearcher must be initialized successfully', () => {
			const tagSearcher = new TagSearcher();
			assert(typeof tagSearcher.addTagToSearcher === 'function');
			assert(typeof tagSearcher.addEpicToSearcher === 'function');
			assert(typeof tagSearcher.getTagFromSearcher === 'function');
			assert(typeof tagSearcher.getAllList === 'function');
			assert(typeof tagSearcher.getTagsList === 'function');
			assert(typeof tagSearcher.getEpicList === 'function');
			assert(typeof tagSearcher.getTagFromSearcher === 'function');
			assert(typeof tagSearcher.getEpicByName === 'function');
			assert(typeof tagSearcher.showTagsDialog === 'function');
			assert(typeof tagSearcher.showEpicDialog === 'function');
			assert(typeof tagSearcher.showTagsSearchDialog === 'function');
			assert(typeof tagSearcher.closeTagsSearchDialog === 'function');
			assert(typeof tagSearcher.showEpicSearchDialog === 'function');
			assert(typeof tagSearcher.closeEpicSearchDialog === 'function');
			assert(typeof TagSearcher.getHashTagNamesFromText === 'function');
			assert(typeof TagSearcher.getHashEpicNamesFromText === 'function');
		});
	});

	describe('Correct behaviour', () => {

		const text = 'Task name #first #second with spaces @название эпика #тег с кириллицей #безпробела';

		it('TagSearcher must be able to get tag names from text', () => {
			const tags = TagSearcher.getHashTagNamesFromText(text);
			assert(Type.isArray(tags) === true);
			assert(tags[0] === 'first ');
			assert(tags[1] === 'second with spaces ');
			assert(tags[2] === 'тег с кириллицей ');
			assert(tags[3] === 'безпробела');
		});
		it('TagSearcher must be able to get epic names from text', () => {
			const epics = TagSearcher.getHashEpicNamesFromText(text);
			assert(Type.isArray(epics) === true);
			assert(epics[0] === 'название эпика ');
		});
		it('TagSearcher must be able to add a tag', () => {
			const tagSearcher = new TagSearcher();

			TagSearcher.getHashTagNamesFromText(text).forEach((tag: string) => {
				tagSearcher.addTagToSearcher(tag);
			});

			assert(Type.isArray(tagSearcher.getTagsList()) === true);
			assert(tagSearcher.getTagsList().length === 4);
		});
		it('TagSearcher must be able to add an epic', () => {
			const tagSearcher = new TagSearcher();

			const epicName = TagSearcher.getHashEpicNamesFromText(text)[0].trim();
			const epic = {
				id: 1,
				name: epicName,
				description: '',
				info: {
					color: '',
				}
			}
			tagSearcher.addEpicToSearcher(epic);

			assert(Type.isArray(tagSearcher.getEpicList()) === true);
			assert(tagSearcher.getEpicList().length === 1);

			const addedEpic = tagSearcher.getEpicByName(epicName);
			assert(addedEpic.id === 1);
			assert(addedEpic.name === epicName);
		});

	});
});