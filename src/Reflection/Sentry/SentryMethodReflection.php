<?php declare(strict_types=1);

namespace PHPStan\Reflection\Sentry;

use Consistence\Sentry\Metadata\Visibility;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

class SentryMethodReflection implements MethodReflection
{

	/** @var \PHPStan\Reflection\ClassReflection */
	private $declaringClass;

	/** @var \Consistence\Sentry\Metadata\Visibility */
	private $visibility;

	/** @var bool|null */
	private $setterParameterNullability;

	public function __construct(
		ClassReflection $declaringClass,
		Visibility $visibility,
		bool $setterParameterNullability = null
	)
	{
		$this->declaringClass = $declaringClass;
		$this->visibility = $visibility;
		$this->setterParameterNullability = $setterParameterNullability;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return false;
	}

	/**
	 * @return \PHPStan\Reflection\ParameterReflection[]
	 */
	public function getParameters(): array
	{
		if ($this->setterParameterNullability === null) {
			return [];
		}

		return [new SentrySetterParameter()];
	}

	public function isVariadic(): bool
	{
		return false;
	}

	public function isPrivate(): bool
	{
		return $this->visibility->equalsValue(Visibility::VISIBILITY_PRIVATE);
	}

	public function isPublic(): bool
	{
		return $this->visibility->equalsValue(Visibility::VISIBILITY_PUBLIC);
	}
}
