# Hybridauth 
Module for EnjoysCMS for social sign. Based on [hybridauth/hybridauth](https://github.com/hybridauth/hybridauth)

[Supported providers](https://hybridauth.github.io/providers.html)

## Install
`composer require enjoyscms/hybridauth:6.x-dev`

## Configure

Add to config.yml
```yaml
enjoyscms/hybridauth:
  allow-auto-register: true
  providers:
    # example
    Google:
      enabled: true
      info:
        name: Google
        icon: fa fa-google
      keys:
        key: public-key
        secret: secret-key
```

### Callback endpoint 
`https://your-domain/oauth/callback`

Route name: `hybridauth_callback`

Twig: `{{ path('hybridauth_authenticate', {'provider': provider, 'redirect': currentUrl|url_encode }) }}`

### Sqlite Foreign key problems

Foreign key checks are disabled by default on pdo_sqlite driver. As it's mentioned here:

> Foreign key constraints are disabled by default (for backwards compatibility), so must be enabled separately for each database connection. (Note, however, that future releases of SQLite might change so that foreign key constraints enabled by default. Careful developers will not make any assumptions about whether or not foreign keys are enabled by default but will instead enable or disable them as necessary.

You should enable it before flushing via an EeventSubscriber:

```php
$evm = new EventManager();
$evm->addEventSubscriber(new \EnjoysCMS\Core\Extensions\Doctrine\Subscribers\SqlitePreFlushSubscriber());
```