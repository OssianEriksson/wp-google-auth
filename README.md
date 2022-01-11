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

    The development server is noe started at <http://localhost:8888/>.

1. When you are done, stop the development environment with
    ```console
    npm run stop
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

Generate a [POT file](https://developer.wordpress.org/plugins/internationalization/localization/#localization-files) by running

```console
npm run pot
```

To create a new translation, copy the generated POT file to a PO file named `wp_google_auth-{locale}.po`, for example

```
cp wp_google_auth.pot wp_google_auth-en_US.po
```

If you are using [POEdit](https://poedit.net/) to edit PO files, you can use the [POT file to update existing PO files](https://stackoverflow.com/a/32316538).

Edit the PO files to your desire and then compile them into MO files with

```console
npm run mo
```

## Licence

Distributed under the GPL-3.0 License. See [LICENSE](./LICENCE) for more information.
