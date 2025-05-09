## How to install
1. Clone the repository
2. Rename commonexample.neon to common.neon and fill the data
3. create the database and run data/mysql.sql and data.sql
4. composer install
5. php -S localhost:8000 -t www
6. open the url `http://localhost:8000/user/recover?token=123`
7. set the new password for admin
8. login as admin, change password for other users if needed