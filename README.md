# REST API for Galette (https://galette.eu)

To date, user texts _T('xxx') are only in French.

## Setup

```
cd plugin-restapi 
composer install
composer dump
```

## Configuration

Prepare a private key

```
cd plugin-restapi/config
openssl genrsa -out private.key 2048
chmod 660 *.key
```

## Add an important line in .htaccess
RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# API : 
Use this path : https://site.tld/galette/webroot/plugins/restapi/home


- /home : this plugin is-it ready ?
-  /api/login : get a token with a staff or admin member
-  /api/whoami : check logged member
## Member
-  /api/member/{id} - GET,PUT,DELETE: get, change or delete a member
-  /api/member : create a new member
-  /api/member/canlogin - POST {login=(email or galette login) + password} : try to login; return uid if correct; return 200
-  /api/member/find - POST {email, or uid + zipcode}: get id of a member with email, or uid & zipcode; return 200 (OK) or 404 (Not Found)
-  /api/member/passwordlost - POST {login or email, error401}: send a mail to reset password
-  /api/member/{id}/mail - POST/DELETE {title, body}: send a mail 

## Newsletter free 
-  /api/newsletter - POST/DELETE {email, urlcallback} : add / remove an email address  
-  /api/members/emails : get emails from all active members or from an other list 

## Staff
-   /api/mail/staff - POST {title, body} : send a mail to staff members

# And more
## Understand how JWT Tokens works
- https://www.primfx.com/json-web-token-jwt-guide-complet/principes-de-securite/
- https://arjunphp.com/secure-web-services-using-jwt-slim3-framework/
- https://github.com/tuupola/slim-jwt-auth/tree/3.x
- https://github.com/tuupola/slim-api-skeleton/blob/master/README.md
- https://discourse.slimframework.com/t/how-integrate-jwt-with-the-4th-version-of-slim/4666/4

## RESTAPI

| METHOD | Action    | Return
| ---- | ---- | --- |
|POST 	|Create 	    |404 (Not Found), 409 (Conflict) if resource already exists..
|GET 	|Read           |200 (OK), single customer. 404 (Not Found), if ID not found or invalid.
|PUT 	|Update/Replace |200 (OK) or 204 (No Content). 404 (Not Found), if ID not found or invalid.
|DELETE |Delete 	    |200 (OK). 404 (Not Found), if ID not found or invalid.
403 : access denied
More informations : https://www.restapitutorial.com/lessons/httpmethods.html