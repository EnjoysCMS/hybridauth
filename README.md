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
`https://project.example/oauth/callback`
