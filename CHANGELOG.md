# Changelog

All notable changes to `code-generate-for-laravel` will be documented in this file.

## 2.0.0 - 2026-04-26

### ✨ Novas Funcionalidades

- **Configuração Flexível por Model**: Cada model pode ter sua própria configuração de campo, comprimento, prefixo e padrão de reset via método `codeGenerateConfig()` ou propriedade `$codeGenerateConfig`
- **Múltiplos Códigos por Model**: Suporte a múltiplos campos de código no mesmo model via `codeGenerateConfigs()`
- **Reset de Sequência**: Padrões automáticos de reinício de sequência (`Y`, `y`, `M`, `m`, `D`, `d`) para criar códigos como `ORD-2025-0001`
- **Cache de Schema**: Cache das informações do banco de dados para melhorar performance
- **Verificação de Colisões**: Verificação de unicidade com retry automático em caso de colisões
- **Macro `codeGenerates`**: Novo macro do Blueprint para criar múltiplas colunas de código de uma vez

### 🔧 Melhorias Técnicas

- **DTOs**: Introdução de DTOs (`FieldInfo`, `CodeConfig`, `CodeGenerateResult`) para tipagem forte
- **Type Hints**: Adicionados tipos em todos os métodos e propriedades
- **Drivers Refatorados**: Cada driver de banco agora retorna o DTO `FieldInfo`
- **Configuração Completa**: Arquivo `config/config.php` com todas as opções do package
- **Proteção em Updates**: Método `updateCode()` para atualização controlada de códigos

### 📦 Novos Arquivos

- `src/DTO/FieldInfo.php` - Informações do campo do banco
- `src/DTO/CodeConfig.php` - Configuração de geração de código
- `src/DTO/CodeGenerateResult.php` - Resultado da geração

### ⚠️ Breaking Changes

- O método `generate()` agora retorna `CodeGenerateResult` em vez de `string`
- A interface `DatabaseDriverInterface` mudou para usar `FieldInfo`
- Drivers agora usam `getFieldInfo()` em vez de `getFieldType()`

---

## 1.0.0 - 2025-11-10

- Primeira versão lançada
- Geração básica de códigos sequenciais
- Suporte a MySQL, PostgreSQL e SQL Server
- Trait `HasCodeGenerate` para models
- Macro `codeGenerate` para migrações
