# GraphSql

## Overview

GraphSql is a Graphql like syntactical method to read data from SQL databases with an ease. It's built on top of Laravel
Eloquent ORM. 

We typically face a dilemma while building api routes. We need to build multiple api for different purposes but the data
is from same database table. Let's say for `products` table with 10 columns, We have 2 lists in our frontend app. List 1
shows just`name` and `image`. List 2 shows `name`,`description`. For this case, we may build 2 apis to return specific
fields only or a single api to return all fields. It takes longer to build 2 apis. If we build a single api, look we 
need just two fields, but we are returning all 10 fields. This issue just scales up with our application grows.

Imagine you have a tool, you can ask backend for fields you need from frontend like `{name,image}`. The api will return 
list of products with `name` and `image` fields, or `{name,description}` to get `name` and `description` only just with 
a single product list api.

This is what **GraphSql** is.

### Is GraphSql just limited to single table?

Hahah, here we go, we can ask for additional data from related tables too. Imagine, we need product list with category 
name of each product. Then we   ask `{name,image,category{name}}`. The api will return a list of product with each 
`product` having its `category` with field `name` only.
\
Or, list of products with its variations (table: `product_variations`), `variations{*}` returns all fields.
\
Or, list of products with its variations_count (table: `product_variations`), `variations.count`.

We may add conditions in a node graphString, like, `variations(status=1,color=Blue){*}` returns variations of `status 1`
and `color Blue`. 
\
Or, `variations(status=1,color=Blue).count` returns `variations_count` of `status 1` and `color Blue` for individual 
product.
Or, `variations(status=1,color=Blue).sum.sale` returns `variations_sale_count` of `status 1` and `color Blue` for individual 
product.

### Examples

Api:

```
product/list?graph={name,image,category{name}}
```

Data from response:

```
[
  {
     name:"Pressure Cooker",
     image:"/image/pressure_cooker.jpg",
     category_id:1,
     category:{
        id: 1,
        name:"Home Appliance"
     }
  },
  .
  .
  .
]
```
Don't worry about the `category_id`, `id` in the output. We will discuss it later.

Api:

```
product/list?graph={name,image,category{name},variations{*}}
```

Data from response:

```
[
  {
     name:"Pressure Cooker",
     image:"/image/pressure_cooker.jpg",
     category_id:1,
     category:{
        id: 1,
        name:"Home Appliance"
     },
     variations:[
        {
           id:10,
           color:"Red",
           size:"Small",
           price:2500,
           status:1,
           created_at: ...,
           updated_at: ...,
        }
        .
        .
        .
     ]
  },
  .
  .
  .
]
```

Api:

```
product/list?graph={name,image,category{name},variations(status=1,color=Blue){*}}
```

Data from response:

```
[
  {
     name:"Pressure Cooker",
     image:"/image/pressure_cooker.jpg",
     category_id:1,
     category:{
        id: 1,
        name:"Home Appliance"
     },
     variations:[
        {
           id:12,
           color:"Blue",
           size:"Small",
           price:2500,
           status:1,
           created_at: ...,
           updated_at: ...,
        }
        .
        .
        .
     ]
  },
  .
  .
  .
]
```

Api:

```
product/list?graph={name,variations.count,variations.sum.sale}
```

Data from response:

```
[
  {
     name:"Pressure Cooker",
     variations_count: 4,
     variations_sum_sale: "200"
  },
  {
     name:"Induction Cooker",
     variations_count: 2,
     variations_sum_sale: "80"
  },
  .
  .
  .
]
```

### What the hack is going on here?

Few questions arising in our minds. Like, how `category` table data is there? or how asking `variations{*}` node, is
getting data from `product_variations` table?

Single line answer: **GraphSql uses Eloquent Relationship for that**

`app/Models/Product.php`
```
    public function category():BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    
    public function variations (): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'product_id', 'id');
    }
```
It's making sense now? That's where they come.

## Installation

### Pre-requisites
 - Laravel Application minimum version 8
 - Models: User, Category, Product, ProductVariation, CartItem etc
 - Proper Eloquent Relationship defined

