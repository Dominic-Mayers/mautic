<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractPhpdocToTypeDeclarationFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class PhpdocToReturnTypeFixer extends AbstractPhpdocToTypeDeclarationFixer
{
    private const TYPE_CHECK_TEMPLATE = '<?php function f(): %s {}';
    /**
     * @var array<int, array<int, int|string>>
     */
    private $excludeFuncNames = [[\T_STRING, '__construct'], [\T_STRING, '__destruct'], [\T_STRING, '__clone']];
    /**
     * @var array<string, true>
     */
    private $skippedTypes = ['resource' => \true, 'null' => \true];
    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition('EXPERIMENTAL: Takes `@return` annotation of non-mixed types and adjusts accordingly the function signature.', [new CodeSample('<?php

/** @return \\My\\Bar */
function f1()
{}

/** @return void */
function f2()
{}

/** @return object */
function my_foo()
{}
'), new CodeSample('<?php

/** @return Foo */
function foo() {}
/** @return string */
function bar() {}
', ['scalar_types' => \false]), new VersionSpecificCodeSample('<?php
final class Foo {
    /**
     * @return static
     */
    public function create($prototype) {
        return new static($prototype);
    }
}
', new VersionSpecification(80000))], null, 'This rule is EXPERIMENTAL and [1] is not covered with backward compatibility promise. [2] `@return` annotation is mandatory for the fixer to make changes, signatures of methods without it (no docblock, inheritdocs) will not be fixed. [3] Manual actions are required if inherited signatures are not properly documented.');
    }
    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound([\T_FUNCTION, \T_FN]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before FullyQualifiedStrictTypesFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAlignFixer, ReturnToYieldFromFixer, ReturnTypeDeclarationFixer.
     * Must run after AlignMultilineCommentFixer, CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority() : int
    {
        return 13;
    }
    protected function isSkippedType(string $type) : bool
    {
        return isset($this->skippedTypes[$type]);
    }
    protected function applyFix(\SplFileInfo $file, Tokens $tokens) : void
    {
        for ($index = $tokens->count() - 1; 0 < $index; --$index) {
            if (!$tokens[$index]->isGivenKind([\T_FUNCTION, \T_FN])) {
                continue;
            }
            $funcName = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$funcName]->equalsAny($this->excludeFuncNames, \false)) {
                continue;
            }
            $docCommentIndex = $this->findFunctionDocComment($tokens, $index);
            if (null === $docCommentIndex) {
                continue;
            }
            $returnTypeAnnotations = $this->getAnnotationsFromDocComment('return', $tokens, $docCommentIndex);
            if (1 !== \count($returnTypeAnnotations)) {
                continue;
            }
            /** @var Annotation $returnTypeAnnotation */
            $returnTypeAnnotation = \current($returnTypeAnnotations);
            $typesExpression = $returnTypeAnnotation->getTypeExpression();
            if (null === $typesExpression) {
                continue;
            }
            $typeInfo = $this->getCommonTypeInfo($typesExpression, \true);
            $unionTypes = null;
            if (null === $typeInfo) {
                $unionTypes = $this->getUnionTypes($typesExpression, \true);
            }
            if (null === $typeInfo && null === $unionTypes) {
                continue;
            }
            if (null !== $typeInfo) {
                [$returnType, $isNullable] = $typeInfo;
            } elseif (null !== $unionTypes) {
                $returnType = $unionTypes;
                $isNullable = \false;
            }
            if (!isset($returnType, $isNullable)) {
                continue;
            }
            $startIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);
            if ($this->hasReturnTypeHint($tokens, $startIndex)) {
                continue;
            }
            if (!$this->isValidSyntax(\sprintf(self::TYPE_CHECK_TEMPLATE, $returnType))) {
                continue;
            }
            $endFuncIndex = $tokens->getPrevTokenOfKind($startIndex, [')']);
            $tokens->insertAt($endFuncIndex + 1, \array_merge([new Token([CT::T_TYPE_COLON, ':']), new Token([\T_WHITESPACE, ' '])], $this->createTypeDeclarationTokens($returnType, $isNullable)));
        }
    }
    protected function createTokensFromRawType(string $type) : Tokens
    {
        $typeTokens = Tokens::fromCode(\sprintf(self::TYPE_CHECK_TEMPLATE, $type));
        $typeTokens->clearRange(0, 7);
        $typeTokens->clearRange(\count($typeTokens) - 3, \count($typeTokens) - 1);
        $typeTokens->clearEmptyTokens();
        return $typeTokens;
    }
    /**
     * Determine whether the function already has a return type hint.
     *
     * @param int $index The index of the end of the function definition line, EG at { or ;
     */
    private function hasReturnTypeHint(Tokens $tokens, int $index) : bool
    {
        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $nextIndex = $tokens->getNextMeaningfulToken($endFuncIndex);
        return $tokens[$nextIndex]->isGivenKind(CT::T_TYPE_COLON);
    }
}
