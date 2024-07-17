# Price

## Functionality

Display price type in user defined currency and locale.

## Design

Server-side price_types are coming from database which are slug texts.
It's handy using this macro to show user interactive price types based on current data.

## Usage

```twig
{% from '@templates/components/price/price.html.twig' import priceType %}

{{ priceType(offer.price_type, ['leasing', 'auction']) }}

```

The `priceType` macro supports the following parameters:

index | parameter | type | description
--- | --- | --- | ---
0 | offer.price_type | string | Offer containing `.price_type` string.
1 | ['leasing', 'rent', 'auction', 'upon-request', 'fixed', 'exchange-accepted'] | array | array containing only price types to be handled
