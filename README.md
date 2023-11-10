# Projet7 - BileMo

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/2141b002cf514748a5354b125eca689f)](https://www.codacy.com/gh/valh-runner/oc_projet7/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=valh-runner/oc_projet7&amp;utm_campaign=Badge_Grade)

Creation of a RESTful API in order to present smartphones available for sale to business consumers.\
Implemented via the Symfony framework and secured by Json Web Token.\
Referenced customers can access with their credentials to manage their simple users accounts.\
Simple users can search products and consult each product details.

### Environment used during development
-   WampServer 3.1.7
    -   Apache 2.4.37
    -   PHP 7.3.1
    -   MySQL 5.7.24
-   Composer 2.0.13
-   Git 2.24.0

### Library used
-   Symfony 5.4.30
    -   lexik/jwt-authentication-bundle
    -   zircote/swagger-php

## Installation

### Environment setup

It is necessary to have an Apache / Php / Mysql environment.\
Depending on your operating system, choose your own:
-   Windows : WAMP (<http://www.wampserver.com>)
-   MAC : MAMP (<https://www.mamp.info/en/mamp>)
-   Linux : LAMP (<https://doc.ubuntu-fr.org/lamp>)
-   Cross system: XAMP (<https://www.apachefriends.org/fr/index.html>)

Symfony 5.4.30 requires PHP 7.2.5 or higher to run.\
Prefer to have MySQL 5.6 or higher.\
Make sure PHP is in the Path environment variable.

You need an installation of Composer.\
So, install it if you don't have it. (<https://getcomposer.org>)

If you want to use Git (optional), install it. (<https://git-scm.com/downloads>)

### Project files local deployement

Manually download the content of the Github repository to a location on your file system.\
You can also use git.\
In Git, go to the chosen location and execute the following command:
```
git clone https://github.com/valh-runner/oc_projet7.git bilemoApi

```

Then, open a command console and go to the application root directory.\
Install dependencies by running the following command:
```
composer install
```

### Database generation
Launch the previously installed software containing a Mysql server.

Change the database connection values for correct ones in the .env file.\
Like the following example with a bilemo named database to create:
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/bilemo?serverVersion=5.7"
```

In a new console placed in the root directory of the application,\
launch the creation of the database:
```
symfony console doctrine:database:create
```

Then, build the database structure using the following command:
```
symfony console doctrine:migrations:migrate
```

Finally, load the initial dataset of products and example users into the database.\
Use the following command:
```
symfony console doctrine:fixtures:load
```
Alternatively, if you want to load the initial dataset without generic users, use this command:
```
symfony console doctrine:fixtures:load --group=AppFixtures
```
### RSA Keys generation
Create a folder named jwt in the config folder.

Then download and install OpenSSL v1.0 or v3.0, light version is sufficient.\
For Windows, installer can be found at <https://slproweb.com/products/Win32OpenSSL.html>.\
If openssl command can't be accessed in console, add ```C:\Program Files\OpenSSL-Win64\bin``` in the Path environment variable.

#### Private key
In the console, place you in the root of the project and run the following command:
```
openssl genrsa -out config/jwt/private.pem -aes256 4096
```
The console must ask you a passphrase and ask it again to avoid mistake.\
Choose the passphrase you want.\
If you use Git Bash console, add ```winpty ``` before these openssl commands, otherwise the console will not ask a passphrase.\
The private key file has been created in /config/jwt.
#### Public key
In the console, place you in the root of the project and run the following command:
```
openssl rsa -outform PEM -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```
The console must ask you the passphrase you choose.\
The public key file has been created in /config/jwt.

#### Passphrase assignement
In the .env file of the root project, change the JWT_PASSPHRASE to the one you choose.

## Launch a web server

### By the Symfony Local Web Server
Place you in project root and launch the symfony server with the following command:
```
symfony serve
```
Leave this console open.

### By a virtualhost
If you don't wan't to use the Symfony Local Web Server, you can use your Apache/Php/Mysql environment in the classic way.\
This by configuring a virtualhost in which to place the project.

## Use the REST API

### Choose a tool to launch HTTP requests
To use REST API, we need to forge HTTP requests.\
You can do it with Curl on console or with Postman.\
I recommend you to install Postman. (<https://www.postman.com/downloads>)

### Request the API

#### Login
Users accounts credentials are described in fixtures.\
You can login as an customer or a simple user of a customer.\
Customers can manage simple users, Simple user can consult products.

We suppose you want to login as a simple user of a customer.

Do a GET request targeting the url /api/login_check with a body content as, for example:
```
{"username":"ServiceAchat", "password":"92cay46k"}
```
The API returns you a json response containing a token between quotation marks.\
Once the token obtained, you can request others endpoints with adding this token in every request.

#### Products list consultation
Do a GET request targeting the url /api/products with an bearer authorization with the value of the token you received.\
The API returns you a json response containing the list of products.

For more actions, see the API Documentation.

## API Documentation
All available endpoints are described in the API documentation.\
The API Documentation is accessible in your web browser at <https://localhost:8000/api/doc/>\
Don't forget the final slash of the url.

## Troubleshooting

If some disfunctionments appear or a file is missing, check your anti-virus quarantine.\
Prefer to set, in your anti-virus, an exclusion for the application folder.
