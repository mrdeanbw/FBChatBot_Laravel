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

## References
* [Mr. Reply Scrum Guide](https://gist.github.com/netcode/917291503a8265eb9c7143db823dcb75)
* [Mr. Reply Git Flow](https://gist.github.com/netcode/b9ecd59b832a344b7f6345cec9678d0c)
* [Mr. Reply PHP Coding Guidelines](https://gist.github.com/netcode/60bfa0f77ec95115556911dd8f10cdfd)
* [Testing Instruction](https://gist.github.com/elghobaty/1e1be1baa13d7273c0c313a00ba039aa)