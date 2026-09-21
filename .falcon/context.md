---
status: active
produto: SDK Pagar.me
---

## O que é e por que existe

Cliente PHP do Pagar.me, usado pelo billing do Falcon Vendas e do DataHub (Pix e cartão).

É pacote: chega nos projetos pelo composer, então mudança aqui afeta quem já
está em produção. Versionar com cuidado.

## Pegadinhas

- 🟠 **Quem consome trava a versão no `composer.lock`** — subir um comportamento
  novo aqui não chega sozinho nos serviços; é preciso atualizar cada um.

- 🔴 **`GET /subscriptions/{id}` NÃO traz o histórico de faturas.** Devolve só o
  ciclo corrente (`current_cycle`, `next_billing_at`) — o campo `invoices` não
  vem, e nada na resposta indica que falta. Quem precisa saber o que o cliente
  já pagou (conciliação, tempo de casa, receita observada) tem de usar
  `PaymentRepository::invoice()`, que bate em `/invoices`, outro recurso da API.

  Custou ao DataHub um ranking de fidelidade zerado: a reconciliação corrigia o
  período pelo `show()` e importava zero cobranças, parecendo que o cliente
  nunca havia pagado. Eram 6 faturas pagas no gateway, desde março.

- 🟠 **`/invoices` pagina em 10 por padrão** (`size` máximo 100). Pedir a
  primeira página e tratá-la como o conjunto devolve um recorte silencioso —
  use `allBySubscription()`, que percorre a paginação.

## Decisões

- **Aditivo sobre `recurrence()`**: `'invoice'` entrou ao lado de
  `'subscription'` e `'plan'`, com o `default => false` intacto. O pacote roda
  em produção em mais de um serviço, e mudar o contrato quebraria quem ainda
  não atualizou o lock.

- **Sem suíte de testes no pacote** (2026-09-21). `composer test` roda apenas
  rector + pint; validação de comportamento é feita pelo serviço que consome.
  Débito conhecido — o `InvoiceRepository` foi conferido contra a API real,
  não por teste automatizado.

> Curadoria ainda rasa: escrever o *porquê* das decisões conforme o pacote for
> tocado. Formato no `CLAUDE.md` do Engineering Hub.
