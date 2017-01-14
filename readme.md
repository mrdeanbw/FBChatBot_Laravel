# Mr. Reply Lumen Backend

##Installation
- Create a Facebook app with Facebook Login, Messenger and Webhooks products.
- Copy `.env.example` to `.env` and fill in the app key, JWT secret, database credentials, Facebook app credentials and verify token.
- Navigate to the root directory and run
```
composer install
php artisan migrate
cd public
php -S localhost:8888
```
- To be able to test payments, setup a stripe test account and add its credentials to the ```.env``` file.
- To be able to test Facebook's and Stripe's webhooks, you will need to use a tunneling tool to receive webhooks locally (e.g. `ngrok`).

## Facebook Webhooks Configuration
- The callback url should be: `https://TUNNELED_URL/callback/facebook/web-hook`.
- Subscribe to the following fields `message_deliveries, message_reads, messages, messaging_optins, messaging_postbacks`
- The verify token should be exactly like the one in your `.env` file.