### Note
 - Graph String: `{name,image,category{name},variations(status=1,color=Blue){*}}`
 - Nodes: `category{name}`, `variations(status=1,color=Blue){*}`
 - Node Title: `category`, `variations`
 - Node Properties: `*`, `name`, etc
 - Node Conditions: `status=1`, `color=Blue`
 - Node Titles are method names defined in the models for a related table
 - Node Properties/Props are the column names
 - Special Node Props: `*` indicates all columns, `_timestamps` indicates `created_at`, `update_at` columns

1. Create routes in `routes/api.php`
    ```
    use App\Http\Controllers\ProductController;
    use Illuminate\Support\Facades\Route;
    
    Route::prefix('/product')->group(function () {
        Route::get('/list', [ProductController::class, 'getList']);
        Route::get('/{id}', [ProductController::class, 'getSingle']);
    });
    ```
2. Add methods in `app/Http/Controllers/ProductController.php`
    ```
    namespace App\Http\Controllers;
    
    use App\Http\Controllers\Controller;
    use App\Http\Services\ProductService;
    
    class ProductController extends Controller
    {
    
        function __construct (private readonly ProductService $service) {}
    
        public function getList ()
        {
            return response()->json( $this->service->getList());
        }
    
        public function getSingle ($id)
        {
            return response()->json( $this->service->getProduct($id));
        }
    
    ```
3. Add methods in `app/Http/Services/ProductService.php`
    ```
    namespace App\Http\Services;
    
    class ProductService extends Service
    {    
        public function getList (): array
        {
            try {
                $dbQuery = Product::get();
            
                return [
                    'success' => true,
                    'data' => ['products' => $products]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
    
        public function getSingle ($id): array
        {
            try {
                $dbQuery = Product::find($id);
            
                return [
                    'success' => true,
                    'data' => ['product' => $product]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
    
    ```
   
#### Let's check what we get now

```
php artisan serve --port=8800
```

Api:

```
http://127.0.0.1:8800/api/product/list
```

Response:

```
{
   'success': true,
   'data': {
      'products': [
         {
           id:1,
           name:"Pressure Cooker",
           description:"Description ...",
           image:"/image/pressure_cooker.jpg",
           category_id:1,
           brand:"Hitachi",
           status:1,
           tags:"pressure,cooker,...",
           created_at: ...,
           updated_at: ...,
         },
         .
         .
         .
      ]
   }
}
```

#### Let's implement GraphSql.

1. Install GraphSql
    ```
    composer require bitsmind/graphsql
    ```
2. Migrate new table `graph_sql_keys` to database. We shall discuss it later.
    ```
    php artisan migrate
    ```
3. Update `app/Http/Services/ProductService.php`
   ```
   use Bitsmind\GraphSql\QueryAssist;
   
   class ProductService extends Service
   {   
   
        use QueryAssist;
   
        public function getList (): array
        {
            try {
                $query = [
                    'graph' => '{*}' // Use necessary graph string here.
                ];
   
                $dbQuery = Product::query();
                $dbQuery = $this->queryGraphSql($dbQuery, $query, new Product);
                $products = $dbQuery->get();
              
                return [
                    'success' => true,
                    'data' => ['products' => $products]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
        .
        .
        .
   }
   ```
   
#### Let's check what we get now

Api:

```
http://127.0.0.1:8800/api/product/list
```
Returns identical content as before.

#### Now play with the graph string and check what you get in return

 - `{*}`
 - `{name,image}`
 - `{id,name,image,_timestamps}`
 - `{name,image,category{*}}`
 - `{name,image,category{name}}`
 - `{name,image,category{name},variations{*}}`
 - `{name,image,category{name},variations.count}`
 - `{name,image,category{name},variations.sum.sale}`
 - `{name,image,category{name},variations(status=1).count}`
 - Your imagination is the limit here

#### Let's receive the string from api query params

1. update `app/Http/Controllers/ProductController.php`
    ```
    namespace App\Http\Controllers;
    
    use App\Http\Controllers\Controller;
    use App\Http\Services\ProductService;
    use Illuminate\Http\Request;
    
    class ProductController extends Controller
    {
    
        function __construct (private readonly ProductService $service) {}
    
        public function getList (Request $request): JsonResponse
        {
            return response()->json( $this->service->getList( $request->query()));
        }
    
        public function getSingle ($id, Request $request)
        {
            return response()->json( $this->service->getProduct($id, $request->query()));
        }
    
    ```
