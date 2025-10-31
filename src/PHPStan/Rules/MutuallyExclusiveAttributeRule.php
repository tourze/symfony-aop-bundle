<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Tourze\Symfony\Aop\Attribute\MutuallyExclusiveAttribute;

/**
 * Checks for the presence of mutually exclusive attributes on classes, methods, and properties.
 *
 * This rule identifies attributes that implement the `MutuallyExclusiveAttribute` interface.
 * If more than one such attribute is used on the same language construct (class, method, or property),
 * it reports an error because they are considered mutually exclusive.
 *
 * @implements Rule<Node>
 */
class MutuallyExclusiveAttributeRule implements Rule
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        // This rule processes classes, properties, and methods.
        // We listen on the generic Node type and then filter internally.
        return Node::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return array<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We are only interested in nodes that can have attributes.
        if (!$node instanceof Class_ && !$node instanceof Property && !$node instanceof ClassMethod) {
            return [];
        }

        $exclusiveAttributes = $this->findExclusiveAttributes($node, $scope);

        if (count($exclusiveAttributes) < 2) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection) {
            return [];
        }

        $attributeNames = array_map(fn (string $class): string => $this->getShortName($class), $exclusiveAttributes);
        $errorBuilder = RuleErrorBuilder::message(
            sprintf(
                '%s uses mutually exclusive attributes: %s.',
                $this->getNodeDescription($node, $classReflection->getDisplayName()),
                implode(', ', $attributeNames)
            )
        )->tip('Attributes that implement MutuallyExclusiveAttribute cannot be used together. Please choose only one.');

        return [$errorBuilder->build()];
    }

    /**
     * Finds all attributes on a node that implement the MutuallyExclusiveAttribute interface.
     *
     * @param Class_|Property|ClassMethod $node
     * @param Scope                       $scope
     *
     * @return string[] a list of fully qualified class names of the exclusive attributes found
     */
    private function findExclusiveAttributes(Node $node, Scope $scope): array
    {
        $exclusiveAttributes = [];

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrClassName = $this->resolveAttributeClassName($attr, $scope);
                if (!$attrClassName) {
                    continue;
                }

                if ($this->isMutuallyExclusive($attrClassName)) {
                    $exclusiveAttributes[] = $attrClassName;
                }
            }
        }

        return array_unique($exclusiveAttributes);
    }

    /**
     * Resolves the fully qualified class name of an attribute node.
     *
     * @param Attribute $attribute
     * @param Scope     $scope
     *
     * @return string|null
     */
    private function resolveAttributeClassName(Attribute $attribute, Scope $scope): ?string
    {
        return $scope->resolveName($attribute->name);
    }

    /**
     * Checks if a given attribute class is mutually exclusive.
     *
     * @param string $className the fully qualified class name of the attribute
     *
     * @return bool
     */
    private function isMutuallyExclusive(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->implementsInterface(MutuallyExclusiveAttribute::class);
    }

    /**
     * Gets the short name of a class from its fully qualified name.
     *
     * @param string $fqcn
     *
     * @return string
     */
    private function getShortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Gets a descriptive name for the node being processed.
     *
     * @param Class_|Property|ClassMethod $node
     * @param string                      $className
     *
     * @return string
     */
    private function getNodeDescription(Node $node, string $className): string
    {
        if ($node instanceof Class_) {
            return sprintf('Class "%s"', $className);
        }

        if ($node instanceof ClassMethod) {
            return sprintf('Method "%s::%s()"', $className, $node->name->toString());
        }

        if ($node instanceof Property) {
            // A property can have multiple props in one definition, but it's rare with attributes.
            $propName = $node->props[0]->name->toString();

            return sprintf('Property "%s::$%s"', $className, $propName);
        }

        return 'The code element';
    }
}
