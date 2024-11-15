# Demystifying Next.js 15 new cache mechanism

Get some coffee. It's going to be a long one.

## Introduction

We are going to build a directory of our favorite coffee shops. The content will be served from a REST API and we will use Next.js 15 to build a client to browse the content.

## Let's setup a REST to deliver our content

I will be using Laravel to handle the backend side of the application but any other framework would do the job as well.

### Laravel local environment

<div style="position: relative; padding-bottom: 61.155152887882224%; height: 0;"><iframe src="https://www.loom.com/embed/c8297e1faed14f45804750d3d040009f?sid=1e418115-bf32-44ff-a981-48a7952fc8d5" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

```
laravel new coffee-api
composer require laravel/sail
php artisan sail:install
```

Edit the `.env` file and add local hostname to `/etc/hosts`:

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/d2ef8c7423bf47158165ee2b944254fd?sid=6ef5e863-e95f-442f-bef6-dc8fd2d46e25" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

We can now launch our containers:

```
sail up
curl http://api.coffeeshops.test
```

We can now add the `api` configuration.

```
sail artisan install:api
```

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/5c41dc8d4879492d972636ffaa44e10c?sid=246af9fe-8f39-468a-8651-ecfd4f0ac927" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

### Let's setup cursor with a few rules to help us

