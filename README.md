# Laravel Code Generate

## 📌 Sobre o Projeto

O **Laravel Code Generate** é um package para Laravel que gera automaticamente códigos sequenciais para os registros do seu banco de dados. Suporta MySQL, PostgreSQL e SQL Server.

## ✨ Funcionalidades

- 🔢 **Código Automático** - Gera códigos sequenciais automaticamente (ex: 0001, 0002, A0001)
- 📝 **Prefixos Customizáveis** - Adicione prefixos aos códigos (ex: ORD-0001, CLI-0001)
- 🔄 **Reset de Sequência** - Reinicie a sequência automaticamente (anual, mensal, diário)
- 🚀 **Múltiplos Códigos** - Suporte a vários campos de código no mesmo model
- ⚡ **Cache de Schema** - Melhora performance com cache das informações do banco
- 🛡️ **Proteção Contra Colisões** - Verificação de unicidade com retry automático
- 💾 **Múltiplos Drivers** - MySQL, PostgreSQL e SQL Server

---

## 🚀 Instalação

### Requisitos

- PHP >= 8.1
- Laravel >= 12

### Instalação

```bash
composer require risetechapps/code-generate-for-laravel
```

### Publicar Configuração

```bash
php artisan vendor:publish --tag=code-generate-config
```

---

## 📝 Uso Básico

### 1. Criar a Migração

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->codeGenerate(); // Cria campo 'code' varchar(4) unique
            $table->string('customer_name');
            $table->timestamps();
        });
    }
};
```

### 2. Adicionar a Trait ao Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RiseTechApps\CodeGenerate\Traits\HasCodeGenerate;

class Order extends Model
{
    use HasCodeGenerate;
}
```

### 3. Usar o Model

```php
// O código será gerado automaticamente: "0001", "0002", etc.
$order = Order::create(['customer_name' => 'João Silva']);
echo $order->code; // "0001"

$order2 = Order::create(['customer_name' => 'Maria Santos']);
echo $order2->code; // "0002"
```

---

## ⚙️ Configuração por Model

### Configuração Simples (Propriedades)

```php
class Order extends Model
{
    use HasCodeGenerate;

    protected $codeField = 'order_number';  // Campo personalizado
    protected $codeLength = 6;                // 6 dígitos
    protected $codePrefix = 'ORD-';           // Prefixo
}
// Resultado: ORD-000001, ORD-000002, ...
```

### Configuração Avançada (Método)

```php
class Order extends Model
{
    use HasCodeGenerate;

    public function codeGenerateConfig(): array
    {
        return [
            'field' => 'order_number',
            'length' => 8,
            'prefix' => 'ORD-',
            'resetPattern' => 'Y',  // Reinicia a cada ano
            'unique' => true,
            'type' => 'string',
        ];
    }
}
// Resultado 2025: ORD-20250001, ORD-20250002
// Resultado 2026: ORD-20260001, ORD-20260002 (reiniciou)
```

### Múltiplos Códigos no Mesmo Model

```php
class Order extends Model
{
    use HasCodeGenerate;

    public function codeGenerateConfigs(): array
    {
        return [
            [
                'field' => 'public_code',
                'length' => 6,
                'prefix' => 'PUB-',
            ],
            [
                'field' => 'internal_code',
                'length' => 8,
                'prefix' => 'INT-',
                'resetPattern' => 'M',  // Reinicia mensalmente
            ],
        ];
    }
}

// Migração:
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->codeGenerate('public_code', 10, true, 'string');
    $table->codeGenerate('internal_code', 12, true, 'string');
    $table->string('customer_name');
    $table->timestamps();
});
```

---

## 🔢 Padrões de Reset de Sequência

| Padrão | Descrição               | Exemplo de Código |
|--------|-------------------------|-------------------|
| `Y` | Anual                   | ORD-20250001, ORD-20260001 |
| `y` | Anual (2 dígitos)       | ORD-250001, ORD-260001 |
| `M` | Mensal                  | ORD-2025-010001, ORD-2025-020001 |
| `m` | Mensal (compacto)       | ORD-2025010001, ORD-2025020001 |
| `D` | Diário                  | ORD-2025-01-150001 |
| `d` | Diário (compacto)       | ORD-202501150001 |
| Custom | Qualquer formato date() | ORD-Jan-0001 |

```php
public function codeGenerateConfig(): array
{
    return [
        'field' => 'code',
        'length' => 10,
        'prefix' => 'ORD-',
        'resetPattern' => 'Y-m',  // Reinicia mensalmente: ORD-2025-010001
    ];
}
```

---

## 🗄️ Migrações Avançadas

### Tipos de Coluna

