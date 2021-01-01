<?php
namespace Bitrix\Crm\Controller\Tracking\Ad;

use Bitrix\Main;
use Bitrix\Crm\Tracking;

/**
 * Class Report
 * @package Bitrix\Crm\Controller\Tracking\Ad
 */
class Report extends Main\Engine\JsonController
{
	/**
	 * Configure actions.
	 *
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'getGrid' => [
				'-prefilters' => [
					Main\Engine\ActionFilter\ContentType::class,
				]
			],
		];
	}

	public function buildAction(Tracking\Ad\ReportBuilder $builder)
	{
		$builder->run();
		$this->addErrors($builder->getErrorCollection()->toArray());

		return [
			'complete' => $builder->isComplete(),
			'label' => $builder->getCompleteLabel(),
		];
	}

	public function listAction(Tracking\Ad\ReportBuilder $builder, array $options = [])
	{
		return [
			'rows' => $builder->getRows($options['level'] ?? 0, $options['parentId'] ?? null)
		];
	}

	public function getGridAction($sourceId, $from, $to, $parentId, $level, $gridId)
	{
		$isGridRequest = !$gridId;
		$component = new Main\Engine\Response\Component(
			'bitrix:crm.tracking.report.source',
			'',
			[
				'GRID_ID' => $gridId,
				'SOURCE_ID' => $sourceId,
				'LEVEL' => $level,
				'PARENT_ID' => $parentId,
				'PERIOD_FROM' => $from,
				'PERIOD_TO' => $to,
			]
		);

		if ($isGridRequest)
		{
			$response = new Main\HttpResponse();
			$content = Main\Web\Json::decode($component->getContent());
			$response->setContent($content['data']['html']);
			return $response;
		}

		return $component;
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new Main\Engine\Autowire\ExactParameter(
			Tracking\Ad\ReportBuilder::class,
			'builder',
			function($className, $sourceId, $from, $to)
			{
				$yesterday = new Main\Type\Date();
				$from = new Main\Type\Date($from);
				$to = new Main\Type\Date($to);
				
				if ($to->getTimestamp() > $yesterday->getTimestamp())
				{
					$to = (clone $yesterday);
				}
				if ($from->getTimestamp() > $to->getTimestamp())
				{
					$from = (clone $to);
				}

				/** @var Tracking\Ad\ReportBuilder $className */
				return (new $className())
					->setSourceId($sourceId)
					->setPeriod($from, $to);
			}
		);
	}
}
