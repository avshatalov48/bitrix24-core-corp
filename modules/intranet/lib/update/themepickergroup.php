<?php

namespace Bitrix\Intranet\Update;

use Bitrix\Main\ArgumentException;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Intranet\Internals\ThemeTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SiteTemplateTable;

final class ThemePickerGroup extends Stepper
{
	protected static $moduleId = 'intranet';
	protected $limit = 50;

	private function getSites(): array
	{
		$result = [];

		$res = SiteTemplateTable::getList([
			'filter' => [
				'CONDITION' => false,
				'=TEMPLATE' => 'bitrix24'
			],
			'select' => [ 'SITE_ID' ]
		]);
		while ($siteTemplateFields = $res->fetch())
		{
			$result[] =  $siteTemplateFields['SITE_ID'];
		}

		return $result;
	}

	private function getCount(): int
	{
		$result = 0;

		$sitesList = $this->getSites();
		if (!empty($sitesList))
		{
			$query = new Query(WorkgroupTable::getEntity());
			$query->registerRuntimeField(
				'',
				new ReferenceField('WGS',
					WorkgroupSiteTable::getEntity(),
					[
						'=ref.GROUP_ID' => 'this.ID',
					],
					[ 'join_type' => 'INNER' ]
				)
			);
			$query->addFilter('@WGS.SITE_ID', $sitesList);
			$query->addSelect(Query::expr()->countDistinct('ID'), 'CNT');
			if ($resultFields = $query->exec()->fetch())
			{
				$result = $resultFields['CNT'];
			}
		}

		return $result;
	}

	private function getPatternThemes(): array
	{
		$result = [];
		try
		{
			$themePicker = new ThemePicker('bitrix24');
			$result = array_map(static function($theme) { return $theme['id']; }, $themePicker->getPatternThemes());
		}
		catch (ArgumentException $exception)
		{
		}

		return $result;
	}

	public function execute(array &$result): bool
	{
		if (!(
			Loader::includeModule('intranet')
			&& Loader::includeModule('socialnetwork')
			&& Option::get('intranet', 'needAssignWorkgroupTheme', 'Y') === 'Y'
		))
		{
			return false;
		}

		/* \Bitrix\Intranet\Update\ThemePickerConvert hasn't completed yet */
		if (Option::get('intranet', 'needConvertThemePicker', 'Y') !== 'N')
		{
			return true;
		}

		$return = false;

		$params = Option::get('intranet', 'themepickerworkgroupassign', '');
		$params = ($params !== '' ? @unserialize($params, [ 'allowed_classes' => false ]) : []);
		$params = (is_array($params) ? $params : []);

		if (empty($params))
		{
			$params = [
				'lastId' => 0,
				'number' => 0,
				'count' => $this->getCount(),
			];
		}

		if ($params['count'] > 0)
		{
			$result['title'] = '';
			$result['progress'] = 1;
			$result['steps'] = '';
			$result['count'] = $params['count'];

			$found = false;

			$sitesList = $this->getSites();
			$themesList = $this->getPatternThemes();

			if (
				!empty($sitesList)
				&& !empty($themesList)
			)
			{
				$query = new Query(WorkgroupTable::getEntity());
				$query->registerRuntimeField(
					'',
					new ReferenceField('WGS',
						WorkgroupSiteTable::getEntity(),
						[
							'=ref.GROUP_ID' => 'this.ID',
						],
						[ 'join_type' => 'INNER' ]
					)
				);
				$query->addFilter('@WGS.SITE_ID', $sitesList);
				$query->addFilter('>ID', (int)$params['lastId']);
				$query->addOrder('ID');
				$query->addGroup('ID');
				$query->addSelect('ID');
				$query->setLimit($this->limit);

				$res = $query->exec();

				while ($workgroupRecord = $res->fetch())
				{
					$themeId = $themesList[array_rand($themesList)];

					$resSites = WorkgroupSiteTable::getList([
						'filter' => [
							'GROUP_ID' => (int)$workgroupRecord['ID']
						],
						'select' => [ 'SITE_ID' ]
					]);
					while($workgroupSiteRecord = $resSites->fetch())
					{
						$context = 'bitrix24_' . $workgroupSiteRecord['SITE_ID'];

						$themeRes = ThemeTable::getList([
							'filter' => [
								'=ENTITY_TYPE' => ThemePicker::ENTITY_TYPE_SONET_GROUP,
								'ENTITY_ID' => (int)$workgroupRecord['ID'],
								'=CONTEXT' => $context
							]
						]);
						if ($themeRes->fetch())
						{
							continue;
						}

						ThemeTable::set([
							'THEME_ID' => $themeId,
							'USER_ID' => 0,
							'ENTITY_TYPE' => ThemePicker::ENTITY_TYPE_SONET_GROUP,
							'ENTITY_ID' => (int)$workgroupRecord['ID'],
							'CONTEXT' => $context,
						]);

					}

					$found = true;
					$params['lastId'] = $workgroupRecord['ID'];
					$params['number']++;
				}
			}

			if ($found)
			{
				Option::set('intranet', 'themepickerworkgroupassign', serialize($params));
				$return = true;
			}

			$result['progress'] = (int)($params['number'] * (int)$this->limit / $params['count']);
			$result['steps'] = $params['number'];

			if ($found === false)
			{
				Option::delete('intranet', [ 'name' => 'themepickerworkgroupassign' ]);
				Option::set('intranet', 'needAssignWorkgroupTheme', 'N');
			}
		}

		return $return;
	}
}
