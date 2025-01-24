<h3 align="center">Pagarme-SDK para Laravel</h3>

## 🧐 Sobre <a name = "about"></a>

Este pacote inclui um sdk simples de comunicação com a pagarme. (em breve)

Sempre que possivel ele sera atualizado, e esta aberto para a comunidade sugerir melhorias.

## 🏁 Para utilizar o pack

Para utilizar a classe, basta instalar ela utilizando o comando do composer:

```
composer require gustavosantarosa/pagarme-sdk
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

## 🎈 Recursos

Nele existem algumas ferramentas uteis.

- Recurrence:
  - Subscript.

## 🧐 Outras Bibliotecas

- [Enum-Basics-Extension](https://packagist.org/packages/gustavosantarosa/enum-basics-extension) - Utilizado para auxiliar nas Classes de Enums;
- [SetSchema-Trait](https://packagist.org/packages/gustavosantarosa/setschema-trait-postgresql) - Suprir a necessidade de setar os schemas automaticamente do PostgreSQL;
- [Validate-Trait](https://packagist.org/packages/gustavosantarosa/validate-trait) - Bindar os Requests automaticamente de acordo com o caminho do Service Pattern;
- [PerPage-Trait](https://packagist.org/packages/gustavosantarosa/perpage-trait) - Padronizar a quantidade do paginate na api inteira e definir uma quantidade máxima;

## ⛏️ Ferramentas

- [php](https://www.php.net/) - linguagem
- [laravel](https://laravel.com/) - framework

## ✍️ Autor

- [@Luis Gustavo Santarosa Pinto](https://github.com/GustavoSantarosa) - Idea & Initial work
