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

