<p align="center">
  <a href="https://packagist.org/packages/happytodev/larafil"><img src="https://img.shields.io/packagist/dt/happytodev/larafil.svg?style=flat-square" alt="Total Downloads" /></a>
  <a href="https://packagist.org/packages/happytodev/larafil"><img src="https://img.shields.io/packagist/v/happytodev/larafil?label=stable" alt="Latest Stable Version" /></a>
  <a href="https://packagist.org/packages/happytodev/larafil"><img src="https://img.shields.io/packagist/l/happytodev/larafil.svg" alt="License" /></a>
</p>


Larafil was created by [HappyToDev](https://github.com/happytodev) and was inspired by a [Povilas Korop](https://github.com/LaravelDaily)'s [tweet](https://x.com/povilaskorop/status/1784916290982826462?s=46&t=8FgNEQBLlkAK3L6Zwe_KyQ).

## How to use Larafil

Get it on Packagist 

```bash
composer global require happytodev/larafil
```

and just use it :

```bash
larafil install
```

It will ask you the name of your future application.

### Create a Filament user

You can ask for a Filament user creation at the end of the installation : 

```bash
larafil install --create-user
```

### Serve your application

You can also ask to launch Laravel integrated server after the installation :

```bash
larafil install --serve
```

Of course, you can combine the two options together :

```bash
larafil install --create-user --serve
```

### Use MySQL database instead SQLite default one

If you want to use MySQL database instead the sqlite default one, you can use the `--mysql` option : 

```bash
larafil install --create-user --mysql --serve
```

### Use Laravel 10 instead of Laravel 11

If you want to use Laravel 10 instead Laravel 11, you can pass the `l10` option during installation :

```bash
larafil install --create-user --laravel-version=previous --serve
```

Nota : you can't use `l10` and `mysql` option together. That's because Laravel 10 use mysql by default. For Laravel 11, it's SQLite by default.
If you try to use both together, you will have an error and the install stops.

The purpose of Larafil was to play with Laravel Zero from [Nuno Maduro](https://github.com/nunomaduro).

## How to build Larafil

1. git clone

```bash
git clone https://github.com/happytodev/larafil.git larafil
```

2. Composer install

```bash
composer install
```

3. Build the app

```bash
php larafil app:build
``` 

4. It's ready

```bash
./builds/larafil -V
```

## Support the development

I don't know if there is another thing to develop for Larafil but tell me. It will be my pleasure to develop some good ideas for this little project.

**Do you like this project? Support it by donating**

- PayPal: [Donate](https://www.paypal.com/donate/?hosted_button_id=VSVEWSM2U437Q)
- Ko-Fi: [Donate](https://ko-fi.com/happytodev/)

## Interested by Laravel 11

[Get my free ebook about news in Laravel 11](https://ko-fi.com/s/7a573b69b0)

## License

Larafil is an open-source software licensed under the MIT license.