2. Update `app/Http/Services/ProductService.php`
   ```
   use Bitsmind\GraphSql\QueryAssist;
   
   class ProductService extends Service
   {   
   
        use QueryAssist;
   
        public function getList (array $query): array
        {
            try {
   
                $dbQuery = Product::query();
                $dbQuery = $this->queryGraphSql($dbQuery, $query, new Product);   
                $products = $dbQuery->get();
              
                return [
                    'success' => true,
                    'data' => ['products' => $products]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
        .
        .
        .
   }
   ```
   
#### Let's check what we get now with query params

Api:

```
http://127.0.0.1:8800/api/product/list
http://127.0.0.1:8800/api/product/list?graph={*}
```
Returns identical content.

#### Now play with the graph string and check what you get in return

- `{*}`
- `{name,image}`
- `{id,name,image,_timestamps}`
- `{name,image,category{*}}`
- `{name,image,category{name}}`
- `{name,image,category{name},variations{*}}`
- `{name,image,category{name},variations.count}`
- `{name,image,category{name},variations.sum.sale}`
- `{name,image,category{name},variations(status=1).count}`
- Your imagination is the limit here

Try the same for the `product/{id}` api.

#### Few example apis
```
// category
http://127.0.0.1:8800/api/category/list?graph={name,description,parent{name}}
http://127.0.0.1:8800/api/category/10?graph={name,description,products{name}}

// product
http://127.0.0.1:8800/api/product/list?graph={name,image,category{name}}
http://127.0.0.1:8800/api/product/2?graph={*,category{name},variations{*}}

// user profile
http://127.0.0.1:8800/api/profile?graph={name,email,phone,addresses{*}}

// cart items (cart_items table should have 'product_id', 'product_variation_id' columns)
http://127.0.0.1:8800/api/cart-item/list?graph={quantity,_timestamps,product{name,image},productVariation{*}}
```

## Additional Methods 

GraphSql comes with few shorthands for traditional queries
```
http://127.0.0.1:8800/api/product/list?page=1&length=10&order_by=name,asc&status=1&category_id=1&brand=Hitachi,LG&graph={name,image,category{name}}
```
Here we have optional `pagination`, `status` and `category_id` columns filter, multi-option filter for `brand` column,
sort by any column

Let's see typical implementation first

`app/Http/Services/ProductService.php`
   ```
   use Bitsmind\GraphSql\QueryAssist;
   
   class ProductService extends Service
   {   
        use QueryAssist;
   
        public function getList (array $query): array
        {
            try {
   
                $dbQuery = Product::query();
                
                // graphSql
                $dbQuery = $this->queryGraphSql($dbQuery, $query, new Product);  
                
                // sorting
                if (array_key_exists('order_by', $query)) {
                    [$column, $order] = explode(',',$query['order_by']);
                    $dbQuery = $dbQuery->orderby($column, $order);
                }
                else {
                    // default
                    $dbQuery = $dbQuery->orderby('id', 'desc');
                }
                
                // column filters
                if (array_key_exists('status', $query)) {
                    $dbQuery = $dbQuery->where('status', $query['status'])
                }
                if (array_key_exists('category_id', $query)) {
                    $dbQuery = $dbQuery->where('category_id', $query['category_id'])
                }
                
                // multi-options filters
                if (array_key_exists('brand', $query)) {
                    $options = explode(',', $query[$field]);
                    $dbQuery = $dbQuery->whereIn('brand', $options);
                }
                
                // pagination
                $count = $dbQuery->count();
                if (!array_key_exists('page', $query))      $query['page']      = 1;
                if (!array_key_exists('length', $query))    $query['length']    = 100;
                $offset = ($query['page']-1)*$query['length'];                
                $products = $dbQuery->offset($offset)->limit($query['length'])->get();
              
                return [
                    'success' => true,
                    'data' => [
                        'page' => $query['page'],
                        'length' => $query['length'],
                        'count' => $count,
                        'products' => $products
                    ]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
   }
   ```

GraphSql Shorthand

