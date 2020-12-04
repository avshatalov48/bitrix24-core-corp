import {StoryPoints} from '../src/utility/story.points';

describe('Tasks.Scrum.StoryPoints', () => {

	describe('Initialization', () => {
		it('StoryPoints must be a function', () => {
			assert(typeof StoryPoints === 'function');
		});
		it('StoryPoints must be initialized successfully', () => {
			const storyPoints = new StoryPoints();
			assert(storyPoints.getPoints() === '');
		});
	});

	describe('Correct behaviour', () => {
		const inputStoryPoints = '3';
		it('StoryPoints must return story points', () => {
			const storyPoints = new StoryPoints();
			storyPoints.addPoints(inputStoryPoints);
			assert(storyPoints.getPoints() === '3');
		});
		it('StoryPoints must add points', () => {
			const storyPoints = new StoryPoints();
			storyPoints.addPoints(inputStoryPoints);

			storyPoints.addPoints('1');
			storyPoints.addPoints('0.5');
			storyPoints.addPoints(2);
			storyPoints.addPoints('');
			storyPoints.addPoints('xxl');
			assert(storyPoints.getPoints() === '6.5');
		});
		it('StoryPoints must subtract points', () => {
			const storyPoints = new StoryPoints();
			storyPoints.addPoints(inputStoryPoints);

			storyPoints.subtractPoints('1');
			storyPoints.subtractPoints('0.5');
			storyPoints.subtractPoints(0.5);
			storyPoints.subtractPoints('');
			storyPoints.subtractPoints('xxl');
			assert(storyPoints.getPoints() === '1');
		});
		it('StoryPoints must set points', () => {
			const storyPoints = new StoryPoints();
			storyPoints.setPoints(inputStoryPoints);
			assert(storyPoints.getPoints() === inputStoryPoints);
			assert(storyPoints.getDifferencePoints() === 3);
			storyPoints.setPoints('xxl');
			assert(storyPoints.getPoints() === 'xxl');
			assert(storyPoints.getDifferencePoints() === 0);
		});
		it('StoryPoints must work with zero', () => {
			const storyPoints = new StoryPoints();
			storyPoints.setPoints(0);
			assert(storyPoints.getPoints() === '0');
			storyPoints.setPoints('0');
			assert(storyPoints.getPoints() === '0');
			storyPoints.addPoints('0');
			storyPoints.addPoints(0);
			assert(storyPoints.getPoints() === '0');
		});
		it('StoryPoints must clear points', () => {
			const storyPoints = new StoryPoints();
			storyPoints.addPoints(inputStoryPoints);

			storyPoints.clearPoints();
			assert(storyPoints.getPoints() === '');
		});
		it('StoryPoints must save and return difference points', () => {
			const storyPoints = new StoryPoints();

			storyPoints.saveDifferencePoints('3', '4');
			assert(storyPoints.getDifferencePoints() === 1);
			storyPoints.saveDifferencePoints('4', '3');
			assert(storyPoints.getDifferencePoints() === -1);
		});
	});

});
