<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;

return static function (RectorConfig $rectorConfig): void {
    // Registra os caminhos para analisar
    $rectorConfig->paths([
        __DIR__ . '/inc',
    ]);

    // Ignora arquivos/diretórios específicos
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '*/cache/*',
        __DIR__ . '*/temp/*',
    ]);

    // Define o conjunto de regras para o PHP 8.4
    $rectorConfig->sets([
        SetList::PHP_84,
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ]);

    // Ativa regras específicas úteis
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->rule(StaticClosureRector::class);
    $rectorConfig->rule(RemoveParentCallWithoutParentRector::class);

    // Regras para simplificar if/else
    $rectorConfig->rule(ChangeIfElseValueAssignToEarlyReturnRector::class);
    $rectorConfig->rule(RemoveAlwaysElseRector::class);
    $rectorConfig->rule(PreparedValueToEarlyReturnRector::class);
    $rectorConfig->rule(ReturnEarlyIfVariableRector::class);
    $rectorConfig->rule(SimplifyIfReturnBoolRector::class);
    $rectorConfig->rule(ShortenElseIfRector::class);

    // Configurações adicionais importantes
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
    $rectorConfig->removeUnusedImports();

    // Usa cache para melhor performance
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');

    // Processar apenas arquivos modificados
    $rectorConfig->parallel();

    // Indentação e formatação
    $rectorConfig->indent(' ', 4);
};