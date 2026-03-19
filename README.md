# MyAdmin OpenSRS Domains Plugin

[![Tests](https://github.com/detain/myadmin-opensrs-domains/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-opensrs-domains/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-opensrs-domains/version)](https://packagist.org/packages/detain/myadmin-opensrs-domains)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-opensrs-domains/downloads)](https://packagist.org/packages/detain/myadmin-opensrs-domains)
[![License](https://poser.pugx.org/detain/myadmin-opensrs-domains/license)](https://packagist.org/packages/detain/myadmin-opensrs-domains)

OpenSRS domain registration, renewal, and management plugin for the MyAdmin control panel. Provides full integration with the OpenSRS API for domain lifecycle operations including registration, transfers, renewals, WHOIS privacy, nameserver management, DNS SEC, lock/unlock, and event polling/acknowledgement.

## Features

- Domain registration and renewal via the OpenSRS API
- Domain transfer initiation and status checking
- WHOIS privacy enable/disable
- Nameserver creation, deletion, and retrieval
- Domain lock/unlock management
- Event polling and acknowledgement for webhook-style notifications
- XML and JSON API request support
- TLD-specific registration field handling (`.ca`, `.eu`, `.au`, `.fr`, `.it`, `.us`, and more)
- Premium domain detection and pricing

## Installation

Install with Composer:

```sh
composer require detain/myadmin-opensrs-domains
```

## Configuration

The plugin requires the following constants or settings to be defined:

| Constant | Description |
|---|---|
| `OPENSRS_USERNAME` | OpenSRS reseller API username |
| `OPENSRS_PASSWORD` | OpenSRS reseller API password |
| `OPENSRS_KEY` | OpenSRS reseller private key |
| `OPENSRS_TEST_KEY` | OpenSRS test environment key |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](LICENSE) license.
