FROM 'wordpress:5.9.2-php7.4'

RUN set -e; \
    curl -fsSL https://deb.nodesource.com/setup_16.x | bash -; \
    apt-get update; \
    apt-get install -y \
        nodejs \
        git \
        gettext \
        sudo \
        mariadb-client \
    ;

RUN set -e; \
    useradd --create-home --shell /bin/bash --groups sudo --password '' wordpress; \
    echo '%wordpress ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers;

RUN set -e; \
    curl -fsSLO https://getcomposer.org/download/2.3.3/composer.phar; \
    chmod +x composer.phar; \
    mv composer.phar /usr/local/bin/composer;

RUN set -e; \
    curl -fsSLO https://github.com/wp-cli/wp-cli/releases/download/v2.6.0/wp-cli-2.6.0.phar; \
    chmod +x wp-cli-2.6.0.phar; \
    mv wp-cli-2.6.0.phar /usr/local/bin/wp;

COPY setup-wordpress.sh /usr/local/bin/setup-wordpress