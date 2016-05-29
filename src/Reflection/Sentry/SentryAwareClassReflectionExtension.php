<?php declare(strict_types=1);

namespace PHPStan\Reflection\Sentry;

use Consistence\Sentry\Metadata\SentryAccess;
use Consistence\Sentry\Metadata\Visibility;
use Consistence\Sentry\MetadataSource\MetadataSource;
use Consistence\Sentry\SentryAware;
use Consistence\Sentry\SentryIdentificatorParser\SentryIdentificatorParser;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\TypehintHelper;

class SentryAwareClassReflectionExtension implements MethodsClassReflectionExtension, BrokerAwareClassReflectionExtension
{

	/** @var \Consistence\Sentry\MetadataSource\MetadataSource */
	private $metadataSource;

	/** @var \Consistence\Sentry\SentryIdentificatorParser\SentryIdentificatorParser */
	private $sentryIdentificatorParser;

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	public function __construct(
		MetadataSource $metadataSource,
		SentryIdentificatorParser $sentryIdentificatorParser
	)
	{
		$this->metadataSource = $metadataSource;
		$this->sentryIdentificatorParser = $sentryIdentificatorParser;
	}

	public function setBroker(Broker $broker)
	{
		$this->broker = $broker;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (!$classReflection->getNativeReflection()->implementsInterface(SentryAware::class)) {
			return false;
		}

		$metadata = $this->metadataSource->getMetadataForClass($classReflection->getNativeReflection());

		try {
			$metadata->getSentryMethodByNameAndRequiredVisibility($methodName, Visibility::get(Visibility::VISIBILITY_PRIVATE));
			return true;
		} catch (\Consistence\Sentry\Metadata\MethodNotFoundException $e) {
			return false;
		}
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		if (!$classReflection->getNativeReflection()->implementsInterface(SentryAware::class)) {
			return false;
		}

		$metadata = $this->metadataSource->getMetadataForClass($classReflection->getNativeReflection());
		$sentryMethodSearchResult = $metadata->getSentryMethodByNameAndRequiredVisibility($methodName, Visibility::get(Visibility::VISIBILITY_PRIVATE));
		$property = $sentryMethodSearchResult->getProperty();
		$sentryMethod = $sentryMethodSearchResult->getSentryMethod();
		$sentryAccess = $sentryMethod->getSentryAccess();
		$isSetter = $sentryAccess->equals(new SentryAccess('set'));
		$methodHasParameter = $isSetter
			|| $sentryAccess->equals(new SentryAccess('add'))
			|| $sentryAccess->equals(new SentryAccess('remove'))
			|| $sentryAccess->equals(new SentryAccess('contains'));

		$typehint = $property->getType();
		$parserResult = $this->sentryIdentificatorParser->parse($property->getSentryIdentificator());
		if ($parserResult->isMany()) {
			$typehint .= '[]';
		}
		return new SentryMethodReflection(
			$methodName,
			$this->broker->getClass($property->getClassName()),
			$sentryMethod->getMethodVisibility(),
			TypehintHelper::getTypeObjectFromTypehint($typehint, $property->isNullable()),
			$methodHasParameter ? ($isSetter ? $property->isNullable() : false) : null
		);
	}
}
