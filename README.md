# Laravel translation checker

This GitHub action can be used to check if all translations are contained within the given translation files.

This works by scanning through all the blade files under the `resources/` folder and comparing all found translation strings to the existing json files.

## Usage

To add this action into your GitHub workflow:

```yaml
- name: Check translations
  uses: rapidez/laravel-translation-checker@1.0.0
```

It's also useful to install your dependencies beforehand, in case you need the translations from those to work:

```yaml
- name: Install dependencies
  run: composer install --prefer-dist --no-interaction
```

## Example workflow

```yaml
name: Check translations

on:
  push:
    branches:
      - master
      - '*.x'
  pull_request:

jobs:
  translations:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v2
      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction
      - name: Check translations
        uses: rapidez/laravel-translation-checker@1.0.0
```