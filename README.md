# demo-product-gitops

Sample to project used as proof of concept of using Docker / K8s that support GitOps flow that improves product development/deployment productivity

# Quick Start Guide


```

git clone git@gitlab.com:fado-devops/demo-product-gitops.git

cd demo-product-gitopsc/code

../tool/composer install

cd ../build/docker/nginx

docker-compose up

```

# To run PHPUnit Test

```

cd demo-product-gitopsc/code

../tool/phpunit


```

# Buid push registry

https://docs.gitlab.com/ee/user/packages/container_registry/


# Pull Private Registry

https://docs.gitlab.com/ee/ci/docker/using_docker_images.html#define-an-image-from-a-private-container-registry