```php
Schema::create('orders', function (Blueprint $table) {
    // String (padrão)
    $table->codeGenerate('code', 6, true, 'string');

    // Char
    $table->codeGenerate('short_code', 4, true, 'char');

    // Integer (apenas prefixos numéricos!)
    $table->codeGenerate('numeric_id', null, true, 'integer');

    // Big Integer
    $table->codeGenerate('big_id', null, true, 'bigint');

    // Múltiplas colunas de uma vez
    $table->codeGenerates([
        ['column' => 'code1', 'length' => 6, 'unique' => true],
        ['column' => 'code2', 'length' => 8, 'unique' => false],
    ]);
});
```

---

## ⚡ Cache de Schema

Ative o cache para melhorar performance em produção:

```bash
# .env
CODE_GENERATE_CACHE_SCHEMA=true
CODE_GENERATE_CACHE_TTL=3600
```

Ou manualmente:

```php
use RiseTechApps\CodeGenerate\Facades\CodeGenerate;

// Limpar cache de uma tabela específica
CodeGenerate::clearSchemaCache('orders', 'code');

// Limpar todo o cache
CodeGenerate::clearAllCache();
```

---

## 🛠️ Métodos Úteis

### Na Trait

```php
$order = Order::first();

// Atualizar código manualmente
$order->updateCode('order_number', 'NOVO-9999');

// Regenerar todos os códigos
$order->regenerateCodes();

// Verificar configuração
$config = $order->getCodeConfig('order_number');
echo $config->prefix;  // "ORD-"
```

### Na Classe CodeGenerate

```php
use RiseTechApps\CodeGenerate\CodeGenerate;
use RiseTechApps\CodeGenerate\DTO\CodeConfig;

// Gerar código manualmente com configuração específica
$config = new CodeConfig(
    field: 'custom_code',
    length: 8,
    prefix: 'CUST-',
);

$result = CodeGenerate::generate(Order::class, $config);
echo $result->code;      // CUST-00000001
echo $result->field;     // custom_code
echo $result->success;   // true
```

---

## 🔒 Proteção de Códigos

Por padrão, o código não pode ser alterado em updates:

```php
$order = Order::first();
$order->code = 'HACKED';
$order->save();

echo $order->fresh()->code;  // Código original preservado!
```

### Permitir Alteração Temporária

```php
// Para todas as operações
Order::ignoreCodeGenerateUpdating(true);
$order->update(['code' => 'NOVO-9999']);
Order::ignoreCodeGenerateUpdating(false);
```

---

## ⚙️ Configuração Global

Arquivo `config/code-generate.php`:

```php
return [
    // Cache de schema
    'cache_schema' => true,
    'cache_store' => 'redis',
    'cache_ttl' => 3600,

    // Comportamento em erro
    'throw_on_error' => true,

    // Padrões
    'default_length' => 4,
    'default_field' => 'code',
    'default_prefix' => '',

    // Concorrência
    'max_collision_attempts' => 5,
];
```

---

## 🧪 Exemplos Completos

### Sistema de Pedidos (Anual)

```php
class Order extends Model
{
    use HasCodeGenerate;

    public function codeGenerateConfig(): array
    {
        return [
            'field' => 'order_number',
            'length' => 10,
            'prefix' => 'PED-',
            'resetPattern' => 'Y',
        ];
    }
}

// PED-20250001, PED-20250002...
// Em 2026: PED-20260001, PED-20260002... (reiniciou)
```

### Sistema de Protocolos (Mensal)

```php
class Protocol extends Model
{
    use HasCodeGenerate;

    public function codeGenerateConfig(): array
    {
        return [
            'field' => 'protocol_number',
            'length' => 12,
            'prefix' => 'PROT-',
            'resetPattern' => 'm',  // 202501, 202502...
        ];
    }
}

// PROT-2025010001, PROT-2025010002...
// Em fevereiro: PROT-2025020001... (reiniciou)
```

### Clientes com Código e Código Interno

```php
class Customer extends Model
{
    use HasCodeGenerate;

    public function codeGenerateConfigs(): array
    {
        return [
            [
                'field' => 'public_id',
                'length' => 6,
                'prefix' => 'CLI-',
            ],
            [
                'field' => 'internal_id',
                'length' => 10,
                'prefix' => 'INT-C-',
                'resetPattern' => null,  // Sequência contínua
            ],
        ];
    }
}
```

---

## 🛠 Contribuição

Sinta-se à vontade para contribuir:

1. Faça um fork do repositório
2. Crie uma branch (`feature/nova-funcionalidade`)
3. Faça commits das suas alterações
4. Envie um Pull Request

---

## 📜 Licença

Este projeto é distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

💡 **Desenvolvido por [Rise Tech](https://risetech.com.br)**
