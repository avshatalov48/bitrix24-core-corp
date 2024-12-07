<?php

namespace Bitrix\Crm\Integration\Analytics\Builder;

use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

abstract class AbstractBuilder implements BuilderContract
{
	private ?string $section = null;
	private ?string $subSection = null;
	private ?string $element = null;
	private ?string $status = null;
	private array $p2 = [];
	private array $p3 = [];
	private array $p4 = [];
	private array $p5 = [];

	final public function validate(): Result
	{
		$result = $this->customValidate();

		foreach ([$this->p2, $this->p3, $this->p4, $this->p5] as $keyAndValue)
		{
			if (empty($keyAndValue))
			{
				continue;
			}

			[$key, $value] = $keyAndValue;

			if (str_contains($key, '_'))
			{
				$result->addError(
					new Error('Additional param key can not contain _ symbol', 0, ['key' => $key, 'value' => $value])
				);
			}

			if (str_contains($value, '_'))
			{
				$result->addError(
					new Error('Additional param value can not contain _ symbol', 0, ['key' => $key, 'value' => $value])
				);
			}
		}

		return $result;
	}

	/**
	 * Inheritors can implement custom validation logic by overriding this method
	 *
	 * @return Result
	 */
	protected function customValidate(): Result
	{
		return new Result();
	}

	final public function buildUri(string|Uri $baseUri): Uri
	{
		$uri = $baseUri instanceof Uri ? clone $baseUri : new Uri($baseUri);

		return $uri->addParams(['st' => $this->buildData()]);
	}

	final public function buildData(): array
	{
		$result = $this->validate();
		if (!$result->isSuccess())
		{
			throw new InvalidOperationException('Cant build data: ' . implode(', ', $result->getErrorMessages()));
		}

		// since this method can add some p or even section/subsection/...,
		// it should be called before the main data construction
		$customData = $this->buildCustomData();

		$data = [
			'tool' => $this->getTool(),
			'c_section' => $this->section,
			'c_sub_section' => $this->subSection,
			'c_element' => $this->element,
			'status' => $this->status,
			'p1' => Dictionary::getCrmMode(),
		] + $customData;


		if ($this->p2)
		{
			[$key, $value] = $this->p2;

			$data['p2'] = "{$key}_{$value}";
		}
		if ($this->p3)
		{
			[$key, $value] = $this->p3;

			$data['p3'] = "{$key}_{$value}";
		}
		if ($this->p4)
		{
			[$key, $value] = $this->p4;

			$data['p4'] = "{$key}_{$value}";
		}
		if ($this->p5)
		{
			[$key, $value] = $this->p5;

			$data['p5'] = "{$key}_{$value}";
		}

		return array_filter($data);
	}

	abstract protected function getTool(): string;

	/**
	 * Return event data
	 *
	 * The builder has been successfully validated
	 *
	 * @return array
	 */
	abstract protected function buildCustomData(): array;

	public function buildEvent(): \Bitrix\Main\Analytics\AnalyticsEvent
	{
		$data = $this->buildData();

		$event = new \Bitrix\Main\Analytics\AnalyticsEvent($data['event'], $data['tool'], $data['category']);
		if (!empty($data['type']))
		{
			$event->setType($data['type']);
		}
		if (!empty($data['c_section']))
		{
			$event->setSection($data['c_section']);
		}
		if (!empty($data['c_sub_section']))
		{
			$event->setSubSection($data['c_sub_section']);
		}
		if (!empty($data['c_element']))
		{
			$event->setElement($data['c_element']);
		}
		if (!empty($data['status']))
		{
			$event->setStatus($data['status']);
		}

		foreach ($data as $key => $value)
		{
			if (!empty($value) && preg_match('#^p(\d)$#u', $key, $matches))
			{
				$pNumber = (int)$matches[1];

				$setPMethodName = 'setP' . $pNumber;

				if (method_exists($event, $setPMethodName))
				{
					$event->$setPMethodName($value);
				}
			}
		}

		return $event;
	}

	final public function getSection(): ?string
	{
		return $this->section;
	}

	final public function setSection(?string $section): self
	{
		$this->section = $section;

		return $this;
	}

	final public function getSubSection(): ?string
	{
		return $this->subSection;
	}

	final public function setSubSection(?string $subSection): self
	{
		$this->subSection = $subSection;

		return $this;
	}

	final public function getElement(): ?string
	{
		return $this->element;
	}

	final public function setElement(?string $element): self
	{
		$this->element = $element;

		return $this;
	}

	final public function setStatus(?string $status): self
	{
		$this->status = $status;

		return $this;
	}

	final public function getStatus(): ?string
	{
		return $this->status;
	}

	private function normalizeStringPValue(string $value): string
	{
		$converter = new Converter(Converter::TO_CAMEL | Converter::LC_FIRST);

		return $converter->process($value);
	}

	private function normalizeBooleanPValue(bool $value): string
	{
		return $value ? '1' : '0';
	}

	// region P2
	final public function setP2(string $key, string $value): static
	{
		$this->p2 = [$key, $value];

		return $this;
	}

	/**
	 * @return array{0: string, 1: string}|array
	 */
	final public function getP2(): array
	{
		return $this->p2;
	}

	final public function setP2WithValueNormalization(string $key, string $value): static
	{
		return $this->setP2($key, $this->normalizeStringPValue($value));
	}

	final public function setP2WithBooleanValue(string $key, bool $value): static
	{
		return $this->setP2($key, $this->normalizeBooleanPValue($value));
	}
	// endregion

	// region P3
	final public function setP3(string $key, string $value): static
	{
		$this->p3 = [$key, $value];

		return $this;
	}

	/**
	 * @return array{0: string, 1: string}|array
	 */
	final public function getP3(): array
	{
		return $this->p3;
	}

	final public function setP3WithValueNormalization(string $key, string $value): static
	{
		return $this->setP3($key, $this->normalizeStringPValue($value));
	}

	final public function setP3WithBooleanValue(string $key, bool $value): static
	{
		return $this->setP3($key, $this->normalizeBooleanPValue($value));
	}
	// endregion

	// region P4
	final public function setP4(string $key, string $value): static
	{
		$this->p4 = [$key, $value];

		return $this;
	}

	/**
	 * @return array{0: string, 1: string}|array
	 */
	final public function getP4(): array
	{
		return $this->p4;
	}

	final public function setP4WithValueNormalization(string $key, string $value): static
	{
		return $this->setP4($key, $this->normalizeStringPValue($value));
	}

	final public function setP4WithBooleanValue(string $key, bool $value): static
	{
		return $this->setP4($key, $this->normalizeBooleanPValue($value));
	}
	// endregion

	// region P5
	final public function setP5(string $key, string $value): static
	{
		$this->p5 = [$key, $value];

		return $this;
	}

	/**
	 * @return array{0: string, 1: string}|array
	 */
	final public function getP5(): array
	{
		return $this->p5;
	}

	final public function setP5WithValueNormalization(string $key, string $value): static
	{
		return $this->setP5($key, $this->normalizeStringPValue($value));
	}

	final public function setP5WithBooleanValue(string $key, bool $value): static
	{
		return $this->setP5($key, $this->normalizeBooleanPValue($value));
	}
	// endregion
}
