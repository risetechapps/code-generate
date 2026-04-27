<?php

/*
 * Configuração do Laravel Code Generate
 *
 * Este package gera automaticamente códigos sequenciais para seus models.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cache do Schema
    |--------------------------------------------------------------------------
    |
    | Habilita o cache das informações do schema do banco de dados.
    | Isso melhora a performance ao evitar consultas repetidas ao
    | information_schema. Recomendado para produção.
    |
    */
    'cache_schema' => env('CODE_GENERATE_CACHE_SCHEMA', false),

    /*
    |--------------------------------------------------------------------------
    | Store de Cache
    |--------------------------------------------------------------------------
    |
    | Define qual store de cache usar para o schema. Se null, usará
    | a store padrão da aplicação.
    |
    */
    'cache_store' => env('CODE_GENERATE_CACHE_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | TTL do Cache
    |--------------------------------------------------------------------------
    |
    | Tempo em segundos que as informações do schema ficam em cache.
    | Padrão: 1 hora (3600 segundos)
    |
    */
    'cache_ttl' => env('CODE_GENERATE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Lançar Exceções
    |--------------------------------------------------------------------------
    |
    | Se true, lança exceções quando não consegue gerar um código.
    | Se false, apenas loga o erro e continua sem código.
    |
    */
    'throw_on_error' => env('CODE_GENERATE_THROW_ON_ERROR', true),

    /*
    |--------------------------------------------------------------------------
    | Comprimento Padrão
    |--------------------------------------------------------------------------
    |
    | Comprimento padrão dos códigos gerados.
    |
    */
    'default_length' => env('CODE_GENERATE_DEFAULT_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | Campo Padrão
    |--------------------------------------------------------------------------
    |
    | Nome padrão do campo que receberá o código gerado.
    |
    */
    'default_field' => env('CODE_GENERATE_DEFAULT_FIELD', 'code'),

    /*
    |--------------------------------------------------------------------------
    | Prefixo Padrão
    |--------------------------------------------------------------------------
    |
    | Prefixo padrão para os códigos gerados.
    |
    */
    'default_prefix' => env('CODE_GENERATE_DEFAULT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Tentativas de Colisão
    |--------------------------------------------------------------------------
    |
    | Número máximo de tentativas para resolver colisões de código.
    | Aumente se estiver tendo problemas com concorrência alta.
    |
    */
    'max_collision_attempts' => env('CODE_GENERATE_MAX_COLLISION_ATTEMPTS', 5),

];
