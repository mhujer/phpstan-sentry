<?php declare(strict_types=1);

namespace PHPStan\Reflection\Sentry;

use Consistence\Sentry\Metadata\Visibility;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

class SentryMethodReflection implements MethodReflection
{

	/** @var string */
	private $name;

	/** @var \PHPStan\Reflection\ClassReflection */
	private $declaringClass;

	/** @var \Consistence\Sentry\Metadata\Visibility */
	private $visibility;

	/** @var \PHPStan\Type\Type */
	private $type;

	/** @var bool|null */
	private $setterParameterNullability;

	public function __construct(
		string $name,
		ClassReflection $declaringClass,
		Visibility $visibility,
		Type $type,
		bool $setterParameterNullability = null
	)
	{
		$this->name = $name;
		$this->declaringClass = $declaringClass;
		$this->visibility = $visibility;
		$this->type = $type;
		$this->setterParameterNullability = $setterParameterNullability;
	}

	public function getName(): string
	{
		return $this->name;
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

		return [new SentrySetterParameter($this->type)];
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

	public function getReturnType(): Type
	{
		if ($this->setterParameterNullability === null) {
			return $this->type;
		}

		return new NullType();
	}


}
