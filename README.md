## Install

Run
`composer install`
to install everything needed to run the app.

You should probably crate an `.env` file (maybe copy
the provided `.env.example` file?)

Load `mailerlite_datatables_example.sql` into your MySQL
table (there's only one table: sessions).

After that run
`php artisan serve --host 0.0.0.0`
to serve the app (default port is 8000).
