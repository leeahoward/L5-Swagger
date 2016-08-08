<?php

// these methods
// found here http://mouf-php.com/blog/php_reflection_api_traits
// allow for finding the exect declared location of a method
// could be helpful with reflection
// when methods are defined in traits
// php only shows the class the are used in, not the defining class

namespace L5Swagger\Helpers;

use \ReflectionClass;
use \ReflectionMethod;

class ReflectionHelper
{
    /**
     * Finds the trait that declares $className::$propertyName
     */
    public static function getDeclaringTraitForProperty($className, $propertyName) {
        var_dump($className.".".$propertyName);
        $reflectionClass = new ReflectionClass($className);
         
        // Let's scan all traits
        $trait = self::deepScanTraitsForProperty($reflectionClass->getTraits(), $propertyName);
        if ($trait != null) {
            return $trait;
        }
        // The property is not part of the traits, let's find in which parent it is part of.
        if ($reflectionClass->getParentClass()) {
            $declaringClass = self::getDeclaringTraitForProperty($reflectionClass->getParentClass()->getName(), $propertyName);
            if ($declaringClass != null) {
                return $declaringClass;
            }
        }
        if ($reflectionClass->hasProperty($propertyName)) {
            return $reflectionClass;
        }
         
        return null;
    }
     
    /**
     * Recursive method called to detect a method into a nested array of traits.
     * 
     * @param $traits ReflectionClass[]
     * @param $propertyName string
     * @return ReflectionClass|null
     */
    public static function deepScanTraitsForProperty(array $traits, $propertyName) {
        foreach ($traits as $trait) {
            var_dump("trait:");
            var_dump($trait);
            // If the trait has a property, it's a win!
            $result = self::deepScanTraitsForProperty($trait->getTraits(), $propertyName);
            if ($result != null) {
                return $result;
            } else {
                if ($trait->hasProperty($propertyName)) {
                    return $trait;
                }
            }
        }
        return null;
    }


/**
 * Finds the trait that declares $className::$methodName
 */
public static function getDeclaringTrait($className, $methodName) {
    $reflectionClass = new ReflectionClass($className);
    $reflectionMethod = $reflectionClass->getMethod($methodName);
     
    $methodFile = $reflectionMethod->getFileName();
    $methodStartLine = $reflectionMethod->getStartLine();
    $methodEndLine = $reflectionMethod->getEndLine();
     
     
    // Let's scan all traits
    $trait = self::deepScanTraits($reflectionClass->getTraits(), $methodFile, $methodStartLine, $methodEndLine);
    if ($trait != null) {
        return $trait;
    } else {
        return $reflectionMethod->getDeclaringClass();
    }
}
 
/**
 * Recursive method called to detect a method into a nested array of traits.
 * 
 * @param $traits ReflectionClass[]
 * @param $methodFile string
 * @param $methodStartLine int
 * @param $methodEndLine int
 * @return ReflectionClass|null
 */
public static function deepScanTraits(array $traits, $methodFile, $methodStartLine, $methodEndLine) {
    foreach ($traits as $trait) {
        // If the trait has a method, is it the method we see?
        if ($trait->getFileName() == $methodFile
                && $trait->getStartLine() <= $methodStartLine
                && $trait->getEndLine() >= $methodEndLine) {
            return $trait;
        }
        return seif::deepScanTraits($trait->getTraits(), $methodFile, $methodStartLine, $methodEndLine);
    }
    return null;
}


    public static function getSignatureParamsForRoute($route) {
        $parameters = [];
        $alternateMethod= false;

        try {
           // Code that may throw an Exception or Error.
            $parameters= $route->signatureParameters();
            return $parameters;
        }
        catch (\Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5
            $alternateMethod = true;
        }
        catch (\Exception $e) {
            // Executed only in PHP 5, will not be reached in PHP 7
            $alternateMethod = true;
        }
        catch (\ReflectionException $e) {
            // Executed only in PHP 5, will not be reached in PHP 7
            $alternateMethod = true;
        }

        $action = $route->getAction();

        if ( $alternateMethod ) {
            if (is_string($action['uses'])) {
                list($class, $method) = explode('@', $action['uses']);
                var_dump("action is:\n");
                var_dump($action);
                $class_methods = get_class_methods($class);

                foreach ($class_methods as $method_name) {
                    echo $method_name. "\n";
                }
                $class = self::getDeclaringTrait($class,$method);

                $parameters = (new ReflectionMethod($class, $method))->getParameters();
            }
        }

        /*
        return is_null($subClass) ? $parameters : array_filter($parameters, function ($p) use ($subClass) {
            return $p->getClass() && $p->getClass()->isSubclassOf($subClass);
        });
        */
    }
}