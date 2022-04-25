##
Project setup

git clone https://github.com/harsha14581/expense-sharing-app.git

cd expense-sharing-app

cp .env.example .env

update database credentials in .env file

composer install

php artisan migrate

php artisan serve

access api base url: http://localhost/api