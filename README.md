Lock
======

这是一个支持 Laravel 5 的并发锁拓展包。

## 安装

```
composer require latrell/lock dev-master
```

使用 ```composer update``` 更新包列表，或使用 ```composer install``` 安装。

找到 `config/app.php` 配置文件中的 `providers` 键，注册服务提供者。

```php
    'providers' => [
        // ...
        Latrell\Lock\LockServiceProvider::class,
    ]
```

找到 `config/app.php` 配置文件中的 `aliases` 键，注册别名。

```php
    'aliases' => [
        // ...
        'Lock' => Latrell\Lock\Facades\Lock::class,
    ]
```

运行 `php artisan vendor:publish` 命令，发布配置文件到你的项目中。

## 使用

```php
// 防止商品超卖。
$key = 'Goods:' . $goods_id;
Lock::granule($key, function() use($goods_id) {
	$goods = Goods::find($goods_id);
	if ( $goods->stock > 0 ) {
		// ...
	}
});
```