`app/Http/Services/ProductService.php`
   ```
   use Bitsmind\GraphSql\QueryAssist;
   
   class ProductService extends Service
   {   
        use QueryAssist;
   
        public function getList (array $query): array
        {
            try {
   
                $dbQuery = Product::query();
                
                $dbQuery = $this->queryGraphSql($dbQuery, $query, new Product);           // graphSql
                $dbQuery = $this->queryOrderBy($dbQuery, $query, 'id', 'desc');           // sorting (default id,desc)
                $dbQuery = $this->queryWhere($dbQuery, $query, ['status','category_id']); // column filters
                $dbQuery = $this->queryWhereIn($dbQuery, $query, ['brand']);              // multi-option filters
                
                $count = $dbQuery->count();
                $products = $this->queryPagination($dbQuery, $query)->get();              // pagination
              
                return [
                    'success' => true,
                    'data' => [
                        'page' => $query['page'],
                        'length' => $query['length'],
                        'count' => $count,
                        'products' => $products
                    ]
                ];
            }
            catch (\Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
   }
   ```

Nice Hah!

## Attention

If you have sensitive data then allowing direct graph string is a bit risky.

#### How?

Imagine a system with authentication system. So, any user is not allowed to access other user data. But look at the api
call below

```
http://127.0.0.1:8800/api/product/2?graph={*,orderItems{*,order{*,user{*}}}}
```

This api will return product data with every order of for the product whether order is from this user or other user.

#### What is the solution then?

GraphSql provides out of the box solution for that. Instead of open graph string, we will map all strings and then use
their map keys.

### GraphSql Key Mapping

Remember the table `graph_sql_keys` we migrated during installation? We will save our graph strings in that table
and set a key on behalf of a string: `customer_product_list` and `{name,image,category{name}}` in `key` and `string` 
column respectively. We shall use `graph_key` instead of `graph` query params in apis.

table: `graph_sql_keys`

| id | key                      | string                             |
|----|--------------------------|------------------------------------|
| 1  | customer_product_list    | {name,image,category{name}}        |
| 2  | customer_product_details | {\*,category{name},variations{\*}} |

#### Let's set up a crud for the graph keys

1. Create routes in `routes/api.php`. The apis are recommended to be private.
    ```
    use App\Http\Controllers\ProductController;
    use Illuminate\Support\Facades\Route;
    
    Route::middleware('auth:api')->prefix('/graph-sql-key')->group(function () {
       Route::get('/list', [GraphSqlKeyController::class, 'getList']);
       Route::post('/sync', [GraphSqlKeyController::class, 'sync']);
   });
    ```
2. Add Controller `app/Http/Controllers/GraphSqlKeyController.php`
    ```
    namespace App\Http\Controllers;
    
    use App\Http\Controllers\Controller;
    use App\Http\Services\ProductService;
    
    class GraphSqlKeyController extends Controller 
    {
       function __construct (private readonly GraphSqlKeyService $service) {}
   
       public function getList (): JsonResponse
       {
           return response()->json( $this->service->getList());
       }
   
       public function sync (GraphSqlKeySyncRequest $request): JsonResponse
       {
           return response()->json( $this->service->syncGraphSqlKey( $request->all()));
       }
    }
    ```
3. Add Service in `app/Http/Services/GraphSqlKeyService.php`
    ```
   namespace App\Http\Services;

   use Bitsmind\GraphSql\Models\GraphSqlKey;
   
   class GraphSqlKeyService
   {
       public function getList(): array
       {
           try {
               $graphSqlKeys = GraphSqlKey::orderBy('key','asc')->get();
   
                return [
                    'success' => true,
                    'data' => ['graphSqlKeys' => $graphSqlKeys]
                ];
           } catch (\Exception $exception) {
               return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
           }
       }
   
       public function syncGraphSqlKey(array $data): array
       {
           try {
               $graphSqlKey = GraphSqlKey::where('key', $data['key'])->first();
               if ($graphSqlKey) {
                   $graphSqlKey->update([
                       'string' => $data['string']
                   ]);
               }
               else {
                   GraphSqlKey::create([
                       'key' => $data['key'],
                       'string' => $data['string']
                   ]);
               }
   
                return [
                    'success' => true,
                    'message' => 'GraphSql Key Synced Successfully'
                ];
           } catch (\Exception $exception) {
               return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
           }
       }
   }
    
    ```

Now the api call
```
http://127.0.0.1:8800/api/product/list?graph_key=customer_product_list
```