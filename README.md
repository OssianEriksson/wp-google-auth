# wp-google-auth

WordPress plugin for syncing WordPress users with Google Workspace users.

## Getting Started

The instructions here will help you set up a local development environemnt.

### Prerequisites

-   [Nodejs](https://nodejs.org) (and npm)
-   [Composer](https://getcomposer.org/)
-   [Docker](https://www.docker.com/)

### Setup

1. Install dependencies and dev dependencies:

    ```console
    npm i
    composer i
    ```

1. Start the development environment:

    ```console
    npm run start
    ```

    The development server should now be started at <http://localhost:8888/>.

1. When you are done, stop the development environment with

    ```console
    npm run stop
    ```

1. Before commtting, please build localization files and javascript:
    ```console
    npm run build
    ```

### Coding Standards

Format/lint the code with

```console
npm run format
npm run lint
```

respectively.

## Localization

Localization files are stored in the [`languages`](./languages) folder:

```console
cd languages
```

### Update localizations for existing locales

Do

```console
npm run lang
```

Then fill out the empty fields in the updated PO files.

### Localize for a new locale

Generate a [POT file](https://developer.wordpress.org/plugins/internationalization/localization/#localization-files) by running

```console
npm run lang:pot
```

Next, copy the generated POT file to a PO file named `wp_google_auth-{locale}.po`, for example

```
cp wp_google_auth.pot wp_google_auth-en_US.po
```

## Licence

Distributed under the GPL-3.0 License. See [LICENSE](./LICENCE) for more information.
