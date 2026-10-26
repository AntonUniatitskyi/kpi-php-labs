## 🚀 Запуск

### Вимоги
- PHP 8.x з розширенням `pdo_mysql` (перевірка: `php -m | grep pdo_mysql`)
- MySQL або MariaDB

### Кроки
1. Створіть файл підключення і за потреби змініть у ньому логін, пароль і порт:
   ```bash
   cp db.example.php db.php
   ```
2. Створіть базу даних і таблицю (один раз):
   ```bash
   mysql -u root -p < schema.sql
   ```
   Або виконайте вміст `schema.sql` у phpMyAdmin.
3. Запустіть вебсервер з директорії проєкту:
   ```bash
   php -S localhost:8000
   ```
4. Відкрийте `http://localhost:8000/index.php`.

<details>
<summary>Запуск на NixOS</summary>

`shell.nix` додає PHP та MariaDB. База зберігається в `.mysql/` у проєкті:

```bash
nix-shell
mariadb-install-db --datadir=$PWD/.mysql --basedir=$(dirname $(dirname $(which mariadbd))) --auth-root-authentication-method=normal
mariadbd --datadir=$PWD/.mysql --socket=$PWD/.mysql/mysql.sock --port=3306 --bind-address=127.0.0.1 &
mariadb -u root --socket=$PWD/.mysql/mysql.sock < schema.sql
php -S localhost:8000
```
</details>