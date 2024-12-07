<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Providers;

use Bitrix\Socialnetwork\EO_Workgroup;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\ProjectProvider;
use Bitrix\UI\EntitySelector\Item;

class ProjectMembersProvider extends ProjectProvider
{
	protected const ENTITY_ID = 'projectmembers';

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->options['addProjectMembersCategories'] = false;
		if (isset($options['addProjectMembersCategories']) && is_bool($options['addProjectMembersCategories']))
		{
			$this->options['addProjectMembersCategories'] = (bool)$options['addProjectMembersCategories'];
		}
	}

	public static function makeItem(EO_Workgroup $project, $options = []): Item
	{
		$item = ProjectProvider::makeItem($project, $options);

		if (isset($options['addProjectMembersCategories']) && $options['addProjectMembersCategories'] === true)
		{
			$item->addChild(new Item([
				'id' => $project->getId() . ':A',
				'title' => $project->getName() . '. '. GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_OWNER'),
				'entityId' => static::ENTITY_ID,
				'entityType' => 'project_members',
				'nodeOptions' => [
					'title' => GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_OWNER'),
					'renderMode' => 'override',
				],
				'customData' => [
					'parentId' => $project->getId(),
					'memberCategory' => 'owner'
				]
			]));

			$item->addChild(new Item([
				'id' => $project->getId() . ':E',
				'title' => $project->getName() . '. '. GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_MODERATOR'),
				'entityType' => 'project_members',
				'entityId' => static::ENTITY_ID,
				'nodeOptions' => [
					'title' => GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_MODERATOR'),
					'renderMode' => 'override',
				],
				'customData' => [
					'parentId' => $project->getId(),
					'memberCategory' => 'moderator'
				]
			]));

			$item->addChild(new Item([
				'id' => $project->getId() . ':K',
				'title' => $project->getName() . '. '. GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_ALL'),
				'entityId' => static::ENTITY_ID,
				'entityType' => 'project_members',
				'nodeOptions' => [
					'title' => GetMessage('CRM_INTEGRATION_PROJECT_MEMBER_PROVIDER_ALL'),
					'renderMode' => 'override',
				],
				'customData' => [
					'parentId' => $project->getId(),
					'memberCategory' => 'all'
				]
			]));
		}

		return $item;
	}
}