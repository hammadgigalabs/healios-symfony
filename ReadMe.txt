After Taking clone from server and master branch

Step 1:
	Composer Install

Step 2:
	Migrate DB Sceheme by below command.
	php bin/console doctrine:migrations:migrate

Step 3:
	Seed User data for admin user by below command.
	php bin/console doctrine:fixtures:load
	After above command one admin user is created in db with admin role who can add other users as well.

For testing test case
php bin/phpunit tests/Controller/AuthControllerTest.php

Import Collection in postMan.
https://www.getpostman.com/collections/ea3e1612ccaaa02de9aa
