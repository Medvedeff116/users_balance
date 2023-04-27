


- Добавление пользователя (в аргументах имя, email, пароль)
``` script
php artisan user:create username example@gmail.com password
```

- Изменение баланса (в аргументах email, направление (in, out), сумма, валюта (usd, eur, rub), описание)
``` script
php artisan transaction:create example@gmail.com in 9.554 rub desc1
php artisan transaction:create example@gmail.com out 155.35 usd desc2
```
