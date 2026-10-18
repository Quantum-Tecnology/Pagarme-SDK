<h3 align="center">Pagarme-SDK para Laravel</h3>

## 🧐 Sobre <a name = "about"></a>

Este pacote inclui um sdk simples de comunicação com a pagarme. (em breve)

Sempre que possivel ele sera atualizado, e esta aberto para a comunidade sugerir melhorias.

## 🏁 Para utilizar o pack

Para utilizar a classe, basta instalar ela utilizando o comando do composer:

```
composer require quantumtecnology/pagarme-sdk
```

Em seguida basta publicar o config dela em config/pagarme.php

```
php artisan vendor:publish --tag=config
```

Crie a seguinte env

```
PAGARME_ACCESS_TOKEN=YourKey
```

Pronto, ja é para estar funcionando.

## 🔁 Repetição e idempotência

O pacote só repete uma requisição quando isso não pode gerar efeito duplicado:

- `GET`, `PUT`, `PATCH` e `DELETE` repetem até 3 vezes, só em falha de conexão ou erro 5xx.
- `POST` **não repete**, a menos que tenha `Idempotency-Key`. Um timeout pode acontecer depois de o Pagar.me criar o pedido, e repetir sem chave criaria uma segunda cobrança.
- Erro 4xx nunca é repetido.

Para criar com segurança, envie uma chave estável por tentativa:

```php
$order = PaymentRepository::order()
    ->withIdempotencyKey("{$invoiceId}-{$attempt}")
    ->create($payload);
```

Em falha de conexão sem chave, a `ConnectionException` sobe para quem chamou. Use `findByCode()` para descobrir se o pedido foi criado antes de tentar de novo:

```php
$order = PaymentRepository::order()->findByCode($invoiceId);
$charge = PaymentRepository::charge()->find('ch_...');
```

Para usar outra chave secreta que não a do `.env` (ex.: guardada no banco): `->withAccessToken($secretKey)`. O tempo limite vem de `PAGARME_TIMEOUT` (padrão: 30 s).

## 🎈 Recursos

Nele existem algumas ferramentas uteis.

- Recurrence:
  - Subscript.

## 🧐 Outras Bibliotecas

- [Enum-Basics-Extension](https://packagist.org/packages/quantumtecnology/enum-basics-extension) - Utilizado para auxiliar nas Classes de Enums;
- [SetSchema-Trait](https://packagist.org/packages/quantumtecnology/setschema-trait-postgresql) - Suprir a necessidade de setar os schemas automaticamente do PostgreSQL;
- [Validate-Trait](https://packagist.org/packages/quantumtecnology/validate-trait) - Bindar os Requests automaticamente de acordo com o caminho do Service Pattern;
- [PerPage-Trait](https://packagist.org/packages/quantumtecnology/perpage-trait) - Padronizar a quantidade do paginate na api inteira e definir uma quantidade máxima;

## ⛏️ Ferramentas

- [php](https://www.php.net/) - linguagem
- [laravel](https://laravel.com/) - framework

## ✍️ Autor

- [@Luis Gustavo Santarosa Pinto](https://github.com/QuantumTecnology) - Idea & Initial work
