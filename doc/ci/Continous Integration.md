References:

Todo
- Test run ci
  
```
stages:
  - preparation
  - building
  - build
  - testing
  - security

image: edbizarro/gitlab-ci-pipeline-php:7.3

# Variables
variables:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_USER: mysql_user
  MYSQL_PASSWORD: mysql_password
  MYSQL_DATABASE: mysql_db
  DB_HOST: mysql

# Composer stores all downloaded packages in the vendor/ directory.
# Do not use the following if the vendor/ directory is committed to
# your git repository.
cache:
    paths:
      - vendor/
    key: "$CI_JOB_NAME-$CI_COMMIT_REF_SLUG"


phpcs:
  script:
    - phpcs --standard=Framgia app/

phpunit:
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  script:
    - cp .env.testing.example .env
    - composer install
    - php artisan key:generate
    - yarn install
    - yarn dev
    - php ./vendor/bin/phpunit --coverage-text --colors=never
    
services:
  - docker:dind

before_script:
  - docker login -u "$CI_REGISTRY_USER" -p "$CI_REGISTRY_PASSWORD"

composer:
  stage: preparation
  script:
    - php -v
    - composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts
    - cp .env.example .env
    - php artisan key:generate
  artifacts:
    paths:
      - vendor/
      - .env
    expire_in: 1 days
    when: always
  cache:
    paths:
      - vendor/

yarn:
  stage: preparation
  script:
    - yarn --version
    - yarn install --pure-lockfile
  artifacts:
    paths:
      - node_modules/
    expire_in: 1 days
    when: always
  cache:
    paths:
      - node_modules/

build-assets:
  stage: building
  # Download the artifacts for these jobs
  dependencies:
    - composer
    - yarn
  script:
    - yarn --version
    - yarn run production --progress false
  artifacts:
    paths:
      - public/css/
      - public/js/
      - public/fonts/
      - public/mix-manifest.json
    expire_in: 1 days
    when: always

Debian 7.4:
  stage: build
  variables:
    IMAGE_VERSION: "7.4"
  script:
    - docker pull php:$IMAGE_VERSION || true
    - docker pull $NAMESPACE:$IMAGE_VERSION || true
    - docker build --compress --cache-from $NAMESPACE:$IMAGE_VERSION --build-arg VCS_REF=$CI_COMMIT_SHORT_SHA --build-arg BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ') -t $NAMESPACE:$IMAGE_VERSION -f php/$IMAGE_VERSION/Dockerfile .
    - docker run -t --rm edbizarro/gitlab-ci-pipeline-php:$IMAGE_VERSION -m
    - docker run -t --rm -v $(pwd):/var/www/html edbizarro/gitlab-ci-pipeline-php:$IMAGE_VERSION goss -g tests/goss-$IMAGE_VERSION.yaml v
    - docker tag $NAMESPACE:$IMAGE_VERSION $NAMESPACE:latest
    - docker tag $NAMESPACE:$IMAGE_VERSION $NAMESPACE:7
    - docker push $NAMESPACE:$IMAGE_VERSION
    - docker push $NAMESPACE:7
    - docker push $NAMESPACE:latest
  when: always

Debian 7.3:
  stage: build
  variables:
    IMAGE_VERSION: "7.3"
  script:
    - docker pull php:$IMAGE_VERSION || true
    - docker pull $NAMESPACE:$IMAGE_VERSION || true
    - docker build --compress --cache-from $NAMESPACE:$IMAGE_VERSION --build-arg VCS_REF=$CI_COMMIT_SHORT_SHA --build-arg BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ') -t $NAMESPACE:$IMAGE_VERSION -f php/7.3/Dockerfile .
    - docker run -t --rm -v $(pwd):/var/www/html edbizarro/gitlab-ci-pipeline-php:$IMAGE_VERSION goss -g tests/goss-7.3.yaml v
    - docker push $NAMESPACE:$IMAGE_VERSION

  when: always
  
db-seeding:
  stage: building
  services:
    - name: mysql:8.0
      command: ["--default-authentication-plugin=mysql_native_password"]
  # Download the artifacts for these jobs
  dependencies:
    - composer
    - yarn
  script:
    - mysql --version
    - php artisan migrate:fresh --seed
    - mysqldump --host="${DB_HOST}" --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" > db.sql
  artifacts:
    paths:
      - storage/logs # for debugging
      - db.sql
    expire_in: 1 days
    when: always

phpunit:
  stage: testing
  services:
    - name: mysql:8.0
      command: ["--default-authentication-plugin=mysql_native_password"]
  # Download the artifacts for these jobs
  dependencies:
    - build-assets
    - composer
    - db-seeding
  script:
    - php -v
    - sudo cp /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.bak
    - echo "" | sudo tee /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
    - mysql --host="${DB_HOST}" --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" < db.sql
    - ./vendor/phpunit/phpunit/phpunit --version
    - php -d short_open_tag=off ./vendor/phpunit/phpunit/phpunit -v --colors=never --stderr
    - sudo cp /usr/local/etc/php/conf.d/docker-php-ext-xdebug.bak /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  artifacts:
    paths:
      - ./storage/logs # for debugging
    expire_in: 1 days
    when: on_failure

codestyle:
  stage: testing
  image: lorisleiva/laravel-docker
  script:
    - phpcs --extensions=php app
  dependencies: []

phpcpd:
  stage: testing
  script:
    - test -f phpcpd.phar || curl -L https://phar.phpunit.de/phpcpd.phar -o phpcpd.phar
    - php phpcpd.phar app/ --min-lines=50
  dependencies: []
  cache:
    paths:
      - phpcpd.phar

sensiolabs:
  stage: security
  script:
    - test -d security-checker || git clone https://github.com/sensiolabs/security-checker.git
    - cd security-checker
    - composer install
    - php security-checker security:check ../composer.lock
  dependencies: []
  cache:
    paths:
      - security-checker/

```


# Prepare Environment Variables helps Gitlab working with private Registry
### Create DOCKER_AUTH_CONFIG for private image

https://docs.gitlab.com/ee/ci/docker/using_docker_images.html#define-an-image-from-a-private-container-registry

Create an environment vaairriable ưith the name: *DOCKER_AUTH_CONFIG*


**Note: *Protected Variable* may make your variable will not be expose to the runner, please disabled that attribute.**
 
```
# The use of "-n" - prevents encoding a newline in the password.
echo -n "my_username:my_password" | base64

# Example output to copy
bXlfdXNlcm5hbWU6bXlfcGFzc3dvcmQ=

```

Create the Docker JSON configuration content as follows
```
{
    "auths": {
        "registry.example.com:5000": {
            "auth": "(Base64 content from above)"
        }
    }
}
```

## Create enviroments for build and push to docker
CI_REGISTRY: your registry url (include port)
CI_REGISTRY_USERNAME: your registry login username
CI_REGISTRY_PASSWORD: your registry login password