Create a new file called `.cursorrules` and paste the content of [this prompt](https://cursorrule.com/posts/laravel-php-cursor-rules)

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/f0061f3780994e74a3ceb52e87b8cc45?sid=a4ac36f0-c968-4944-8a3f-91ed43981af5" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

### Let's add our model

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/6f63941e31e04231ad0c21f62e61c848?sid=e5ff9bc0-fedc-4ed4-aafc-d84bec2e8d83" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

```
sail artisan make:model Shop -m
sail artisan make:controller Api\\ShopController
sail artisan make:resource ShopResource
sail artisan migrate
```

We are now adding our properties to the `Shop` model in the migration file as well as the model file into the `$fillable` property. Migrate everything again:

```
sail artisan migrate:refresh
```

### Let's seed our database for testing

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/05b212346ce84aad8bb00794e0162588?sid=50ef4ade-0a37-4906-94b1-ee1da90c6398" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

Edit `DatabaseSeeder.php` and add a call to the Shop factory:

```
Shop::factory(10)->create();
```

In order to work, Laravel needs to know the model has a factory so head to the `Shop` model and add the `HasFactory` trait.

Once done, we need to define the factory. I'm just copy pasting the `UserFactory` in the example and modifying it to match what we need.

```php
public function definition(): array
{
    return [
        'name' => fake()->company(),
        'address' => fake()->streetAddress(),
        'city' => fake()->city(),
        'state' => fake()->state(),
        'zip' => fake()->postcode(),
        'country' => fake()->country(),
        'phone' => fake()->phoneNumber(),
        'website' => fake()->url(),
        'rating' => fake()->numberBetween(1, 5),
        //'image' => fake()->imageUrl(),
    ];
}
```

### Let's work on our controller

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/4056056462134fd6a5494d5e055b3d29?sid=9efa5bc2-72cd-4c01-9477-7fd1e55fd408" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

Edit `ShopController` and add our two methods:

```php
class ShopController extends Controller
{
    public function index()
    {
        return ShopResource::collection(Shop::all());
    }

    public function show(Shop $shop)
    {
        return new ShopResource($shop);
    }
}
```

Add the routes in `routes/api.php`:

```php
Route::get('shops', [\App\Http\Controllers\Api\ShopController::class, 'index']);
Route::get('shops/{shop}', [\App\Http\Controllers\Api\ShopController::class, 'show']);
```

We can now test our API on both endpoints `/shops` and `/shops/{id}`:

```
curl api.coffeeshops.test/api/shops | jq
{
  "data": [
    {
      "id": 1,
      "name": "Wisozk, Sanford and Rice",
      "address": "318 Caleigh Causeway Apt. 403",
      "city": "Forestburgh",
      "state": "Iowa",
      "zip": "33765-9124",
      "country": "Brunei Darussalam",
      "phone": "+1-231-859-2227",
      "website": "https://www.barton.com/quia-ut-error-voluptatem-qui-eos-similique-expedita",
      "rating": "5",
      "created_at": "2024-11-15T14:56:20.000000Z",
      "updated_at": "2024-11-15T14:56:20.000000Z"
    },
    [...]
}
```

### Let's deploy it!

Our REST API is ready, let's push that to our Upsun hosting.

First let's create our Upsun project.

```
upsun project:create
```

<div style="position: relative; padding-bottom: 62.5%; height: 0;"><iframe src="https://www.loom.com/embed/be7b61359e064994b1f0032b723b731a?sid=d58974d6-d3f0-44cd-a63a-f0bc34bac2a7" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>

In the meantime we create a git repository at the root of our project. The `laravel` CLI tool automatically created one in the `coffee-api` folder so I'll remote this one.

We can now set the remote our git repository to the Upsun one:

```
upsun project:set-remote [project id]
git add .
git commit -m "Setup base Laravel with API"
```

Before pushing our code, we now need to add our Upsun configuration in there. Refer to [our documentation](https://docs.upsun.com) if you need any help.

```yaml
applications:
  coffee-api:
    # Application source code directory
    source:
      root: "/coffee-api"

    # The runtime the application uses.
    # Complete list of available runtimes: https://docs.upsun.com/create-apps/app-reference.html#types
    type: "php:8.3"

    # Choose which container profile (ratio CPU+RAM) your app will use. Default value comes from the image itself.
    # More information: https://docs.upsun.com/manage-resources/adjust-resources.html#adjust-a-container-profile
    # container_profile:

    # The relationships of the application with services or other applications.
    # The left-hand side is the name of the relationship as it will be exposed
    # to the application in the PLATFORM_RELATIONSHIPS variable. The right-hand
    # side is in the form `<service name>:<endpoint name>`.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#relationships
    relationships:
      db: "postgresql:postgresql"
      cache: "redis:redis"


    # Mounts define directories that are writable after the build is complete.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#mounts
    mounts:
      "/.config":
        source: "storage"
        source_path: "config"

      "bootstrap/cache":
        source: "storage"
        source_path: "cache"

      "storage":
        source: "storage"
        source_path: "storage"



    # The web key configures the web server running in front of your app.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#web
    web:
      # Commands are run once after deployment to start the application process.
      # More information: https://docs.upsun.com/create-apps/app-reference.html#web-commands
      # commands:
        # The command to launch your app. If it terminates, it’s restarted immediately.
      #   You can use the $PORT or the $SOCKET environment variable depending on the socket family of your upstream
      #   PHP applications run PHP-fpm by default
      #   Read about alternative commands here: https://docs.upsun.com/languages/php.html#alternate-start-commands
      #   start: echo 'Put your start command here'
      # You can listen to a UNIX socket (unix) or a TCP port (tcp, default).
      # For PHP, the defaults are configured for PHP-FPM and shouldn't need adjustment.
      # Whether your app should speak to the webserver via TCP or Unix socket. Defaults to tcp
      # More information: https://docs.upsun.com/create-apps/app-reference.html#where-to-listen
      # upstream:
      #  socket_family: unix
      # Each key in locations is a path on your site with a leading /.
      # More information: https://docs.upsun.com/create-apps/app-reference.html#locations
      locations:
        "/":
          passthru: "/index.php"
          root: "public"



    # Alternate copies of the application to run as background processes.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#workers
    # workers:
    #   horizon:
    #     commands:
    #       start: |
    #         php artisan horizon

    # The timezone for crons to run. Format: a TZ database name. Defaults to UTC, which is the timezone used for all logs
    # no matter the value here. More information: https://docs.upsun.com/create-apps/timezone.html
    # timezone: <time-zone>

    # Access control for roles accessing app environments.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#access
    # access:

    # Variables to control the environment. More information: https://docs.upsun.com/create-apps/app-reference.html#variables
    # variables:
    #   env:
    #     # Add environment variables here that are static.
    #     XDEBUG_MODE: off

    # Outbound firewall rules for the application. More information: https://docs.upsun.com/create-apps/app-reference.html#firewall
    # firewall:

    # Specifies a default set of build tasks to run. Flavors are language-specific.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#build
    build:
      flavor: none

    # Installs global dependencies as part of the build process. They’re independent of your app’s dependencies and
    # are available in the PATH during the build process and in the runtime environment. They’re installed before
    # the build hook runs using a package manager for the language.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#dependencies
    dependencies:
      php:
        composer/composer: "^2"

    # Hooks allow you to customize your code/environment as the project moves through the build and deploy stages
    # More information: https://docs.upsun.com/create-apps/app-reference.html#hooks
    hooks:
      # The build hook is run after any build flavor.
      # More information: https://docs.upsun.com/create-apps/hooks/hooks-comparison.html#build-hook
      build: |
        set -eux
        composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader --no-dev
        php artisan horizon:install

      # The deploy hook is run after the app container has been started, but before it has started accepting requests.
      # More information: https://docs.upsun.com/create-apps/hooks/hooks-comparison.html#deploy-hook
      deploy: |
        set -eux
        mkdir -p storage/framework/sessions
        mkdir -p storage/framework/cache
        mkdir -p storage/framework/views
        php artisan migrate --force
        php artisan optimize:clear

      # The post_deploy hook is run after the app container has been started and after it has started accepting requests.
      # More information: https://docs.upsun.com/create-apps/hooks/hooks-comparison.html#deploy-hook
      # post_deploy: |

    # Scheduled tasks for the app.
    # More information: https://docs.upsun.com/create-apps/app-reference.html#crons
    # crons:

    # Customizations to your PHP or Lisp runtime. More information: https://docs.upsun.com/create-apps/app-reference.html#runtime
    runtime:
      extensions:
        - redis
        - pdo
        - pdo_pgsql

    # More information: https://docs.upsun.com/create-apps/app-reference.html#additional-hosts
    # additional_hosts:

# The services of the project.
#
# Each service listed will be deployed
# to power your Upsun project.
# More information: https://docs.upsun.com/add-services.html
# Full list of available services: https://docs.upsun.com/add-services.html#available-services
services:
  postgresql:
    type: postgresql:16 # All available versions are: 16.1, 15.1, 14.1, 13.1

  redis:
    type: redis:7.0 # All available versions are: 7.0, 6.2

# The routes of the project.
#
# Each route describes how an incoming URL is going
# to be processed by Upsun.
# More information: https://docs.upsun.com/define-routes.html
routes:
  "https://api.{default}/":
    type: upstream
    upstream: "coffee-api:http"
```

We also need to map some environment variables so Laravel can configure itself properly. Create a `.environment` file with the following:

```bash
export APP_KEY="base64:[key]" # CHANGE IT!

# Set database environment variables
export DB_SCHEME="pgsql"
export DATABASE_URL="${DB_SCHEME}://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_PATH}"

# Set Laravel-specific environment variables
export DB_CONNECTION="$DB_SCHEME"
export DB_DATABASE="$DB_PATH"

# Set Cache environment variables
export CACHE_STORE="redis"
export CACHE_URL="${CACHE_SCHEME}://${CACHE_HOST}:${CACHE_PORT}"

# Set Redis environment variables
export REDIS_URL="$CACHE_URL"
export QUEUE_CONNECTION="redis"
export SESSION_DRIVER="redis"
```