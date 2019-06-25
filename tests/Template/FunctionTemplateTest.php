<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class FunctionTemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'validTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo("string"));',
            ],
            'validPsalmTemplatedFunctionType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @psalm-template T
                     * @psalm-param T $x
                     * @psalm-return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo("string"));',
            ],
            'validTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar(A::foo("string"));',
            ],
            'validTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar((new A())->foo("string"));',
            ],
            'genericArrayKeys' => [
                '<?php
                    /**
                     * @template T as array-key
                     *
                     * @param array<T, mixed> $arr
                     * @return array<int, T>
                     */
                    function my_array_keys($arr) {
                        return array_keys($arr);
                    }

                    $a = my_array_keys(["hello" => 5, "goodbye" => new \Exception()]);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'genericArrayFlip' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     * @return array<TValue, TKey>
                     */
                    function my_array_flip($arr) {
                        return array_flip($arr);
                    }

                    $b = my_array_flip(["hello" => 5, "goodbye" => 6]);',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'byRefKeyValueArray' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TKey as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     */
                    function byRef(array &$arr) : void {}

                    $b = ["a" => 5, "c" => 6];
                    byRef($b);',
                'assertions' => [
                    '$b' => 'array<string, int>',
                ],
            ],
            'byRefMixedKeyArray' => [
                '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<mixed, TValue> $arr
                     */
                    function byRef(array &$arr) : void {}

                    $b = ["a" => 5, "c" => 6];
                    byRef($b);',
                'assertions' => [
                    '$b' => 'array<mixed, int>',
                ],
            ],
            'mixedArrayPop' => [
                '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<array-key, TValue> $arr
                     * @return TValue|null
                     */
                    function my_array_pop(array &$arr) {
                        return array_pop($arr);
                    }

                    /** @var mixed */
                    $b = ["a" => 5, "c" => 6];
                    $a = my_array_pop($b);',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'array<array-key, mixed>',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'genericArrayPop' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TKey as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     * @return TValue|null
                     */
                    function my_array_pop(array &$arr) {
                        return array_pop($arr);
                    }

                    $b = ["a" => 5, "c" => 6];
                    $a = my_array_pop($b);',
                'assertions' => [
                    '$a' => 'null|int',
                    '$b' => 'array<string, int>',
                ],
            ],
            'templateCallableReturnType' => [
                '<?php
                    namespace NS;

                    /**
                     * @template T
                     * @psalm-param callable():T $action
                     * @psalm-return T
                     */
                    function retry(int $maxRetries, callable $action) {
                        return $action();
                    }

                    function takesInt(int $p): void{};

                    takesInt(retry(1, function(): int { return 1; }));',
            ],
            'templateClosureReturnType' => [
                '<?php
                    namespace NS;

                    /**
                     * @template T
                     * @psalm-param \Closure():T $action
                     * @psalm-return T
                     */
                    function retry(int $maxRetries, callable $action) {
                        return $action();
                    }

                    function takesInt(int $p): void{};

                    takesInt(retry(1, function(): int { return 1; }));',
            ],
            'replaceChildTypeWithGenerator' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @param Traversable<TKey, TValue> $t
                     * @return array<TKey, TValue>
                     */
                    function f(Traversable $t): array {
                        $ret = [];
                        foreach ($t as $k => $v) $ret[$k] = $v;
                        return $ret;
                    }

                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    takesArrayOfStdClass(f(g()));

                    /** @param array<stdClass> $p */
                    function takesArrayOfStdClass(array $p): void {}',
            ],

            'splatTemplateParam' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $arr
                     * @param array $arr2
                     * @return array<TKey, TValue>
                     */
                    function splat_proof(array $arr, array $arr2) {
                        return $arr;
                    }

                    $foo = [
                        [1, 2, 3],
                        [1, 2],
                    ];

                    $a = splat_proof(...$foo);',
                'assertions' => [
                    '$a' => 'array<int, int>',
                ],
            ],
            'passArrayByRef' => [
                '<?php
                    function acceptsStdClass(stdClass $_p): void {}

                    $q = [new stdClass];
                    acceptsStdClass(fNoRef($q));
                    acceptsStdClass(fRef($q));
                    acceptsStdClass(fNoRef($q));

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $_arr
                     * @return null|TValue
                     * @psalm-ignore-nullable-return
                     */
                    function fRef(array &$_arr) {
                        return array_shift($_arr);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $_arr
                     * @return null|TValue
                     * @psalm-ignore-nullable-return
                     */
                    function fNoRef(array $_arr) {
                        return array_shift($_arr);
                    }',
            ],

            'classTemplateAsCorrect' => [
                '<?php
                    class Foo {}
                    class FooChild extends Foo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new Foo());
                    bar(new FooChild());',
            ],
            'classTemplateOfCorrect' => [
                '<?php
                    class Foo {}
                    class FooChild extends Foo {}

                    /**
                     * @template T of Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new Foo());
                    bar(new FooChild());',
            ],
            'classTemplateAsInterface' => [
                '<?php
                    interface Foo {}
                    interface FooChild extends Foo {}
                    class FooImplementer implements Foo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    function takesFoo(Foo $f) : void {
                        bar($f);
                    }

                    function takesFooChild(FooChild $f) : void {
                        bar($f);
                    }

                    function takesFooImplementer(FooImplementer $f) : void {
                        bar($f);
                    }',
            ],
            'templateFunctionVar' => [
                '<?php
                    namespace A\B;

                    class C {
                        public function bar() : void {}
                    }

                    interface D {}

                    /**
                     * @template T as C
                     * @return T
                     */
                    function foo($some_t) : C {
                        /** @var T */
                        $a = $some_t;
                        $a->bar();

                        /** @var T&D */
                        $b = $some_t;
                        $b->bar();

                        /** @var D&T */
                        $b = $some_t;
                        $b->bar();

                        return $a;
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MissingParamType'],
            ],
            'returnClassString' => [
                '<?php
                    /**
                     * @template T
                     * @param T::class $s
                     * @return T::class
                     */
                    function foo(string $s) : string {
                        return $s;
                    }

                    /**
                     * @param  A::class $s
                     */
                    function bar(string $s) : void {
                    }

                    class A {}

                    bar(foo(A::class));',
            ],
            'callStaticMethodOnTemplatedClassName' => [
                '<?php
                    /**
                     * @template T
                     * @param class-string $class
                     * @template-typeof T $class
                     */
                    function foo(string $class, array $args) : void {
                        $class::bar($args);
                    }',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall'],
            ],
            'returnTemplatedClassClassName' => [
                '<?php
                    class I {
                        /**
                         * @template T as Foo
                         * @param class-string<T> $class
                         * @return T|null
                         */
                        public function loader(string $class) {
                            return $class::load();
                        }
                    }

                    class Foo {
                        /** @return static */
                        public static function load() {
                            return new static();
                        }
                    }

                    class FooChild extends Foo{}

                    $a = (new I)->loader(FooChild::class);',
                'assertions' => [
                    '$a' => 'null|FooChild',
                ],
            ],
            'upcastIterableToTraversable' => [
                '<?php
                    /**
                     * @template T as iterable
                     * @param T::class $class
                     */
                    function foo(string $class) : void {
                        $a = new $class();

                        foreach ($a as $b) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'upcastGenericIterableToGenericTraversable' => [
                '<?php
                    /**
                     * @template T as iterable<int>
                     * @param T::class $class
                     */
                    function foo(string $class) : void {
                        $a = new $class();

                        foreach ($a as $b) {}
                    }',
                'assertions' => [],
                'error_levels' => [],
            ],
            'bindFirstTemplatedClosureParameter' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void
                    {
                        $t1($t2);
                    }

                    apply(function(int $_i) : void {}, 5);
                    apply(function(string $_i) : void {}, "hello");
                    apply(function(stdClass $_i) : void {}, new stdClass);

                    class A {}
                    class AChild extends A {}

                    apply(function(A $_i) : void {}, new AChild());',
            ],
            'callableReturnsItself' => [
                '<?php
                    $a =
                      /**
                       * @param callable():string $s
                       * @return string
                       */
                      function(callable $s) {
                        return $s();
                      };

                    /**
                     * @template T1
                     * @param callable(callable():T1):T1 $s
                     * @return void
                     */
                    function takesReturnTCallable(callable $s) {}

                    takesReturnTCallable($a);',
            ],
            'nonBindingParamReturn' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure():T $t1
                     * @param T $t2
                     */
                    function foo(Closure $t1, $t2) : void {}
                    foo(
                        function () : int {
                            return 5;
                        },
                        "hello"
                    );',
            ],
            'templatedInterfaceMethodInheritReturnType' => [
                '<?php
                    class Foo {}

                    class SomeIterator implements IteratorAggregate
                    {
                        public function getIterator() {
                            yield new Foo;
                        }
                    }

                    $i = (new SomeIterator())->getIterator();',
                [
                    '$i' => 'Traversable<mixed, mixed>',
                ],
            ],
            'upcastArrayToIterable' => [
                '<?php
                    /**
                     * @template K
                     * @template V
                     * @param iterable<K,V> $collection
                     * @return V
                     * @psalm-suppress InvalidReturnType
                     */
                    function first($collection) {}

                    $one = first([1,2,3]);',
                [
                    '$one' => 'int',
                ],
            ],
            'understandTemplatedCalculationInOtherFunction' => [
                '<?php
                    /**
                     * @template T as Exception
                     * @param T::class $type
                     * @return T
                     */
                    function a(string $type): Exception {
                        return new $type;
                    }

                    /**
                     * @template T as InvalidArgumentException
                     * @param T::class $type
                     * @return T
                     */
                    function b(string $type): InvalidArgumentException {
                        return a($type);
                    }',
            ],
            'objectReturn' => [
                '<?php
                    /**
                     * @template T as object
                     *
                     * @param class-string<T> $foo
                     *
                     * @return T
                     */
                    function Foo(string $foo) : object {
                      return new $foo;
                    }

                    echo Foo(DateTime::class)->format("c");',
            ],
            'templateIntersectionLeft' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $a
                     */
                    function templatedBar(I1 $a) : void {}',
            ],
            'templateIntersectionRight' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $b
                     */
                    function templatedBar(I2 $b) : void {}',
            ],
            'matchMostSpecificTemplate' => [
                '<?php
                    /**
                     * @template TReturn
                     * @param callable():(\Generator<mixed, mixed, mixed, TReturn>|TReturn) $gen
                     * @return array<int, TReturn>
                     */
                    function call(callable $gen) : array {
                        $return = $gen();
                        if ($return instanceof Generator) {
                            return [$gen->getReturn()];
                        }
                        return [$gen];
                    }

                    $arr = call(
                        /**
                         * @return Generator<mixed, mixed, mixed, string>
                         */
                        function() {
                            yield 1;
                            return "hello";
                        }
                    );',
                [
                    '$arr' => 'array<int, string>',
                ],
            ],
            'templatedClassStringParamAsClass' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class E {
                        /**
                         * @template T as C
                         * @param class-string<T> $c_class
                         *
                         * @return C
                         * @psalm-return T
                         */
                        public static function get(string $c_class) : C {
                            $c = new $c_class;
                            $c->foo();
                            return $c;
                        }
                    }

                    /**
                     * @param class-string<C> $c_class
                     */
                    function bar(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->foo();
                    }

                    /**
                     * @psalm-suppress TypeCoercion
                     */
                    function bat(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->foo();
                    }',
            ],
            'templatedClassStringParamAsObject' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class E {
                        /**
                         * @template T as object
                         * @param class-string<T> $c_class
                         *
                         * @psalm-return T
                         */
                        public static function get(string $c_class) {
                            return new $c_class;
                        }
                    }

                    /**
                     * @psalm-suppress TypeCoercion
                     */
                    function bat(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->bar = "bax";
                    }',
            ],
            'templatedClassStringParamMoreSpecific' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class D extends C {
                        public function faa() : void{}
                    }

                    class E {
                        /**
                         * @template T as C
                         * @param class-string<T> $c_class
                         *
                         * @return C
                         * @psalm-return T
                         */
                        public static function get(string $c_class) : C {
                            $c = new $c_class;
                            $c->foo();
                            return $c;
                        }
                    }

                    /**
                     * @param class-string<D> $d_class
                     */
                    function moreSpecific(string $d_class) : void {
                        $d = E::get($d_class);
                        $d->foo();
                        $d->faa();
                    }',
            ],
            'templateOfWithSpace' => [
                '<?php
                    /**
                     * @template T of array<int, mixed>
                     */
                    class Foo
                    {
                    }

                    /**
                     * @param Foo<array<int, DateTime>> $a
                     */
                    function bar(Foo $a) : void {}',
            ],
            'allowUnionTypeParam' => [
                '<?php
                    /**
                     * @template T
                     * @param callable(T) $x
                     * @param array<T> $y
                     */
                    function example($x, $y): void {}

                    example(
                        /**
                         * @param int|false $x
                         */
                        function($x): void {},
                        [strpos("str", "str")]
                    );',
            ],
            'ignoreTooManyArrayArgs' => [
                '<?php

                    function takesArray(array $arr) : void {}

                    /**
                     * @psalm-suppress TooManyTemplateParams
                     * @var array<int, int, int>
                     */
                    $b = [1, 2, 3];
                    takesArray($b);',
            ],
            'functionTemplateUnionType' => [
                '<?php
                    /**
                     * @template T0 as int|string
                     * @param T0 $t
                     * @return T0
                     */
                    function foo($t) {
                        return $t;
                    }

                    $s = foo("hello");
                    $i = foo(5);',
                'assertions' => [
                    '$s' => 'string',
                    '$i' => 'int',
                ],
            ],
            'reconcileTraversableTemplatedAndNormal' => [
                '<?php
                    function foo(Traversable $t): void {
                        if ($t instanceof IteratorAggregate) {
                            $a = $t->getIterator();
                            $t = $a;
                        }

                        if (!$t instanceof Iterator) {
                            return;
                        }

                        if (rand(0, 1) && rand(0, 1)) {
                            $t->next();
                        }
                    }',
            ],
            'templateArrayIntersection' => [
                '<?php
                    /**
                     * @template T as object
                     * @template S as object
                     * @param array<T> $a
                     * @param class-string<S> $type
                     * @return array<T&S>
                     */
                    function filter(array $a, string $type): array {
                        $result = [];
                        foreach ($a as $item) {
                            if (is_a($item, $type)) {
                                $result[] = $item;
                            }
                        }
                        return $result;
                    }

                    interface A {}
                    interface B {}

                    /** @var array<A> */
                    $x = [];
                    $y = filter($x, B::class);',
                [
                    '$y' => 'array<array-key, A&B>',
                ]
            ],
            'keyOfTemplate' => [
                '<?php
                    /**
                     * @template T as array
                     * @template K as key-of<T>
                     *
                     * @param T $o
                     * @param K $name
                     *
                     * @return T[K]
                     */
                    function getOffset(array $o, $name) {
                        return $o[$name];
                    }

                    $a = ["foo" => "hello", "bar" => 2];

                    $b = getOffset($a, "foo");
                    $c = getOffset($a, "bar");',
                [
                    '$b' => 'string',
                    '$c' => 'int',
                ]
            ],
            'unionTOrClassStringTPassedClassString' => [
                '<?php
                    /**
                     * @psalm-template T of object
                     * @psalm-param T|class-string<T> $someType
                     * @psalm-return T
                     */
                    function getObject($someType) {
                        if (is_object($someType)) {
                            return $someType;
                        }

                        return new $someType();
                    }

                    class C {
                        function sayHello() : string {
                            return "hi";
                        }
                    }

                    getObject(C::class)->sayHello();'
            ],
            'unionTOrClassStringTPassedObject' => [
                '<?php
                    /**
                     * @psalm-template T of object
                     * @psalm-param T|class-string<T> $someType
                     * @psalm-return T
                     */
                    function getObject($someType) {
                        if (is_object($someType)) {
                            return $someType;
                        }

                        return new $someType();
                    }

                    class C {
                        function sayHello() : string {
                            return "hi";
                        }
                    }

                    getObject(new C())->sayHello();'
            ],
            'dontModifyByRefTemplatedArray' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T of object
                     * @param class-string<T> $className
                     * @param array<T> $map
                     * @param-out array<T> $map
                     * @param int $id
                     * @return T
                     */
                    function get(string $className, array &$map, int $id) {
                        if(!array_key_exists($id, $map)) {
                            $map[$id] = new $className();
                        }
                        return $map[$id];
                    }

                    /**
                     * @param array<A> $mapA
                     */
                    function getA(int $id, array $mapA): A {
                        return get(A::class, $mapA, $id);
                    }

                    /**
                     * @param array<B> $mapB
                     */
                    function getB(int $id, array $mapB): B {
                        return get(B::class, $mapB, $id);
                    }'
            ],
            'dontGeneraliseBoundParamWithWiderCallable' => [
                '<?php
                    class C {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-template T
                     * @psalm-param T $t
                     * @psalm-param callable(?T):void $callable
                     * @return T
                     */
                    function makeConcrete($t, callable $callable) {
                        $callable(rand(0, 1) ? $t : null);
                        return $t;
                    }

                    $c = makeConcrete(new C(), function (?C $c) : void {});',
                [
                    '$c' => 'C',
                ]
            ],
            'unionClassStringTWithTReturnsObjectWhenCoerced' => [
                '<?php
                    /**
                     * @template T as object
                     * @param T|class-string<T> $s
                     * @return T
                     */
                    function bar($s) {
                        if (is_object($s)) {
                            return $s;
                        }

                        return new $s();
                    }

                    function foo(string $s) : object {
                        /** @psalm-suppress ArgumentTypeCoercion */
                        return bar($s);
                    }'
            ],

            'allowTemplatedIntersectionFirst' => [
                '<?php
                    class MockObject
                    {
                        public function checkExpectations() : void
                        {
                        }
                    }

                    /**
                     * @psalm-template RequestedType
                     * @psalm-param class-string<RequestedType> $className
                     * @psalm-return RequestedType&MockObject
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function mock(string $className)
                    {
                        eval(\'"there be dragons"\');

                        return $instance;
                    }

                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-template UnknownType
                     * @psalm-param class-string<UnknownType> $className
                     */
                    function useMockTemplated(string $className) : void
                    {
                        mock($className)->checkExpectations();
                    }

                    mock(A::class)->foo();'
            ],
            'allowTemplatedIntersectionFirstTemplatedMock' => [
                '<?php
                    class MockObject
                    {
                        public function checkExpectations() : void
                        {
                        }
                    }

                    /**
                     * @psalm-template RequestedType
                     * @psalm-param class-string<RequestedType> $className
                     * @psalm-return RequestedType&MockObject
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function mock(string $className)
                    {
                        eval(\'"there be dragons"\');

                        return $instance;
                    }

                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-template UnknownType
                     * @psalm-param class-string<UnknownType> $className
                     */
                    function useMockTemplated(string $className) : void
                    {
                        mock($className)->checkExpectations();
                    }

                    mock(A::class)->foo();'
            ],
            'allowTemplatedIntersectionSecond' => [
                '<?php
                    class MockObject
                    {
                        public function checkExpectations() : void
                        {
                        }
                    }

                    /**
                     * @psalm-template RequestedType
                     * @psalm-param class-string<RequestedType> $className
                     * @psalm-return MockObject&RequestedType
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function mock(string $className)
                    {
                        eval(\'"there be dragons"\');

                        return $instance;
                    }

                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-param class-string $className
                     */
                    function useMock(string $className) : void {
                        mock($className)->checkExpectations();
                    }

                    /**
                     * @psalm-template UnknownType
                     * @psalm-param class-string<UnknownType> $className
                     */
                    function useMockTemplated(string $className) : void
                    {
                        mock($className)->checkExpectations();
                    }

                    mock(A::class)->foo();'
            ],
            'allowTemplateTypeBeingUsedInsideFunction' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @param callable(T) $callable
                     * @param T $value
                     */
                    function foo(callable $callable, DateTime $value) : void {
                        $callable($value);
                    }',
            ],
            'callFindAnother' => [
                '<?php
                    /**
                     * @template T as Foo
                     * @param T $foo
                     * @return T
                     */
                    function loader($foo) {
                        return $foo::getAnother();
                    }

                    class Foo {
                        /** @return static */
                        public static function getAnother() {
                            return new static();
                        }
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar(A::foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar((new A())->foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'replaceChildTypeNoHint' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @param Traversable<TKey, TValue> $t
                     * @return array<TKey, TValue>
                     */
                    function f(Traversable $t): array {
                        $ret = [];
                        foreach ($t as $k => $v) $ret[$k] = $v;
                        return $ret;
                    }

                    function g():Generator { yield new stdClass; }

                    takesArrayOfStdClass(f(g()));

                    /** @param array<stdClass> $p */
                    function takesArrayOfStdClass(array $p): void {}',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'classTemplateAsIncorrectClass' => [
                '<?php
                    class Foo {}
                    class NotFoo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new NotFoo());',
                'error_message' => 'InvalidArgument',
            ],
            'classTemplateAsIncorrectInterface' => [
                '<?php
                    interface Foo {}
                    interface NotFoo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    function takesNotFoo(NotFoo $f) : void {
                        bar($f);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'templateFunctionMethodCallWithoutMethod' => [
                '<?php
                    namespace A\B;

                    class C {}

                    /**
                     * @template T as C
                     * @param T $some_t
                     */
                    function foo($some_t) : void {
                        $some_t->bar();
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'templateFunctionMethodCallWithoutAsType' => [
                '<?php
                    namespace A\B;

                    /**
                     * @template T
                     * @param T $some_t
                     */
                    function foo($some_t) : void {
                        $some_t->bar();
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'forbidLossOfInformationWhenCoercing' => [
                '<?php
                    /**
                     * @template T as iterable<int>
                     * @param T::class $class
                     */
                    function foo(string $class) : void {}

                    function bar(Traversable $t) : void {
                        foo(get_class($t));
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'bindFirstTemplatedClosureParameter' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void
                    {
                        $t1($t2);
                    }

                    apply(function(int $_i) : void {}, "hello");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'bindFirstTemplatedClosureParameterTypeCoercion' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void
                    {
                        $t1($t2);
                    }

                    class A {}
                    class AChild extends A {}

                    apply(function(AChild $_i) : void {}, new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],

            'callableDoesNotReturnItself' => [
                '<?php
                    $b =
                      /**
                       * @param callable():int $s
                       * @return string
                       */
                      function(callable $s) {
                        return "#" . $s();
                      };

                    /**
                     * @template T1
                     * @param callable(callable():T1):T1 $s
                     * @return void
                     */
                    function takesReturnTCallable(callable $s) {}

                    takesReturnTCallable($b);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'multipleArgConstraintWithMoreRestrictiveFirstArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(AChild $_a) : void {},
                      function(A $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'multipleArgConstraintWithMoreRestrictiveSecondArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(A $_a) : void {},
                      function(AChild $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'multipleArgConstraintWithLessRestrictiveThirdArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(AChild $_a) : void {},
                      function(AChild $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'possiblyInvalidArgumentWithUnionFirstArg' => [
                '<?php

                    /**
                     * @template T
                     * @param T $a
                     * @param T $b
                     * @return T
                     */
                    function foo($a, $b) {
                      return rand(0, 1) ? $a : $b;
                    }

                    echo foo([], "hello");',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'possiblyInvalidArgumentWithUnionSecondArg' => [
                '<?php

                    /**
                     * @template T
                     * @param T $a
                     * @param T $b
                     * @return T
                     */
                    function foo($a, $b) {
                      return rand(0, 1) ? $a : $b;
                    }

                    echo foo("hello", []);',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'copyScopedClassInFunction' => [
                '<?php
                    /**
                     * @template Throwable as DOMNode
                     *
                     * @param class-string<Throwable> $foo
                     */
                    function Foo(string $foo) : string {
                        return $foo;
                    }',
                'error_message' => 'ReservedWord',
            ],
            'copyScopedClassInNamespacedFunction' => [
                '<?php
                    namespace Foo;

                    class Bar {}

                    /**
                     * @template Bar as DOMNode
                     *
                     * @param class-string<Bar> $foo
                     */
                    function Foo(string $foo) : string {
                        return $foo;
                    }',
                'error_message' => 'ReservedWord',
            ],
            'constrainTemplateTypeWhenClassStringUsed' => [
                '<?php
                    class GenericObjectFactory {
                       /**
                        * @psalm-template T
                        * @psalm-param class-string<T> $type
                        * @psalm-return T
                        */
                        public function getObject(string $type)
                        {
                            return 3;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
            'preventTemplateTypeAsBeingUsedInsideFunction' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @param callable(T) $callable
                     */
                    function foo(callable $callable) : void {
                        $callable(new \DateTime());
                    }',
                'error_message' => 'InvalidArgument'
            ],
            'preventWrongTemplateBeingPassed' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @template T2 of DateTime
                     * @param callable(T): T $parameter
                     * @param T2 $value
                     * @return T
                     */
                    function foo(callable $parameter, $value)
                    {
                        return $parameter($value);
                    }',
                'error_message' => 'InvalidArgument'
            ],
            'preventTemplateTypeReturnMoreGeneral' => [
                '<?php
                    /**
                     * @template T of DateTimeInterface
                     * @param T $x
                     * @return T
                     */
                    function foo($x)
                    {
                        return new \DateTime();
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}