# Delivery LGX

## Iniciar Projeto

Após realizar o clone do repositório, instale os repositórios de terceiros contidos no projeto

    $> composer install

Em seguida crie o banco de dados local e configure suas variaveis de ambiente no `.env`

    DB_CONNECTION=mysql
    DB_HOST=localhost
    DB_PORT=3306
    DB_DATABASE=fariaslgx
    DB_USERNAME=root
    DB_PASSWORD=

Execute as migrações

    $> php artisan migrate


## API Documentation (Postman)

[https://documenter.getpostman.com/view/1929826/UVXgMxar](https://documenter.getpostman.com/view/1929826/UVXgMxar)

## Diagrama Entidade Relacional

![der](https://api.fariaslgx.com/storage/images/der.png)

