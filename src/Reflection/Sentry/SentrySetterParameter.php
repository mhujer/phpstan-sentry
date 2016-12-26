<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Sentry;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;

class SentrySetterParameter implements ParameterReflection
{

	/** @var \PHPStan\Type\Type */
	private $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function getName(): string
	{
		return 'newValue';
	}

	public function isOptional(): bool
	{
		return false;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function isPassedByReference(): bool
	{
		return false;
	}

	public function isVariadic(): bool
	{
		return false;
	}

}
