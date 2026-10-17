# CHANGE LOG

## V2.0.0 (17/10/2026)

# PT-BR

⚠️ **Quebra de comportamento.** Veja "Como atualizar".

- **POST não é mais repetido sem `Idempotency-Key`.** Antes, toda requisição usava `retry(3, 2000)`. Um timeout depois de o Pagar.me criar o pedido fazia o pacote reenviar e cobrar o cliente 2 ou 3 vezes. Agora `GET`, `PUT`, `PATCH` e `DELETE` repetem só em falha de conexão ou 5xx, e erro 4xx nunca é repetido. @GustavoSantarosa
- **Novo `withIdempotencyKey(string)`** em todos os repositórios. Envia o header `Idempotency-Key` e libera a repetição segura do POST. @GustavoSantarosa
- **Novo `withAccessToken(string)`**, para usar uma chave secreta diferente da configurada. @GustavoSantarosa
- **Consulta para conciliação:** `OrderRepository::find($id)`, `OrderRepository::findByCode($code)` e `ChargeRepository::find($id)`, este por `PaymentRepository::charge()`. @GustavoSantarosa
- **`CardRepository::create()` e `CustomerRepository::create()` sem dados lançam `PaymentException`.** Antes criavam um cartão e um cliente de teste fixos, que iam parar na conta real se o payload fosse esquecido. @GustavoSantarosa
- **Tempo limite configurável** por `PAGARME_TIMEOUT` (padrão: 30 s). @GustavoSantarosa

### Como atualizar

1. Em cada `create()`/`store()` de pedido, cobrança ou assinatura, passe `->withIdempotencyKey(...)` com uma chave estável por tentativa (ex.: `"{$faturaId}-{$tentativa}"`). Sem ela, a chamada continua funcionando, mas não é mais repetida.
2. Trate `Illuminate\Http\Client\ConnectionException` no POST: o pedido pode ter sido criado. Consulte com `findByCode()` antes de tentar de novo, ou espere o webhook.
3. Se algum código chamava `create()` de cartão ou cliente sem argumentos, passe os dados.

# EN

⚠️ **Behavior change.** See "How to upgrade".

- **POST is no longer retried without an `Idempotency-Key`.** Every request used `retry(3, 2000)`, so a timeout after the order was created made the package resend it and charge the customer 2 or 3 times. Now `GET`, `PUT`, `PATCH` and `DELETE` retry only on connection failures or 5xx, and 4xx is never retried. @GustavoSantarosa
- **New `withIdempotencyKey(string)`** on every repository. It sends the `Idempotency-Key` header and enables safe POST retries. @GustavoSantarosa
- **New `withAccessToken(string)`** to use a secret key other than the configured one. @GustavoSantarosa
- **Lookups for reconciliation:** `OrderRepository::find($id)`, `OrderRepository::findByCode($code)` and `ChargeRepository::find($id)`, the latter via `PaymentRepository::charge()`. @GustavoSantarosa
- **`CardRepository::create()` and `CustomerRepository::create()` without data throw `PaymentException`.** They used to create a fixed test card and customer, which ended up in the live account if the payload was forgotten. @GustavoSantarosa
- **Configurable timeout** through `PAGARME_TIMEOUT` (default 30 s). @GustavoSantarosa

### How to upgrade

1. On every order, charge or subscription `create()`/`store()`, pass `->withIdempotencyKey(...)` with a key that is stable per attempt (e.g. `"{$invoiceId}-{$attempt}"`). Without it the call still works but is never retried.
2. Handle `Illuminate\Http\Client\ConnectionException` on POST: the order may have been created. Look it up with `findByCode()` before retrying, or wait for the webhook.
3. If any code called the card or customer `create()` with no arguments, pass the data.

## V1.1.0 (01/06/2025)

# PT-BR

- Instalado pint, rector e php-cs-fixer para padronização do código. @GustavoSantarosa
- Rodado o pint no projeto. @GustavoSantarosa
- Rodado o rector no projeto. @GustavoSantarosa
- Iniciado a implementação dos planos de assinatura. @GustavoSantarosa
- Corrigido alguns problemas no subscriber. @GustavoSantarosa

# EN

- Installed pint, rector, and php-cs-fixer for code standardization. @GustavoSantarosa
- Ran pint on the project. @GustavoSantarosa
- Ran rector on the project. @GustavoSantarosa
- Started the implementation of subscription plans. @GustavoSantarosa
- Fixed some issues in the subscriber. @GustavoSantarosa
