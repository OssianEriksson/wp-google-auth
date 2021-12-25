# wp-google-auth

WordPress plugin for syncing WordPress users with Google Workspace users.

## Getting Started

The instructions here will help you set up a local development environemnt.

### Prerequisites

- [Nodejs](https://nodejs.org) (and npm)
- [Composer](https://getcomposer.org/)
- [Docker](https://www.docker.com/)

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

## Licence

Distributed under the GPL-3.0 License. See [LICENSE](./LICENCE) for more information.
