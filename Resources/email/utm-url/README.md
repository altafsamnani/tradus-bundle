# UTM URL

Add utm query parameters to an url

## Default properties

There are a couple of default properties set:

* Source: `email`
* Medium: `link`

## Usage

```twig
{% from '@templates/email/utm-url/utm-url.html.twig' import utmUrl %}

{{ utmUrl(url[, properties]) }}
```

The macro expects the following arguments:

argument | type | description
--- | --- | ---
url | string | The string to concat the utm parameters to
properties | object | **Optional**, an object containing new properties or excluding defaults

## Examples

**Only defaults**:

```twig
{{ utmUrl("http://example.com") }}
```

result: `http://example.com?utm_source=email&utm_medium=link`

**Overriding a default property**:

```twig
{{ utmUrl("http://example.com", { source: app }) }}
```

result: `http://example.com?utm_source=app&utm_medium=link`

**Exclude a default property**:

```twig
{{ utmUrl("http://example.com", { source: null }) }}
```

result: `http://example.com?utm_medium=link`

**Add a property (campaign)**:

```twig
{{ utmUrl("http://example.com", {campaign: 'lead-information'}) }}
```

result: `http://example.com?utm_source=email&utm_medium=link&utm_campaign=lead-confirmation`
