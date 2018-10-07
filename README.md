Worse Completion
================

[![Build Status](https://travis-ci.org/phpactor/completion.svg?branch=master)](https://travis-ci.org/phpactor/completion)

PHP Code Completion library which uses
[Phpactor's](https://github.com/phpactor/phpactor) [Worse
Reflection](https://github.com/phpactor/worse-reflection).

Usage
-----

Each completor implements the `Completor` interface which accepts the source
code as a string and a byte offset from which to complete from. The completor
must `yield` instances of the `Suggestion` class:

```php
$completor = new MyCompletor();
$suggestions = $completor->complete($sourceCode, $byteOffset);

/** @var Suggestion $suggestion */
foreach ($suggestions as $suggestion) {
    echo $suggestion->name();
    echo $suggestion->shortDescription();
}
```

Multiple completors can be chained together with the ChainCompletor:

```php
$completor = new ChainCompletor([
    new MyCompletor1(),
    new MyCompletor2(),
]);

$suggestions = $completor->complete($sourceCode, $byteOffset);
```

Tolerant Completors
-------------------

The library currently includes a suite of completors using the Tolerant PHP
Parser and [WorseReflection](https://github.com/phpactor/worse-reflection).
All of the tolerant completors are instances of `TolerantCompletorInterface`
and accept a parser node rather than a byte offset. They can be collected in a
`TolerantChainCompletor` class which in turn implements the primary
`Completor` interface:

```php
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseLocalVariableCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseClassMemberCompletor;

$reflector = ReflectorBuilder::create()->addSource($sourceCode)->build();
$formatter = new ObjectFormatter([
    // ... instances of ObjectFormatter
]);
$completor = new ChainTolerantCompletor([
    new WorseLocalVariableCompletor($reflector, $formatter),
    new WorseClassMemberCompletor($reflector, $formatter),
]);

$completor->complete($sourceCode, $byteOffset);
```

Formatters
----------

This library can format arbitrary objects as strings, for example to go from a
`ReflectionMethod` to the synopsis: `pub function($foobar)`.

```php
$formatter = new ObjectFormatter([
    new TypeFormatter(),
    new TypesFormatter(),
    new FunctionFormatter(),
    new MethodFormatter(),
    new ParameterFormatter(),
    new ParametersFormatter(),
    new PropertyFormatter(),
    new VariableFormatter(),
]);

$reflectionMethod = $reflector->reflectClass(Foobar::class)->methods()->get('barfoo');
$formatter->format($reflectionMethod);
```

Each formatter is able to indicate if it can format a given object, and if so
it is chosen.
